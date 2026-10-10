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
 * Unit tests for the form importing a Dash block file.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\form\import_block;
use core_form\external\dynamic_form;

/**
 * Unit tests for the form importing a Dash block file.
 *
 * @covers \block_dash\form\import_block
 */
final class import_block_form_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Submit the form the way the modal does.
     *
     * @param \block_base $block Block to import into.
     * @param string $content Content of the uploaded file.
     * @return array Result of the dynamic form web service.
     */
    private function submit(\block_base $block, string $content): array {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'block.json',
        ], $content);

        return dynamic_form::execute(import_block::class, http_build_query(import_block::mock_ajax_submit([
            'blockid' => $block->instance->id,
            'importfile' => $draftitemid,
        ])));
    }

    /**
     * A valid file is imported and a notice is queued for the reloaded page.
     */
    public function test_submit_imports_file(): void {
        global $CFG, $DB;

        $block = $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([]);

        $result = $this->submit($block, file_get_contents($CFG->dirroot . '/blocks/dash/tests/fixtures/users_block.json'));

        $this->assertTrue($result['submitted']);
        $block = block_instance('dash', $DB->get_record('block_instances', ['id' => $block->instance->id]));
        $this->assertSame('Imported users', $block->config->title);
        $messages = array_map(function ($notification) {
            return $notification->get_message();
        }, \core\notification::fetch());
        $this->assertContains(get_string('importdone', 'block_dash'), $messages);
    }

    /**
     * An invalid file shows an error in the form and changes nothing.
     */
    public function test_submit_shows_error_for_invalid_file(): void {
        global $DB;

        $block = $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([]);
        $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $block->instance->id]);

        $result = $this->submit($block, 'not a Dash block file');

        $this->assertFalse($result['submitted']);
        $this->assertStringContainsString('a Dash block file.', $result['html']);
        $this->assertSame($configdata, $DB->get_field('block_instances', 'configdata', ['id' => $block->instance->id]));
    }

    /**
     * Users who may not edit the block can't import into it.
     */
    public function test_submit_requires_permission_to_edit_the_block(): void {
        $block = $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([]);
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\moodle_exception::class);
        $this->submit($block, '{}');
    }
}
