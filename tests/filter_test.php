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
 * Unit test for search indexing.
 *
 * @package block_html
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\test;

use block_dash\data_grid\filter\choice_filter;
use block_dash\data_grid\filter\filter;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\filter\filter_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test for filtering.
 *
 * @group block_dash
 * @group bdecent
 * @group filter_test
 */
class filter_test extends \advanced_testcase
{
    /**
     * @var filter_collection_interface
     */
    private $filter_collection;

    /**
     * @var \stdClass
     */
    private $user;

    /**
     * This method is called before each test.
     */
    protected function setUp()
    {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $USER;

        $this->user = $USER;

        $this->filter_collection = new filter_collection('testing', \context_system::instance());
        $this->filter_collection->add_filter(new filter('filter1', 'table.fieldname'));
        $this->filter_collection->init();
    }

    public function test_general_stuff()
    {
        $this->assertEquals('testing', $this->filter_collection->get_unique_identifier());
        $this->assertCount(1, $this->filter_collection->get_filters());
        $this->assertTrue($this->filter_collection->has_filter('filter1'));
        $this->assertFalse($this->filter_collection->has_filter('missing'));
    }

    public function test_remove_filter()
    {
        $filter = $this->filter_collection->get_filter('filter1');
        $this->filter_collection->remove_filter($filter);

        $this->assertFalse($this->filter_collection->has_filters());
        $this->assertFalse($this->filter_collection->remove_filter($filter), 'Ensure false is returend when filter was already removed.');
    }

    public function test_applying_filter()
    {
        $this->assertCount(0, $this->filter_collection->get_applied_filters());
        $this->assertCount(0, $this->filter_collection->get_filters_with_values());

        $this->assertTrue($this->filter_collection->apply_filter('filter1', 123));
        $this->assertFalse($this->filter_collection->apply_filter('filter1', ''));
        $this->assertFalse($this->filter_collection->apply_filter('missing', 123));

        $this->assertCount(1, $this->filter_collection->get_applied_filters());

        $this->assertCount(1, $this->filter_collection->get_filters_with_values());
    }

    public function test_filter_sql_and_params_collection()
    {
        $this->assertTrue($this->filter_collection->apply_filter('filter1', 123));

        list($sql, $params) = $this->filter_collection->get_sql_and_params();

        $this->assertEquals(' AND table.fieldname = :param1', $sql, 'Ensure SQL is generated.');
        $this->assertEquals($params, ['param1' => 123], 'Ensure params are returned.');
    }

    public function test_required_filters()
    {
        $this->assertFalse($this->filter_collection->has_required_filters());
        $this->assertCount(0, $this->filter_collection->get_required_filters());

        $filter = new filter('filter2', 'table.fieldname2');
        $filter->set_required(filter_interface::REQUIRED);

        $this->filter_collection->add_filter($filter);
        $this->assertTrue($this->filter_collection->has_required_filters());
        $this->assertCount(1, $this->filter_collection->get_required_filters());
    }

    public function test_caching()
    {
        $this->filter_collection->apply_filter('filter1', 234);
        $this->filter_collection->cache($this->user);

        $this->assertEquals(234, $this->filter_collection->get_cache($this->user)['filter1']);

        $this->filter_collection->delete_cache($this->user);

        $this->assertEmpty($this->filter_collection->get_cache($this->user));
    }
}

