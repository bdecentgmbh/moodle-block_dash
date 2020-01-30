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
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid;

use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\field\attribute\identifier_attribute;
use block_dash\data_grid\field\sql_field_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * Based on database query.
 *
 * @package block_dash
 */
class sql_data_grid extends data_grid {

    /**
     * Query template to process and execute.
     *
     * @var string
     */
    private $querytemplate;

    /**
     * @var string
     */
    private $countquerytemplate;

    /**
     * @var string
     */
    private $groupby;

    /**
     * @var data_collection_interface
     */
    private $datacollection;

    /**
     * @var int Store record count so we don't have to query it multiple times.
     */
    private $recordcount = null;

    /**
     * Set query template.
     *
     * @param string $querytemplate
     */
    public function set_query_template($querytemplate) {
        $this->querytemplate = $querytemplate;
    }

    /**
     * Set count query template.
     *
     * @param string $countquerytemplate
     */
    public function set_count_query_template($countquerytemplate) {
        $this->countquerytemplate = $countquerytemplate;
    }

    /**
     * Get group by field.
     *
     * @return string
     */
    public function get_groupby() {
        return $this->groupby;
    }

    /**
     * Set group by field.
     *
     * @param string $groupby
     */
    public function set_groupby($groupby) {
        $this->groupby = $groupby;
    }

    /**
     * Return main query without select.
     *
     * @return string
     */
    protected function get_query() {
        return $this->querytemplate;
    }

    /**
     * Combines all field selects for SQL select.
     *
     * @return string SQL select
     * @throws \moodle_exception
     */
    protected function get_query_select() {
        $selects = array();
        $fields = $this->get_field_definitions();

        foreach ($fields as $field) {
            if (is_null($field->get_select())) {
                continue;
            }

            $selects[] = $field->get_select() . ' AS ' . $field->get_name();
        }

        $select = implode(', ', $selects);

        if (empty($select)) {
            throw new \moodle_exception('SQL select cannot be empty.');
        }

        return $select;
    }

    /**
     * Get final SQL query and params.
     *
     * @param bool $count If true, query will be counting records instead of selecting fields.
     * @return array
     * @throws \Exception
     * @throws \moodle_exception
     */
    protected function get_sql_and_params($count = false) {
        if (!$this->has_any_field_definitions()) {
            throw new \moodle_exception('Grid initialized without any fields.
                Did you forget to call data_grid::init()?');
        }

        if ($this->get_filter_collection() && $this->get_filter_collection()->has_filters()) {
            list ($filtersql, $filterparams) = $this->get_filter_collection()->get_sql_and_params();
            $wheresql = $filtersql[0];
            $havingsql = $filtersql[1];
        } else {
            $wheresql = '1=1';
            $havingsql = '1=1';
            $filterparams = [];
        }

        $groupby = '';
        // Use count query and only select a count of primary field.
        if ($count) {
            $query = $this->countquerytemplate;
            $selects = 'COUNT(DISTINCT ' . $this->get_field_definitions()[0]->get_select() . ')';
            $orderby = '';
        } else {
            $query = $this->get_query();
            $selects = $this->get_query_select();
            $orderby = $this->get_sort_sql();
            if ($groupby = $this->get_groupby()) {
                $groupby = 'GROUP BY ' . $this->get_groupby();
            }
        }

        if (!$count) {
            // If there are multiple identifiers in the data source, construct a unique column.
            // This is to prevent warnings when multiple rows have the same value in the first column.
            $identifierselects = [];
            /** @var sql_field_definition $fielddefinition */
            foreach ($this->get_field_definitions() as $fielddefinition) {
                if ($fielddefinition->has_attribute(identifier_attribute::class)) {
                    $identifierselects[] = $fielddefinition->get_select();
                }
            }
            global $DB;
            $concat = $DB->sql_concat_join("'-'", $identifierselects);
            if (count($identifierselects) > 1) {
                $selects = sprintf('%s as unique_id, %s', $concat, $selects);
            }
        }

        $query = str_replace('%%SELECT%%', $selects, $query);
        $query = str_replace('%%GROUPBY%%', $groupby, $query);
        $query = str_replace('%%WHERE%%', 'WHERE ' . $wheresql, $query);
        $query = str_replace('%%HAVING%%', 'HAVING ' . $havingsql, $query);
        $query = str_replace('%%ORDERBY%%', $orderby, $query);

        return [$query, $filterparams];
    }

    /**
     * Execute query and return data collection.
     *
     * @throws \moodle_exception
     * @return data_collection_interface
     * @since 2.2
     */
    public function get_data() {
        if (!$this->datacollection) {
            $records = $this->get_records();
            $this->datacollection = $this->get_data_strategy()->convert_records_to_data_collection($records, $this);
        }

        return $this->datacollection;
    }

    /**
     * Get raw records from database.
     *
     * @return \stdClass[]
     * @throws \Exception
     * @throws \moodle_exception
     * @since 2.2
     */
    protected function get_records() {
        global $DB;

        list($query, $filterparams) = $this->get_sql_and_params(false);

        if ($this->supports_pagination() && !$this->is_pagination_disabled()) {
            return $DB->get_records_sql($query, $filterparams, $this->get_paginator()->get_limit_from(),
                $this->get_paginator()->get_per_page());
        }

        return $DB->get_records_sql($query, $filterparams, 0, 100);
    }

    /**
     * Build ORDER BY sql for grid.
     *
     * @return string
     */
    protected function get_sort_sql() {
        $sql = '';
        $sorts = [];

        /** @var sql_field_definition $field */
        foreach ($this->get_field_definitions() as $field) {
            if ($field->get_sort()) {
                $sorts[] = $field->get_select() . ' ' . strtoupper($field->get_sort_direction());
            }
        }

        if (!empty($sorts)) {
            $sql = 'ORDER BY ' . implode(',', $sorts);
        }

        return $sql;
    }

    #region Counting

    /**
     * Get total number of records for pagination.
     *
     * @return int
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_count() {
        global $DB;

        if (is_null($this->recordcount)) {
            list($query, $filterparams) = $this->get_sql_and_params(true);

            $this->recordcount = $DB->count_records_sql($query, $filterparams);
        }

        return $this->recordcount;
    }

    #endregion

}
