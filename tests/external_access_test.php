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

namespace block_dash;

use block_dash\local\widget\groups\groups_widget;

/**
 * Tests who may configure a Dash block, and see its content, through the block's web services and fragments.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dash\external
 */
final class external_access_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $course;

    /** @var \stdClass */
    protected $editingteacher;

    /** @var \stdClass Non-editing teacher, holds block/dash:addinstance by default. */
    protected $teacher;

    /** @var \stdClass */
    protected $student;

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/dash/lib.php');

        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->editingteacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->teacher = $generator->create_and_enrol($this->course, 'teacher');
        $this->student = $generator->create_and_enrol($this->course, 'student');
    }

    /**
     * Creates a Dash block on the course page.
     *
     * @param array $config
     * @param string $blockname
     * @return \context_block
     */
    protected function create_block(array $config = [], string $blockname = 'dash'): \context_block {
        global $DB;

        $config += ['data_source_idnumber' => groups_widget::class];
        $id = $DB->insert_record('block_instances', (object) [
            'blockname' => $blockname,
            'parentcontextid' => \context_course::instance($this->course->id)->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize((object) $config)),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return \context_block::instance($id);
    }

    /**
     * Encodes preferences the way the preferences modal sends them.
     *
     * @param array $data
     * @return string
     */
    protected function formdata(array $data): string {
        return json_encode(http_build_query($data, '', '&'));
    }

    public function test_preferences_need_permission_to_edit_the_block(): void {
        $context = $this->create_block();
        $this->setUser($this->teacher);

        $this->expectException(\required_capability_exception::class);
        external::submit_preferences_form($context->id, $this->formdata(['config_preferences' => ['perpage' => 5]]));
    }

    public function test_preferences_need_a_dash_block_context(): void {
        $this->setUser($this->editingteacher);

        $this->expectException(\invalid_parameter_exception::class);
        external::submit_preferences_form(
            \context_course::instance($this->course->id)->id,
            $this->formdata(['config_preferences' => ['perpage' => 5]])
        );
    }

    public function test_preferences_saved_by_an_editor(): void {
        $context = $this->create_block();
        $this->setUser($this->editingteacher);

        external::submit_preferences_form($context->id, $this->formdata(['config_preferences' => ['perpage' => 5]]));

        $block = block_instance_by_id($context->instanceid);
        $this->assertEquals(5, $block->config->preferences['perpage']);
    }

    public function test_preferences_cannot_switch_to_a_data_source_the_editor_may_not_choose(): void {
        $context = $this->create_block();
        $this->setUser($this->editingteacher);

        try {
            external::submit_preferences_form($context->id, $this->formdata([
                'config_data_source_idnumber' => \block_dash\local\data_source\users_data_source::class,
            ]));
            $this->fail('Editing teachers may not choose data sources by default.');
        } catch (\required_capability_exception $e) {
            $block = block_instance_by_id($context->instanceid);
            $this->assertSame(groups_widget::class, $block->config->data_source_idnumber);
        }
    }

    public function test_preferences_fragment_needs_permission_to_edit_the_block(): void {
        $context = $this->create_block();
        $this->setUser($this->teacher);

        $this->expectException(\required_capability_exception::class);
        block_dash_output_fragment_block_preferences_form(['context' => $context, 'tab' => '']);
    }

    public function test_content_respects_the_block_restrictions(): void {
        global $DB;

        $managerrole = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $restricted = $this->create_block(['restrict_roles' => [$managerrole]]);
        $open = $this->create_block();
        $this->setUser($this->student);

        $result = external::get_block_content($open->instanceid, '[]', 0, '', '');
        $this->assertArrayHasKey('html', $result);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('nopermissions', 'error', get_string('view')));
        external::get_block_content($restricted->instanceid, '[]', 0, '', '');
    }

    public function test_content_on_an_activity_page_checks_that_activity(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');

        $DB->set_field('course', 'enablecompletion', 1, ['id' => $this->course->id]);
        $page = $this->getDataGenerator()->create_module(
            'page',
            ['course' => $this->course->id],
            ['completion' => COMPLETION_TRACKING_MANUAL]
        );
        $modulecontext = \context_module::instance($page->cmid);
        $id = $DB->insert_record('block_instances', (object) [
            'blockname' => 'dash',
            'parentcontextid' => $modulecontext->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'mod-page-*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize((object) [
                'data_source_idnumber' => groups_widget::class,
                'restrict_activitycompletion' => 'complete',
            ])),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $other = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        // Restricted to those who completed the activity of the page.
        $completion = new \completion_info(get_course($this->course->id));
        [, $cm] = get_course_and_cm_from_cmid($page->cmid);
        $completion->update_state($cm, COMPLETION_COMPLETE, $this->student->id);

        $this->setUser($this->student);
        $this->assertArrayHasKey('html', external::get_block_content($id, '[]', 0, '', '', '', $modulecontext->id));

        // Each web service call is a request of its own, with a fresh page.
        $GLOBALS['PAGE'] = new \moodle_page();
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        external::get_block_content($id, '[]', 0, '', '', '', $modulecontext->id);
    }

    public function test_content_only_of_dash_blocks(): void {
        $html = $this->create_block([], 'html');
        $this->setUser($this->student);

        $this->expectException(\dml_missing_record_exception::class);
        external::get_block_content($html->instanceid, '[]', 0, '', '');
    }
}
