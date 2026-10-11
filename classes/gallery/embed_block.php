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
 * A Dash block built in memory from a gallery item, to render it outside a block region.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\gallery;

use block_dash\local\gallery\host_block;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/dash/block_dash.php');

/**
 * A Dash block built in memory from a gallery item, to render it outside a block region.
 *
 * Its setup and its block context are the item's host block's (see \block_dash\local\gallery\host_block),
 * so widgets find the pictures they keep in their block's context. Its parent context is the context
 * it is rendered in, which decides the data it shows, and its id is negative and unique within the
 * request.
 *
 * @package    block_dash
 */
class embed_block extends \block_dash {
    /** @var int Last id handed out; ids count down from -1. */
    protected static $lastid = 0;

    /** @var item The item this block shows. */
    protected $item;

    /**
     * Build the block for an item.
     *
     * @param item $item
     * @param \context $context Context to render in.
     * @param \moodle_page $page
     * @param int|null $id Id the page gave the block, when it is rendered again for paging, filters or sorting.
     * @return embed_block
     */
    public static function create(item $item, \context $context, \moodle_page $page, ?int $id = null): embed_block {
        $host = host_block::get($item);

        $block = new embed_block();
        $block->item = $item;
        $block->page = $page;
        $block->context = $host->context;
        $block->config = clone $host->config;
        // Data downloads load the block from the database, which an embedded item doesn't have.
        if (isset($block->config->preferences) && is_array($block->config->preferences)) {
            $block->config->preferences['exportdata'] = 0;
        }
        $block->instance = (object) [
            'id' => $id !== null && $id < 0 ? $id : --self::$lastid,
            'blockname' => 'dash',
            'parentcontextid' => $context->id,
            'showinsubcontexts' => 0,
            'requiredbytheme' => 0,
            'pagetypepattern' => '',
            'subpagepattern' => null,
            'defaultregion' => '',
            'defaultweight' => 0,
            'configdata' => '',
        ];
        $block->specialization();
        return $block;
    }

    /**
     * The block's name, as in a real Dash block.
     *
     * @return string
     */
    public function name() {
        return 'dash';
    }

    /**
     * The item this block shows.
     *
     * @return item
     */
    public function get_item(): item {
        return $this->item;
    }

    /**
     * This block is never saved.
     *
     * @param object $data
     * @param bool $nolongerused
     * @throws \coding_exception
     */
    public function instance_config_save($data, $nolongerused = false) {
        throw new \coding_exception('A gallery item rendered outside a block can\'t be saved.');
    }
}
