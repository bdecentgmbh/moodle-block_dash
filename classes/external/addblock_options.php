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
 * Web service listing what Dash's "Add a block" chooser offers for a page.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\external;

use block_dash\gallery\gallery;
use block_dash\local\addblock\chooser;
use core_block\external\fetch_addable_blocks;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service listing what Dash's "Add a block" chooser offers for a page.
 *
 * The blocks are core's addable blocks, with a description where one is known. Gallery items are
 * only offered where a Dash block can be added.
 *
 * @package    block_dash
 */
class addblock_options extends gallery_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pagehash' => new external_value(PARAM_ALPHANUM, 'Hash of the page a block is added to'),
        ]);
    }

    /**
     * List the blocks and gallery items.
     *
     * @param string $pagehash
     * @return array
     */
    public static function execute(string $pagehash): array {
        ['pagehash' => $pagehash] = self::validate_parameters(self::execute_parameters(), ['pagehash' => $pagehash]);
        $context = self::get_editable_page_context($pagehash);

        $blocks = [];
        foreach (fetch_addable_blocks::execute(0, '', '', '', $pagehash) as $block) {
            $blocks[] = $block + ['description' => chooser::get_block_description($block['name'])];
        }
        $items = in_array('dash', array_column($blocks, 'name')) ? gallery::get_usable_items($context) : [];

        return ['blocks' => $blocks, 'items' => self::export_items($items)];
    }

    /**
     * Returned structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'blocks' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_PLUGIN, 'Name of the block'),
                'title' => new external_value(PARAM_RAW, 'Title of the block'),
                'blockform' => new external_value(PARAM_RAW, 'Class of the form shown before adding the block, if any'),
                'description' => new external_value(PARAM_TEXT, 'What the block does, or empty'),
            ])),
            'items' => self::items_structure(),
        ]);
    }
}
