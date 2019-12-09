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

use block_dash\data_source\data_source_factory;

/**
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dash_edit_form extends block_edit_form {

    /**
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function specific_definition($mform)
    {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_dash'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_hide_when_empty', get_string('hidewhenempty', 'block_dash'), [
            0 => get_string('no'),
            1 => get_string('yes')
        ]);
        $mform->setType('config_hide_When_empty', PARAM_BOOL);

        $mform->addElement('text', 'config_css_class', get_string('cssclass', 'block_dash'));
        $mform->setType('config_css_class', PARAM_TEXT);

        $mform->addElement('header', 'choose_data_source', get_string('choosedatasource', 'block_dash'));

        $mform->addElement('select', 'config_data_source_idnumber', get_string('choosedatasource', 'block_dash'),
            data_source_factory::get_data_source_form_options());
        $mform->setType('config_data_source_idnumber', PARAM_TEXT);
        $mform->addRule('config_data_source_idnumber', get_string('required'), 'required');

        $mform->addElement('header', 'extracontent', get_string('extracontent', 'block_dash'));

        $mform->addElement('editor', 'config_header_content', get_string('headercontent', 'block_dash'));
        $mform->setType('config_header_content', PARAM_RAW);
        $mform->addHelpButton('config_header_content', 'headercontent', 'block_dash');

        $mform->addElement('editor', 'config_footer_content', get_string('footercontent', 'block_dash'));
        $mform->setType('config_footer_content', PARAM_RAW);
        $mform->addHelpButton('config_footer_content', 'footercontent', 'block_dash');
    }
}
