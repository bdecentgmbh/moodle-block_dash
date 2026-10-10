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
 * Modal form changing a gallery item's details and embed settings.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

use block_dash\gallery\gallery;
use block_dash\gallery\item;
use context;
use core_form\dynamic_form;
use moodle_url;

/**
 * Modal form changing a gallery item's details and embed settings.
 *
 * Site items have a name, shortname, description and preview image. Shipped items take theirs
 * from the plugin, so only the embed settings can change.
 *
 * @package    block_dash
 */
class gallery_details extends dynamic_form {
    use gallery_form_trait;

    /** Options of the preview image file manager. */
    const PREVIEW_OPTIONS = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']];

    /**
     * The item being edited.
     *
     * @return item
     */
    protected function get_item(): item {
        $item = gallery::get_item($this->optional_param('item', '', PARAM_RAW_TRIMMED));
        if (!$item) {
            throw new \moodle_exception('invalidrecord', 'error', '', 'block_dash_gallery');
        }
        return $item;
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
     * Only gallery managers change items.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/dash:managegallery', \context_system::instance());
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
     * Load the item's details.
     */
    public function set_data_for_dynamic_submission(): void {
        $item = $this->get_item();
        $data = [
            'item' => $item->get_shortname(),
            'embeddable' => (int) $item->is_embeddable(),
            'embedroles' => $item->get_embed_roles(),
            'embedrolecontext' => $item->get_embed_role_context(),
        ];

        if (!$item->is_shipped()) {
            $record = $item->get_record();
            $draftitemid = file_get_submitted_draft_itemid('preview');
            file_prepare_draft_area(
                $draftitemid,
                \context_system::instance()->id,
                'block_dash',
                'gallerypreview',
                $record->get('id'),
                self::PREVIEW_OPTIONS
            );
            $data += [
                'name' => $record->get('name'),
                'shortname' => $record->get('shortname'),
                'description' => $record->get('description'),
                'preview' => $draftitemid,
            ];
        }

        $this->set_data($data);
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $item = $this->get_item();

        $mform->addElement('hidden', 'item');
        $mform->setType('item', PARAM_RAW_TRIMMED);

        if ($item->is_shipped()) {
            $mform->addElement('static', 'itemname', get_string('galleryname', 'block_dash'), s($item->get_name()));
            $mform->addElement('static', 'itemsource', get_string('gallerysource', 'block_dash'), s($item->get_source_name()));
        } else {
            $this->add_details_fields($mform);
            if ($item->is_embeddable()) {
                $mform->addElement('static', 'renamewarning', '', get_string('galleryrenamewarning', 'block_dash'));
            }
            $mform->addElement(
                'filemanager',
                'preview',
                get_string('gallerypreviewimage', 'block_dash'),
                null,
                self::PREVIEW_OPTIONS
            );
        }

        $this->add_embed_fields($mform);
    }

    /**
     * Check a changed shortname.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $item = $this->get_item();
        if (!$item->is_shipped() && $data['shortname'] !== $item->get_shortname()) {
            if ($error = $this->validate_shortname((string) $data['shortname'], (int) $item->get_record()->get('id'))) {
                $errors['shortname'] = $error;
            }
        }

        return $errors;
    }

    /**
     * Save the details.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $item = $this->get_item();

        $settings = $this->get_embed_settings($data);
        if (!$item->is_shipped()) {
            $settings += [
                'shortname' => $data->shortname,
                'name' => $data->name,
                'description' => (string) ($data->description ?? ''),
            ];
            file_save_draft_area_files(
                $data->preview,
                \context_system::instance()->id,
                'block_dash',
                'gallerypreview',
                $item->get_record()->get('id'),
                self::PREVIEW_OPTIONS
            );
        }

        $item = gallery::update_settings($item, $settings);
        return ['result' => true, 'shortname' => $item->get_shortname()];
    }
}
