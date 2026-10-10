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
 * Modal form importing a Dash block file into a block.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

use block_dash\local\porter;
use context;
use core_form\dynamic_form;
use moodle_exception;
use moodle_url;

/**
 * Modal form importing a Dash block file into a block.
 *
 * @package    block_dash
 */
class import_block extends dynamic_form {
    /**
     * The block being imported into.
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
     * Context of the block.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_block::instance($this->optional_param('blockid', 0, PARAM_INT));
    }

    /**
     * Only users who may edit the block can import into it.
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!porter::can_edit_block($this->get_block())) {
            throw new moodle_exception('nopermissions', 'error', '', get_string('importblock', 'block_dash'));
        }
    }

    /**
     * Page the block is shown on.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return $this->get_context_for_dynamic_submission()->get_url();
    }

    /**
     * Load the block id into the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data(['blockid' => $this->optional_param('blockid', 0, PARAM_INT)]);
    }

    /**
     * Form definition.
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('html', \html_writer::tag('p', get_string('importblockdesc', 'block_dash')));

        if (!empty($this->get_block()->config->data_source_idnumber)) {
            $mform->addElement('html', $OUTPUT->notification(get_string('importblockreplace', 'block_dash'), 'warning', false));
        }

        $mform->addElement('filepicker', 'importfile', get_string('importblockfile', 'block_dash'), null, [
            'accepted_types' => ['.json'],
        ]);
        $mform->addRule('importfile', null, 'required');
    }

    /**
     * Check the uploaded file before anything is changed.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($errors['importfile'])) {
            try {
                porter::validate(porter::decode($this->get_uploaded_content((int) $data['importfile'])), $this->get_block());
            } catch (moodle_exception $e) {
                $errors['importfile'] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Import the file and queue the notices shown after the page reloads.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();

        $warnings = porter::import($this->get_block(), porter::decode($this->get_uploaded_content((int) $data->importfile)));

        \core\notification::success(get_string('importdone', 'block_dash'));
        foreach ($warnings as $warning) {
            \core\notification::warning($warning);
        }

        return ['result' => true];
    }

    /**
     * Content of the file in the file picker's draft area.
     *
     * @param int $draftitemid
     * @return string
     */
    protected function get_uploaded_content(int $draftitemid): string {
        global $USER;

        $files = get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftitemid,
            'id DESC',
            false
        );
        $file = reset($files);

        return $file ? $file->get_content() : '';
    }
}
