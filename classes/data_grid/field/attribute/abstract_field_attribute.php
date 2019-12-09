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
abstract class abstract_field_attribute implements field_attribute_interface
{
    private $options = [];

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
}