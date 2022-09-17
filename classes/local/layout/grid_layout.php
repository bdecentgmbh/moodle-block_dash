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
 * This layout displays data in a grid of cards.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;
/**
 * A layout contains information on how to display data.
 * @see abstract_layout for creating new layouts.
 *
 * This layout displays data in a grid of cards.
 *
 * @package block_dash
 */
class grid_layout extends abstract_layout {

    /**
     * Get mustache template name.
     *
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_grid';
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination() {
        return true;
    }

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility() {
        return true;
    }

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering() {
        return true;
    }

    /**
     * If the layout supports field sorting.
     *
     * @return mixed
     */
    public function supports_sorting() {
        return true;
    }
}
