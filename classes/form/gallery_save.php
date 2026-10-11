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
 * Modal form saving a block's setup to the gallery.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

use block_dash\gallery\gallery;
use block_dash\local\data_source\data_source_factory;
use block_dash\local\porter;
use context;
use core_form\dynamic_form;
use moodle_url;

/**
 * Modal form saving a block's setup to the gallery.
 *
 * Saves the block as a new site item, or over an existing one: that is how a site item's setup
 * is changed.
 *
 * @package    block_dash
 */
class gallery_save extends dynamic_form {
    use gallery_form_trait;

    /** Save as a new item. */
    const MODE_NEW = 'new';

    /** Replace an existing item's setup. */
    const MODE_REPLACE = 'replace';

    /**
     * The block being saved.
     *
     * @return \block_base
     */
    protected function get_block(): \block_base {
        global $DB;

        $blockid = $this->optional_param('blockid', 0, PARAM_INT);
        $instance = $DB->get_record('block_instances', ['id' => $blockid, 'blockname' => 'dash'], '*', MUST_EXIST);
        return block_instance('dash', $instance);
    }

    /**
     * The gallery belongs to the site.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Only gallery managers save blocks, and only blocks that are set up.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/dash:managegallery', \context_system::instance());
        if (empty($this->get_block()->config->data_source_idnumber)) {
            throw new \moodle_exception('galleryerrornotsetup', 'block_dash');
        }
    }

    /**
     * Gallery page.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/blocks/dash/gallery.php');
    }

    /**
     * Default name: the block's title.
     */
    public function set_data_for_dynamic_submission(): void {
        // An untitled block suggests its data source's name, e.g. "Hero".
        $config = $this->get_block()->config;
        $title = trim((string) ($config->title ?? ''));
        if ($title === '') {
            $info = data_source_factory::get_data_source_info($config->data_source_idnumber ?? '');
            $title = trim((string) ($info['name'] ?? ''));
        }
        $this->set_data([
            'blockid' => $this->optional_param('blockid', 0, PARAM_INT),
            'mode' => self::MODE_NEW,
            'name' => $title,
            'shortname' => trim(preg_replace('/[^a-z0-9]+/', '-', \core_text::strtolower($title)), '-'),
        ]);
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $siteitems = [];
        foreach (gallery::get_items() as $shortname => $item) {
            if (!$item->is_shipped()) {
                $siteitems[$shortname] = $item->get_name() . ' (' . $shortname . ')';
            }
        }

        if ($siteitems) {
            $mform->addElement(
                'radio',
                'mode',
                get_string('gallerysavemode', 'block_dash'),
                get_string('gallerysavenew', 'block_dash'),
                self::MODE_NEW
            );
            $mform->addElement('radio', 'mode', '', get_string('gallerysavereplace', 'block_dash'), self::MODE_REPLACE);
            $mform->addElement('select', 'replace', get_string('galleryitem', 'block_dash'), $siteitems);
            $mform->hideIf('replace', 'mode', 'neq', self::MODE_REPLACE);
        } else {
            $mform->addElement('hidden', 'mode', self::MODE_NEW);
        }
        $mform->setType('mode', PARAM_ALPHA);

        $this->add_details_fields($mform, false);
        $this->add_embed_fields($mform);

        foreach (['name', 'shortname', 'description', 'embeddable', 'embedroles', 'embedrolecontext'] as $field) {
            if ($siteitems) {
                $mform->hideIf($field, 'mode', 'eq', self::MODE_REPLACE);
            }
        }
    }

    /**
     * Check the shortname and the size of the images.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (($data['mode'] ?? self::MODE_NEW) === self::MODE_NEW) {
            if (trim((string) ($data['name'] ?? '')) === '') {
                $errors['name'] = get_string('required');
            }
            if ($error = $this->validate_shortname((string) ($data['shortname'] ?? ''))) {
                $errors['shortname'] = $error;
            }
        } else if (!gallery::get_item((string) ($data['replace'] ?? ''))) {
            $errors['replace'] = get_string('required');
        }

        if (gallery::get_files_size(porter::export($this->get_block())) > gallery::MAX_FILES_SIZE) {
            $errors['name'] = get_string('galleryerrortoolarge', 'block_dash', display_size(gallery::MAX_FILES_SIZE));
        }

        return $errors;
    }

    /**
     * Save the block to the gallery.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $export = porter::export($this->get_block());

        if ($data->mode === self::MODE_REPLACE) {
            $item = gallery::replace_data(gallery::get_item($data->replace), $export);
        } else {
            $item = gallery::create_site_item(
                $data->shortname,
                $data->name,
                (string) ($data->description ?? ''),
                $export,
                $this->get_embed_settings($data)
            );
        }

        \core\notification::success(get_string('gallerysaved', 'block_dash', $item->get_name()));
        return ['result' => true, 'shortname' => $item->get_shortname()];
    }
}
