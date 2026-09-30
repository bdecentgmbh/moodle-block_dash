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
 * Tests for resolving the dashboard a block belongs to.
 *
 * @package   block_dash
 * @copyright 2026, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

/**
 * Tests for resolving the dashboard a block belongs to.
 *
 * A dashboard block that cannot be traced back to its dashboard is treated as a block on an ordinary page, and its
 * content is then fetched behind a login check - which fails with "Course or activity not accessible" for a visitor
 * of a public dashboard who is not logged in.
 *
 * @group block_dash
 * @group bdecent
 * @covers \block_dash\external::get_block_dashboard
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class external_dashboard_test extends \advanced_testcase {
    /**
     * Set up the test.
     */
    public function setUp(): void {
        parent::setUp();

        if (!class_exists('\dashaddon_dashboard\model\dashboard')) {
            $this->markTestSkipped('The dashboard addon (dashaddon_dashboard) is not installed.');
        }

        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Create a public dashboard in the system context.
     *
     * @param  string $shortname The dashboard short name.
     * @return \dashaddon_dashboard\model\dashboard The dashboard.
     */
    protected function create_public_dashboard(string $shortname): \dashaddon_dashboard\model\dashboard {
        $dashboard = new \dashaddon_dashboard\model\dashboard(0, (object) [
            'name' => 'Public dashboard',
            'shortname' => $shortname,
            'contexttype' => 'system',
            'permission' => \dashaddon_dashboard\model\dashboard::PERMISSION_PUBLIC,
            'roles' => '',
            'secondarynav' => 0,
            'dashicon' => '',
            'dashthumbnailimage' => 0,
            'dashbgimage' => 0,
            'includedblocks' => '',
        ]);

        return $dashboard->create();
    }

    /**
     * Build a block instance record without saving it.
     *
     * @param  string $pagetypepattern The page type pattern the block was added with.
     * @param  string $defaultregion The region the block was added to.
     * @return \block_base The block instance.
     */
    protected function create_block(string $pagetypepattern, string $defaultregion): \block_base {
        global $DB;

        $instance = (object) [
            'blockname' => 'dash',
            'parentcontextid' => \context_system::instance()->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => $pagetypepattern,
            'subpagepattern' => null,
            'defaultregion' => $defaultregion,
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $instance->id = $DB->insert_record('block_instances', $instance);
        \context_block::instance($instance->id);

        return block_instance('dash', $instance);
    }

    /**
     * Resolve the dashboard for a block.
     *
     * @param  \block_base $block The block instance.
     * @return \dashaddon_dashboard\model\dashboard|null The dashboard, or null.
     */
    protected function resolve(\block_base $block) {
        $method = new \ReflectionMethod(\block_dash\external::class, 'get_block_dashboard');
        $method->setAccessible(true);

        return $method->invoke(null, $block);
    }

    /**
     * A block added to the dashboard's own region resolves to the dashboard.
     */
    public function test_block_in_the_dashboard_region(): void {
        $dashboard = $this->create_public_dashboard('publicdash');
        $block = $this->create_block('dashaddon-dashboard-publicdash', 'publicdash');

        $this->assertEquals($dashboard->get('id'), $this->resolve($block)->get('id'));
    }

    /**
     * A block added to one of the page layout's own regions resolves to the dashboard as well.
     *
     * This is the case the page type pattern is needed for: the region says "side-pre", not the dashboard's name.
     */
    public function test_block_in_a_layout_region(): void {
        $dashboard = $this->create_public_dashboard('publicdash');
        $block = $this->create_block('dashaddon-dashboard-publicdash', 'side-pre');

        $this->assertEquals($dashboard->get('id'), $this->resolve($block)->get('id'));
    }

    /**
     * A wildcard page type pattern falls back to the region the block was created in.
     */
    public function test_wildcard_page_type_pattern(): void {
        $dashboard = $this->create_public_dashboard('publicdash');
        $block = $this->create_block('dashaddon-dashboard-*', 'publicdash');

        $this->assertEquals($dashboard->get('id'), $this->resolve($block)->get('id'));
    }

    /**
     * A block that is not on a dashboard resolves to nothing.
     */
    public function test_block_outside_a_dashboard(): void {
        $this->create_public_dashboard('publicdash');
        $block = $this->create_block('my-index', 'content');

        $this->assertNull($this->resolve($block));
    }

    /**
     * A block on a dashboard that no longer exists resolves to nothing.
     */
    public function test_block_of_a_deleted_dashboard(): void {
        $block = $this->create_block('dashaddon-dashboard-goneaway', 'goneaway');

        $this->assertNull($this->resolve($block));
    }
}
