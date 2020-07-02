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
 * Unit test for field definition factory.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\test;

use block_dash\local\data_grid\field\field_definition_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test for field definition factory.
 *
 * @group block_dash
 * @group bdecent
 * @group field_definition_factory_test
 */
class field_definition_factory_test extends \advanced_testcase {

    public function test_field_definition() {
        global $CFG;

        require_once("$CFG->dirroot/blocks/dash/lib.php");

        $this->assertNotEmpty(field_definition_factory::get_all_field_definitions(),
            'Ensure field definitions are retrieved.');
        $this->assertCount(count(block_dash_register_field_definitions()), field_definition_factory::get_all_field_definitions(),
            'Ensure expected number of test field definitions are retrieved.');

        $this->assertNotEmpty(field_definition_factory::get_field_definition('u_id'),
            'Ensure field definition was retrieved');
        $this->assertEquals('User ID', field_definition_factory::get_field_definition('u_id')->get_title(),
            'Ensure correct field definition was retrieved.');
        $this->assertCount(2, field_definition_factory::get_field_definitions(['u_id', 'u_firstname']),
            'Ensure multiple field definitions were retrieved.');
        $this->assertNull(field_definition_factory::get_field_definition('missing'),
            'Ensure missing field is returned as null');
    }
}

