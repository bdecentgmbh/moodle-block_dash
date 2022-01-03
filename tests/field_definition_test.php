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
 * Unit test for field definitions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\test;

use \block_dash\local\data_grid\field\attribute\identifier_attribute;
use \block_dash\local\data_grid\field\field_definition_factory;
use \block_dash\local\data_grid\field\field_definition_interface;
use \block_dash\local\data_grid\field\sql_field_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test for field definitions.
 *
 * @group block_dash
 * @group bdecent
 * @group field_definition_test
 */
class field_definition_test extends \advanced_testcase {

    /**
     * @var field_definition_interface
     */
    private $fielddefinition;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        $this->fielddefinition = new sql_field_definition('u.id', 'u_id', 'User ID');
    }

    public function test_field_definition_options() {
        global $CFG;

        require_once("$CFG->dirroot/blocks/dash/lib.php");

        $this->assertEmpty($this->fielddefinition->get_options(), 'Ensure default options is empty.');

        $this->fielddefinition->set_option('foo', 123);
        $this->fielddefinition->set_options(['bar' => 'testing']);

        $this->assertCount(2, $this->fielddefinition->get_options(), 'Ensure correct number of options returned.');
        $this->assertEquals(123, $this->fielddefinition->get_option('foo'), 'Ensure option return value is correct.');
        $this->assertEquals('testing', $this->fielddefinition->get_option('bar'),
            'Ensure option return value is correct.');
    }

    public function test_field_definition_attributes() {
        $attribute = new identifier_attribute();

        $this->assertFalse($this->fielddefinition->has_attribute(get_class($attribute)),
            'Ensure field does not have attribute yet.');

        $this->fielddefinition->add_attribute($attribute);
        $this->fielddefinition->set_visibility(field_definition_interface::VISIBILITY_VISIBLE);

        $this->assertTrue($this->fielddefinition->has_attribute(get_class($attribute)),
            'Ensure field does have attribute.');

        $this->assertCount(1, $this->fielddefinition->get_attributes(), 'Ensure attributes are returned.');

        $this->fielddefinition->remove_attribute($attribute);

        $this->assertCount(0, $this->fielddefinition->get_attributes(), 'Ensure no attributes are returned.');
    }
}
