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
 * Web service rendering a gallery item, for previews.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\external;

use block_dash\gallery\embed;
use block_dash\gallery\gallery;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service rendering a gallery item, for previews.
 *
 * With a block, or the hash of a page a block is added to, the item renders in that context, for
 * the pickers. Without either, it renders in the system context, for the gallery page.
 *
 * @package    block_dash
 */
class gallery_preview extends gallery_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'shortname' => new external_value(PARAM_RAW_TRIMMED, 'Shortname of the item'),
            'blockid' => new external_value(
                PARAM_INT,
                'Id of the Dash block it would go into, 0 for the gallery page',
                VALUE_DEFAULT,
                0
            ),
            'pagehash' => self::pagehash_parameter(),
        ]);
    }

    /**
     * Render the item.
     *
     * @param string $shortname
     * @param int $blockid
     * @param string $pagehash
     * @return array
     */
    public static function execute(string $shortname, int $blockid = 0, string $pagehash = ''): array {
        ['shortname' => $shortname, 'blockid' => $blockid, 'pagehash' => $pagehash] = self::validate_parameters(
            self::execute_parameters(),
            ['shortname' => $shortname, 'blockid' => $blockid, 'pagehash' => $pagehash]
        );

        if ($blockid || $pagehash !== '') {
            $context = self::get_target_context($blockid, $pagehash);
            $item = self::get_usable_item($shortname, $context);
        } else {
            $context = \context_system::instance();
            self::validate_context($context);
            require_capability('block/dash:managegallery', $context);
            $item = gallery::get_item($shortname);
            if (!$item) {
                throw new \moodle_exception('galleryerrornotusable', 'block_dash');
            }
        }

        return ['html' => embed::render_item($item, $context)];
    }

    /**
     * Returned structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Rendered item'),
        ]);
    }
}
