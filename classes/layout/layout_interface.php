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
 * A layout contains information on how to display data.
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
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform);

    /**
     * Modify objects before data is retrieved in the template.
     */
    public function before_data();

    /**
     * Modify objects after data is retrieved in the template.
     */
    public function after_data();
}