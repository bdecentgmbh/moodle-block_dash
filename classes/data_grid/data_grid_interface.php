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

namespace block_dash\data_grid;

use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data\strategy\data_strategy_interface;
use block_dash\data_grid\field\field_definition_interface;

/**
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package block_dash
 */
interface data_grid_interface
{
    /**
     * @return data_strategy_interface
     */
    public function get_data_strategy();

    /**
     * @param data_strategy_interface $data_strategy
     */
    public function set_data_strategy(data_strategy_interface $data_strategy);

    /**
     * Execute and return data collection.
     *
     * @throws \moodle_exception
     * @return data_collection_interface
     * @since 2.2
     */
    public function get_data();

    /**
     * Get field definition by name. Returns false if not found.
     *
     * @param $name
     * @return bool|field_definition_interface
     */
    public function get_field_definition($name);

    /**
     * Get all field definitions in this data grid.
     *
     * @return field_definition_interface[]
     */
    public function get_field_definitions();

    /**
     * Sets field definitions on data grid.
     *
     * @param field_definition_interface[] $field_definitions
     * @throws \moodle_exception
     */
    public function set_field_definitions($field_definitions);

    /**
     * Add a single field definition to the report.
     *
     * @param field_definition_interface $field_definition
     * @throws \moodle_exception
     */
    public function add_field_definition(field_definition_interface $field_definition);

    /**
     * Check if grid has any field definitions set.
     *
     * @return bool
     */
    public function has_any_field_definitions();

    /**
     * Check if report has a certain field
     *
     * @param $name
     * @return bool
     */
    public function has_field_definition($name);
}
