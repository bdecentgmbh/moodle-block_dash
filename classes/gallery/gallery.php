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
 * The gallery of ready-made Dash setups.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\gallery;

use block_dash\hook\gallery_items;
use block_dash\local\gallery\host_block;
use block_dash\local\gallery\site_item;
use moodle_exception;

/**
 * The gallery of ready-made Dash setups.
 *
 * Merges the items shipped by plugins (through the gallery_items hook) with the items saved on the
 * site, and applies the admin's settings to both.
 *
 * @package    block_dash
 */
class gallery {
    /** Shortnames of site items: lowercase letters, digits and hyphens. */
    const SHORTNAME_PATTERN = '/^[a-z0-9][a-z0-9-]*$/';

    /** Longest shortname of a site item. */
    const SHORTNAME_MAXLENGTH = 100;

    /** Largest total size of the files inside an item, in bytes. */
    const MAX_FILES_SIZE = 2097152;

    /**
     * All items, sorted by name.
     *
     * @return item[] Keyed by shortname.
     */
    public static function get_items(): array {
        $records = [];
        foreach (site_item::get_records() as $record) {
            $records[$record->get('shortname')] = $record;
        }

        $items = [];
        foreach (self::get_shipped_definitions() as $shortname => $definition) {
            $items[$shortname] = item::from_definition($definition, $records[$shortname] ?? null);
        }
        foreach ($records as $shortname => $record) {
            if ($record->get('source') === site_item::SOURCE_SITE) {
                $items[$shortname] = item::from_site_item($record);
            }
        }

        \core_collator::asort_objects_by_method($items, 'get_name');
        return $items;
    }

    /**
     * One item.
     *
     * @param string $shortname
     * @return item|null
     */
    public static function get_item(string $shortname): ?item {
        return self::get_items()[$shortname] ?? null;
    }

    /**
     * Items the current user may put into a block.
     *
     * @param \context $context Context of the block.
     * @return item[] Keyed by shortname.
     */
    public static function get_usable_items(\context $context): array {
        return array_filter(self::get_items(), function (item $item) use ($context) {
            return $item->can_use($context);
        });
    }

    /**
     * Items the current user may embed in text.
     *
     * @param \context $context Context where the items would be embedded.
     * @return item[] Keyed by shortname.
     */
    public static function get_embeddable_items(\context $context): array {
        return array_filter(self::get_items(), function (item $item) use ($context) {
            return $item->can_embed($context);
        });
    }

    /**
     * Save a new item on the site.
     *
     * @param string $shortname
     * @param string $name
     * @param string $description
     * @param array $data Export file, see \block_dash\local\porter::export().
     * @param array $settings 'embeddable', 'embedroles' and 'embedrolecontext', see update_settings().
     * @return item
     * @throws moodle_exception When the shortname is invalid or taken, or the files are too large.
     */
    public static function create_site_item(
        string $shortname,
        string $name,
        string $description,
        array $data,
        array $settings = []
    ): item {
        if ($error = self::validate_shortname($shortname)) {
            throw new moodle_exception($error, 'block_dash');
        }
        self::check_files_size($data);

        $record = new site_item(0, (object) [
            'shortname' => $shortname,
            'source' => site_item::SOURCE_SITE,
            'name' => $name,
            'description' => $description,
            'data' => json_encode($data),
        ]);
        self::apply_settings($record, $settings);
        $record->create();

        $item = item::from_site_item($record);
        host_block::get($item);
        return $item;
    }

    /**
     * Replace a site item's setup, keeping its details and settings.
     *
     * @param item $item
     * @param array $data Export file, see \block_dash\local\porter::export().
     * @return item
     * @throws moodle_exception When the item is shipped by a plugin, or the files are too large.
     */
    public static function replace_data(item $item, array $data): item {
        if ($item->is_shipped()) {
            throw new moodle_exception('galleryerrorshipped', 'block_dash');
        }
        self::check_files_size($data);

        $record = $item->get_record();
        $record->set('data', json_encode($data));
        $record->update();

        $item = item::from_site_item($record);
        host_block::get($item);
        return $item;
    }

