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
 * Form for editing block preferences.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source\form;

use block_dash\local\configuration\configuration;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing block preferences.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences_form extends \moodleform {

    const TAB_GENERAL = 'tabgeneral';
    const TAB_FIELDS = 'tabfields';
    const TAB_FILTERS = 'tabfilters';

    const TABS = [
        self::TAB_GENERAL,
        self::TAB_FIELDS,
        self::TAB_FILTERS
    ];

    /**
     * Define form fields.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function definition() {
        $block = $this->_customdata['block'];

        if (!isset($this->_customdata['tab'])) {
            $this->_customdata['tab'] = self::TABS[0];
        }

        $configuration = configuration::create_from_instance($block);
        if ($configuration->is_fully_configured()) {
            $configuration->get_data_source()->build_preferences_form($this, $this->_form);
        }

        $mform = $this->_form;

        //when two elements we need a group
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), ['data-action' => 'cancel']);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Get current tab of preferences form.
     */
    public function get_tab(): string {
        return $this->_customdata['tab'];
    }
}
