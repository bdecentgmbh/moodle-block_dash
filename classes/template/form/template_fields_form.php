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

class template_fields_form extends \moodleform
{
    /**
     * Form definition. Abstract method - always override!
     */
    protected function definition()
    {
        global $OUTPUT;

        $mform = $this->_form;

        $template = $this->_customdata['template'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', $OUTPUT->render_from_template('block_dash/field_edits', [
            'field_definitions' => $template->get_available_field_definitions(),
            'all_field_definitions' => block_builder::get_all_field_definitions()
        ]));

        $this->add_action_buttons();
    }

}
