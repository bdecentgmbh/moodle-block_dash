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
 * Modal form adding an export file to the gallery.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

use block_dash\gallery\gallery;
use block_dash\local\porter;
use context;
use core_form\dynamic_form;
use moodle_url;

/**
 * Modal form adding an export file to the gallery.
 *
 * @package    block_dash
 */
class gallery_upload extends dynamic_form {
    use gallery_form_trait;
    use uploaded_file_trait;

    /**
     * The gallery belongs to the site.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Only gallery managers upload items.
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
     * Nothing to load.
     */
    public function set_data_for_dynamic_submission(): void {
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'importfile', get_string('importblockfile', 'block_dash'), null, [
            'accepted_types' => ['.json'],
        ]);
        $mform->addRule('importfile', null, 'required');

        $this->add_details_fields($mform);
        $this->add_embed_fields($mform);
    }

    /**
     * Check the file and the shortname.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        try {
            $export = porter::decode($this->get_uploaded_content((int) $data['importfile']));
            if (gallery::get_files_size($export) > gallery::MAX_FILES_SIZE) {
                $errors['importfile'] = get_string('galleryerrortoolarge', 'block_dash', display_size(gallery::MAX_FILES_SIZE));
            }
        } catch (\moodle_exception $e) {
            $errors['importfile'] = $e->getMessage();
        }

        if ($error = $this->validate_shortname((string) $data['shortname'])) {
            $errors['shortname'] = $error;
        }

        return $errors;
    }

    /**
     * Add the file to the gallery.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $export = porter::decode($this->get_uploaded_content((int) $data->importfile));

        $item = gallery::create_site_item(
            $data->shortname,
            $data->name,
            (string) ($data->description ?? ''),
            $export,
            $this->get_embed_settings($data)
        );

        \core\notification::success(get_string('gallerysaved', 'block_dash', $item->get_name()));
        return ['result' => true, 'shortname' => $item->get_shortname()];
    }
}
