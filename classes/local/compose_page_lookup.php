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
 * Recognise blocks on a page of Compose's Pages add-on.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;

/**
 * Recognise blocks on a page of Compose's Pages add-on (composeaddon_pages, reached at /p/<shortname>).
 */
class compose_page_lookup {
    /**
     * Whether a block sits on a Compose page, which visitors who are not logged in may read.
     *
     * Mirrors the page view: it builds its page type as "dashaddon-pages-<shortname>" and answers everybody while
     * the Pages add-on is switched on, unless the site forces login.
     *
     * @param  \stdClass $blockinstance A block_instances record.
     * @return bool True when the block is on a page visitors may read without logging in.
     */
    public static function is_public_page(\stdClass $blockinstance): bool {
        global $CFG;

        if (strpos((string) ($blockinstance->pagetypepattern ?? ''), 'dashaddon-pages-') !== 0) {
            return false;
        }

        return empty($CFG->forcelogin)
            && class_exists('\composeaddon_pages\model\page')
            && \local_dashcompose\plugininfo\composeaddon::is_addon_enabled('pages');
    }
}
