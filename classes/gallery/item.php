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
 * A gallery item: a ready-made Dash setup, shipped by a plugin or saved on the site.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\gallery;

use block_dash\local\data_source\data_source_factory;
use block_dash\local\gallery\site_item;
use block_dash\local\porter;
use moodle_url;

/**
 * A gallery item: a ready-made Dash setup, shipped by a plugin or saved on the site.
 *
 * @package    block_dash
 */
class item {
    /** Embed roles are checked in the context where the item is embedded. */
    const ROLES_ANY_CONTEXT = 1;

    /** Embed roles are checked in the system context. */
    const ROLES_SYSTEM = 2;

    /** Image extensions looked for next to a shipped item's file. */
    const PREVIEW_EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

    /** @var string Unique key. */
    protected $shortname;

    /** @var string 'site', or the component shipping the item. */
    protected $source;

    /** @var array|null Export file, decoded; loaded on demand for shipped items. */
    protected $data = null;

    /** @var string|null Path of a shipped item's export file. */
    protected $file = null;

    /** @var site_item|null Row of a site item, or of a shipped item's admin settings. */
    protected $record = null;

    /** @var bool Whether a shipped item can be embedded when the admin hasn't changed it. */
    protected $defaultembeddable = false;

    /**
     * Item saved on the site.
     *
     * @param site_item $record
     * @return item
     */
    public static function from_site_item(site_item $record): item {
        $item = new item();
        $item->shortname = $record->get('shortname');
        $item->source = site_item::SOURCE_SITE;
        $item->record = $record;
        return $item;
    }

    /**
     * Item shipped by a plugin.
     *
     * @param array $definition As collected by \block_dash\hook\gallery_items.
     * @param site_item|null $settings The admin's settings for the item, if any.
     * @return item
     */
    public static function from_definition(array $definition, ?site_item $settings = null): item {
        $item = new item();
        $item->shortname = $definition['shortname'];
        $item->source = $definition['component'];
        $item->file = $definition['file'];
        $item->defaultembeddable = !empty($definition['embeddable']);
        $item->record = $settings;
        return $item;
    }

    /**
     * Unique key, used in [dash item="..."].
     *
     * @return string
     */
    public function get_shortname(): string {
        return $this->shortname;
    }

    /**
     * 'site', or the component shipping the item.
     *
     * @return string
     */
    public function get_source(): string {
        return $this->source;
    }

    /**
     * Whether a plugin ships the item.
     *
     * @return bool
     */
    public function is_shipped(): bool {
        return $this->source !== site_item::SOURCE_SITE;
    }

    /**
     * Row of a site item, or of a shipped item's admin settings.
     *
     * @return site_item|null
     */
    public function get_record(): ?site_item {
        return $this->record;
    }

    /**
     * Human-readable name.
     *
     * @return string
     */
    public function get_name(): string {
        if (!$this->is_shipped()) {
            return format_string($this->record->get('name'), true, ['context' => \context_system::instance()]);
        }
        $identifier = 'gallery:' . substr($this->shortname, strlen($this->source) + 1);
        return get_string_manager()->string_exists($identifier, $this->source)
            ? get_string($identifier, $this->source) : $this->shortname;
    }

    /**
     * Description, plain text.
     *
     * @return string
     */
    public function get_description(): string {
        if (!$this->is_shipped()) {
            return (string) $this->record->get('description');
        }
        $identifier = 'gallery:' . substr($this->shortname, strlen($this->source) + 1) . '_desc';
        return get_string_manager()->string_exists($identifier, $this->source) ? get_string($identifier, $this->source) : '';
    }

    /**
     * Name of the source: 'Site', or the shipping plugin's name.
     *
     * @return string
     */
    public function get_source_name(): string {
        if (!$this->is_shipped()) {
            return get_string('gallerysourcesite', 'block_dash');
        }
        return get_string_manager()->string_exists('pluginname', $this->source)
            ? get_string('pluginname', $this->source) : $this->source;
    }

