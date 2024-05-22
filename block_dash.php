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
 * Dash block class.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\block_builder;
use block_dash\local\data_source\data_source_factory;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/blocks/dash/lib.php");
require_once("$CFG->libdir/filelib.php");

/**
 * Dash block class.
 */
class block_dash extends block_base {

    /**
     * Initialize block instance.
     *
     * @throws coding_exception
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_dash');
    }

    /**
     * This block supports configuration fields.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     *
     * @throws coding_exception
     */
    public function specialization() {
        global $OUTPUT;

        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('newblock', 'block_dash');
        }

        try {
            $bb = block_builder::create($this);
            if ($bb->is_collapsible_content_addon()) {
                $addclass = "collapsible-block dash-block-collapse-icon";
                if (!$bb->is_section_expand_content_addon()) {
                    $addclass .= " collapsed";
                }
                $attr = [
                    'data-toggle' => 'collapse',
                    'class' => $addclass,
                    'href' => "#dash-{$this->instance->id}",
                    "aria-expanded" => "false",
                    "aria-controls" => "dash-{$this->instance->id}",
                ];
                $this->title = html_writer::tag('span', $this->title, $attr);
            }
        } catch (\Exception $e) {
            // Configured datasource is missing.
            $this->title = get_string('newblock', 'block_dash');
        }

        $showheader = get_config('block_dash', 'showheader');
        if (isset($this->config->showheader)) {
            $showheader = $this->config->showheader;
        }

