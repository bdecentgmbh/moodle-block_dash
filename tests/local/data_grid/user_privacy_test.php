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

namespace block_dash\local\data_grid;

use block_dash\local\dash_framework\structure\user_table;
use block_dash\local\data_grid\field\attribute\user_privacy_attribute;

/**
 * Tests that the Users data source shows personal details only to viewers core would show them to.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dash\local\data_grid\field\attribute\user_privacy_attribute
 */
final class user_privacy_test extends \advanced_testcase {
    /** @var \stdClass The user whose details are listed. */
    protected $user;

    /** @var \stdClass Another student. */
    protected $viewer;

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        parent::setUp();
        $this->resetAfterTest();
        user_privacy_attribute::reset_cache();

        $this->user = $this->getDataGenerator()->create_user([
            'email' => 'listed@example.com', 'maildisplay' => \core_user::MAILDISPLAY_HIDE, 'city' => 'Berlin',
        ]);
        $this->viewer = $this->getDataGenerator()->create_user();
    }

    /**
     * Transforms a value the way the named field of the user table does for the current user.
     *
     * @param string $alias Field alias, such as u_email.
     * @param mixed $value
     * @return mixed
     */
    protected function shown(string $alias, $value) {
        user_privacy_attribute::reset_cache();
        foreach ((new user_table())->get_fields() as $field) {
            if ($field->get_alias() === $alias) {
                return $field->transform_data($value, (object) ['u_id' => $this->user->id]);
            }
        }
        $this->fail("No field $alias");
    }

    public function test_email_follows_maildisplay(): void {
        global $DB;

        $this->setUser($this->viewer);
        $this->assertNull($this->shown('u_email', 'listed@example.com'));

        $DB->set_field('user', 'maildisplay', \core_user::MAILDISPLAY_EVERYONE, ['id' => $this->user->id]);
        $this->assertSame('listed@example.com', $this->shown('u_email', 'listed@example.com'));
    }

    public function test_email_to_course_members_only(): void {
        global $DB;

        $DB->set_field('user', 'maildisplay', \core_user::MAILDISPLAY_COURSE_MEMBERS_ONLY, ['id' => $this->user->id]);
        $this->setUser($this->viewer);
        $this->assertNull($this->shown('u_email', 'listed@example.com'));

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->user->id, $course->id);
        $this->getDataGenerator()->enrol_user($this->viewer->id, $course->id);
        $this->assertSame('listed@example.com', $this->shown('u_email', 'listed@example.com'));
    }

    public function test_own_details_and_managers(): void {
        $this->setUser($this->user);
        $this->assertSame('listed@example.com', $this->shown('u_email', 'listed@example.com'));

        $this->setAdminUser();
        $this->assertSame('listed@example.com', $this->shown('u_email', 'listed@example.com'));
    }

    public function test_hidden_user_fields(): void {
        $this->setUser($this->viewer);
        $this->assertSame('Berlin', $this->shown('u_city', 'Berlin'));

        set_config('hiddenuserfields', 'city,description');
        $this->assertNull($this->shown('u_city', 'Berlin'));
        $this->assertNull($this->shown('u_description', 'About me'));
    }

    public function test_profile_field_visibility(): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'secret', 'name' => 'Secret', 'visible' => PROFILE_VISIBLE_PRIVATE,
        ]);
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'open', 'name' => 'Open', 'visible' => PROFILE_VISIBLE_ALL,
        ]);

        $this->setUser($this->viewer);
        $this->assertNull($this->shown('u_pf_secret', 'hidden value'));
        $this->assertSame('shown value', $this->shown('u_pf_open', 'shown value'));

        $this->setUser($this->user);
        $this->assertSame('hidden value', $this->shown('u_pf_secret', 'hidden value'));
    }
}
