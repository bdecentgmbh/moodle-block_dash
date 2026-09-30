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
 * Tests for finding the dashboard a block was added to.
 *
 * @package   block_dash
 * @copyright 2026 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;

/**
 * Tests for finding the dashboard a block was added to.
 *
 * A block on a public dashboard that cannot be traced back to its dashboard is treated as a block on an ordinary
 * page, and its content is then fetched behind a login check - which answers "Course or activity not accessible"
 * for a visitor who is not logged in.
 *
 * @group block_dash
 * @group bdecent
 */
final class dashboard_lookup_test extends \advanced_testcase {
    /**
     * Build a block instance record.
     *
     * @param  string $pagetypepattern The page type pattern the block was added with.
     * @param  string $defaultregion The region the block was added to.
     * @return \stdClass The record.
     */
    protected function block_instance(string $pagetypepattern, string $defaultregion): \stdClass {
        return (object) [
            'blockname' => 'dash',
            'pagetypepattern' => $pagetypepattern,
            'defaultregion' => $defaultregion,
        ];
    }

    /**
     * A block added to the dashboard's own region names the dashboard.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_block_in_the_dashboard_region(): void {
        $block = $this->block_instance('dashaddon-dashboard-publicdash', 'publicdash');

        $this->assertSame('publicdash', dashboard_lookup::get_shortname($block));
    }

    /**
     * A block added to one of the page layout's own regions names the dashboard as well.
     *
     * This is what the page type pattern is needed for: the region says "side-pre", not the dashboard's short name.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_block_in_a_layout_region(): void {
        $block = $this->block_instance('dashaddon-dashboard-publicdash', 'side-pre');

        $this->assertSame('publicdash', dashboard_lookup::get_shortname($block));
    }

    /**
     * A wildcard page type pattern falls back to the region the block was created in.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_wildcard_page_type_pattern(): void {
        $block = $this->block_instance('dashaddon-dashboard-*', 'publicdash');

        $this->assertSame('publicdash', dashboard_lookup::get_shortname($block));
    }

    /**
     * A wildcard page type pattern on a block outside the dashboard's region names no dashboard.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_wildcard_page_type_pattern_without_a_region(): void {
        $block = $this->block_instance('dashaddon-dashboard-*', '');

        $this->assertNull(dashboard_lookup::get_shortname($block));
    }

    /**
     * A block that is not on a dashboard names no dashboard.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_block_outside_a_dashboard(): void {
        $block = $this->block_instance('my-index', 'content');

        $this->assertNull(dashboard_lookup::get_shortname($block));
    }

    /**
     * A page type that only looks like a dashboard's names no dashboard.
     *
     * @covers \block_dash\local\dashboard_lookup::get_shortname
     */
    public function test_page_type_that_merely_contains_the_prefix(): void {
        $block = $this->block_instance('course-view-dashaddon-dashboard-publicdash', 'publicdash');

        $this->assertNull(dashboard_lookup::get_shortname($block));
    }

    /**
     * A block in a layout region of a public dashboard is recognised as public.
     *
     * Needs the dashboard addon, which ships with local_dash, so this is skipped where only block_dash is installed.
     *
     * @covers \block_dash\local\dashboard_lookup::is_public_dashboard
     */
    public function test_public_dashboard_is_recognised(): void {
        if (!class_exists('\dashaddon_dashboard\model\dashboard')) {
            $this->markTestSkipped('The dashboard addon (dashaddon_dashboard) is not installed.');
        }

        $this->resetAfterTest(true);
        $this->setAdminUser();

        (new \dashaddon_dashboard\model\dashboard(0, (object) [
            'name' => 'Public dashboard',
            'shortname' => 'publicdash',
            'contexttype' => 'system',
            'permission' => \dashaddon_dashboard\model\dashboard::PERMISSION_PUBLIC,
            'roles' => '',
            'secondarynav' => 0,
            'dashicon' => '',
            'dashthumbnailimage' => 0,
            'dashbgimage' => 0,
            'includedblocks' => '',
        ]))->create();

        $this->assertTrue(
            dashboard_lookup::is_public_dashboard($this->block_instance('dashaddon-dashboard-publicdash', 'side-pre'))
        );
        $this->assertFalse(
            dashboard_lookup::is_public_dashboard($this->block_instance('my-index', 'content'))
        );
    }
}
