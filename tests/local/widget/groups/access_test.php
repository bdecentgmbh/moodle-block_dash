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

namespace block_dash\local\widget\groups;

/**
 * Tests the permission checks of the My groups widget's web services and fragment.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dash\local\widget\groups\external
 * @covers     \block_dash\local\widget\groups\access
 */
final class access_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $course;

    /** @var \stdClass Group of member1. */
    protected $group;

    /** @var \stdClass Group of member2. */
    protected $othergroup;

    /** @var \stdClass Enrolled, member of $group. */
    protected $member1;

    /** @var \stdClass Enrolled, member of $othergroup. */
    protected $member2;

    /** @var \stdClass Enrolled, in no group. */
    protected $student;

    /** @var \stdClass Not enrolled in the course. */
    protected $outsider;

    /** @var \context_block Context of a Dash block showing the My groups widget. */
    protected $blockcontext;

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/dash/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->member1 = $generator->create_and_enrol($this->course, 'student');
        $this->member2 = $generator->create_and_enrol($this->course, 'student');
        $this->student = $generator->create_and_enrol($this->course, 'student');
        $this->outsider = $generator->create_user();
        $this->group = $generator->create_group(['courseid' => $this->course->id]);
        $this->othergroup = $generator->create_group(['courseid' => $this->course->id]);
        $generator->create_group_member(['groupid' => $this->group->id, 'userid' => $this->member1->id]);
        $generator->create_group_member(['groupid' => $this->othergroup->id, 'userid' => $this->member2->id]);

        $this->blockcontext = $this->create_dash_block(groups_widget::class);

        // By default only managers and non-editing teachers may add users or create groups from the widget.
        $userrole = $CFG->defaultuserroleid;
        assign_capability('block/dash:mygroups_addusers', CAP_ALLOW, $userrole, \context_system::instance()->id, true);
        assign_capability('block/dash:mygroups_creategroup', CAP_ALLOW, $userrole, \context_system::instance()->id, true);
    }

    /**
     * Creates a Dash block on the default dashboard.
     *
     * @param string $datasource Identifier of the block's data source or widget.
     * @param string $blockname
     * @return \context_block
     */
    protected function create_dash_block(string $datasource, string $blockname = 'dash'): \context_block {
        global $DB;

        $id = $DB->insert_record('block_instances', (object) [
            'blockname' => $blockname,
            'parentcontextid' => \context_system::instance()->id,
            'showinsubcontexts' => 1,
            'pagetypepattern' => 'my-index',
            'defaultregion' => 'content',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize((object) ['data_source_idnumber' => $datasource])),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return \context_block::instance($id);
    }

    /**
     * Encodes form data the way the widget's JavaScript sends it.
     *
     * @param array $data
     * @return string
     */
    protected function formdata(array $data): string {
        return http_build_query($data, '', '&');
    }

    public function test_add_members_needs_group_membership(): void {
        $this->setUser($this->student);

        try {
            external::add_members($this->blockcontext->id, $this->formdata([
                'groupid' => $this->group->id, 'users' => [$this->student->id],
            ]));
            $this->fail('A user who is not in the group must not add members to it.');
        } catch (\required_capability_exception $e) {
            $this->assertFalse(groups_is_member($this->group->id, $this->student->id));
        }
    }

    public function test_add_members_by_member(): void {
        $this->setUser($this->member1);

        $this->assertTrue(external::add_members($this->blockcontext->id, $this->formdata([
            'groupid' => $this->group->id, 'users' => [$this->student->id],
        ])));
        $this->assertTrue(groups_is_member($this->group->id, $this->student->id));
    }

    public function test_add_members_only_enrolled_users(): void {
        $this->setUser($this->member1);

        $this->expectException(\invalid_parameter_exception::class);
        try {
            external::add_members($this->blockcontext->id, $this->formdata([
                'groupid' => $this->group->id, 'users' => [$this->student->id, $this->outsider->id],
            ]));
        } finally {
            $this->assertFalse(groups_is_member($this->group->id, $this->outsider->id));
            $this->assertFalse(groups_is_member($this->group->id, $this->student->id));
        }
    }

    public function test_add_members_needs_widget_capability(): void {
        global $CFG;
        unassign_capability('block/dash:mygroups_addusers', $CFG->defaultuserroleid);
        $this->setUser($this->member1);

        $this->expectException(\required_capability_exception::class);
        external::add_members($this->blockcontext->id, $this->formdata([
            'groupid' => $this->group->id, 'users' => [$this->student->id],
        ]));
    }

    public function test_group_manager_can_add_to_any_group(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);

        $this->assertTrue(external::add_members($this->blockcontext->id, $this->formdata([
            'groupid' => $this->othergroup->id, 'users' => [$this->student->id],
        ])));
        $this->assertTrue(groups_is_member($this->othergroup->id, $this->student->id));
    }

    /**
     * Contexts that are not the context of a Dash block.
     *
     * @return array
     */
    public static function foreign_context_provider(): array {
        return [
            'course context' => ['course'],
            'context of another block' => ['html'],
        ];
    }

    /**
     * The widget's services refuse contexts that are not the context of a Dash block.
     *
     * @dataProvider foreign_context_provider
     * @param string $kind
     */
    public function test_widget_context_must_be_a_dash_block(string $kind): void {
        $context = $kind === 'course'
            ? \context_course::instance($this->course->id)
            : $this->create_dash_block('', 'html');
        $this->setUser($this->member1);

        $this->expectException(\invalid_parameter_exception::class);
        external::leave_group($context->id, $this->group->id);
    }

    public function test_create_group_sets_only_course_and_name(): void {
        $this->setUser($this->student);

        $this->assertTrue(external::create_group($this->blockcontext->id, $this->formdata([
            'courseid' => $this->course->id, 'name' => 'Study group', 'enrolmentkey' => 'letmein', 'idnumber' => 'x1',
        ])));

        $group = groups_get_group_by_name($this->course->id, 'Study group');
        $this->assertNotEmpty($group);
        $group = groups_get_group($group);
        $this->assertEmpty($group->enrolmentkey);
        $this->assertEmpty($group->idnumber);
        $this->assertTrue(groups_is_member($group->id, $this->student->id));
    }

    public function test_create_group_needs_enrolment(): void {
        $this->setUser($this->outsider);

        try {
            external::create_group($this->blockcontext->id, $this->formdata([
                'courseid' => $this->course->id, 'name' => 'Intruders',
            ]));
            $this->fail('A user who is not enrolled in the course must not create groups in it.');
        } catch (\required_capability_exception $e) {
            $this->assertFalse(groups_get_group_by_name($this->course->id, 'Intruders'));
        }
    }

    public function test_create_group_rejects_duplicate_names(): void {
        $this->setUser($this->student);

        $this->expectException(\moodle_exception::class);
        external::create_group($this->blockcontext->id, $this->formdata([
            'courseid' => $this->course->id, 'name' => $this->group->name,
        ]));
    }

    public function test_leave_group_only_own_membership(): void {
        $this->setUser($this->member1);

        $this->assertFalse(external::leave_group($this->blockcontext->id, $this->othergroup->id));
        $this->assertTrue(groups_is_member($this->othergroup->id, $this->member2->id));

        $this->assertTrue(external::leave_group($this->blockcontext->id, $this->group->id));
        $this->assertFalse(groups_is_member($this->group->id, $this->member1->id));
    }

    public function test_get_non_members(): void {
        $this->setUser($this->student);
        try {
            external::get_non_members($this->blockcontext->id, '', $this->group->id);
            $this->fail('A user who is not in the group must not list the users who could join it.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }

        $this->setUser($this->member1);
        $ids = array_column(external::get_non_members($this->blockcontext->id, '', $this->group->id), 'id');
        $this->assertContainsEquals($this->student->id, $ids);
        $this->assertNotContainsEquals($this->member1->id, $ids);
        $this->assertNotContainsEquals($this->outsider->id, $ids);
    }

    public function test_fragment_calls_only_offered_methods(): void {
        $this->setUser($this->member1);

        $this->expectException(\invalid_parameter_exception::class);
        block_dash_output_fragment_loadwidget([
            'context' => $this->blockcontext, 'method' => 'build_widget', 'args' => '{}',
        ]);
    }

    public function test_fragment_checks_the_group(): void {
        $this->setUser($this->member1);

        $html = block_dash_output_fragment_loadwidget([
            'context' => $this->blockcontext, 'method' => 'addmembers', 'args' => json_encode(['group' => $this->group->id]),
        ]);
        $this->assertStringContainsString('name="groupid"', $html);

        $this->expectException(\required_capability_exception::class);
        block_dash_output_fragment_loadwidget([
            'context' => $this->blockcontext, 'method' => 'addmembers', 'args' => json_encode(['group' => $this->othergroup->id]),
        ]);
    }

    public function test_fragment_needs_a_dash_block(): void {
        $this->setUser($this->member1);

        $this->expectException(\invalid_parameter_exception::class);
        block_dash_output_fragment_loadwidget([
            'context' => \context_course::instance($this->course->id), 'method' => 'viewmembers',
            'args' => json_encode(['group' => $this->group->id]),
        ]);
    }
}
