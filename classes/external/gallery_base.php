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
use core_external\external_value;

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
     * Context of a page the current user is adding a block to, for the "Add a block" chooser.
     *
     * @param string $pagehash From moodle_page::get_edited_page_hash().
     * @return \context Context the new block would go into.
     */
    protected static function get_editable_page_context(string $pagehash): \context {
        $page = \moodle_page::retrieve_edited_page($pagehash, MUST_EXIST);
        self::validate_context($page->context);
        if (!$page->user_can_edit_blocks()) {
            throw new \moodle_exception('nopermissions', '', '', get_string('addblock'));
        }
        return $page->context;
    }

    /**
     * Context a picker works in: the parent of a Dash block, or a page a block is added to.
     *
     * @param int $blockid
     * @param string $pagehash
     * @return \context
     */
    protected static function get_target_context(int $blockid, string $pagehash): \context {
        if ($pagehash !== '') {
            return self::get_editable_page_context($pagehash);
        }
        return self::get_editable_block($blockid)[1];
    }

    /**
     * Parameter for the page a block is added to.
     *
     * @return external_value
     */
    protected static function pagehash_parameter(): external_value {
        return new external_value(
            PARAM_ALPHANUM,
            'Hash of the page a Dash block would be added to, instead of a block',
            VALUE_DEFAULT,
            ''
        );
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
