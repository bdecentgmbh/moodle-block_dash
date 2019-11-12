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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\template\form;

use block_dash\block_builder;
use block_dash\template\custom_template;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

class template_general_form extends \moodleform
{
    /**
     * Form definition. Abstract method - always override!
     */
    protected function definition()
    {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('templatename', 'block_dash'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required');

        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', get_string('required'), 'required');

        $mform->addElement('textarea', 'query_template', get_string('querytemplate', 'block_dash'));
        $mform->setType('query_template', PARAM_RAW);
        $mform->addRule('query_template', get_string('required'), 'required');

        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'layout_type', '', get_string('standard'),
            custom_template::LAYOUT_TYPE_PATH);
        $radioarray[] = $mform->createElement('radio', 'layout_type', '', get_string('custom', 'block_dash'),
            custom_template::LAYOUT_TYPE_RAW_MUSTACHE);
        $mform->addGroup($radioarray, 'layout_type', get_string('layouttype', 'block_dash'), [' '], false);
        $mform->setDefault('layout_type', 1);
        $mform->addGroupRule('layout_type', get_string('required'), 'required');

        $mform->addElement('select', 'layout_path', get_string('layout', 'block_dash'), [
            'block_dash/layout_grid' => get_string('layoutgrid', 'block_dash')
        ]);
        $mform->setType('layout_path', PARAM_TEXT);
        $mform->disabledIf('layout_path', 'layout_type', 'noeq', 1);
        $mform->hideIf('layout_path', 'layout_type', 'noeq', 1);

        $mform->addElement('textarea', 'layout_mustache', get_string('mustachetemplate', 'block_dash'));
        $mform->setType('layout_mustache', PARAM_RAW);
        $mform->disabledIf('layout_mustache', 'layout_type', 'noeq', 2);
        $mform->hideIf('layout_mustache', 'layout_type', 'noeq', 2);
        $mform->setDefault('layout_mustache',
            file_get_contents("$CFG->dirroot/blocks/dash/templates/layout_example.mustache"));

        $this->add_action_buttons();
    }

}
