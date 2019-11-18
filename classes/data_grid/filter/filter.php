<?php
// This file is part of The Bootstrap Moodle theme
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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\filter;

class filter implements filter_interface
{
    /**
     * @var mixed The value a user has chosen. Or the default.
     */
    private $raw_value;

    /**
     * @var string Unique name used for placeholder.
     */
    private $name;

    /**
     * @var string The select portion of filter SQL express.
     */
    private $select;

    /**
     * @var string Human readable name for field.
     */
    private $label;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var bool If the filter is required to display data. Make sure required filters have default value if you want
     * the results to display without submitting a filter form.
     */
    protected $required = self::NOT_REQUIRED;

    /**
     * @var string SQL WHERE operation.
     */
    private $operation = self::OPERATION_IN_OR_EQUAL;

    /**
     * @var \context
     */
    private $context;

    /**
     * @param string $name
     * @param string $select
     */
    public function __construct($name, $select)
    {
        $this->name = $name;
        $this->select = $select;

        $this->init();
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init()
    {
        $this->initialized = true;
    }

    /**
     * @return mixed
     */
    public function get_raw_value()
    {
        return $this->raw_value;
    }

    /**
     * @param $value mixed Raw value (most likely from form submission).
     */
    public function set_raw_value($value)
    {
        $this->raw_value = $value;
    }

    /**
     * Check if a user value was set.
     *
     * @return bool
     */
    public function has_raw_value()
    {
        return !is_null($this->raw_value);
    }

    /**
     * Check if this filter was applied by the user.
     *
     * @return bool
     */
    public function is_applied()
    {
        return $this->has_raw_value() && $this->get_raw_value() != $this->get_default_raw_value();
    }

    /**
     * @return bool
     */
    public function is_required()
    {
        return $this->required == self::REQUIRED;
    }

    /**
     * @param $required
     */
    public function set_required($required)
    {
        $this->required = $required;
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
    public function get_select()
    {
        return $this->select;
    }

    /**
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function get_operation()
    {
        return $this->operation;
    }

    /**
     * Set an operation
     *
     * @param $operation
     * @throws \coding_exception
     */
    public function set_operation($operation)
    {
        if (!in_array($operation, $this->get_supported_operations())) {
            throw new \coding_exception(get_class($this) . ' does not support operation: ' . $operation);
        }

        $this->operation = $operation;
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value()
    {
        return null;
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations()
    {
        // Return all operations.
        return filter_interface::OPERATIONS;
    }

    /**
     * @return bool
     */
    public function has_default_raw_value()
    {
        return !empty($this->get_default_raw_value());
    }

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values()
    {
        if (!$this->has_raw_value()) {
            if ($this->has_default_raw_value()) {
                return [$this->get_default_raw_value()];
            }
            return [];
        }
        return [$this->get_raw_value()];
    }

    /**
     * Return where SQL and params for placeholders.
     *
     * @return array
     * @throws \Exception
     */
    public function get_sql_and_params()
    {
        if (!$this->initialized) {
            throw new \Exception('Filter was not initialized properly. Did you call parent::init()?');
        }

        if (!$values = $this->get_values()) {
            // Return empty
            return ['', []];
        }

        reset($values);
        // Get first value for operations that only support one value.
        $value = $values[0];

        $sql = '';
        $placeholder = $this->get_name();
        $params = [$placeholder => $value];
        $select = $this->get_select();

        switch ($this->operation) {
            case self::OPERATION_EQUAL:
                $sql = "$select = :$placeholder";
                break;
            case self::OPERATION_LESS_THAN:
                $sql = "$select < :$placeholder";
                break;
            case self::OPERATION_GREATER_THAN:
                $sql = "$select > :$placeholder";
                break;
            case self::OPERATION_LESS_THAN_EQUAL:
                $sql = "$select <= :$placeholder";
                break;
            case self::OPERATION_GREATER_THAN_EQUAL:
                $sql = "$select >= :$placeholder";
                break;
            case self::OPERATION_IN_OR_EQUAL:
                list ($sql, $params) = $this->get_in_or_equal();
                $sql = "$select $sql";
                break;
            case self::OPERATION_LIKE:
                $sql = "$select LIKE :$placeholder";
                break;
            case self::OPERATION_LIKE_WILDCARD:
                $sql = "$select LIKE :$placeholder";
                // Convert value to wildcard.
                $params[$placeholder] = '%'.$params[$placeholder].'%';
                break;
        }

        return [$sql, $params];
    }

    /**
     * This variable is used for storing the results of $DB->get_in_or_equal() since it internally keeps a
     * running count (so calling it twice will result in different param names).
     *
     * Used only in filter::get_in_or_equal() as a sort of runtime cache
     *
     * @var array
     */
    private $sql_and_params;

    /**
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_in_or_equal()
    {
        global $DB;

        if (!$values = $this->get_values()) {
            return ['', []];
        }

        if (is_null($this->sql_and_params)) {

            $values = $this->get_values();

            $this->sql_and_params = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
        }

        return $this->sql_and_params;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filter_collection
     * @param string $element_name_prefix
     * @throws \Exception
     */
    public function create_form_element(filter_collection_interface $filter_collection,
                                        $element_name_prefix = '')
    {
        throw new \coding_exception('Filter element does not exist. Did you forget to override filter::create_form_element()?');
    }

    /**
     * Get option label based on value.
     *
     * @param $value
     * @return string
     */
    public function get_option_label($value)
    {
        return $value;
    }

    /**
     * @return \context
     */
    public function get_context()
    {
        return $this->context;
    }

    /**
     * @param \context $context
     */
    public function set_context(\context $context)
    {
        $this->context = $context;
    }

}
