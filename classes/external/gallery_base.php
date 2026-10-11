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
 * Shared checks of the gallery web services.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\external;

use block_dash\gallery\gallery;
use block_dash\gallery\item;
use block_dash\local\porter;
use core_external\external_api;

/**
 * Shared checks of the gallery web services.
 *
 * @package    block_dash
 */
abstract class gallery_base extends external_api {
    /**
     * Load a Dash block the current user may edit, and check the context.
     *
     * @param int $blockid
     * @return array [\block_base $block, \context $parentcontext]
     */
    protected static function get_editable_block(int $blockid): array {
        global $DB;

        $instance = $DB->get_record('block_instances', ['id' => $blockid, 'blockname' => 'dash'], '*', MUST_EXIST);
        $context = \context_block::instance($blockid);
        self::validate_context($context);

        $block = block_instance('dash', $instance);
        if (!porter::can_edit_block($block)) {
            throw new \required_capability_exception($context, 'moodle/block:edit', 'nopermissions', '');
        }

        return [$block, \context::instance_by_id($instance->parentcontextid)];
    }

    /**
     * An item the current user may put into a block in a context.
     *
     * @param string $shortname
     * @param \context $context
     * @return item
     */
    protected static function get_usable_item(string $shortname, \context $context): item {
        $item = gallery::get_item($shortname);
        if (!$item || !$item->can_use($context)) {
            throw new \moodle_exception('galleryerrornotusable', 'block_dash');
        }
        return $item;
    }
}
