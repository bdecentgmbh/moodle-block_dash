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

/**
 * Tests how the edit form finds the help of a widget another plugin registers.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dash_edit_form
 */
final class widget_help_test extends \advanced_testcase {
    /**
     * Calls the edit form's help lookup.
     *
     * @param array|string $help
     * @param string $identifier
     * @return array
     */
    protected function resolve($help, string $identifier): array {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/edit_form.php');
        require_once($CFG->dirroot . '/blocks/dash/edit_form.php');

        $method = new \ReflectionMethod(\block_dash_edit_form::class, 'resolve_widget_help');
        $method->setAccessible(true);
        return $method->invoke(null, $help, $identifier);
    }

    public function test_help_of_block_dash_widgets(): void {
        $this->assertSame(
            ['name' => 'widget:mygroups', 'component' => 'block_dash'],
            $this->resolve('widget:mygroups', \block_dash\local\widget\groups\groups_widget::class)
        );
    }

    public function test_missing_help_gives_no_icon(): void {
        // A widget of another plugin whose help string exists nowhere this lookup can find it.
        $this->assertSame([], $this->resolve('widget:nosuchhelp', 'tool_nosuchplugin\widget\some_widget'));
    }

    public function test_explicit_help_declaration(): void {
        $help = ['name' => 'widget:timings', 'component' => 'tool_timings'];
        $this->assertSame($help, $this->resolve($help, 'tool_timings\widget\timings_widget'));
    }
}
