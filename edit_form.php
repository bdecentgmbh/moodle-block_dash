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
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\data_source\data_source_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dash_edit_form extends block_edit_form {

    /**
     * Add form fields.
     *
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     */
    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_dash'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_width', get_string('blockwidth', 'block_dash'), [
            100 => '100',
            50 => '1/2',
            33 => '1/3',
            66 => '2/3',
            25 => '1/4',
            20 => '1/5',
            16 => '1/6'
        ]);
        $mform->setType('config_width', PARAM_INT);
        $mform->setDefault('config_width', 100);

        $mform->addElement('select', 'config_hide_when_empty', get_string('hidewhenempty', 'block_dash'), [
            0 => get_string('no'),
            1 => get_string('yes')
        ]);
        $mform->setType('config_hide_When_empty', PARAM_BOOL);

        $mform->addElement('text', 'config_css_class', get_string('cssclass', 'block_dash'));
        $mform->setType('config_css_class', PARAM_TEXT);

        if (!isset($this->block->config->data_source_idnumber)) {
            $mform->addElement('select', 'config_data_source_idnumber', get_string('choosedatasource', 'block_dash'),
                data_source_factory::get_data_source_form_options());
            $mform->setType('config_data_source_idnumber', PARAM_TEXT);
            $mform->addRule('config_data_source_idnumber', get_string('required'), 'required');
        } else {
            if ($ds = data_source_factory::build_data_source($this->block->config->data_source_idnumber,
                $this->block->context)) {
                $label = $ds->get_name();
            } else {
                $label = get_string('datasourcemissing', 'block_dash');
            }
            $mform->addElement('static', 'data_source_label', get_string('datasource', 'block_dash'), $label);
        }

        $mform->addElement('header', 'extracontent', get_string('extracontent', 'block_dash'));

        $mform->addElement('editor', 'config_header_content', get_string('headercontent', 'block_dash'));
        $mform->setType('config_header_content', PARAM_RAW);
        $mform->addHelpButton('config_header_content', 'headercontent', 'block_dash');

        $mform->addElement('editor', 'config_footer_content', get_string('footercontent', 'block_dash'));
        $mform->setType('config_footer_content', PARAM_RAW);
        $mform->addHelpButton('config_footer_content', 'footercontent', 'block_dash');

        $mform->addElement('header', 'apperance', get_string('appearance'));

        $mform->addElement('filemanager', 'config_backgroundimage', get_string('backgroundimage', 'block_dash'), null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image'], 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL]);
        $mform->addHelpButton('config_backgroundimage', 'backgroundimage', 'block_dash');

        $mform->addElement('text', 'config_backgroundgradient', get_string('backgroundgradient', 'block_dash'), ['placeholder' => 'linear-gradient(#e66465, #9198e5)']);
        $mform->setType('config_backgroundgradient', PARAM_TEXT);
        $mform->addHelpButton('config_backgroundgradient', 'backgroundgradient', 'block_dash');

        $mform->addElement('text', 'config_css[color]', get_string('fontcolor', 'block_dash'));
        $mform->setType('config_css[color]', PARAM_TEXT);
        $mform->addHelpButton('config_css[color]', 'fontcolor', 'block_dash');
    }
}
