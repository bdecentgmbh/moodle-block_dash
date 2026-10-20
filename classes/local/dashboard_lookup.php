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
 * Find the dashboard a block was added to.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;

/**
 * Find the dashboard a block was added to.
 *
 * Blocks on a dashboard of the dashboard addon have to be recognised as such before their content is fetched, because
 * a public dashboard serves its blocks to visitors who are not logged in.
 */
class dashboard_lookup {
    /** @var string The prefix the dashboard page builds its page type from. */
    const PAGETYPE_PREFIX = 'dashaddon-dashboard-';

    /**
     * Read the short name of the dashboard a block was added to.
     *
     * The short name comes from the block's page type pattern, which the dashboard page builds as
     * "dashaddon-dashboard-<shortname>". The region the block was created in carries the short name only when the
     * block was added to the dashboard's own region: a block added to one of the page layout's regions, side-pre for
     * instance, carries that region's name instead. It is still the best answer for a wildcard pattern, which names
     * no dashboard of its own.
     *
     * @param  \stdClass $blockinstance A block_instances record.
     * @return string|null The dashboard's short name, or null when the block is not on a dashboard.
     */
    public static function get_shortname(\stdClass $blockinstance): ?string {
        $pagetypepattern = (string) ($blockinstance->pagetypepattern ?? '');

        if (strpos($pagetypepattern, self::PAGETYPE_PREFIX) !== 0) {
            return null;
        }

        $shortname = substr($pagetypepattern, strlen(self::PAGETYPE_PREFIX));
        if ($shortname === '' || $shortname === '*') {
            $shortname = (string) ($blockinstance->defaultregion ?? '');
        }

        return $shortname !== '' ? $shortname : null;
    }

    /**
     * Whether a block sits on a dashboard that anybody may read, including visitors who are not logged in.
     *
     * @param  \stdClass $blockinstance A block_instances record.
     * @return bool True when the block is on a public dashboard.
     */
    public static function is_public_dashboard(\stdClass $blockinstance): bool {
        $shortname = self::get_shortname($blockinstance);

        if ($shortname === null || !class_exists('\dashaddon_dashboard\model\dashboard')) {
            return false;
        }

        $dashboard = \dashaddon_dashboard\model\dashboard::get_record(['shortname' => $shortname]);

        return $dashboard
            && $dashboard->get('permission') == \dashaddon_dashboard\model\dashboard::PERMISSION_PUBLIC;
    }
}
