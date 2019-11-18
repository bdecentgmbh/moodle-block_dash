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

interface filter_interface
{
    const REQUIRED = true;
    const NOT_REQUIRED = false;

    const OPERATION_EQUAL = 'equal';
    const OPERATION_LESS_THAN = 'less';
    const OPERATION_GREATER_THAN = 'greater';
    const OPERATION_LESS_THAN_EQUAL = 'less_equal';
    const OPERATION_GREATER_THAN_EQUAL = 'greater_equal';
    const OPERATION_IN_OR_EQUAL = 'in_or_equal';
    const OPERATION_LIKE = 'like';
    const OPERATION_LIKE_WILDCARD = 'like_wild';

    /**
     * Supported SQL WHERE operations.
     */
    const OPERATIONS = [
        self::OPERATION_EQUAL,
        self::OPERATION_LESS_THAN,
        self::OPERATION_GREATER_THAN,
        self::OPERATION_LESS_THAN_EQUAL,
        self::OPERATION_GREATER_THAN_EQUAL,
        self::OPERATION_IN_OR_EQUAL,
        self::OPERATION_LIKE,
        self::OPERATION_LIKE_WILDCARD
    ];

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init();

    /**
     * @return mixed
     */
    public function get_raw_value();

    /**
     * @param $value mixed Raw value (most likely from form submission).
     */
    public function set_raw_value($value);

    /**
     * Check if a user value was set.
     *
     * @return bool
     */
    public function has_raw_value();

    /**
     * Check if this filter was applied by the user.
     *
     * @return bool
     */
    public function is_applied();

    /**
     * @return bool
     */
    public function is_required();

    /**
     * @param $required
     */
    public function set_required($required);

    /**
     * @return string
     */
    public function get_name();

    /**
     * @return string
     */
    public function get_select();

    /**
     * @return string
     */
    public function get_label();

    /**
     * @return string
     */
    public function get_operation();

    /**
     * Set an operation
     *
     * @param $operation
     * @throws \coding_exception
     */
    public function set_operation($operation);

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value();

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations();
    /**
     * @return bool
     */
    public function has_default_raw_value();

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values();

    /**
     * Return where SQL and params for placeholders.
     *
     * @return array
     * @throws \Exception
     */
    public function get_sql_and_params();

    /**
     * Override this method and call it after creating a form element.
     *
     * @param \MoodleQuickForm $form
     * @param filter_collection_interface $filter_collection
     * @param string $element_name_prefix
     * @throws \Exception
     */
    public function create_form_element(\MoodleQuickForm &$form, filter_collection_interface $filter_collection,
                                        $element_name_prefix = '');

    /**
     * Get option label based on value.
     *
     * @param $value
     * @return string
     */
    public function get_option_label($value);

    /**
     * @return \context
     */
    public function get_context();

    /**
     * @param \context $context
     */
    public function set_context(\context $context);
}
