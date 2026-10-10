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
 * Web service listing the gallery items a user may put into a block.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\external;

use block_dash\gallery\gallery;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service listing the gallery items a user may put into a block.
 *
 * @package    block_dash
 */
class gallery_list extends gallery_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blockid' => new external_value(PARAM_INT, 'Id of the Dash block the item would go into'),
        ]);
    }

    /**
     * List the items.
     *
     * @param int $blockid
     * @return array
     */
    public static function execute(int $blockid): array {
        ['blockid' => $blockid] = self::validate_parameters(self::execute_parameters(), ['blockid' => $blockid]);
        [, $parentcontext] = self::get_editable_block($blockid);

        $items = [];
        foreach (gallery::get_usable_items($parentcontext) as $item) {
            $preview = $item->get_preview_url();
            $items[] = [
                'shortname' => $item->get_shortname(),
                'name' => $item->get_name(),
                'description' => $item->get_description(),
                'source' => $item->get_source_name(),
                'previewurl' => $preview ? $preview->out(false) : '',
            ];
        }
        return ['items' => $items];
    }

    /**
     * Returned structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(new external_single_structure([
                'shortname' => new external_value(PARAM_RAW, 'Shortname'),
                'name' => new external_value(PARAM_TEXT, 'Name'),
                'description' => new external_value(PARAM_TEXT, 'Description'),
                'source' => new external_value(PARAM_TEXT, 'Site, or the shipping plugin\'s name'),
                'previewurl' => new external_value(PARAM_URL, 'Preview image, or empty'),
            ])),
        ]);
    }
}
