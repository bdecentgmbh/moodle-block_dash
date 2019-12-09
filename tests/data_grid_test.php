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

namespace block_dash\test;

use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_factory;
use block_dash\data_grid\testing_data_grid;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test for field definition factory.
 *
 * @group block_dash
 * @group bdecent
 * @group data_grid_test
 */
class data_grid_test extends \advanced_testcase
{
    /**
     * @var data_grid_interface
     */
    private $data_grid;

    /**
     * This method is called before each test.
     */
    protected function setUp()
    {
        $this->data_grid = new testing_data_grid(\context_system::instance());
        $this->data_grid->set_field_definitions(field_definition_factory::get_field_definitions([
            'u_id',
            'u_firstname',
            'u_lastname',
            'u_firstaccess',
            'u_picture'
        ]));
    }

    public function test_getting_data()
    {
        $data = $this->data_grid->get_data();

        $users = $data['users'];

        $this->assertCount(2, $users);
        $this->assertEquals(1, $users[0]['u_id']);
        $this->assertEquals('Guest', $users[0]['u_firstname']);
        $this->assertEquals('10/12/19', $users[0]['u_firstaccess']);
        $this->assertContains('<a href=', $users[0]['u_picture']);
        $this->assertEquals(2, $users[1]['u_id']);
        $this->assertEquals('Admin', $users[1]['u_firstname']);
        $this->assertEquals('10/12/19', $users[1]['u_firstaccess']);
        $this->assertContains('<a href=', $users[1]['u_picture']);
    }
}