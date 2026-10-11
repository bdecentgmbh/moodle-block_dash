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
 * Export a Dash block's setup to a file and import it into another block.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;

use block_dash\local\configuration\configuration;
use block_dash\local\data_source\data_source_factory;
use block_dash\local\layout\layout_factory;
use moodle_exception;

/**
 * Export a Dash block's setup to a file and import it into another block.
 *
 * The file is the block's config as JSON, plus every file stored in the block's context (the
 * background image, and pictures that widgets keep in their own file areas) and whatever the data
 * source exports itself. Data sources that keep data outside the block config and its context can
 * implement two optional methods, called only when they exist:
 *
 * - export_instance_data(): array
 * - import_instance_data(array $data, bool $samesite, array &$warnings): void
 *
 * $samesite is true when the ids in the file are valid here: the file comes from this site,
 * or it names no site at all, like gallery items written to be portable.
 *
 * @package    block_dash
 */
class porter {
    /** Marks a file as a Dash block export. */
    const FORMAT = 'block_dash';

    /** Version of the file format. Files with a higher version are refused. */
    const VERSION = 1;

    /** Config keys that only make sense on the block and site they were saved on. */
    const SITE_ONLY_KEYS = [
        'backgroundimage',
        'dash_configure_options',
        'desktop_learningpath',
        'tablet_learningpath',
        'mobile_learningpath',
        'orgparentblkcontextid',
        'parentblkcontextid',
    ];

    /** Editor config keys, stored as ['text' => ..., 'format' => ..., 'itemid' => ...]. */
    const EDITOR_KEYS = ['header_content', 'footer_content', 'emptystate'];

    /** Access restrictions holding ids that are valid site-wide, mapped to the string naming them. */
    const SITE_RESTRICTIONS = [
        'restrict_roles' => 'restrictbyrole',
        'restrict_cohorts' => 'restrictbycohort',
    ];

    /** Access restrictions holding ids that are valid in one course, mapped to the string naming them. */
    const COURSE_RESTRICTIONS = [
        'restrict_groups' => 'restrictbygroup',
        'restrict_modules' => 'restrictbyactivitycompletion',
    ];

