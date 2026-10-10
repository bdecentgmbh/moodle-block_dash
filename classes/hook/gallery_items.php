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
 * Hook letting plugins ship ready-made Dash setups in the gallery.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\hook;

/**
 * Hook letting plugins ship ready-made Dash setups in the gallery.
 *
 * A shipped item is an export file (see \block_dash\local\porter) inside the plugin, usually in its
 * gallery/ folder. Its name and description come from the strings gallery:<name> and
 * gallery:<name>_desc in the plugin's language file. An image next to the file with the same base
 * name (.png, .jpg or .svg) becomes its preview.
 *
 * @package    block_dash
 */
#[\core\attribute\label('Allows plugins to add ready-made Dash setups to the gallery.')]
#[\core\attribute\tags('block_dash')]
final class gallery_items {
    /** Item names: lowercase letters, digits, hyphens and underscores. */
    const NAME_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/';

    /** @var array Items added by plugins, keyed by shortname. */
    private $items = [];

    /**
     * Add an item shipped by a plugin.
     *
     * @param string $component Plugin shipping the item, e.g. 'dashaddon_tiles'.
     * @param string $name Name of the item within the plugin, e.g. 'quick-links'.
     * @param string $file Absolute path of the export file.
     * @param array $options 'embeddable' => bool, whether the item can be embedded by default.
     */
    public function add_item(string $component, string $name, string $file, array $options = []): void {
        if (!preg_match(self::NAME_PATTERN, $name) || !\core_component::get_component_directory($component)) {
            debugging("Invalid Dash gallery item '{$name}' of '{$component}'.", DEBUG_DEVELOPER);
            return;
        }
        if (!is_readable($file)) {
            debugging("Dash gallery item file not found: {$file}", DEBUG_DEVELOPER);
            return;
        }

        $shortname = $component . ':' . $name;
        $this->items[$shortname] = [
            'shortname' => $shortname,
            'component' => $component,
            'name' => $name,
            'file' => $file,
            'embeddable' => !empty($options['embeddable']),
        ];
    }

    /**
     * Items added by plugins, keyed by shortname.
     *
     * @return array
     */
    public function get_items(): array {
        return $this->items;
    }
}
