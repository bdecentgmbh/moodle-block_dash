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
 * Unit tests for forced conditions and the filter collection augment callback.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_grid\filter\condition;
use block_dash\local\data_grid\filter\forced_condition_interface;
use block_dash\local\data_source\users_data_source;

/**
 * Unit tests for forced conditions surviving preference based filter removal.
 *
 * @group block_dash
 * @group bdecent
 * @group forced_condition_test
 */
final class forced_condition_test extends \advanced_testcase {

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        require_once($CFG->dirroot . '/blocks/dash/lib.php');
    }

    /**
     * Build a forced condition stub.
     *
     * @return condition
     */
    private function new_forced_condition(): condition {
        return new class('testforced', 'u.id') extends condition implements forced_condition_interface {
            /**
             * Static WHERE fragment for testing.
             *
             * @return array
             */
            public function get_sql_and_params() {
                return ['u.id > :testforcedparam', ['testforcedparam' => 0]];
            }
        };
    }

    /**
     * A forced condition survives before_data() when no filter preferences exist.
     */
    public function test_forced_condition_survives_without_preferences(): void {
        $source = new users_data_source(\context_system::instance());

        $forced = $this->new_forced_condition();
        $forced->init();
        $source->get_filter_collection()->add_filter($forced);

        $countbefore = count($source->get_filter_collection()->get_filters());
        $this->assertGreaterThan(1, $countbefore);

        $source->before_data();

        // Everything except the forced condition was removed.
        $this->assertTrue($source->get_filter_collection()->has_filter('testforced'));
        $this->assertCount(1, $source->get_filter_collection()->get_filters());
    }

    /**
     * A forced condition survives before_data() when preferences enable other filters.
     */
    public function test_forced_condition_survives_with_preferences(): void {
        $source = new users_data_source(\context_system::instance());
        $source->set_preferences(['filters' => ['unrelated' => ['enabled' => 1]]]);

        $forced = $this->new_forced_condition();
        $forced->init();
        $source->get_filter_collection()->add_filter($forced);

        $source->before_data();

        $this->assertTrue($source->get_filter_collection()->has_filter('testforced'));
    }

    /**
     * A forced condition contributes its WHERE fragment to the final query.
     */
    public function test_forced_condition_contributes_sql(): void {
        $source = new users_data_source(\context_system::instance());

        $forced = $this->new_forced_condition();
        $forced->init();
        $source->get_filter_collection()->add_filter($forced);

        $source->before_data();

        [$sql, $params] = $source->get_query()->get_sql_and_params();

        $this->assertStringContainsString('u.id > :testforcedparam', $sql);
        $this->assertArrayHasKey('testforcedparam', $params);
    }

    /**
     * The augment callback lookup runs without errors while building collections.
     */
    public function test_augment_callback_is_invoked_safely(): void {
        $source = new users_data_source(\context_system::instance());

        // Building the collection triggers get_plugins_with_function('dash_augment_filter_collection').
        $collection = $source->get_filter_collection();

        $this->assertNotNull($collection);
        [$sql, $params] = $source->get_query()->get_sql_and_params();
        $this->assertStringContainsString('{user}', $sql);
    }
}
