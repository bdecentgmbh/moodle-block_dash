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

namespace block_dash\layout;

use block_dash\template\template_interface;

/**
 * A layout contains information on how to display data. @see abstract_layout for creating new layouts.
 *
 * @package block_dash\layout
 */
interface layout_interface
{
    /**
     * @return template_interface
     */
    public function get_template();

    /**
     * @return string
     */
    public function get_mustache_template_name();

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility();

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering();

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination();

    /**
     * Modify objects before data is retrieved in the template. This allows the layout to make decisions on the
     * template and data grid.
     */
    public function before_data();

    /**
     * Modify objects after data is retrieved in the template. This allows the layout to make decisions on the
     * template and data grid.
     */
    public function after_data();

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the template. When a user chooses a layout, specific form elements may be
     * displayed after a quick refresh of the form.
     *
     * Be sure to call parent::build_preferences_form() if you override this method.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform);

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(\renderer_base $output);
}