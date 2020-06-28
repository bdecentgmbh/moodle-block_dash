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
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid;

use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data\strategy\data_strategy_interface;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_source\data_source_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package block_dash
 */
interface data_grid_interface {

    /**
     * Get data source running this data grid.
     *
     * @return data_source_interface
     */
    public function get_data_source();

    /**
     * Get data strategy.
     *
     * @return data_strategy_interface
     */
    public function get_data_strategy();

    /**
     * Set data strategy.
     *
     * @param data_strategy_interface $datastrategy
     */
    public function set_data_strategy(data_strategy_interface $datastrategy);

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
     * @param string $name
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
     * @param field_definition_interface[] $fielddefinitions
     * @throws \moodle_exception
     */
    public function set_field_definitions($fielddefinitions);

    /**
     * Add a single field definition to the report.
     *
     * @param field_definition_interface $fielddefinition
     * @throws \moodle_exception
     */
    public function add_field_definition(field_definition_interface $fielddefinition);

    /**
     * Check if grid has any field definitions set.
     *
     * @return bool
     */
    public function has_any_field_definitions();

    /**
     * Check if report has a certain field
     *
     * @param string $name
     * @return bool
     */
    public function has_field_definition($name);
}
