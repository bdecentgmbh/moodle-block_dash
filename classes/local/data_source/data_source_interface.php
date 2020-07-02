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
 * A data source defines which query, fields, and filters are used to retrieve data from a data grid.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\data_grid_interface;
use block_dash\local\data_grid\field\field_definition_interface;
use block_dash\local\data_grid\filter\filter_collection_interface;
use block_dash\local\layout\layout_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * A data source defines which query, fields, and filters are used to retrieve data from a data grid.
 *
 * @package block_dash
 */
interface data_source_interface {

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
     * Get data collection.
     *
     * @return data_collection_interface
     */
    public function get_data();

    /**
     * Modify objects after data is retrieved.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection);

    /**
     * Explicitly set layout.
     *
     * @param layout_interface $layout
     */
    public function set_layout(layout_interface $layout);

    /**
     * Get layout.
     *
     * @return layout_interface
     */
    public function get_layout();

    /**
     * Get context.
     *
     * @return \context
     */
    public function get_context();

    /**
     * Get template variables.
     *
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
     * Get query template.
     *
     * @return string
     */
    public function get_query_template();

    /**
     * Get count query template.
     *
     * @return string
     */
    public function get_count_query_template();

    /**
     * Get group by fields.
     *
     * @return string
     */
    public function get_groupby();

    /**
     * Build available field definitions for this data source.
     *
     * @return field_definition_interface[]
     */
    public function build_available_field_definitions();

    /**
     * Get available field definitions for this data source.
     *
     * @return field_definition_interface[]
     */
    public function get_available_field_definitions();

    /**
     * Get sorted field definitions based on preferences.
     *
     * @return field_definition_interface[]
     */
    public function get_sorted_field_definitions();

    /**
     * Build filter collection.
     *
     * @return filter_collection_interface
     */
    public function build_filter_collection();

    /**
     * Set block instance.
     *
     * @param \block_base $blockinstance
     */
    public function set_block_instance(\block_base $blockinstance);

    /**
     * Get block instance.
     *
     * @return null|\block_base
     */
    public function get_block_instance();
}
