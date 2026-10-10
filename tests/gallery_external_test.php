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
 * Unit tests for the gallery web services.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\external\gallery_list;
use block_dash\external\gallery_preview;
use block_dash\external\gallery_use;
use block_dash\gallery\gallery;

/**
 * Unit tests for the gallery web services.
 *
 * @covers \block_dash\external\gallery_list
 * @covers \block_dash\external\gallery_preview
 * @covers \block_dash\external\gallery_use
 */
final class gallery_external_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        gallery::purge_shipped_items();
    }

    /**
     * A new, empty Dash block in a course.
     *
     * @return \block_base
     */
    private function create_block(): \block_base {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
            'configdata' => (object) [],
        ]);
    }

    /**
     * The picker lists the items the user may put into the block.
     */
    public function test_list(): void {
        $block = $this->create_block();
        gallery::update_settings(gallery::get_item('block_dash:participants'), ['hidden' => true]);

        $result = gallery_list::clean_returnvalue(
            gallery_list::execute_returns(),
            gallery_list::execute($block->instance->id)
        );

        $shortnames = array_column($result['items'], 'shortname');
        $this->assertContains('block_dash:my-learning', $shortnames);
        $this->assertNotContains('block_dash:participants', $shortnames);
    }

    /**
     * Only users who may edit the block see the list.
     */
    public function test_list_requires_editing_the_block(): void {
        $block = $this->create_block();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\moodle_exception::class);
        gallery_list::execute($block->instance->id);
    }

    /**
     * Previews render in the block's context, or in the system context on the gallery page.
     */
    public function test_preview(): void {
        $block = $this->create_block();

        $result = gallery_preview::clean_returnvalue(
            gallery_preview::execute_returns(),
            gallery_preview::execute('block_dash:my-learning', $block->instance->id)
        );
        $this->assertStringContainsString('dash-embed', $result['html']);

        $result = gallery_preview::clean_returnvalue(
            gallery_preview::execute_returns(),
            gallery_preview::execute('block_dash:participants', 0)
        );
        $this->assertStringContainsString('dash-embed', $result['html']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        gallery_preview::execute('block_dash:participants', 0);
    }

    /**
     * Using an item copies its setup into the block.
     */
    public function test_use(): void {
        global $DB;

        $block = $this->create_block();

        $result = gallery_use::clean_returnvalue(
            gallery_use::execute_returns(),
            gallery_use::execute($block->instance->id, 'block_dash:participants')
        );

        $this->assertSame([], $result['warnings']);
        $config = unserialize_object(base64_decode($DB->get_field(
            'block_instances',
            'configdata',
            ['id' => $block->instance->id]
        )));
        $this->assertSame('Participants', $config->title);
        $this->assertSame(\block_dash\local\data_source\users_data_source::class, $config->data_source_idnumber);
    }

    /**
     * Hidden items can't be used.
     */
    public function test_use_refuses_hidden_items(): void {
        $block = $this->create_block();
        gallery::update_settings(gallery::get_item('block_dash:participants'), ['hidden' => true]);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('galleryerrornotusable', 'block_dash'));
        gallery_use::execute($block->instance->id, 'block_dash:participants');
    }
}
