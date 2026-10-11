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
 * Render gallery items outside a block region.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\gallery;

use html_writer;

/**
 * Render gallery items outside a block region.
 *
 * @package    block_dash
 */
class embed {
    /**
     * Render an embeddable item in a context, as a block would show it there.
     *
     * Doesn't check who may embed the item: a text filter can't know who wrote the text. The
     * item's access restrictions apply to the person viewing it.
     *
     * @param string $shortname
     * @param \context $context Context the item is embedded in.
     * @return string HTML, empty when the item is missing, hidden or not embeddable.
     */
    public static function render(string $shortname, \context $context): string {
        $item = gallery::get_item($shortname);
        if (!$item || $item->is_hidden() || !$item->is_embeddable() || !$item->is_available()) {
            return '';
        }
        return self::render_item($item, $context);
    }

    /**
     * Render any available item in a context, for previews.
     *
     * @param item $item
     * @param \context $context
     * @return string HTML.
     */
    public static function render_item(item $item, \context $context): string {
        global $PAGE;

        if (!$item->is_available()) {
            return '';
        }

        $block = embed_block::create($item, $context, $PAGE);
        $content = $block->get_content();
        if (empty($content->text)) {
            return '';
        }

        $title = $block->title !== '' ? html_writer::tag('h5', $block->title, ['class' => 'card-title']) : '';
        $attributes = $block->html_attributes();
        $attributes['class'] .= ' card mb-3 dash-embed';
        $attributes['data-gallery-item'] = $item->get_shortname();

        return self::get_stylesheet_links()
            . html_writer::tag('section', html_writer::div($title . $content->text, 'card-body'), $attributes);
    }

    /**
     * Links to the stylesheets Dash blocks need, once per request, when the page head is already printed.
     *
     * @return string HTML.
     */
    protected static function get_stylesheet_links(): string {
        global $PAGE;
        static $done = false;

        if ($done || !$PAGE->requires->is_head_done()) {
            return '';
        }
        $done = true;

        $links = '';
        foreach (\block_dash::get_extra_stylesheets() as $stylesheet) {
            $links .= html_writer::empty_tag('link', ['rel' => 'stylesheet', 'href' => $stylesheet->out(false)]);
        }
        return $links;
    }
}
