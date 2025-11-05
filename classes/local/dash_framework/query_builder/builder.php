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

use coding_exception;
use dml_exception;

/**
 * Builds a query.
 *
 * @package block_dash
 */
class builder {
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $tablealias;

    /**
     * @var string[]
     */
    protected $selects = [];

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @var array
     */
    protected $rawwhere;

    /**
     * @var array
     */
    protected $rawwhereparameters = [];

    /**
     * @var int Return a subset of records, starting at this point (optional).
     */
    protected $limitfrom = 0;

    /**
     * @var int Return a subset comprising this many records in total (optional, required if $limitfrom is set).
     */
    protected $limitnum = 0;

    /**
     * @var array ['field1' => 'ASC', 'field2' => 'DESC', ...]
     */
    protected $orderby = [];

    /**
     * @var join[]
     */
    protected $joins = [];

    /**
     * @var array ['field1', 'field2', ...]
     */
    protected $groupby = [];

    /**
     * Extra conditions to be added in WHERE clause.
     *
     * @var array
     */
    protected $rawconditions = [];

    /**
     * @var array
     */
    protected $rawconditionparameters = [];

    /**
     * @var array
     */
    protected $rawjoins = [];

    /**
     * @var array
     */
    protected $rawjoinsparameters = [];

    /**
     * @var int|null
     */
    protected static $lastcount = null;

    /**
     * Last count SQL and parameters.
     *
     * @var array
     */
    protected static $lastcountcachekey = null;

    /**
     * Last built COUNT sql (for debug).
     * @var string|null
     */
    protected static $lastcountsql = null;

    /**
     * Last built COUNT params (for debug).
     * @var array
     */
    protected static $lastcountparams = [];

    /**
     * Sanitize CTE definitions to avoid Moodle table prefix replacement on CTE names.
     * Converts patterns like: WITH {cte} AS (...) or , {cte} AS (...) to WITH cte AS (...) / , cte AS (...).
     *
     * @param string $sql
     * @return string
     */
    protected function sanitize_cte_sql(string $sql): string {
        global $DB;
        // 1) Drop Moodle table braces around CTE names.
        $patterns = [
            '/\bWITH(?:\s+RECURSIVE)?\s+\{([a-zA-Z0-9_]+)\}\s+AS\b/i',
            '/,\s*\{([a-zA-Z0-9_]+)\}\s+AS\b/i',
        ];
        $replacements = [
            'WITH $1 AS',
            ', $1 AS',
        ];
        $sql = preg_replace($patterns, $replacements, $sql);

        // 2) For joins to module tables that may not exist, replace the table with a dummy derived table
        // providing the columns typically referenced (id, name, timemodified).
        // Pattern matches: [LEFT] JOIN {tablename} alias
        $manager = isset($DB) ? $DB->get_manager() : null;
        $sql = preg_replace_callback(
            '/\b(LEFT\s+JOIN|JOIN|RIGHT\s+JOIN)\s+\{([a-zA-Z0-9_]+)\}\s+([a-zA-Z0-9_]+)\b/i',
            function ($m) use ($manager) {
                $jointype = strtoupper($m[1]);
                $tablename = $m[2];
                $alias = $m[3];
                // If we cannot check, keep as-is.
                if (!$manager || $manager->table_exists($tablename)) {
                    return $m[0];
                }
                // Replace with a harmless derived table exposing common columns.
                return 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS timemodified) ' . $alias;
            },
            $sql
        );

        // 3) For existing module tables missing specific columns (e.g., timemodified or name),
        // replace references 'alias.timemodified' or 'alias.name' with NULL to avoid unknown column errors.
        if ($manager) {
            // Build alias -> table map from FROM/JOIN clauses.
            $aliasmap = [];
            if (preg_match_all('/\bFROM\s+\{([a-zA-Z0-9_]+)\}\s+([a-zA-Z0-9_]+)/i', $sql, $mfrom, PREG_SET_ORDER)) {
                foreach ($mfrom as $row) {
                    $aliasmap[$row[2]] = $row[1];
                }
            }
            if (preg_match_all('/\b(LEFT\s+JOIN|JOIN|RIGHT\s+JOIN)\s+\{([a-zA-Z0-9_]+)\}\s+([a-zA-Z0-9_]+)/i', $sql, $mjoin, PREG_SET_ORDER)) {
                foreach ($mjoin as $row) {
                    $aliasmap[$row[3]] = $row[2];
                }
            }

            foreach ($aliasmap as $alias => $table) {
                if (!$manager->table_exists($table)) {
                    // Already handled above by replacing the JOIN, skip.
                    continue;
                }
                $cols = [];
                try {
                    $cols = $DB->get_columns($table) ?? [];
                } catch (\Throwable $e) {
                    // If we cannot fetch columns, be conservative.
                    $cols = [];
                }
                // Replace alias.timemodified if missing.
                if (!isset($cols['timemodified'])) {
                    $sql = preg_replace('/\b' . preg_quote($alias, '/') . '\.timemodified\b/i', 'NULL', $sql);
                }
                // Replace alias.name if missing.
                if (!isset($cols['name'])) {
                    $sql = preg_replace('/\b' . preg_quote($alias, '/') . '\.name\b/i', 'NULL', $sql);
                }
            }
        }

