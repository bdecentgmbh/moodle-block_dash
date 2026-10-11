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
 * Take over the "Add a block" button where the theme lets plugins replace it.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\addblock;

/**
 * Take over the "Add a block" button where the theme lets plugins replace it.
 *
 * @package    block_dash
 */
class hook_callbacks {
    /**
     * Replace the button with Dash's, when the site turned that on.
     *
     * The parameter has no type, so that this class doesn't depend on Boost Union being installed.
     *
     * @param \theme_boost_union\hook\output\add_block_button $hook
     */
    public static function add_block_button($hook): void {
        if (get_config('block_dash', 'replaceaddblock')) {
            $hook->set_html(chooser::render_button($hook->page, $hook->region));
        }
    }
}
