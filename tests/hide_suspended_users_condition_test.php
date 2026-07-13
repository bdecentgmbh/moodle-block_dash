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
 * Unit test for the hide suspended users condition.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_grid\filter\hide_suspended_users_condition;
use block_dash\local\data_source\users_data_source;
use block_dash\local\layout\grid_layout;

/**
 * Unit test for the hide suspended users condition.
 *
 * @group block_dash
 * @group bdecent
 * @group hide_suspended_users_condition_test
 * @covers \block_dash\local\data_grid\filter\hide_suspended_users_condition
 */
final class hide_suspended_users_condition_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        // Building the users data source filter collection calls block_dash_has_pro(). Outside of a
        // block request nothing has pulled in the plugin library yet.
        require_once($CFG->dirroot . '/blocks/dash/lib.php');
    }

    /**
     * Build a users data source with the given filter preferences.
     *
     * @param array|null $filters Value for the 'filters' preference, or null to omit it entirely.
     * @return users_data_source
     */
    protected function create_data_source(?array $filters = null): users_data_source {
        $datasource = new users_data_source(\context_system::instance());
        $datasource->set_layout(new grid_layout($datasource));

        $preferences = [
            'available_fields' => [
                'u_id' => ['visible' => 1],
                'u_username' => ['visible' => 1],
            ],
            'perpage' => 100,
        ];
        if (!is_null($filters)) {
            $preferences['filters'] = $filters;
        }
        $datasource->set_preferences($preferences);

        return $datasource;
    }

    /**
     * Query the data source and return the usernames of the resulting rows.
     *
     * @param array|null $filters Value for the 'filters' preference, or null to omit it entirely.
     * @return string[]
     */
    protected function get_result_usernames(?array $filters = null): array {
        $usernames = [];
        foreach ($this->create_data_source($filters)->get_data()->get_child_collections('rows') as $row) {
            foreach ($row->get_data() as $cell) {
                if ($cell->get_name() === 'u_username') {
                    $usernames[] = $cell->get_value();
                }
            }
        }

        return $usernames;
    }

    /**
     * The condition builds an equality clause against the suspended column.
     *
     * @return void
     */
    public function test_sql_and_params(): void {
        $condition = new hide_suspended_users_condition('hide_suspended_users', 'u.suspended');
        $condition->init();

        [$sql, $params] = $condition->get_sql_and_params();

        $this->assertEquals('u.suspended = :hide_suspended_users', $sql);
        $this->assertEquals(['hide_suspended_users' => 0], $params);
    }

    /**
     * The users data source offers the condition, and it is switched off until a block enables it.
     *
     * @return void
     */
    public function test_registered_but_disabled_by_default(): void {
        $datasource = $this->create_data_source();
        $filtercollection = $datasource->get_filter_collection();

        $this->assertTrue($filtercollection->has_filter('hide_suspended_users'));

        $condition = $filtercollection->get_filter('hide_suspended_users');
        $this->assertInstanceOf(hide_suspended_users_condition::class, $condition);
        $this->assertEquals(get_string('hidesuspendedusers', 'block_dash'), $condition->get_label());
        $this->assertArrayNotHasKey('enabled', $condition->get_preferences());
    }

    /**
     * Suspended users are excluded only while the condition is enabled.
     *
     * @return void
     */
    public function test_filters_suspended_users(): void {
        $active = $this->getDataGenerator()->create_user(['username' => 'activeuser']);
        $suspended = $this->getDataGenerator()->create_user([
            'username' => 'suspendeduser',
            'suspended' => 1,
        ]);

        // A block that never saved any condition preference sees every user.
        $unconfigured = $this->get_result_usernames();
        $this->assertContains($active->username, $unconfigured);
        $this->assertContains($suspended->username, $unconfigured);

        // So does a block that saved preferences but left the condition switched off.
        $disabled = $this->get_result_usernames([]);
        $this->assertContains($active->username, $disabled);
        $this->assertContains($suspended->username, $disabled);

        // Once enabled, the suspended user drops out and everyone else stays.
        $enabled = $this->get_result_usernames(['hide_suspended_users' => ['enabled' => 1]]);
        $this->assertContains($active->username, $enabled);
        $this->assertNotContains($suspended->username, $enabled);
    }
}
