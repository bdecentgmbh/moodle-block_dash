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

namespace block_dash\data_grid\form;

use block_dash\data_grid\data_grid_interface;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * @package block_dash
 */
class data_grid_filter_form extends \moodleform
{
    /**
     * @var data_grid_interface
     */
    private $data_grid;

    /**
     * @param data_grid_interface $data_grid
     */
    public function set_data_grid(data_grid_interface $data_grid)
    {
        $this->data_grid = $data_grid;
    }

    public function definition()
    {
        $mform = $this->_form;

        if (!$this->data_grid) {
            throw new \coding_exception('Data grid not set on filter form.');
        }

        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);

        $this->data_grid->get_filter_collection()->create_form_elements($mform);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('filter', 'block_dash'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', 'Clear filters');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files)
    {
        return [];
    }
}
