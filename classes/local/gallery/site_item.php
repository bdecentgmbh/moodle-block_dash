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
 * Row of the gallery table: a site item, or admin settings for a shipped item.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\gallery;

/**
 * Row of the gallery table: a site item, or admin settings for a shipped item.
 *
 * @package    block_dash
 */
class site_item extends \core\persistent {
    /** Database table. */
    const TABLE = 'block_dash_gallery';

    /** Source of items saved on the site. */
    const SOURCE_SITE = 'site';

    /**
     * Properties of a row.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'shortname' => ['type' => PARAM_TEXT],
            'source' => ['type' => PARAM_ALPHANUMEXT, 'default' => self::SOURCE_SITE],
            'name' => ['type' => PARAM_TEXT, 'null' => NULL_ALLOWED, 'default' => null],
            'description' => ['type' => PARAM_TEXT, 'null' => NULL_ALLOWED, 'default' => null],
            'data' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED, 'default' => null],
            'embeddable' => ['type' => PARAM_BOOL, 'default' => false],
            'embedroles' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED, 'default' => null],
            'embedrolecontext' => ['type' => PARAM_INT, 'default' => 1],
            'hidden' => ['type' => PARAM_BOOL, 'default' => false],
        ];
    }
}
