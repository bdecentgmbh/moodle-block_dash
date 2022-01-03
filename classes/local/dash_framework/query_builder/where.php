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

namespace block_dash\local\dash_framework\query_builder;

use block_dash\local\dash_framework\query_builder\exception\invalid_operator_exception;
use block_dash\local\dash_framework\query_builder\exception\invalid_where_clause_exception;
use coding_exception;
use dml_exception;

/**
 * Builds a query.
 *
 * @package block_dash
 */
class where {

    /**
     * @var int Unique counter for param/placeholder names.
     */
    public static $paramcounter = 0;

    /**
     * Sql equal sign.
     */
    const OPERATOR_EQUAL = '=';
    /**
     * SQL not equal.
     */
    const OPERATOR_NOT_EQUAL = '!=';
    /**
     * SQL IN operator.
     */
    const OPERATOR_IN = 'in';
    /**
     * SQL IN query.
     */
    const OPERATOR_IN_QUERY = 'in_query';

    /**
     * @var string Field or subquery.
     */
    private $selector;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string See self::OPERATION_*
     */
    private $operator;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $queryparams;

    /**
     * Create new where condition.
     *
     * @param string $selector
     * @param array $values
     * @param string $operator
     */
    public function __construct(string $selector, array $values, string $operator = self::OPERATOR_EQUAL) {
        $this->selector = $selector;
        $this->values = array_values($values);
        $this->operator = $operator;
    }

    /**
     * Set sql query to build.
     *
     * @param string $query
     * @param array $params
     */
    public function set_query(string $query, array $params = []) {
        $this->query = $query;
        $this->queryparams = $params;
        $this->operator = self::OPERATOR_IN_QUERY;
    }

    /**
     * Get sql query and parameters with processed operators.
     *
     * @return array<string, array>
     * @throws invalid_operator_exception
     * @throws invalid_where_clause_exception
     * @throws dml_exception|coding_exception
     */
    public function get_sql_and_params(): array {
        global $DB;

        $sql = '';
        // Named parameters.
        $params = [];

        // First ensure this where clause is valid.
        switch ($this->operator) {
            case self::OPERATOR_EQUAL:
            case self::OPERATOR_IN:
                if (empty($this->values)) {
                    throw new invalid_where_clause_exception();
                }
                break;
        }

        // Build SQL and params.
        switch ($this->operator) {
            case self::OPERATOR_EQUAL:
            case self::OPERATOR_NOT_EQUAL:
                $placeholder = self::get_param_name();
                $sql = sprintf('%s %s :%s', $this->selector, $this->operator, $placeholder);
                $params[$placeholder] = $this->values[0];
                break;
            case self::OPERATOR_IN:
                // At this point we are guaranteed at least one value being applied.
                [$psql, $pparams] = $DB->get_in_or_equal($this->values, SQL_PARAMS_NAMED, 'p');
                $sql = sprintf('%s %s', $this->selector, $psql);
                $params = array_merge($params, $pparams);
                break;
            case self::OPERATOR_IN_QUERY:
                $sql = sprintf('%s IN (%s)', $this->selector, $this->query);
                $params = array_merge($params, $this->queryparams);
                break;
            default:
                throw new invalid_operator_exception('', ['operator' => $this->operator]);
        }

        return [$sql, $params];
    }

    /**
     * Get unique parameter name.
     *
     * @param string $prefix
     * @return string
     */
    public static function get_param_name(string $prefix = 'p_'): string {
        return $prefix . self::$paramcounter++;
    }
}
