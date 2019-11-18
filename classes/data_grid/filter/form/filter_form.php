<?php
// This file is part of The Bootstrap Moodle theme
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

namespace block_dash\data_grid\filter\form;

use block_dash\data_grid\filter\filter_collection_interface;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Generates form for filter collection.
 */
class filter_form extends \moodleform
{
    /**
     * @var filter_collection_interface
     */
    private $filter_collection;

    public function __construct(filter_collection_interface $filter_collection, $action = null, $customdata = null,
                                $method = 'post', $target = '', $attributes = null, $editable = true,
                                $ajaxformdata = null)
    {
        $this->filter_collection = $filter_collection;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);

        foreach ($this->filter_collection->get_filters() as $filter) {
            $filter->create_form_element($mform, $this->filter_collection);
        }

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('filter'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', 'Clear filters');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files)
    {
        return [];
    }
}