<?php


namespace block_dash\data_grid;


use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\field\field_definition_interface;

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
            $selects = 'COUNT(' . $this->get_field_definitions()[0]->get_select() . ')';
            $order_by = '';
        } else {
            $query = $this->get_query();
            $selects = $this->get_query_select();
            $order_by = '';
        }

        $query = str_replace('%%SELECT%%', $selects, $query);
        $query = str_replace('%%FILTERS%%', $filter_sql, $query);
        $query = str_replace('%%ORDERBY%%', $order_by, $query);

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
        if ($this->data_collection) {
            return $this->data_collection;
        }

        $records = $this->get_records();

        $grid_data = new data_collection();

        foreach ($records as $record) {
            $row = new data_collection();
            foreach ($this->get_field_definitions() as $field_definition) {
                $name = $field_definition->get_name();

                if ($field_definition->get_visibility() == field_definition_interface::VISIBILITY_HIDDEN) {
                    unset($record->$name);
                    continue;
                }

                $record->$name = $field_definition->transform_data($record->$name, $record);
            }

            $row->add_data_associative($record);
            $grid_data->add_child_collection('rows', $row);
        }

        $this->data_collection = $grid_data;

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

        return $DB->get_records_sql($query, $filter_params);
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
