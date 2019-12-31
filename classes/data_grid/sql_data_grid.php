<?php


namespace block_dash\data_grid;


use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\field\attribute\identifier_attribute;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\field\sql_field_definition;

class sql_data_grid extends data_grid
{
    /**
     * @var string
     */
    private $query_template;

    /**
     * @var data_collection_interface
     */
    private $data_collection;

    /**
     * @var int Store record count so we don't have to query it multiple times.
     */
    private $record_count = null;

    public function set_query_template($query_template)
    {
        $this->query_template = $query_template;
    }

    /**
     * Return main query without select
     *
     * @return string
     */
    protected function get_query()
    {
        return $this->query_template;
    }

    /**
     * Combines all field selects for SQL select
     *
     * @return string SQL select
     * @throws \moodle_exception
     */
    protected function get_query_select()
    {
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
    protected function get_sql_and_params($count = false)
    {
        if (!$this->has_any_field_definitions()) {
            throw new \moodle_exception('Grid initialized without any fields. Did you forget to call data_grid::init()?');
        }

        if ($this->get_filter_collection() && $this->get_filter_collection()->has_filters()) {
            list ($filter_sql, $filter_params) = $this->get_filter_collection()->get_sql_and_params();
        } else {
            $filter_sql = '';
            $filter_params = [];
        }

        // Use count query and only select a count of primary field.
        if ($count) {
            $query = $this->get_query();
            $selects = 'COUNT(DISTINCT ' . $this->get_field_definitions()[0]->get_select() . ')';
            $order_by = '';
            $groupby = '';
        } else {
            $query = $this->get_query();
            $selects = $this->get_query_select();
            $order_by = '';
            $groupby = ' GROUP BY ' . $this->get_field_definitions()[0]->get_select();
        }

        if (!$count) {
            // If there are multiple identifiers in the data source, construct a unique column.
            // This is to prevent warnings when multiple rows have the same value in the first column.
            $identifier_selects = [];
            /** @var sql_field_definition $field_definition */
            foreach ($this->get_field_definitions() as $field_definition) {
                if ($field_definition->has_attribute(identifier_attribute::class)) {
                    $identifier_selects[] = $field_definition->get_select();
                }
            }
            global $DB;
            $concat = $DB->sql_concat_join('"-"', $identifier_selects);
            if (count($identifier_selects) > 1) {
                $selects = sprintf('%s as unique_id, %s', $concat, $selects);
            }
        }

        $query = str_replace('%%SELECT%%', $selects, $query);
        $query = str_replace('%%FILTERS%%', $filter_sql, $query);
        $query = str_replace('%%ORDERBY%%', $order_by, $query);
        $query = str_replace('%%GROUPBY%%', $groupby, $query);

        return [$query, $filter_params];
    }

    /**
     * Execute query and return data collection.
     *
     * @throws \moodle_exception
     * @return data_collection_interface
     * @since 2.2
     */
    public function get_data()
    {
        if (!$this->data_collection) {
            $records = $this->get_records();
            $this->data_collection = $this->get_data_strategy()->convert_records_to_data_collection($records, $this);
        }

        return $this->data_collection;
    }

    /**
     * Get raw records from database.
     *
     * @return \stdClass[]
     * @throws \Exception
     * @throws \moodle_exception
     * @since 2.2
     */
    protected function get_records()
    {
        global $DB;

        list($query, $filter_params) = $this->get_sql_and_params(false);

        if ($this->supports_pagination() && !$this->is_pagination_disabled()) {
            return $DB->get_records_sql($query, $filter_params, $this->get_paginator()->get_limit_from(),
                $this->get_paginator()->get_per_page());
        }

        return $DB->get_records_sql($query, $filter_params, 0, 100);
    }

    #region Counting

    /**
     * Get total number of records for pagination.
     *
     * @return int
     */
    public function get_count()
    {
        global $DB;

        if (is_null($this->record_count)) {
            list($query, $filter_params) = $this->get_sql_and_params(true);

            $this->record_count = $DB->count_records_sql($query, $filter_params);
        }

        return $this->record_count;
    }

    #endregion
}