        return $sql;
    }

    /**
     * Replace any references to defined CTE names written as {ctename} with plain ctename.
     * This avoids Moodle table prefix expansion for CTEs.
     *
     * @param string $sql
     * @return string
     */
    protected function sanitize_cte_references(string $sql): string {
        if (empty($this->sqlctelist)) {
            return $sql;
        }
        foreach (array_keys($this->sqlctelist) as $ctename) {
            // Replace occurrences of {ctename} with ctename (word boundaries around name).
            $pattern = '/\{' . preg_quote($ctename, '/') . '\}/';
            $sql = preg_replace($pattern, $ctename, $sql);
        }
        return $sql;
    }

    /**
     * Whether to put order by before joins.
     *
     * @var array
     */
    protected $sqlctelist = [];

    /**
     * Fields to retried from sql query. Sql select field.
     * @param string $field
     * @param string $alias
     * @return builder
     */
    public function select(string $field, string $alias): builder {
        $this->selects[$alias] = $field;
        return $this;
    }

    /**
     * Set all selects on builder.
     *
     * @param array $selects [alias => field, ...]
     * @return $this
     */
    public function set_selects(array $selects): builder {
        $this->selects = [];
        foreach ($selects as $alias => $select) {
            $this->selects[$alias] = $select;
        }
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
     * Set whether to put order by before joins.
     *
     * @param array $fromsql
     * @return $this
     */
    public function set_sql_cte($fromsql) {
        $this->sqlctelist = array_merge($this->sqlctelist, $fromsql);
        return $this;
    }

    /**
     * Join table in query.
     *
     * @param string $table Table name of joined table.
     * @param string $alias Joined table alias.
     * @param string $jointablefield Field of joined table to reference in join condition.
     * @param string $origintablefield Field of origin table to join to.
     * @param string $jointype SQL join type. See self::TYPE_*
     * @param array $extraparameters Extra parameters used in join SQL.
     * @return $this
     */
    public function join(string $table, string $alias, string $jointablefield, string $origintablefield,
                         $jointype = join::TYPE_INNER_JOIN, array $extraparameters = []): builder {
        $this->joins[] = new join($table, $alias, $jointablefield, $origintablefield, $jointype, $extraparameters);
        return $this;
    }

    /**
     * Join raw in query.
     *
     * @param join $join
     * @return $this
     */
    public function join_raw(join $join): builder {
        $this->rawjoins[] = $join;
        return $this;
    }

    /**
     * Add additional join condition to existing join.
     *
     * @param string $alias
     * @param string $condition
     * @return $this
     * @throws coding_exception
     */
    public function join_condition(string $alias, string $condition): builder {
        $added = false;
        foreach ($this->joins as $join) {
            if ($join->get_alias() == $alias) {
                $join->add_join_condition($condition);
                $added = true;
                break;
            }
        }

        if (!$added) {
            throw new coding_exception('Table alias not found: ' . $alias);
        }

        return $this;
    }

    /**
     * Add where clause to query.
     *
     * @param string $selector Field or alias of where clause.
     * @param array $values Values that where clause will compare to.
     * @param string $operator Equals, greater than, in, etc etc. See where::OPERATOR_*
     * @param string $conjunctive AND, OR etc etc. See where::CONJUCTIVE_OPERATOR_*
     *
     * @return where
     */
    public function where(string $selector, array $values, string $operator = where::OPERATOR_EQUAL,
        string $conjunctive = where::CONJUNCTIVE_OPERATOR_AND): where {
        $where = new where($selector, $values, $operator, $conjunctive);
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
     * Add where clause to query.
     *
     * @param string $wheresql
     * @param array $parameters
     * @return builder
     */
    public function where_raw(string $wheresql, array $parameters = []): builder {
        $this->rawwhere[] = $wheresql;
        $this->rawwhereparameters = array_merge($this->rawwhereparameters, $parameters);

        return $this;
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
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            throw new coding_exception('Invalid order by direction ' . $direction);
        }

        $this->orderby[$field] = $direction;
        return $this;
    }

    /**
     * Remove order by conditions.
     *
     * @return builder
     */
    public function remove_orderby(): builder {
        $this->orderby = [];
        return $this;
    }

    /**
     * Group by field for aggregations.
     *
     * @param string $field
     * @return builder
     */
    public function groupby(string $field): builder {
        $this->groupby[] = $field;
        return $this;
    }

    /**
     * Add raw condition to builder.
     *
     * @param string $condition
     * @param array $parameters
     * @return builder
     */
    public function rawcondition(string $condition, array $parameters = []): builder {
        $this->rawconditions[] = $condition;
        $this->rawconditionparameters = $parameters;
        return $this;
    }

    /**
     * Get the query where conditions.
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

        // Move the unique id to the first position.
        if (array_key_exists('unique_id', $this->selects)) {
            $uniqueid = $this->selects['unique_id'];
            unset($this->selects['unique_id']);
            $this->selects = array_merge(['unique_id' => $uniqueid], $this->selects);
        }

        foreach ($this->selects as $alias => $select) {
            $selects[] = $select . ' AS ' . $alias;
        }

        return implode(',', $selects);
    }

    /**
     * Get the query where condition and it parameters.
     * @return array
     * @throws exception\invalid_operator_exception
     */
    protected function get_where_sql_and_params(): array {
        $wheresql = [];
        $params = [];
        $wsql = ''; // Where builder queryies.
        foreach ($this->get_wheres() as $where) {
            [$sql, $wparams] = $where->get_sql_and_params();
            $conjunc = $where->get_conjunctive_operator() ?: 'AND';
            $wsql .= !empty($wsql) ? sprintf(' %s %s ', $conjunc, $sql) : $sql;
            $params = array_merge($params, $wparams);
        }

        $wheresql[] = $wsql;

        if ($this->rawwhere) {
            foreach ($this->rawwhere as $where) {
                $wheresql[] = $where;
            }
            $params = array_merge($params, $this->rawwhereparameters);
        }

        return [implode(' AND ', array_filter($wheresql)), $params];
    }

    /**
     * Get the query and required parameters.
     *
     * @return array<string, array>
     * @throws exception\invalid_operator_exception
     */
    final public function get_sql_and_params(): array {
        global $DB;

        $sql = '';

        if (!empty($this->sqlctelist)) {
            foreach ($this->sqlctelist as $viewname => $fromsql) {
                $sql .= $this->sanitize_cte_sql($fromsql) . ' ';
            }
        }

        // Avoid adding a top-level DISTINCT for COUNT queries to prevent SQL errors.
        $selectvalues = array_values($this->selects);
        $iscountselect = count($selectvalues) === 1 && preg_match('/^\s*COUNT\s*\(/i', (string)$selectvalues[0]);
        $unique = (!$iscountselect && !array_key_exists('unique_id', $this->selects)) ? 'DISTINCT' : '';
        $sql .= 'SELECT ' . $unique . ' ' . $this->build_select() . ' FROM {' . $this->table . '} ' . $this->tablealias;

        $params = [];

        foreach ($this->joins as $join) {
            [$jsql, $jparams] = $join->get_sql_and_params();
            $sql .= ' ' . $jsql . ' ';
            $params = array_merge($params, $jparams);
        }

        foreach ($this->rawjoins as $join) {
            [$jsql, $jparams] = $join->get_sql_and_params();
            $sql .= ' ' . $jsql . ' ';
            $params = array_merge($params, $jparams);
        }

        [$wsql, $wparams] = $this->get_where_sql_and_params();

        if ($wsql) {
            $sql .= ' WHERE ' . $wsql;
            $params = array_merge($params, $wparams);
        }

        if (count($this->groupby) > 0) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
        }

        if ($this->orderby) {
            $orderbys = [];
            foreach ($this->orderby as $field => $direction) {
                $orderbys[] = sprintf('%s %s', $field, $direction);
            }

            $sql .= ' ORDER BY ' . implode(', ', $orderbys);
        }

        $sql = $this->sanitize_cte_references($sql);
        return [$sql, $params];
    }

    /**
     * Execute query and return results.
     *
     * @return moodle_recordset
     * @throws dml_exception
     * @throws exception\invalid_operator_exception
     */
    public function query() {
        global $DB;

        [$sql, $params] = $this->get_sql_and_params();
        // Emit debug info when developer debugging is enabled.
        if (function_exists('debugging')) {
            debugging('[block_dash] DATA SQL: ' . $sql . ' | params=' . json_encode($params), DEBUG_DEVELOPER);
        }
        if (function_exists('error_log')) {
            @error_log('[block_dash] DATA SQL: ' . $sql . ' | params=' . json_encode($params));
        }
        return $DB->get_records_sql($sql, $params, $this->get_limitfrom(), $this->get_limitnum());
    }

    /**
     * Get number of records this query will return.
     *
     * @param bool $isunique When true, count distinct main table IDs.
     * @return int
     * @throws dml_exception
     * @throws exception\invalid_operator_exception
     */
    public function count($isunique): int {
        global $DB;

        // Clone and normalise the base query first.
        $base = clone $this;
        $base->limitfrom(0)->limitnum(0)->remove_orderby();
        if (!empty($base->groupby)) {
            $base->groupby = [];
        }

        // Build the inner query to wrap for a robust COUNT on all DBs.
        // Prepare inner select and lift any CTEs to the top-level (MariaDB/MySQL require WITH at statement start).
        $inner = clone $base;
        $cteprefix = '';
        if (!empty($inner->sqlctelist)) {
            foreach ($inner->sqlctelist as $viewname => $fromsql) {
                $cteprefix .= $this->sanitize_cte_sql($fromsql) . ' ';
            }
            // Prevent duplication of CTEs inside the derived table.
            $inner->sqlctelist = [];
        }

        $hascte = trim($cteprefix) !== '';
        if ($hascte) {
            // Build a direct COUNT query when CTEs are present to avoid referencing CTEs from a derived table.
            $direct = clone $base;
            if ($isunique) {
                $direct->set_selects(['count' => 'COUNT(DISTINCT ' . $this->tablealias . '.id)']);
            } else {
                $direct->set_selects(['count' => 'COUNT(*)']);
            }
            [$innersql, $params] = $direct->get_sql_and_params();
            $sql = $innersql; // $innersql already includes the sanitized CTE prefix at the start.
        } else {
            // No CTEs – safe to wrap in a derived table for robust COUNT behaviour.
            // Always select the main table id with a stable alias.
            $inner->set_selects(['unique_id' => $this->tablealias . '.id']);
            [$innersql, $params] = $inner->get_sql_and_params();
            if ($isunique) {
                $sql = 'SELECT COUNT(DISTINCT x.unique_id) AS count FROM (' . $innersql . ') x';
            } else {
                $sql = 'SELECT COUNT(*) AS count FROM (' . $innersql . ') x';
            }
        }

        // Store for debug.
        self::$lastcountsql = $sql;
        self::$lastcountparams = $params;

        $countcachekey = md5($sql . serialize($params));
        if (self::$lastcount !== null) {
            if (self::$lastcountcachekey == $countcachekey) {
                return self::$lastcount;
            }
        }

        self::$lastcountcachekey = $countcachekey;
        // Emit debug info when developer debugging is enabled.
        if (function_exists('debugging')) {
            debugging('[block_dash] COUNT SQL: ' . $sql . ' | params=' . json_encode($params), DEBUG_DEVELOPER);
        }
        if (function_exists('error_log')) {
            @error_log('[block_dash] COUNT SQL: ' . $sql . ' | params=' . json_encode($params));
        }
        try {
            $count = $DB->count_records_sql($sql, $params);
        } catch (\dml_exception $e) {
            if (function_exists('error_log')) {
                @error_log('[block_dash] COUNT ERROR: ' . $e->getMessage() . ' | sql=' . $sql);
            }
            // Fallback: try a direct COUNT(*) without a derived table.
            $fallback = clone $base;
            $fallback->set_selects(['count' => 'COUNT(*)']);
            [$fsql, $fparams] = $fallback->get_sql_and_params();
            if (function_exists('debugging')) {
                debugging('[block_dash] FALLBACK COUNT SQL: ' . $fsql . ' | params=' . json_encode($fparams), DEBUG_DEVELOPER);
            }
            if (function_exists('error_log')) {
                @error_log('[block_dash] FALLBACK COUNT SQL: ' . $fsql . ' | params=' . json_encode($fparams));
            }
            try {
                $count = $DB->count_records_sql($fsql, $fparams);
            } catch (\dml_exception $e2) {
                if (function_exists('error_log')) {
                    @error_log('[block_dash] FALLBACK COUNT ERROR: ' . $e2->getMessage() . ' | sql=' . $fsql);
                }
                throw $e2; // rethrow to surface the DB error.
            }
        }
        self::$lastcount = $count;
        return $count;
    }

    /**
     * Get last built COUNT SQL (debugging aid).
     * @return string|null
     */
    public static function get_last_count_sql() {
        return self::$lastcountsql;
    }

    /**
     * Get last built COUNT params (debugging aid).
     * @return array
     */
    public static function get_last_count_params(): array {
        return self::$lastcountparams;
    }
}
