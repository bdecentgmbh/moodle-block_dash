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
 * Unit tests for block_dash_visible_addons().
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

/**
 * Unit tests for block_dash_visible_addons().
 *
 * @covers ::block_dash_visible_addons
 */
final class visible_addons_test extends \advanced_testcase {
    /**
     * Load the function under test.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/dash/lib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Ids of plugins that don't hide them are offered.
     *
     * @dataProvider offered_provider
     * @param string $id
     */
    public function test_offered(string $id): void {
        $this->assertTrue(block_dash_visible_addons($id));
    }

    /**
     * Ids of plugins that don't hide them.
     *
     * @return array
     */
    public static function offered_provider(): array {
        return [
            'block_dash data source' => ['block_dash\local\data_source\users_data_source'],
            'leading backslash' => ['\block_dash\local\data_source\users_data_source'],
            // The name of another subplugin type ending in "dashaddon" once decided over a
            // Dash addon of the same name, here dashaddon_programs.
            'other subplugin type' => ['wpdashaddon_programs\local\block_dash\programs_data_source'],
            'preset of a plugin that is not installed' => ['dashaddon_nosuchaddon:preset'],
        ];
    }

    /**
     * A disabled subplugin's data sources and widgets are not offered.
     */
    public function test_disabled_subplugin(): void {
        $this->resetAfterTest();
        $id = 'tiny_h5p\local\block_dash\example_data_source';
        $this->assertTrue(block_dash_visible_addons($id));

        \editor_tiny\plugininfo\tiny::enable_plugin('h5p', 0);
        \core_plugin_manager::reset_caches();
        $this->assertFalse(block_dash_visible_addons($id));
    }
}
