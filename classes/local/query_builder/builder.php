<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Builds a query.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\query_builder;

use coding_exception;
use dml_exception;

/**
 * Builds a query.
 *
 * @package block_dash\local\query_builder
 */
class builder {

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $tablealias;

    /**
     * @var string[]
     */
    private $selects = [];

    /**
     * @var array
     */
    private $wheres = [];

    /**
     * @var int Return a subset of records, starting at this point (optional).
     */
    private $limitfrom = 0;

    /**
     * @var int Return a subset comprising this many records in total (optional, required if $limitfrom is set).
     */
    private $limitnum = 0;

    /**
     * @var array ['field1' => 'ASC', 'field2' => 'DESC', ...]
     */
    private $orderby = [];

    /**
     * @param string $field
     * @param string $alias
     * @return builder
     */
    public function select(string $field, string $alias): builder {
        $this->selects[$alias] = $field;
        return $this;
    }

    /**
     * Set main table of query.
     *
     * @param string $table
     * @param string $alias
     * @return builder
     */
    public function from(string $table, string $alias): builder {
        $this->table = $table;
        $this->tablealias = $alias;
        return $this;
    }

    /**
     * Add where clause to query.
     *
     * @param string $selector Field or alias of where clause.
     * @param array $values Values that where clause will compare to.
     * @param string $operator Equals, greater than, in, etc etc. See where::OPERATOR_*
     * @return where
     */
    public function where(string $selector, array $values, string $operator = where::OPERATOR_EQUAL): where {
        $where = new where($selector, $values, $operator);
        $this->wheres[] = $where;
        return $where;
    }

    /**
     * Add where (in subquery) clause to query.
     *
     * @param string $selector Field or alias of where clause.
     * @param string $query Subquery of WHERE IN (subquery) clause.
     * @param array $params Any extra parameters used in subquery.
     * @return where
     */
    public function where_in_query(string $selector, string $query, array $params = []): where {
        $where = new where($selector, []);
        $where->set_query($query, $params);
        $this->wheres[] = $where;
        return $where;
    }

    /**
     * Order by a field.
     *
     * @param string $field Field or alias to order by.
     * @param string $direction 'ASC' or 'DESC'
     * @return builder
     * @throws coding_exception
     */
    public function orderby(string $field, string $direction): builder {
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new coding_exception('Invalid order by direction ' . $direction);
        }

        $this->orderby[$field] = $direction;
        return $this;
    }

    /**
     * @return where[]
     */
    public function get_wheres(): array {
        return $this->wheres;
    }

    /**
     * Get the query limit from.
     *
     * @return int
     */
    public function get_limitfrom(): int {
        return $this->limitfrom;
    }

    /**
     * Set the query limit from.
     *
     * @param int $limitfrom
     * @return $this
     */
    public function limitfrom(int $limitfrom): builder {
        $this->limitfrom = $limitfrom;
        return $this;
    }

    /**
     * Get the query limit number.
     *
     * @return int
     */
    public function get_limitnum(): int {
        return $this->limitnum;
    }

    /**
     * Set the query limit number.
     *
     * @param int $limitnum
     * @return $this
     */
    public function limitnum(int $limitnum): builder {
        $this->limitnum = $limitnum;
        return $this;
    }

    /**
     * Build and return complete query select SQL.
     *
     * @return string
     */
    protected function build_select(): string {
        $selects = [];
        foreach ($this->selects as $alias => $select) {
            $selects[] = $select . ' AS ' . $alias;
        }

        return implode(',', $selects);
    }

    /**
     * @return array
     * @throws exception\invalid_operator_exception
     */
    protected function get_where_sql_and_params(): array {
        $wheresql = [];
        $params = [];
        foreach ($this->get_wheres() as $where) {
            [$wsql, $wparams] = $where->get_sql_and_params();
            $wheresql[] = $wsql;
            $params = array_merge($params, $wparams);
        }

        return [implode(' AND ', $wheresql), $params];
    }

    /**
     * @return array<string, array>
     * @throws exception\invalid_operator_exception
     */
    protected function get_sql_and_params(): array {
        $sql = 'SELECT DISTINCT ' . $this->build_select() . ' FROM {' . $this->table . '} ' . $this->tablealias;
        $params = [];

        [$wsql, $wparams] = $this->get_where_sql_and_params();

        if ($wsql) {
            $sql .= ' WHERE ' . $wsql;
            $params = array_merge($params, $wparams);
        }

        if ($this->orderby) {
            $orderbys = [];
            foreach ($this->orderby as $field => $direction) {
                $orderbys[] = sprintf('%s %s', $field, $direction);
            }

            $sql .= ' ORDER BY ' . implode(', ', $orderbys);
        }

        return [$sql, $params];
    }

    /**
     * Execute query and return results.
     *
     * @return array
     * @throws dml_exception
     * @throws exception\invalid_operator_exception
     */
    public function query() {
        global $DB;

        [$sql, $params] = $this->get_sql_and_params();

        return $DB->get_records_sql($sql, $params, $this->get_limitfrom(), $this->get_limitnum());
    }
}