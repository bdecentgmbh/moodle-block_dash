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
 * Hidden Dash block holding a gallery item's setup and files.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\gallery;

use block_dash\gallery\item;
use block_dash\local\porter;

/**
 * Hidden Dash block holding a gallery item's setup and files.
 *
 * Widgets keep pictures in their own file areas of their block's context, and look them up there
 * or link to them. A gallery item rendered outside a block region has no block context of its
 * own, so each item gets a host block: a Dash block in the system context, on a page type no page
 * uses, into which the item is imported. Previews and embeds render with its context, so the
 * pictures are found and can be served.
 *
 * @package    block_dash
 */
class host_block {
    /** Page type of host blocks; no page has it, so they never show. */
    const PAGETYPE = 'block-dash-gallery-host';

    /** Config key naming the item a host block holds. */
    const ITEM_KEY = 'galleryitem';

    /** Config key holding a hash of the item's data, to notice changes. */
    const HASH_KEY = 'galleryhash';

    /**
     * The host block of an item, created or refreshed when needed.
     *
     * @param item $item
     * @return \block_base
     */
    public static function get(item $item): \block_base {
        global $DB;

        $data = $item->get_data() ?? [];
        $hash = sha1(json_encode($data));

        $instance = self::find($item->get_shortname());
        if (!$instance) {
            $now = time();
            $instance = (object) [
                'blockname' => 'dash',
                'parentcontextid' => \context_system::instance()->id,
                'showinsubcontexts' => 0,
                'requiredbytheme' => 0,
                'pagetypepattern' => self::PAGETYPE,
                'subpagepattern' => null,
                'defaultregion' => 'gallery',
                'defaultweight' => 0,
                'configdata' => base64_encode(serialize((object) [self::ITEM_KEY => $item->get_shortname()])),
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $instance->id = $DB->insert_record('block_instances', $instance);
            \context_block::instance($instance->id);
        }

        $block = block_instance('dash', $instance);
        if (($block->config->{self::HASH_KEY} ?? '') !== $hash) {
            porter::import($block, $data);
            $block->config->{self::ITEM_KEY} = $item->get_shortname();
            $block->config->{self::HASH_KEY} = $hash;
            $block->instance_config_commit();
        }

        return $block;
    }

    /**
     * Follow a renamed item.
     *
     * @param string $oldshortname
     * @param string $newshortname
     */
    public static function rename(string $oldshortname, string $newshortname): void {
        $instance = self::find($oldshortname);
        if ($instance) {
            $block = block_instance('dash', $instance);
            $block->config->{self::ITEM_KEY} = $newshortname;
            $block->instance_config_commit();
        }
    }

    /**
     * Delete the host block of an item, with its files.
     *
     * @param string $shortname
     */
    public static function delete(string $shortname): void {
        $instance = self::find($shortname);
        if ($instance) {
            blocks_delete_instance($instance);
        }
    }

    /**
     * The host block row of an item.
     *
     * @param string $shortname
     * @return \stdClass|null
     */
    protected static function find(string $shortname): ?\stdClass {
        global $DB;

        $instances = $DB->get_records('block_instances', ['blockname' => 'dash', 'pagetypepattern' => self::PAGETYPE], 'id');
        foreach ($instances as $instance) {
            $config = unserialize_object(base64_decode((string) $instance->configdata));
            if (is_object($config) && ($config->{self::ITEM_KEY} ?? null) === $shortname) {
                return $instance;
            }
        }
        return null;
    }
}
