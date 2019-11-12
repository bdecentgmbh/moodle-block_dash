<?php


namespace block_dash\data_grid\field;


interface field_definition_interface
{
    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_HIDDEN = 2;

    const DEFAULT_EMPTY_VALUE = '-';

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param $data
     * @param \stdClass $record Entire row
     * @return string
     */
    public function transform_data($data, \stdClass $record);

    /**
     * @return string
     */
    public function get_name();

    /**
     * @return string
     */
    public function get_title();

    /**
     * @return string
     */
    public function get_select();

    /**
     * @return int
     */
    public function get_visibility();

    /**
     * @return array
     */
    public function get_tables();

    /**
     * @param int $visibility
     */
    public function set_visibility($visibility);

    /**
     * Get a single option.
     *
     * @param $name
     * @return mixed|null
     */
    public function get_option($name);

    /**
     * Set option on field.
     *
     * @param $name
     * @param $value
     */
    public function set_option($name, $value);

    /**
     * Set options on field.
     *
     * @param array $options
     */
    public function set_options($options);

    /**
     * Get all options for this field.
     *
     * @return array
     */
    public function get_options();

    /**
     * @param $name
     * @param $value
     */
    public function add_option($name, $value);

    /**
     * Set if field should be sorted.
     *
     * @param bool $sort
     * @throws \Exception
     */
    public function set_sort($sort);

    /**
     * @return bool
     */
    public function get_sort();

    /**
     * Set direction sort should happen for this field.
     *
     * @param $direction
     * @throws \Exception
     */
    public function set_sort_direction($direction);

    /**
     * @return string
     */
    public function get_sort_direction();

    /**
     * Set optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     *
     * @param $select
     */
    public function set_sort_select($select);

    /**
     * Return select for ORDER BY.
     *
     * @return string
     */
    public function get_sort_select();

    /**
     * Override if inheriting class supports filtering.
     *
     * @return bool
     */
    public function supports_sorting();

    /**
     * @return string
     */
    public function get_custom_form();
}