    /**
     * The export file, decoded.
     *
     * @return array|null Null when a shipped item's file can't be read.
     */
    public function get_data(): ?array {
        if ($this->data === null) {
            if ($this->is_shipped()) {
                $content = is_readable($this->file) ? file_get_contents($this->file) : '';
            } else {
                $content = $this->record->get('data');
            }
            try {
                $this->data = porter::decode((string) $content);
            } catch (\moodle_exception $e) {
                debugging("Dash gallery item '{$this->shortname}' isn't a valid export file.", DEBUG_DEVELOPER);
                return null;
            }
        }
        return $this->data;
    }

    /**
     * Identifier of the item's data source.
     *
     * @return string
     */
    public function get_data_source(): string {
        return $this->get_data()['config']['data_source_idnumber'] ?? '';
    }

    /**
     * Whether the item's data source is installed.
     *
     * @return bool
     */
    public function is_available(): bool {
        return $this->get_data() !== null && data_source_factory::exists($this->get_data_source());
    }

    /**
     * Plugins the item needs that aren't installed.
     *
     * @return string[] Component names.
     */
    public function get_missing_components(): array {
        $requires = $this->get_data()['requires'] ?? [];
        return array_values(array_filter($requires, function ($component) {
            return !\core_component::get_component_directory($component);
        }));
    }

    /**
     * Whether the item can be embedded in text.
     *
     * @return bool
     */
    public function is_embeddable(): bool {
        return $this->record ? (bool) $this->record->get('embeddable') : $this->defaultembeddable;
    }

    /**
     * Whether the item is left out of pickers and embed lists.
     *
     * @return bool
     */
    public function is_hidden(): bool {
        return $this->record && $this->record->get('hidden');
    }

    /**
     * Roles that may embed the item; empty for anyone.
     *
     * @return int[]
     */
    public function get_embed_roles(): array {
        $roles = $this->record ? json_decode((string) $this->record->get('embedroles'), true) : [];
        return is_array($roles) ? array_map('intval', $roles) : [];
    }

    /**
     * Where embed roles are checked: ROLES_ANY_CONTEXT or ROLES_SYSTEM.
     *
     * @return int
     */
    public function get_embed_role_context(): int {
        return $this->record ? (int) $this->record->get('embedrolecontext') : self::ROLES_ANY_CONTEXT;
    }

    /**
     * Whether a user may put the item into a block in a context.
     *
     * @param \context $context Context of the block.
     * @return bool
     */
    public function can_use(\context $context): bool {
        return !$this->is_hidden() && $this->is_available()
            && data_source_factory::can_manage($this->get_data_source(), $context);
    }

    /**
     * Whether the current user may embed the item in a context.
     *
     * @param \context $context Context where the item is embedded.
     * @return bool
     */
    public function can_embed(\context $context): bool {
        global $CFG, $USER;

        if ($this->is_hidden() || !$this->is_embeddable() || !$this->is_available()) {
            return false;
        }

        $roles = $this->get_embed_roles();
        if (!$roles) {
            return true;
        }

        $checkcontext = $this->get_embed_role_context() == self::ROLES_SYSTEM ? \context_system::instance() : $context;
        $userroles = array_column(get_user_roles($checkcontext, $USER->id, true), 'roleid');
        if (isloggedin() && !isguestuser()) {
            $userroles[] = $CFG->defaultuserroleid;
        } else if (isguestuser()) {
            $userroles[] = $CFG->guestroleid;
        }

        return (bool) array_intersect($roles, array_map('intval', $userroles));
    }

    /**
     * Preview image, if any.
     *
     * @return moodle_url|null
     */
    public function get_preview_url(): ?moodle_url {
        global $CFG;

        if ($this->is_shipped()) {
            $base = substr($this->file, 0, -strlen(pathinfo($this->file, PATHINFO_EXTENSION)));
            foreach (self::PREVIEW_EXTENSIONS as $extension) {
                if (is_readable($base . $extension) && strpos($base, $CFG->dirroot) === 0) {
                    return new moodle_url(substr($base . $extension, strlen($CFG->dirroot)));
                }
            }
            return null;
        }

        $files = get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'block_dash',
            'gallerypreview',
            $this->record->get('id'),
            'id DESC',
            false
        );
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    'block_dash',
                    'gallerypreview',
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                );
            }
        }
        return null;
    }
}
