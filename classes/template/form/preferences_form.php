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
 * Form for editing InfoDash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\template\form;

use block_dash\template\template_factory;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing InfoDash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences_form extends \moodleform
{
    protected function definition()
    {
        $block = $this->_customdata['block'];

        $parentcontext = \context::instance_by_id($block->instance->parentcontextid);

        if (isset($block->config->template_idnumber) &&
            $template = template_factory::get_template($block->config->template_idnumber, $parentcontext)) {
            $template->build_preferences_form($this, $this->_form);
        }
    }
}
