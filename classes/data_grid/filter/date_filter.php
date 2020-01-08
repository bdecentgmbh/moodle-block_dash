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

class date_filter extends filter
{
    const DATE_FUNCTION_CEIL = 'ceil';
    const DATE_FUNCTION_FLOOR = 'floor';
    const DATE_FUNCTION_NONE = 'none';

    protected $function;

    /**
     * date_filter constructor.
     * @param $name
     * @param $select
     * @param $function
     * @param string $label
     * @param string $clause_type
     * @throws \coding_exception
     */
    public function __construct($name, $select, $function, $label = '', $clause_type = self::CLAUSE_TYPE_WHERE)
    {
        if (!in_array($function, array(self::DATE_FUNCTION_CEIL, self::DATE_FUNCTION_FLOOR, self::DATE_FUNCTION_NONE))) {
            throw new \coding_exception('Invalid date function');
        }

        $this->function = $function;

        parent::__construct($name, $select, $label, $clause_type);
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations()
    {
        return [
            self::OPERATION_GREATER_THAN,
            self::OPERATION_GREATER_THAN_EQUAL,
            self::OPERATION_LESS_THAN,
            self::OPERATION_LESS_THAN_EQUAL
        ];
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
     * Get values, converted to unix timestamps. (There should only be one value though).
     *
     * @return array
     */
    public function get_values()
    {
        $values = parent::get_values();

        foreach ($values as $key => $value) {
            if (!empty($value)) {
                $values[$key] = $this->to_timestamp($value);
            }
        }

        return $values;
    }

    /**
     * Convert user submitted value to a unix timestamp in user's timezone.
     *
     * @param $value
     * @return int
     */
    protected function to_timestamp($value)
    {
        // User submitted date in user's timezone
        $date = \DateTime::createFromFormat('Y-m-d', $value, \core_date::get_user_timezone_object());

        switch ($this->function) {
            case self::DATE_FUNCTION_FLOOR:
                $date->setTime(0,0,0);
                break;
            case self::DATE_FUNCTION_CEIL:
                $date->setTime(23, 59, 59);
                break;
        }

        // Now convert to UTC for accurate comparisons to database values.
        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->getTimestamp();
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
        global $OUTPUT;

        $name = $element_name_prefix . $this->get_name();

        return $OUTPUT->render_from_template('block_dash/filter_date', [
            'label' => $this->get_label(),
            'name' => $name,
            'value' => $this->get_raw_value()
        ]);
    }
}