        if (!$showheader && !$this->page->user_is_editing()) {
            $this->title = "";
        }
    }

    /**
     * Multiple dashes can be added to a single page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Serialize and store config data
     *
     * @param string $data
     * @param bool $nolongerused
     * @return void
     */
    public function instance_config_save($data, $nolongerused = false) {
        if (isset($data->backgroundimage)) {
            file_save_draft_area_files($data->backgroundimage, $this->context->id, 'block_dash', 'images',
                0, ['subdirs' => 0, 'maxfiles' => 1]);
        }
        if (isset($data->dash_configure_options) && isset($data->data_source_idnumber)) {
            $datasource = data_source_factory::build_data_source($data->data_source_idnumber,
                $this->context);
            if ($datasource) {
                if (method_exists($datasource, 'set_default_preferences')) {
                    $configpreferences = ['config_preferences' => []];
                    $datasource->set_default_preferences($configpreferences);
                    $data->preferences = $configpreferences['config_preferences'];
                }
            }
            unset($data->dash_configure_options);
        }

        parent::instance_config_save($data, $nolongerused);
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     *
     * @param int $frominstanceid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($frominstanceid) {

        // Copy the block instance background image.
        $fromcontext = \context_block::instance($frominstanceid);
        $fs = get_file_storage();
        // Do not use draft files hacks outside of forms.
        $files = $fs->get_area_files($fromcontext->id, 'block_dash', 'images', 0, 'id ASC', false);
        foreach ($files as $file) {
            $filerecord = ['contextid' => $this->context->id];
            $fs->create_file_from_storedfile($filerecord, $file);
        }

        // Copy the datasource images and files.
        $bb = block_builder::create($this);
        $datasource = $bb->get_configuration()->get_data_source();
        if (!empty($datasource) && method_exists($datasource, 'instance_copy')) {
            $datasource->instance_copy($frominstanceid, $this->context->id);
        }

        return true;
    }

    /**
     * Dashes are suitable on all page types.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Return block content. Build dash.
     *
     * @return \stdClass
     */
    public function get_content() {
        global $OUTPUT;

        // Prevent the jqueryui conflict with bootstrap tooltip.
        if (class_exists('\core\navigation\views\secondary')) {
            $this->page->requires->js_init_code(
                'require(["jquery", "jqueryui"], function($, ui) {
                    $.widget.bridge("uibutton", $.ui.button);
                    $.widget.bridge("uitooltip", $.ui.tooltip);
                });'
            );
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new \stdClass();

        if (block_dash_is_disabled()) {
            $this->content->text = is_siteadmin() ? get_string('disableallmessage', 'block_dash') : '';
            return $this->content;
        }

        try {
            $bb = block_builder::create($this);

            if (!$bb->get_configuration()) {
                return $this->content->text = get_string('missingdatasource', 'block_dash');
            }

            $datasource = $bb->get_configuration()->get_data_source();
            // Conditionally hide the block when empty.
            $hidewhenempty = get_config('block_dash', 'hide_when_empty');
            if (isset($this->config->hide_when_empty)) {
                $hidewhenempty = $this->config->hide_when_empty;
            }

            if ($datasource && $hidewhenempty && (($datasource->is_widget() && $datasource->is_empty())
                || (!$datasource->is_widget() && $datasource->get_data()->is_empty()))
                && !$this->page->user_is_editing()) {
                return $this->content;
            }
            $this->content = $bb->get_block_content();

            if ($css = $this->get_extra_css()) {
                $this->content->text .= $css;
            }
        } catch (\Exception $e) {
            $this->content->text = $OUTPUT->notification($e->getMessage() . $e->getTraceAsString(), 'error');
        }

        $this->page->requires->css(new \moodle_url('/blocks/dash/styles/select2.min.css'));
        $this->page->requires->css(new \moodle_url('/blocks/dash/styles/datepicker.css'));
        $this->page->requires->css(new \moodle_url('/blocks/dash/styles/slick.css'));
        return $this->content;
    }

    /**
     * Add block width CSS classes.
     *
     * @return array
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();
        if (isset($this->config->css_class)) {
            $cssclasses = $this->config->css_class;
            if (!is_array($cssclasses)) {
                $cssclasses = explode(',', $cssclasses);
            }
            foreach ($cssclasses as $class) {
                $attributes['class'] .= ' ' . trim($class);
            }

        }
        if (isset($this->config->width)) {
            $attributes['class'] .= ' dash-block-width-' . $this->config->width;
        } else {
            $attributes['class'] .= ' dash-block-width-100';
        }

        if (isset($this->config->preferences['layout'])) {
            $attributes['class'] .= ' ' . str_replace('\\', '-', $this->config->preferences['layout']);
        }

        try {
            $bb = block_builder::create($this);
            if ($bb->is_collapsible_content_addon()) {
                $attributes['class'] .= ' block-collapse-block';
            }
        } catch (\Exception $e) {
            $attributes['class'] .= ' missing-datasource';
        }

        return $attributes;
    }

    /**
     * Get extra CSS styling for this specific block.
     *
     * @return string
     */
    public function get_extra_css() {
        global $OUTPUT;

        $blockcss = [];
        $data = [
            'block' => $this,
            'headerfootercolor' => isset($this->config->headerfootercolor) ? $this->config->headerfootercolor : null,
        ];

        $backgroundgradient = isset($this->config->backgroundgradient)
            ? str_replace(';', '', $this->config->backgroundgradient) : null;

        if ($this->get_background_image_url()) {
            if ($backgroundgradient) {
                $blockcss[] = sprintf('background-image: %s, url(%s);',
                    $backgroundgradient, $this->get_background_image_url()->out()
                );
            } else {
                $blockcss[] = sprintf('background-image: url(%s);', $this->get_background_image_url());
            }
        } else if ($backgroundgradient) {
            $blockcss[] = sprintf('background-image: %s;', $this->config->backgroundgradient);
        }

        // Background postition.
        if (isset($this->config->backgroundimage_position)) {
            $bgpostion = $this->config->backgroundimage_position;
            $bgpostionvalue = ($bgpostion == 'custom') ? $this->config->backgroundimage_customposition : $bgpostion;
            $blockcss[] = sprintf('background-position: %s;', $bgpostionvalue);
        }

        // Background size.
        if (isset($this->config->backgroundimage_size)) {
            $bgsize = $this->config->backgroundimage_size;
            $bgsizevalue = ($bgsize == 'custom') ? $this->config->backgroundimage_customsize : $bgsize;
            $blockcss[] = sprintf('background-size: %s;', $bgsizevalue);
        }

        if (isset($this->config->css) && is_array($this->config->css)) {
            foreach ($this->config->css as $property => $value) {
                if (!empty($value)) {
                    $blockcss[] = sprintf('%s: %s;', $property, $value);
                }
            }
        }

        if (isset($this->config->border_option)) {
            if ($this->config->border_option) {
                $bordervalue = isset($this->config->border) && ($this->config->border) ? $this->config->border
                    : "1px solid rgba(0,0,0,.125)";
                $blockcss[] = sprintf('%s: %s;', 'border', $bordervalue);
            } else {
                $blockcss[] = sprintf('%s: %s;', 'border', "none");
            }
        }

        $data['blockcss'] = implode(PHP_EOL, $blockcss);

        return $OUTPUT->render_from_template('block_dash/extra_css', $data);
    }

    /**
     * Get background image.
     *
     * @return stored_file|null
     * @throws coding_exception
     */
    public function get_background_image() {
        $fs = get_file_storage();
        $backgroundimage = null;
        foreach ($fs->get_area_files($this->context->id, 'block_dash', 'images', 0) as $file) {
            if ($file->is_valid_image()) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Get background image URL.
     *
     * @return moodle_url|null
     */
    public function get_background_image_url() {
        if ($backgroundimage = $this->get_background_image()) {
            return moodle_url::make_pluginfile_url(
                $backgroundimage->get_contextid(),
                $backgroundimage->get_component(),
                $backgroundimage->get_filearea(),
                $backgroundimage->get_itemid(),
                $backgroundimage->get_filepath(),
                $backgroundimage->get_filename()
            );
        }

        return null;
    }

    /**
     * Set dash sorting.
     *
     * @param string $fieldname
     * @param string|null $direction
     * @throws coding_exception
     */
    public function set_sort($fieldname, $direction = null) {
        global $USER;

        $key = $USER->id . '_' . $this->instance->id;

        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'sort');

        if (!$cache->has($key)) {
            $sorting = [];
        } else {
            $sorting = $cache->get($key);
        }

        if (isset($sorting[$fieldname]) && !$direction) {
            if ($sorting[$fieldname] == 'asc') {
                $sorting[$fieldname] = 'desc';
            } else {
                $sorting[$fieldname] = 'asc';
            }
        } else {
            if ($direction) {
                $sorting[$fieldname] = $direction;
            } else {
                $sorting[$fieldname] = 'asc';
            }
        }

        $cache->set($key, [$fieldname => $sorting[$fieldname]]);
    }

    /**
     * Include the preference option to the blocks controls before genreate the output.
     *
     * @param \core_renderer $output
     * @return \block_contents
     */
    public function get_content_for_output($output) {

        $bc = parent::get_content_for_output($output);

        $datasource = $this->config->data_source_idnumber ?? '';

        if ($datasource) {
            $info = \block_dash\local\data_source\data_source_factory::get_data_source_info($datasource);
            $type = $info['type'] ?? 'datasource';

            switch($type) {
                case 'datasource':
                    $hascapability = has_capability('block/dash:managedatasource', $this->context);
                    break;
                case 'widget':
                    $hascapability = has_capability('block/dash:managewidget', $this->context);
                    break;
                case 'custom':
                    $hascapability = $datasource::has_capbility($this->context);
                    break;
            }

        } else {
            $hascapability = true;
        }

        if (!isset($bc->controls) || !$hascapability) {
            return $bc;
        }
        // Move icon.
        $str = new lang_string('preferences', 'core');
        $icon = $output->render(new pix_icon('i/dashboard', $str, 'moodle', ['class' => 'iconsmall', 'title' => '']));

        $newcontrols = [];
        foreach ($bc->controls as $controls) {
            $newcontrols[] = $controls;
            if ($controls->text instanceof lang_string && $controls->text->get_identifier() == 'configureblock') {
                $newcontrols[] = html_writer::link('javascript:void(0);', $icon . $str, ['class' => 'dash-edit-preferences']);
            }
        }
        $bc->controls = $newcontrols;
        return $bc;
    }

}
