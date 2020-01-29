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
 * A field is a simple container for a single value within a row/collection.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\data;

use block_dash\data_grid\field\field_definition_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * A field is a simple container for a single value within a row/collection.
 *
 * @package block_dash
 */
class field implements field_interface {

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed|string
     */
    private $value;

    /**
     * Create a new field.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get field name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get field value.
     *
     * @return mixed|string
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Get label of field definition.
     *
     * @return string|null
     */
    public function get_label() {
        if ($fielddefinition = field_definition_factory::get_field_definition($this->get_name())) {
            return $fielddefinition->get_title();
        }

        return null;
    }
}
