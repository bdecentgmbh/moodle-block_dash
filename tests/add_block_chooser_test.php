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
 * Unit tests for Dash's "Add a block" chooser.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\external\gallery_list;
use block_dash\external\gallery_preview;
use block_dash\gallery\gallery;
use block_dash\local\addblock\chooser;
use block_dash\local\addblock\hook_callbacks;

/**
 * Unit tests for Dash's "Add a block" chooser.
 *
 * @covers \block_dash\local\addblock\hook_callbacks
 * @covers \block_dash\local\addblock\chooser
 * @covers \block_dash\external\gallery_base
 */
final class add_block_chooser_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        gallery::purge_shipped_items();
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * The course page, with editing on.
     *
     * @return \moodle_page
     */
    private function get_page(): \moodle_page {
        global $USER;

        $USER->editing = 1;
        $page = new \moodle_page();
        $page->set_course($this->course);
        $page->set_pagetype('course-view-topics');
        $page->set_pagelayout('course');
        $page->set_url(new \moodle_url('/course/view.php', ['id' => $this->course->id]));
        $page->blocks->add_region('side-pre', false);
        $page->blocks->set_default_region('side-pre');
        return $page;
    }

    /**
     * A stand-in for Boost Union's hook, which this plugin doesn't depend on.
     *
     * @param \moodle_page $page
     * @return object
     */
    private function get_hook(\moodle_page $page): object {
        return new class ($page) {
            /** @var \moodle_page */
            public $page;

            /** @var string */
            public $region = 'side-pre';

            /** @var string */
            public $html = 'core';

            /**
             * Constructor.
             *
             * @param \moodle_page $page
             */
            public function __construct(\moodle_page $page) {
                $this->page = $page;
            }

            /**
             * Replace the button.
             *
             * @param string $html
             */
            public function set_html(string $html): void {
                $this->html = $html;
            }
        };
    }

    /**
     * Add a Dash block to the page as the "Add a block" URL does.
     *
     * @param array $params Extra URL parameters.
     * @return \stdClass Config of the new block.
     */
    private function add_block(array $params): \stdClass {
        global $DB;

        $_GET = ['bui_addblock' => 'dash', 'sesskey' => sesskey()] + $params;
        $page = $this->get_page();
        $page->blocks->load_blocks();
        $block = $page->blocks->add_block_at_end_of_default_region('dash');
        $_GET = [];

        $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $block->instance->id]);
        return $configdata ? unserialize_object(base64_decode($configdata)) : new \stdClass();
    }

    /**
     * Dash only takes over the button when the setting is on.
     */
    public function test_button_replaced_when_enabled(): void {
        $page = $this->get_page();

        $hook = $this->get_hook($page);
        hook_callbacks::add_block_button($hook);
        $this->assertSame('core', $hook->html);

        set_config('replaceaddblock', 1, 'block_dash');
        hook_callbacks::add_block_button($hook);
        $this->assertStringContainsString('data-action="block_dash-addblock"', $hook->html);
        $this->assertStringContainsString('data-blockregion="side-pre"', $hook->html);
        $this->assertStringContainsString('data-pagehash="' . $page->get_edited_page_hash() . '"', $hook->html);
    }

    /**
     * The chooser lists and previews the items for the page a block is added to.
     */
    public function test_list_and_preview_for_page(): void {
        $pagehash = $this->get_page()->get_edited_page_hash();
        gallery::update_settings(gallery::get_item('block_dash:participants'), ['hidden' => true]);

        $result = gallery_list::clean_returnvalue(gallery_list::execute_returns(), gallery_list::execute(0, $pagehash));
        $shortnames = array_column($result['items'], 'shortname');
        $this->assertContains('block_dash:my-learning', $shortnames);
        $this->assertNotContains('block_dash:participants', $shortnames);

        $result = gallery_preview::clean_returnvalue(
            gallery_preview::execute_returns(),
            gallery_preview::execute('block_dash:my-learning', 0, $pagehash)
        );
        $this->assertStringContainsString('dash-embed', $result['html']);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('galleryerrornotusable', 'block_dash'));
        gallery_preview::execute('block_dash:participants', 0, $pagehash);
    }

    /**
     * Only users who may add blocks to the page get the list.
     */
    public function test_list_requires_editing_blocks(): void {
        global $DB;

        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $pagehash = $this->get_page()->get_edited_page_hash();
        $this->assertNotEmpty($pagehash);

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:manageblocks', CAP_PROHIBIT, $roleid, \context_course::instance($this->course->id));

        $this->expectException(\moodle_exception::class);
        gallery_list::execute(0, $pagehash);
    }

    /**
     * A Dash block added with a gallery item is set up from it.
     */
    public function test_add_block_with_item(): void {
        $config = $this->add_block([chooser::ITEM_PARAM => 'block_dash:participants']);

        $this->assertSame('Participants', $config->title);
        $this->assertSame(\block_dash\local\data_source\users_data_source::class, $config->data_source_idnumber);
    }

    /**
     * Without an item, or with one the user may not use, the new block stays empty.
     */
    public function test_add_block_without_usable_item(): void {
        $this->assertFalse(isset($this->add_block([])->data_source_idnumber));

        gallery::update_settings(gallery::get_item('block_dash:participants'), ['hidden' => true]);
        $config = $this->add_block([chooser::ITEM_PARAM => 'block_dash:participants']);
        $this->assertFalse(isset($config->data_source_idnumber));
        $messages = array_map(function (\core\output\notification $notification) {
            return $notification->get_message();
        }, \core\notification::fetch());
        $this->assertContains(get_string('galleryerrornotusable', 'block_dash'), $messages);
    }
}
