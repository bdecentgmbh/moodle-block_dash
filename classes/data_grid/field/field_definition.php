<?php


namespace block_dash\data_grid\field;

class field_definition implements field_definition_interface
{
    /**
     * @var array
     */
    private $tables;

    /**
     * @var string Unique name of field (e.g. u_firstname).
     */
    private $name;

    /**
     * @var string String identifier of human readable name of field (e.g. Firstname).
     */
    private $title;

    /**
     * @var string Field select in SQL query (e.g. u.firstname).
     */
    private $select;

    /**
     * @var int Set if the field should be shown in the report.
     * Some fields are for data purposes only, such as joining.
     */
    private $visibility;

    /**
     * @var array Arbitrary options belonging to this field.
     */
    private $options = [];

    /**
     * @var bool If field should be sorted.
     */
    private $sort = false;

    /**
     * @var string Direction of sort, if sorting.
     */
    private $sort_direction = 'asc';

    /**
     * @var string Optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     */
    private $sort_select;

    public function __construct($tables, $name, $select, $title, $visibility = self::VISIBILITY_VISIBLE, $options = [])
    {
        $this->tables = $tables;
        $this->name = $name;
        $this->title = $title;
        $this->select = $select;
        $this->visibility = $visibility;
        $this->options = $options;
    }

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param $data
     * @param \stdClass $record Entire row
     * @return mixed
     */
    public function transform_data($data, \stdClass $record)
    {
        // Check if value is empty. A string with a zero (0) does not count as empty in this case.
        if (empty($data) && $data !== '0') {
            return self::DEFAULT_EMPTY_VALUE;
        }
        return $data;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function get_select()
    {
        return $this->select;
    }

    /**
     * @return int
     */
    public function get_visibility()
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     */
    public function set_visibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * @return array
     */
    public function get_tables()
    {
        return $this->tables;
    }

    #region Options

    /**
     * Get a single option.
     *
     * @param $name
     * @return mixed|null
     */
    public function get_option($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Set option on field.
     *
     * @param $name
     * @param $value
     */
    public function set_option($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Set options on field.
     *
     * @param array $options
     */
    public function set_options($options)
    {
        foreach ($options as $name => $value) {
            $this->set_option($name, $value);
        }
    }

    /**
     * Get all options for this field.
     *
     * @return array
     */
    public function get_options()
    {
        return $this->options;
    }

    /**
     * @param $name
     * @param $value
     */
    public function add_option($name, $value)
    {
        $this->options[$name] = $value;
    }

    #endregion

    #region Sorting

    /**
     * Set if field should be sorted.
     *
     * @param bool $sort
     * @throws \Exception
     */
    public function set_sort($sort)
    {
        if (!is_bool($sort)) {
            throw new \Exception('Sort expected to be a bool.');
        }

        $this->sort = $sort;
    }

    /**
     * @return bool
     */
    public function get_sort()
    {
        return $this->sort;
    }

    /**
     * Set direction sort should happen for this field.
     *
     * @param $direction
     * @throws \Exception
     */
    public function set_sort_direction($direction)
    {
        if (!in_array($direction, ['desc', 'asc'])) {
            throw new \Exception('Invalid sort direction: ' . $direction);
        }
        $this->sort_direction = $direction;
    }

    /**
     * @return string
     */
    public function get_sort_direction()
    {
        return $this->sort_direction;
    }

    /**
     * Set optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     *
     * @param $select
     */
    public function set_sort_select($select)
    {
        $this->sort_select = $select;
    }

    /**
     * Return select for ORDER BY.
     *
     * @return string
     */
    public function get_sort_select()
    {
        if (!is_null($this->sort_select)) {
            return $this->sort_select;
        }

        return $this->get_name();
    }

    /**
     * Override if inheriting class supports filtering.
     *
     * @return bool
     */
    public function supports_sorting()
    {
        return true;
    }

    #endregion

    /**
     * @return string
     */
    public function get_custom_form()
    {
        return '<input type="text" name="available_field_definitions[' . $this->get_name()
            . '][options][title_override]" value="' . $this->get_option('title_override') .'">';
    }
}
