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
 * Version details
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\template;

use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection_interface;

interface template_interface
{
    /**
     * @return \context
     */
    public function get_context();

    /**
     * @return string
     */
    public function get_query_template();

    /**
     * @return field_definition_interface[]
     */
    public function get_available_field_definitions();

    /**
     * @return filter_collection_interface
     */
    public function get_filter_collection();

    /**
     * @return string
     */
    public function get_mustache_template_name();

    /**
     * @return string
     */
    public function render();

    /**
     * @return data_grid_interface
     */
    public function get_data_grid();
}
