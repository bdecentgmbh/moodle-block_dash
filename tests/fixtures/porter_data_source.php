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
 * Data source with the optional export and import methods, for porter tests.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

/**
 * Data source with the optional export and import methods, for porter tests.
 *
 * @package    block_dash
 */
class porter_test_data_source extends \block_dash\local\data_source\users_data_source {
    /** @var array|null Arguments of the last import_instance_data() call. */
    public static $imported = null;

    /**
     * Data kept outside the block config.
     *
     * @return array
     */
    public function export_instance_data(): array {
        return ['zones' => [['zoneid' => 'z1', 'courseid' => 7]]];
    }

    /**
     * Record what was imported.
     *
     * @param array $data
     * @param bool $samesite
     * @param array $warnings
     */
    public function import_instance_data(array $data, bool $samesite, array &$warnings): void {
        self::$imported = ['data' => $data, 'samesite' => $samesite, 'blockid' => $this->get_block_instance()->instance->id];
        $warnings[] = 'Data source warning';
    }
}
