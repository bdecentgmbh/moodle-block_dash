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
 * The "Add a block" chooser, offering Dash gallery items next to the blocks.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_dash\local\addblock;

use block_dash\gallery\gallery;

/**
 * The "Add a block" chooser, offering Dash gallery items next to the blocks.
 *
 * Adding an item uses core's URL for adding a Dash block, with the item in an extra parameter;
 * the new block imports it when it is created.
 *
 * @package    block_dash
 */
class chooser {
    /** URL parameter naming the gallery item a new Dash block is set up from. */
    const ITEM_PARAM = 'dashgalleryitem';

    /**
     * The "Add a block" button of a block region, opening the chooser.
     *
     * Mirrors core_renderer::addblockbutton(), which only renders when blocks can be added.
     *
     * @param \moodle_page $page
     * @param string $region
     * @return string HTML.
     */
    public static function render_button(\moodle_page $page, string $region): string {
        global $OUTPUT;

        $params = ['bui_addblock' => '', 'sesskey' => sesskey()];
        if ($region !== '') {
            $params['bui_blockregion'] = $region;
        }
        $url = new \moodle_url($page->url, $params);

        return $OUTPUT->render_from_template('block_dash/add_block_button', [
            'link' => $url->out(false),
            'escapedlink' => '?' . $url->get_query_string(false),
            'pagehash' => $page->get_edited_page_hash(),
            'blockregion' => $region,
        ]);
    }

    /**
     * What a block does, for the chooser.
     *
     * Blocks can describe themselves with a pluginname_desc string, as enrol plugins do. Dash
     * describes Moodle's own blocks.
     *
     * @param string $blockname Name of the block, without block_.
     * @return string Plain text, or empty when there is no description.
     */
    public static function get_block_description(string $blockname): string {
        $strings = get_string_manager();
        foreach ([['pluginname_desc', 'block_' . $blockname], ['addblockdesc:' . $blockname, 'block_dash']] as [$key, $component]) {
            if ($strings->string_exists($key, $component)) {
                return trim(strip_tags(get_string($key, $component)));
            }
        }
        return '';
    }

    /**
     * Set up a Dash block that was just added from the chooser with the chosen gallery item.
     *
     * Problems are shown as notifications; the block then stays empty, offering its own ways to set it up.
     *
     * @param \block_base $block
     */
    public static function import_requested_item(\block_base $block): void {
        $shortname = optional_param(self::ITEM_PARAM, '', PARAM_RAW_TRIMMED);
        if ($shortname === '' || optional_param('bui_addblock', '', PARAM_PLUGIN) !== 'dash' || !confirm_sesskey()) {
            return;
        }

        try {
            $item = gallery::get_item($shortname);
            if (!$item || !$item->can_use(\context::instance_by_id($block->instance->parentcontextid))) {
                throw new \moodle_exception('galleryerrornotusable', 'block_dash');
            }
            gallery::copy_into_block($item, $block);
        } catch (\moodle_exception $e) {
            \core\notification::error($e->getMessage());
        }
    }
}
