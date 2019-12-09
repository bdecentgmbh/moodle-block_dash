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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\field\attribute;

/**
 * An attribute changes how a field definition is designated or behaves.
 *
 * @package block_dash\data_grid\field\attribute
 */
interface field_attribute_interface
{
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
}