    /**
     * Export a block's setup.
     *
     * @param \block_base $block
     * @return array
     */
    public static function export(\block_base $block): array {
        $config = !empty($block->config) ? json_decode(json_encode($block->config), true) : [];

        foreach (self::SITE_ONLY_KEYS as $key) {
            unset($config[$key]);
        }
        foreach (self::EDITOR_KEYS as $key) {
            if (isset($config[$key]) && is_array($config[$key])) {
                unset($config[$key]['itemid']);
            }
        }

        $datasourcedata = [];
        $datasource = self::get_data_source($block);
        if ($datasource && method_exists($datasource, 'export_instance_data')) {
            $datasourcedata = $datasource->export_instance_data();
        }

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported' => gmdate('c'),
            'site' => self::site_hash(),
            'requires' => self::get_required_components($config),
            'name' => $config['title'] ?? '',
            'source' => ['blockid' => (int) $block->instance->id, 'contextid' => (int) $block->context->id],
            'config' => $config,
            'files' => self::export_files($block->context),
            'datasource_data' => $datasourcedata,
        ];
    }

    /**
     * Suggested file name for a block's export.
     *
     * @param \block_base $block
     * @return string
     */
    public static function get_filename(\block_base $block): string {
        $title = \core_text::strtolower(clean_filename(trim($block->config->title ?? '')));
        $title = trim(preg_replace('/\s+/', '-', $title), '-');
        return ($title !== '' ? $title . '-' : '') . 'dash-' . gmdate('Ymd') . '.json';
    }

    /**
     * Read the content of an export file.
     *
     * @param string $content
     * @return array
     * @throws moodle_exception When the file isn't a Dash export, or comes from a newer version.
     */
    public static function decode(string $content): array {
        $data = json_decode($content, true);

        if (
            !is_array($data)
            || ($data['format'] ?? '') !== self::FORMAT
            || !isset($data['config']['data_source_idnumber'])
            || !is_string($data['config']['data_source_idnumber'])
        ) {
            throw new moodle_exception('importerrorinvalid', 'block_dash');
        }

        if ((int) ($data['version'] ?? 0) > self::VERSION) {
            throw new moodle_exception('importerrorversion', 'block_dash');
        }

        foreach (['requires', 'files', 'datasource_data'] as $key) {
            $data[$key] = isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
        }

        return $data;
    }

    /**
     * Check that a decoded file can be imported into a block by the current user.
     *
     * @param array $data Decoded file, see decode().
     * @param \block_base $block
     * @throws moodle_exception When the data source isn't installed or the user may not use it.
     */
    public static function validate(array $data, \block_base $block): void {
        $identifier = $data['config']['data_source_idnumber'];

        if (!data_source_factory::exists($identifier)) {
            $names = array_map(function ($component) {
                return get_string_manager()->string_exists('pluginname', $component)
                    ? get_string('pluginname', $component) : $component;
            }, array_diff($data['requires'] ?? [], ['block_dash']));
            throw new moodle_exception('importerrordatasource', 'block_dash', '', $names ? implode(', ', $names) : $identifier);
        }

        if (!data_source_factory::can_manage($identifier, $block->context)) {
            throw new moodle_exception('importerrorcapability', 'block_dash');
        }
    }

    /**
     * Replace a block's setup with an imported one.
     *
     * Call validate() first.
     *
     * @param \block_base $block
     * @param array $data Decoded file, see decode().
     * @return string[] Warnings about what could not be carried over.
     */
    public static function import(\block_base $block, array $data): array {
        $warnings = [];
        $config = (object) $data['config'];

        foreach (self::SITE_ONLY_KEYS as $key) {
            unset($config->$key);
        }
        $config->orgparentblkcontextid = $block->context->id;

        // Files without a site hash (gallery items) are portable by design.
        $samesite = empty($data['site']) || $data['site'] === self::site_hash();
        if ($samesite) {
            self::filter_course_restrictions($config, $block, $warnings);
        } else {
            self::remove_restrictions($config, self::SITE_RESTRICTIONS + self::COURSE_RESTRICTIONS, 'importwarningsite', $warnings);
            $warnings[] = get_string('importwarningpreferences', 'block_dash');
        }

        if (!empty($config->preferences['layout'])) {
            $layout = layout_factory::normalise_identifier($config->preferences['layout']);
            if (layout_factory::exists($layout)) {
                $config->preferences['layout'] = $layout;
            } else {
                unset($config->preferences['layout']);
                $warnings[] = get_string('importwarninglayout', 'block_dash');
            }
        }

        if (!has_capability('moodle/site:trustcontent', $block->context)) {
            foreach (self::EDITOR_KEYS as $key) {
                if (isset($config->{$key}['text'])) {
                    $config->{$key}['text'] = clean_text($config->{$key}['text'], $config->{$key}['format'] ?? FORMAT_HTML);
                }
            }
        }

        // Pictures that widgets link to from their preferences now live in this block's context.
        $source = $data['source'] ?? [];
        if (!empty($source['contextid'])) {
            $config = (object) self::rewrite_file_urls(
                (array) $config,
                (int) $source['contextid'],
                (int) ($source['blockid'] ?? 0),
                $block
            );
        }

        $block->config = $config;
        $block->instance_config_commit();

        self::import_files($block, $data['files'] ?? [], (int) ($source['blockid'] ?? 0), $warnings);

        $datasource = self::get_data_source($block);
        if ($datasource && method_exists($datasource, 'import_instance_data')) {
            $datasource->import_instance_data($data['datasource_data'] ?? [], $samesite, $warnings);
        } else if ($datasource && method_exists($datasource, 'instance_copy')) {
            $warnings[] = get_string('importwarningdatasourcedata', 'block_dash');
        }

        return $warnings;
    }

    /**
     * Whether the current user may edit a block.
     *
     * Mirrors block_base::user_can_edit(), which needs the page the block is shown on.
     *
     * @param \block_base $block
     * @return bool
     */
    public static function can_edit_block(\block_base $block): bool {
        global $USER;

        if (has_capability('moodle/block:edit', $block->context)) {
            return true;
        }

        // Blocks on a user's own Dashboard.
        $parentcontext = \context::instance_by_id($block->instance->parentcontextid);
        return $parentcontext->contextlevel == CONTEXT_USER && $parentcontext->instanceid == $USER->id
            && has_capability('moodle/my:manageblocks', $parentcontext);
    }

    /**
     * Hash of this site's identifier, so a file can tell whether ids are local without revealing the site.
     *
     * @return string
     */
    public static function site_hash(): string {
        global $CFG;
        return sha1($CFG->siteidentifier);
    }

    /**
     * Keep only the group and activity restrictions that belong to the block's course.
     *
     * @param \stdClass $config
     * @param \block_base $block
     * @param array $warnings
     */
    protected static function filter_course_restrictions(\stdClass $config, \block_base $block, array &$warnings): void {
        $coursecontext = $block->context->get_course_context(false);
        $courseid = $coursecontext ? $coursecontext->instanceid : 0;

        $valid = [
            'restrict_groups' => $courseid ? array_keys(groups_get_all_groups($courseid)) : [],
            'restrict_modules' => $courseid ? array_keys(get_fast_modinfo($courseid)->get_cms()) : [],
        ];

        $removed = [];
        foreach (self::COURSE_RESTRICTIONS as $key => $label) {
            if (empty($config->$key) || !is_array($config->$key)) {
                continue;
            }
            $kept = array_values(array_intersect($config->$key, $valid[$key]));
            if (count($kept) < count($config->$key)) {
                $removed[] = get_string($label, 'block_dash');
            }
            $config->$key = $kept;
        }

        if ($removed) {
            $warnings[] = get_string('importwarningcourse', 'block_dash', implode(', ', $removed));
        }
    }

    /**
     * Remove access restrictions and add a warning naming them.
     *
     * @param \stdClass $config
     * @param array $restrictions Config keys mapped to the string naming them.
     * @param string $warning String identifier of the warning.
     * @param array $warnings
     */
    protected static function remove_restrictions(\stdClass $config, array $restrictions, string $warning, array &$warnings): void {
        $removed = [];
        foreach ($restrictions as $key => $label) {
            if (!isset($config->$key)) {
                continue;
            }
            if (!empty($config->$key)) {
                $removed[] = get_string($label, 'block_dash');
            }
            $config->$key = [];
        }

        if ($removed) {
            $warnings[] = get_string($warning, 'block_dash', implode(', ', $removed));
        }
    }

    /**
     * Every file stored in a block's context, whichever plugin it belongs to.
     *
     * @param \context $context The block's context.
     * @return array
     */
    protected static function export_files(\context $context): array {
        global $DB;

        $fs = get_file_storage();
        $files = [];
        $areas = $DB->get_recordset_sql(
            "SELECT DISTINCT component, filearea FROM {files} WHERE contextid = :contextid AND filename <> '.'
           ORDER BY component, filearea",
            ['contextid' => $context->id]
        );
        foreach ($areas as $area) {
            $sort = 'itemid, filepath, filename';
            foreach ($fs->get_area_files($context->id, $area->component, $area->filearea, false, $sort, false) as $file) {
                $files[] = [
                    'component' => $file->get_component(),
                    'area' => $file->get_filearea(),
                    'itemid' => (int) $file->get_itemid(),
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                    'content' => base64_encode($file->get_content()),
                ];
            }
        }
        $areas->close();

        return $files;
    }

    /**
     * Replace the files in the block's context with the ones from the file.
     *
     * Files of plugins that aren't installed are skipped. Item ids equal to the exporting block's id
     * become the importing block's id, as some plugins key their files by block.
     *
     * @param \block_base $block
     * @param array $files
     * @param int $sourceblockid Id of the exporting block, 0 when unknown.
     * @param array $warnings
     */
    protected static function import_files(\block_base $block, array $files, int $sourceblockid, array &$warnings): void {
        $fs = get_file_storage();
        $fs->delete_area_files($block->context->id);

        $hasbackground = false;
        foreach ($files as $file) {
            // Files of DASH-1296 exports have no component: they are the block's background image.
            $component = clean_param($file['component'] ?? 'block_dash', PARAM_COMPONENT);
            $area = clean_param($file['area'] ?? '', PARAM_AREA);
            $filename = clean_param($file['filename'] ?? '', PARAM_FILE);
            if (
                !$component || !$area || !$filename || !isset($file['content'])
                    || !\core_component::get_component_directory($component)
            ) {
                continue;
            }

            $isbackground = $component === 'block_dash' && $area === 'images';
            if ($isbackground && $hasbackground) {
                continue;
            }

            $itemid = (int) ($file['itemid'] ?? 0);
            if ($sourceblockid && $itemid === $sourceblockid) {
                $itemid = (int) $block->instance->id;
            }
            $filepath = '/' . trim(clean_param($file['filepath'] ?? '/', PARAM_PATH), '/') . '/';
            $filepath = $filepath === '//' ? '/' : $filepath;

            if ($fs->file_exists($block->context->id, $component, $area, $itemid, $filepath, $filename)) {
                continue;
            }
            $stored = $fs->create_file_from_string([
                'contextid' => $block->context->id,
                'component' => $component,
                'filearea' => $area,
                'itemid' => $itemid,
                'filepath' => $filepath,
                'filename' => $filename,
            ], (string) base64_decode($file['content']));

            if ($isbackground) {
                if (!$stored->is_valid_image()) {
                    $stored->delete();
                    $warnings[] = get_string('importwarningimage', 'block_dash');
                    continue;
                }
                // The block shows one background image.
                $hasbackground = true;
            }
        }
    }

    /**
     * Point pluginfile URLs at the exporting block's context to the importing block's context.
     *
     * Covers absolute and root-relative URLs, and the escaped form inside JSON strings that some
     * widgets keep in their preferences. Absolute URLs get this site's address.
     *
     * @param mixed $value Config, or a part of it.
     * @param int $sourcecontextid Context id of the exporting block.
     * @param int $sourceblockid Id of the exporting block, 0 when unknown.
     * @param \block_base $block The importing block.
     * @return mixed
     */
    protected static function rewrite_file_urls($value, int $sourcecontextid, int $sourceblockid, \block_base $block) {
        global $CFG;

        if (is_array($value) || is_object($value)) {
            $isobject = is_object($value);
            $value = (array) $value;
            foreach ($value as $key => $item) {
                $value[$key] = self::rewrite_file_urls($item, $sourcecontextid, $sourceblockid, $block);
            }
            return $isobject ? (object) $value : $value;
        }
        if (!is_string($value) || strpos($value, 'pluginfile.php') === false) {
            return $value;
        }

        foreach (['/', '\\/'] as $separator) {
            $s = preg_quote($separator, '~');
            $pattern = '~(?:https?:' . $s . $s . '[^\s"\'<>]*?)?' . $s . 'pluginfile\.php' . $s . $sourcecontextid . $s
                . '([a-z0-9_]+)' . $s . '([A-Za-z0-9_-]+)' . $s . '(\d+)' . $s . '~';
            $value = preg_replace_callback($pattern, function ($match) use ($separator, $sourceblockid, $block, $CFG) {
                $itemid = $sourceblockid && (int) $match[3] === $sourceblockid ? $block->instance->id : $match[3];
                return str_replace('/', $separator, $CFG->wwwroot . '/pluginfile.php/' . $block->context->id . '/'
                    . $match[1] . '/' . $match[2] . '/' . $itemid . '/');
            }, $value);
        }

        return $value;
    }

    /**
     * Plugins a block's setup depends on, for error messages and the gallery.
     *
     * @param array $config
     * @return string[]
     */
    protected static function get_required_components(array $config): array {
        $components = ['block_dash'];

        foreach ([$config['data_source_idnumber'] ?? '', $config['preferences']['layout'] ?? ''] as $identifier) {
            if (!$identifier) {
                continue;
            }
            $info = data_source_factory::get_data_source_info($identifier) ?? layout_factory::get_layout_info($identifier);
            $class = $info['factory'] ?? $identifier;
            if ($component = \core_component::get_component_from_classname($class)) {
                $components[] = $component;
            }
        }

        return array_values(array_unique($components));
    }

    /**
     * The data source configured for a block, if it can be built.
     *
     * @param \block_base $block
     * @return \block_dash\local\data_source\data_source_interface|null
     */
    protected static function get_data_source(\block_base $block) {
        if (empty($block->config->data_source_idnumber)) {
            return null;
        }
        $configuration = configuration::create_from_instance($block);
        return $configuration ? $configuration->get_data_source() : null;
    }
}
