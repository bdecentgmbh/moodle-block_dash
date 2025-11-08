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

namespace block_dash\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');


/**
 * Dash features form to configure the data source or widget.
 */
class block_dash_featuresform extends \moodleform {
    /**
     * Defined the form fields for the datasource selector list.
     *
     * @return void
     */
    public function definition() {
        // @codingStandardsIgnoreStart
        global $PAGE;
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        // @codingStandardsIgnoreEnd

        $mform = $this->_form;

        $mform->updateAttributes(['class' => 'form-inline']);
        $mform->updateAttributes(['id' => 'dash-configuration']);

        $block = $this->_customdata['block'] ?? '';
        // @codingStandardsIgnoreStart
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        \block_dash_edit_form::dash_features_list($mform, $block, $PAGE);
        // @codingStandardsIgnoreEnd
    }
}
