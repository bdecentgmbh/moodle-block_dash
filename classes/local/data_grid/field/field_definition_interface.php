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
 * Represents a predefined field that can be added to a data grid.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field;

use block_dash\local\data_grid\field\attribute\field_attribute_interface;
use block_dash\local\data_grid\data_grid_interface;

/**
 * Represents a predefined field that can be added to a data grid.
 *
 * @package block_dash
 */
interface field_definition_interface {

    /**
     * Visible to user.
     */
    const VISIBILITY_VISIBLE = 1;

    /**
     * Not visible to user.
     */
    const VISIBILITY_HIDDEN = 0;

    /**
     * Value to display when empty or null.
     */
    const DEFAULT_EMPTY_VALUE = '-';

    /**
     * Set data source this field definition is attached to.
     *
     * @param data_grid_interface $datagrid
     * @return mixed
     */
    public function set_data_grid(data_grid_interface $datagrid);

    /**
     * Get data source this field definition is attached to.
     *
     * @return data_grid_interface
     */
    public function get_data_grid();

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     */
    public function transform_data($data, \stdClass $record);

    /**
     * Get unique field name.
     *
     * @return string
     */
    public function get_name();

    /**
     * Get field title.
     *
     * @return string
     */
    public function get_title();

    /**
     * Get field visibility.
     *
     * @return int
     */
    public function get_visibility();

    /**
     * Set field visibility.
     *
     * @param int $visibility
     */
    public function set_visibility($visibility);

    /**
     * Add attribute to this field definition.
     *
     * @param field_attribute_interface $attribute
     */
    public function add_attribute(field_attribute_interface $attribute);

    /**
     * Remove attribute to this field definition.
     *
     * @param field_attribute_interface $attribute
     */
    public function remove_attribute(field_attribute_interface $attribute);

    /**
     * Get all attributes associated with this field definition.
     *
     * @return field_attribute_interface[]
     */
    public function get_attributes();

    /**
     * Check if field has an attribute type.
     *
     * @param string $classname Full class path to attribute
     * @return bool
     */
    public function has_attribute($classname);

    /**
     * Get a single option.
     *
     * @param string $name
     * @return mixed|null
     */
    public function get_option($name);

    /**
     * Set option on field.
     *
     * @param string $name
     * @param string $value
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
     * Set if field should be sorted.
     *
     * @param bool $sort
     */
    public function set_sort($sort);

    /**
     * Is the field sorted.
     *
     * @return bool
     */
    public function get_sort();

    /**
     * Set direction sort should happen for this field.
     *
     * @param string $direction
     */
    public function set_sort_direction($direction);

    /**
     * Get sort direction.
     *
     * @return string
     */
    public function get_sort_direction();

    /**
     * Set optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     *
     * @param string $select
     */
    public function set_sort_select($select);

    /**
     * Return select for ORDER BY.
     *
     * @return string
     */
    public function get_sort_select();
}
