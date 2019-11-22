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

namespace block_dash\data_source;

use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data\field;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\layout\layout_interface;

/**
 * A data source defines which query, fields, and filters are used to retrieve data from a data grid.
 *
 * @package block_dash\data_source
 */
interface data_source_interface
{
    /**
     * Get human readable name of data source.
     *
     * @return string
     */
    public function get_name();

    /**
     * Get data grid. Build if necessary.
     *
     * @return data_grid_interface
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_data_grid();

    /**
     * Get filter collection for data grid. Build if necessary.
     *
     * @return filter_collection_interface
     */
    public function get_filter_collection();

    /**
     * Modify objects before data is retrieved.
     */
    public function before_data();

    /**
     * @return data_collection_interface
     */
    public function get_data();

    /**
     * Modify objects after data is retrieved.
     */
    public function after_data();

    /**
     * @return layout_interface
     */
    public function get_layout();

    /**
     * @return \context
     */
    public function get_context();

    /**
     * @param \renderer_base $output
     * @return array|\renderer_base|\stdClass|string
     */
    public function export_for_template(\renderer_base $output);

    /**
     * Add form fields to the block edit form. IMPORTANT: Prefix field names with config_ otherwise the values will
     * not be saved.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform);

    /**
     * Get a specific preference.
     *
     * @param string $name
     * @return mixed|array
     */
    public function get_preferences($name);

    /**
     * Get all preferences associated with the data source.
     *
     * @return array
     */
    public function get_all_preferences();

    /**
     * Set preferences on this data source.
     *
     * @param array $preferences
     */
    public function set_preferences(array $preferences);

    /**
     * @return string
     */
    public function get_query_template();

    /**
     * @return field_definition_interface[]
     */
    public function build_available_field_definitions();

    /**
     * @return field_definition_interface[]
     */
    public function get_available_field_definitions();

    /**
     * @return filter_collection_interface
     */
    public function build_filter_collection();
}
