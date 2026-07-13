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
 * Filters results to users that are not suspended.
 *
 * The users data source already excludes deleted users, but suspended accounts remain
 * visible. Enabling this condition restricts the results to accounts whose suspended
 * flag is unset.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

/**
 * Filters results to users that are not suspended.
 *
 * @package block_dash
 */
class hide_suspended_users_condition extends condition {
    /**
     * Compare the suspended column against a single value.
     *
     * The inherited default, OPERATION_IN_OR_EQUAL, delegates to $DB->get_in_or_equal(), whose
     * placeholder is named from a process wide counter. OPERATION_EQUAL keeps the placeholder
     * tied to the condition name instead.
     *
     * @return string
     */
    public function get_operation() {
        return self::OPERATION_EQUAL;
    }

    /**
     * Only include users whose suspended flag is unset.
     *
     * @return array
     */
    public function get_values() {
        return [0];
    }

    /**
     * Get filter label.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_label() {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('hidesuspendedusers', 'block_dash');
    }
}