    /**
     * Change an item's details and settings.
     *
     * Shipped items only take 'embeddable', 'embedroles', 'embedrolecontext' and 'hidden'. Site
     * items also take 'shortname', 'name' and 'description'.
     *
     * @param item $item
     * @param array $settings
     * @return item
     * @throws moodle_exception When a new shortname is invalid or taken.
     */
    public static function update_settings(item $item, array $settings): item {
        $record = $item->get_record();

        if ($item->is_shipped()) {
            if (!$record) {
                $record = new site_item(0, (object) [
                    'shortname' => $item->get_shortname(),
                    'source' => $item->get_source(),
                    'embeddable' => $item->is_embeddable(),
                ]);
            }
        } else {
            if (isset($settings['shortname']) && $settings['shortname'] !== $record->get('shortname')) {
                if ($error = self::validate_shortname($settings['shortname'], $record->get('id'))) {
                    throw new moodle_exception($error, 'block_dash');
                }
                host_block::rename($record->get('shortname'), $settings['shortname']);
                $record->set('shortname', $settings['shortname']);
            }
            foreach (['name', 'description'] as $key) {
                if (isset($settings[$key])) {
                    $record->set($key, $settings[$key]);
                }
            }
        }

        self::apply_settings($record, $settings);
        $record->save();

        return $item->is_shipped() ? self::get_item($item->get_shortname()) : item::from_site_item($record);
    }

    /**
     * Delete a site item.
     *
     * @param item $item
     * @throws moodle_exception When the item is shipped by a plugin.
     */
    public static function delete(item $item): void {
        if ($item->is_shipped()) {
            throw new moodle_exception('galleryerrorshipped', 'block_dash');
        }

        $record = $item->get_record();
        get_file_storage()->delete_area_files(\context_system::instance()->id, 'block_dash', 'gallerypreview', $record->get('id'));
        host_block::delete($item->get_shortname());
        $record->delete();
    }

    /**
     * Check a shortname for a site item.
     *
     * @param string $shortname
     * @param int $exceptid Id of the item being renamed.
     * @return string|null Identifier of the error string, or null when the shortname is fine.
     */
    public static function validate_shortname(string $shortname, int $exceptid = 0): ?string {
        if (!preg_match(self::SHORTNAME_PATTERN, $shortname) || strlen($shortname) > self::SHORTNAME_MAXLENGTH) {
            return 'galleryerrorshortname';
        }

        $existing = site_item::get_record(['shortname' => $shortname]);
        if ($existing && $existing->get('id') != $exceptid) {
            return 'galleryerrorshortnametaken';
        }

        return null;
    }

    /**
     * Total size of the files inside an export file.
     *
     * @param array $data
     * @return int Bytes.
     */
    public static function get_files_size(array $data): int {
        $size = 0;
        foreach ($data['files'] ?? [] as $file) {
            $size += strlen((string) base64_decode($file['content'] ?? ''));
        }
        return $size;
    }

    /**
     * Forget the items shipped by plugins, so the hook runs again.
     */
    public static function purge_shipped_items(): void {
        \cache::make('block_dash', 'shippeditems')->purge();
    }

    /**
     * Refuse export files whose files are too large.
     *
     * @param array $data
     * @throws moodle_exception
     */
    protected static function check_files_size(array $data): void {
        if (self::get_files_size($data) > self::MAX_FILES_SIZE) {
            throw new moodle_exception('galleryerrortoolarge', 'block_dash', '', display_size(self::MAX_FILES_SIZE));
        }
    }

    /**
     * Copy the settings shared by site and shipped items into a row.
     *
     * @param site_item $record
     * @param array $settings
     */
    protected static function apply_settings(site_item $record, array $settings): void {
        if (isset($settings['embeddable'])) {
            $record->set('embeddable', (bool) $settings['embeddable']);
        }
        if (isset($settings['embedroles'])) {
            $roles = array_values(array_map('intval', (array) $settings['embedroles']));
            $record->set('embedroles', $roles ? json_encode($roles) : null);
        }
        if (isset($settings['embedrolecontext'])) {
            $record->set('embedrolecontext', (int) $settings['embedrolecontext']);
        }
        if (isset($settings['hidden'])) {
            $record->set('hidden', (bool) $settings['hidden']);
        }
    }

    /**
     * Items shipped by plugins, as collected through the gallery_items hook.
     *
     * @return array Definitions keyed by shortname.
     */
    protected static function get_shipped_definitions(): array {
        $cache = \cache::make('block_dash', 'shippeditems');
        $definitions = $cache->get('items');
        if ($definitions === false) {
            $hook = new gallery_items();
            \core\di::get(\core\hook\manager::class)->dispatch($hook);
            $definitions = $hook->get_items();
            $cache->set('items', $definitions);
        }
        return $definitions;
    }
}
