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
 * Custom behat step definitions.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
Behat\Mink\Exception\DriverException as DriverException,
Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Custom behat step definitions.
 */
class behat_block_dash extends behat_base {

    /**
     * Turns block editing mode on.
     * @Given I switch block editing mode on
     * @Given I turn block editing mode on
     */
    public function i_turn_block_editing_mode_on() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_forms::i_set_the_field_to', [get_string('editmode'), 1]);
            if (!$this->running_javascript()) {
                $this->execute('behat_general::i_click_on', [
                    get_string('setmode', 'core'),
                    'button',
                ]);
            }
        } else {
            $this->execute('behat_general::i_click_on', ['Blocks editing on', 'button']);
        }
    }

    /**
     * I follow badge recipients
     * @Given I follow badge recipients
     */
    public function i_follow_badge_recipients() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_forms::i_select_from_the_singleselect', ["Recipients (0)", "jump"]);
        } else {
            $this->execute('behat_general::i_click_on', ["Recipients (0)", "link"]);
        }
    }

    /**
     * I follow dashboard
     * @Given I follow dashboard
     */
    public function i_follow_dashboard() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_general::i_click_on', ["Dashboard", 'link']);
        } else {
            $this->execute('behat_navigation::i_follow_in_the_user_menu', ["Dashboard"]);
        }
    }
}
