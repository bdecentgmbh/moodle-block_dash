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
 * Gallery items shipped with block_dash.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\gallery;

use block_dash\hook\gallery_items;

/**
 * Gallery items shipped with block_dash.
 *
 * @package    block_dash
 */
class hook_callbacks {
    /**
     * Add block_dash's starter items to the gallery.
     *
     * @param gallery_items $hook
     */
    public static function gallery_items(gallery_items $hook): void {
        global $CFG;

        $dir = $CFG->dirroot . '/blocks/dash/gallery';
        $hook->add_item('block_dash', 'my-learning', $dir . '/my-learning.json', ['embeddable' => true]);
        $hook->add_item('block_dash', 'participants', $dir . '/participants.json');
    }
}
