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
 * Filters results to currently logged in (online) users.
 *
 * Mirrors the logic used by Moodle core's block_online_users: a user is
 * considered online when their lastaccess is more recent than the configured
 * timeframe (block_online_users_timetosee, default 5 minutes).
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

/**
 * Filters results to currently logged in (online) users.
 *
 * @package block_dash
 */
class online_users_condition extends condition {
    /**
     * Return the lower bound timestamp for u.lastaccess.
     *
     * Matches block_online_users: timefrom = 100 * floor((now - timetoshowusers) / 100),
     * where timetoshowusers comes from $CFG->block_online_users_timetosee (minutes),
     * falling back to 5 minutes when the setting is not present.
     *
     * @return array
     */
    public function get_values() {
        global $CFG;

        $timetoshowusers = 300;
        if (isset($CFG->block_online_users_timetosee)) {
            $timetoshowusers = (int) $CFG->block_online_users_timetosee * 60;
        }
        $now = time();
        $timefrom = 100 * (int) floor(($now - $timetoshowusers) / 100);

        return [$timefrom];
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

        return get_string('loggedinusers', 'block_dash');
    }
}
