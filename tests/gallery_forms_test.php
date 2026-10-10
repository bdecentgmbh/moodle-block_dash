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
 * Unit tests for the gallery forms.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\form\gallery_details;
use block_dash\form\gallery_save;
use block_dash\form\gallery_upload;
use block_dash\gallery\gallery;
use block_dash\local\data_source\users_data_source;
use core_form\external\dynamic_form;

/**
 * Unit tests for the gallery forms.
 *
 * @covers \block_dash\form\gallery_save
 * @covers \block_dash\form\gallery_upload
 * @covers \block_dash\form\gallery_details
 */
final class gallery_forms_test extends \advanced_testcase {
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
     * Submit a dynamic form the way its modal does.
     *
     * @param string $formclass
     * @param array $data
     * @return array Result of the dynamic form web service.
     */
    private function submit(string $formclass, array $data): array {
        // Moodle 5.1 and older set arg_separator.output to '&amp;', which parse_str() doesn't read back.
        return dynamic_form::execute($formclass, http_build_query($formclass::mock_ajax_submit($data), '', '&'));
    }

    /**
     * A draft file holding some content.
     *
     * @param string $content
     * @return int Draft item id.
     */
    private function draft_file(string $content): int {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'item.json',
        ], $content);
        return $draftitemid;
    }

    /**
     * A configured Dash block.
     *
     * @param string $title
     * @return \block_base
     */
    private function create_block(string $title = 'Our people'): \block_base {
        return $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([
            'configdata' => (object) ['title' => $title, 'data_source_idnumber' => users_data_source::class],
        ]);
    }

    /**
     * Save to gallery creates a new site item from a block, or replaces an item's setup.
     */
    public function test_save_new_and_replace(): void {
        $result = $this->submit(gallery_save::class, [
            'blockid' => $this->create_block()->instance->id,
            'mode' => gallery_save::MODE_NEW,
            'name' => 'Our people',
            'shortname' => 'our-people',
            'description' => 'Everyone.',
            'embeddable' => 1,
            'embedrolecontext' => 1,
        ]);

        $this->assertTrue($result['submitted']);
        $item = gallery::get_item('our-people');
        $this->assertSame('Our people', $item->get_data()['config']['title']);
        $this->assertTrue($item->is_embeddable());

        $result = $this->submit(gallery_save::class, [
            'blockid' => $this->create_block('Staff')->instance->id,
            'mode' => gallery_save::MODE_REPLACE,
            'replace' => 'our-people',
            'name' => 'Ignored',
            'shortname' => 'ignored',
            'embedrolecontext' => 1,
        ]);

        $this->assertTrue($result['submitted']);
        $item = gallery::get_item('our-people');
        $this->assertSame('Staff', $item->get_data()['config']['title']);
        $this->assertSame('Our people', $item->get_name());
        $this->assertNull(gallery::get_item('ignored'));
    }

    /**
     * Save to gallery refuses taken shortnames, and users who don't manage the gallery.
     */
    public function test_save_validation_and_access(): void {
        $block = $this->create_block();
        gallery::create_site_item('taken', 'Taken', '', \block_dash\local\porter::export($block));

        $result = $this->submit(gallery_save::class, [
            'blockid' => $block->instance->id,
            'mode' => gallery_save::MODE_NEW,
            'name' => 'Again',
            'shortname' => 'taken',
            'embedrolecontext' => 1,
        ]);
        $this->assertFalse($result['submitted']);
        $this->assertStringContainsString(get_string('galleryerrorshortnametaken', 'block_dash'), $result['html']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        $this->submit(gallery_save::class, ['blockid' => $block->instance->id]);
    }

    /**
     * Upload item adds an export file to the gallery.
     */
    public function test_upload(): void {
        global $CFG;

        $content = file_get_contents($CFG->dirroot . '/blocks/dash/tests/fixtures/users_block.json');
        $result = $this->submit(gallery_upload::class, [
            'importfile' => $this->draft_file($content),
            'name' => 'Uploaded',
            'shortname' => 'uploaded',
            'embedrolecontext' => 1,
        ]);

        $this->assertTrue($result['submitted']);
        $this->assertSame('Imported users', gallery::get_item('uploaded')->get_data()['config']['title']);

        $result = $this->submit(gallery_upload::class, [
            'importfile' => $this->draft_file('not an export file'),
            'name' => 'Broken',
            'shortname' => 'broken',
            'embedrolecontext' => 1,
        ]);
        $this->assertFalse($result['submitted']);
        $this->assertNull(gallery::get_item('broken'));
    }

    /**
     * Edit details renames site items and changes the embed settings of shipped items.
     */
    public function test_details(): void {
        global $DB;

        gallery::create_site_item('people', 'People', '', \block_dash\local\porter::export($this->create_block()));
        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        $result = $this->submit(gallery_details::class, [
            'item' => 'people',
            'name' => 'Everyone',
            'shortname' => 'everyone',
            'description' => 'All of us.',
            'preview' => file_get_unused_draft_itemid(),
            'embeddable' => 1,
            'embedroles' => [$roleid],
            'embedrolecontext' => 1,
        ]);
        $this->assertTrue($result['submitted']);
        $item = gallery::get_item('everyone');
        $this->assertSame('Everyone', $item->get_name());
        $this->assertSame([$roleid], $item->get_embed_roles());

        $result = $this->submit(gallery_details::class, [
            'item' => 'block_dash:participants',
            'embeddable' => 1,
            'embedrolecontext' => 1,
        ]);
        $this->assertTrue($result['submitted']);
        $this->assertTrue(gallery::get_item('block_dash:participants')->is_embeddable());
    }
}
