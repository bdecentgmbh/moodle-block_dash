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
 * Web service copying a gallery item into a block.
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
 * Web service copying a gallery item into a block.
 *
 * The block's setup is replaced, as with Import. The notices are queued for the reloaded page.
 *
 * @package    block_dash
 */
class gallery_use extends gallery_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blockid' => new external_value(PARAM_INT, 'Id of the Dash block'),
            'shortname' => new external_value(PARAM_RAW_TRIMMED, 'Shortname of the item'),
        ]);
    }

    /**
     * Copy the item into the block.
     *
     * @param int $blockid
     * @param string $shortname
     * @return array
     */
    public static function execute(int $blockid, string $shortname): array {
        ['blockid' => $blockid, 'shortname' => $shortname] = self::validate_parameters(
            self::execute_parameters(),
            ['blockid' => $blockid, 'shortname' => $shortname]
        );

        [$block, $parentcontext] = self::get_editable_block($blockid);
        $item = self::get_usable_item($shortname, $parentcontext);

        return ['warnings' => gallery::copy_into_block($item, $block)];
    }

    /**
     * Returned structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'warnings' => new external_multiple_structure(new external_value(PARAM_TEXT, 'What could not be carried over')),
        ]);
    }
}
