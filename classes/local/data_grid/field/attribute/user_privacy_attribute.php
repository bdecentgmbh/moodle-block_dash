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

namespace block_dash\local\data_grid\field\attribute;

/**
 * Hides a user's personal details from viewers that core would not show them to.
 *
 * Add it as the last attribute of a field of the user table, with one of these options:
 * - 'identity' => user field shown only as an identity field (see $CFG->showuseridentity), or by its owner's choice
 *   for email (maildisplay);
 * - 'hidden' => key in $CFG->hiddenuserfields that hides the field;
 * - 'profilefield' => visibility of a custom profile field (PROFILE_VISIBLE_*).
 * A user always sees their own details. The row needs the user's id as u_id.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_privacy_attribute extends abstract_field_attribute {
    /** @var array Per request: capabilities of the current user, maildisplay of listed users. */
    protected static $cache = [];

    /**
     * Returns the data, or null when the current user may not see it for the user of the row.
     *
     * @param mixed $data
     * @param \stdClass $record
     * @return mixed
     */
    public function transform_data($data, \stdClass $record) {
        global $CFG, $USER;

        if ($data === null || $data === '') {
            return $data;
        }
        $userid = (int) ($record->u_id ?? 0);
        if ($userid && $userid == $USER->id) {
            return $data;
        }
        if (self::has_capability('moodle/user:viewalldetails')) {
            return $data;
        }

        if (($field = $this->get_option('identity')) !== null) {
            $visible = in_array($field, self::identity_fields()) || ($field === 'email' && self::email_shared_with_viewer($userid));
        } else if (($hidden = $this->get_option('hidden')) !== null) {
            $visible = !in_array($hidden, self::hidden_fields()) || self::has_capability('moodle/user:viewhiddendetails')
                || self::has_capability('moodle/course:viewhiddenuserfields');
        } else if (($profilevisible = $this->get_option('profilefield')) !== null) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $visible = $profilevisible == PROFILE_VISIBLE_ALL
                || ($profilevisible == PROFILE_VISIBLE_TEACHERS && self::has_capability('moodle/course:viewhiddenuserfields'));
        } else {
            $visible = true;
        }

        return $visible ? $data : null;
    }

    /**
     * Whether the current user has a capability where the block is shown.
     *
     * @param string $capability
     * @return bool
     */
    protected static function has_capability(string $capability): bool {
        global $PAGE, $USER;

        $context = $PAGE->context;
        $key = "cap:$USER->id:$context->id:$capability";
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = has_capability($capability, $context);
        }
        return self::$cache[$key];
    }

    /**
     * Identity fields the current user may see where the block is shown.
     *
     * @return string[]
     */
    protected static function identity_fields(): array {
        global $PAGE, $USER;

        $context = $PAGE->context;
        $key = "identity:$USER->id:$context->id";
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = \core_user\fields::get_identity_fields($context, false);
        }
        return self::$cache[$key];
    }

    /**
     * Keys of the user fields the site hides.
     *
     * @return string[]
     */
    protected static function hidden_fields(): array {
        global $CFG;

        return array_map('trim', explode(',', $CFG->hiddenuserfields ?? ''));
    }

    /**
     * Whether a user shows their email address to the current user, by their own choice.
     *
     * @param int $userid
     * @return bool
     */
    protected static function email_shared_with_viewer(int $userid): bool {
        global $DB, $USER;

        if (!$userid) {
            return false;
        }
        $key = "maildisplay:$userid";
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = (int) $DB->get_field('user', 'maildisplay', ['id' => $userid]);
        }
        switch (self::$cache[$key]) {
            case \core_user::MAILDISPLAY_EVERYONE:
                return true;
            case \core_user::MAILDISPLAY_COURSE_MEMBERS_ONLY:
                return isloggedin() && !isguestuser() && enrol_sharing_course($userid, $USER->id);
            default:
                return false;
        }
    }

    /**
     * Forgets what was looked up, for example between tests.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }
}
