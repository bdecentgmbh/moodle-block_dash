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
 * Unit tests for displaying select filters as buttons.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_grid\filter\choice_filter;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\data_grid\filter\select_filter;
use block_dash\local\layout\cards_layout;
use block_dash\local\layout\cards_masonry_layout;
use block_dash\local\layout\grid_layout;

/**
 * Unit tests for displaying select filters as buttons.
 *
 * @covers \block_dash\local\data_grid\filter\select_filter
 */
final class select_filter_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Render a filter with the given number of options, in a block using the given layout.
     *
     * @param int $count Number of options, not counting the "All" option.
     * @param string $layout Layout identifier.
     * @param bool $alloption Whether the filter has an "All" option.
     * @return string
     */
    private function render_filter(int $count, string $layout, bool $alloption = true): string {
        $choices = [];
        for ($i = 1; $i <= $count; $i++) {
            $choices[$i] = 'Option ' . $i;
        }

        if ($alloption) {
            $filter = new choice_filter('filter1', 'table.fieldname', $choices);
        } else {
            $filter = new class ('filter1', 'table.fieldname') extends select_filter {
                /**
                 * Initialize the filter without the "All" option.
                 */
                public function init() {
                    parent::init();
                    unset($this->options[self::ALL_OPTION]);
                }
            };
            $filter->add_options($choices);
        }

        $filtercollection = new filter_collection('testing', \context_system::instance());
        $filtercollection->add_filter($filter);
        $filtercollection->init();

        return $filtercollection->create_form_elements('', $layout);
    }

    /**
     * Assert that the filter is displayed as the given number of buttons.
     *
     * @param int $expected
     * @param string $html
     */
    private function assert_buttons(int $expected, string $html): void {
        $this->assertEquals($expected, substr_count($html, 'tab-filter'));
        if ($expected) {
            $this->assertStringNotContainsString('select2', $html);
        } else {
            $this->assertStringContainsString('select2', $html);
        }
    }

    /**
     * Filters with few options are displayed as buttons in the card layouts by default.
     */
    public function test_default_settings(): void {
        $this->assert_buttons(4, $this->render_filter(4, cards_layout::class));
        $this->assert_buttons(3, $this->render_filter(3, cards_masonry_layout::class));
        $this->assert_buttons(0, $this->render_filter(5, cards_layout::class));
        $this->assert_buttons(0, $this->render_filter(3, grid_layout::class));
        $this->assert_buttons(0, $this->render_filter(3, ''));
    }

    /**
     * The "All" option is neither counted nor displayed as a button.
     */
    public function test_all_option(): void {
        $html = $this->render_filter(4, cards_layout::class);
        $this->assert_buttons(4, $html);
        $this->assertStringNotContainsString('data-value="' . select_filter::ALL_OPTION . '"', $html);

        $this->assert_buttons(4, $this->render_filter(4, cards_layout::class, false));
        $this->assert_buttons(0, $this->render_filter(5, cards_layout::class, false));
        // A single option is only worth a button when it can be told apart from "All".
        $this->assert_buttons(1, $this->render_filter(1, cards_layout::class));
        $this->assert_buttons(0, $this->render_filter(1, cards_layout::class, false));
    }

    /**
     * Blocks that still store a local_dash layout identifier are treated like the block_dash layout.
     */
    public function test_legacy_layout_identifier(): void {
        $this->assert_buttons(3, $this->render_filter(3, 'local_dash\layout\cards_layout'));
        $this->assert_buttons(3, $this->render_filter(3, 'local_dash\layout\cards_masonry_layout'));
    }

    /**
     * The maximum number of options is configurable, 0 disables the buttons.
     */
    public function test_count_setting(): void {
        set_config('filterbuttonscount', 6, 'block_dash');
        $this->assert_buttons(6, $this->render_filter(6, cards_layout::class));
        $this->assert_buttons(0, $this->render_filter(7, cards_layout::class));

        set_config('filterbuttonscount', 0, 'block_dash');
        $this->assert_buttons(0, $this->render_filter(2, cards_layout::class));
    }

    /**
     * The layouts that display buttons are configurable.
     */
    public function test_layouts_setting(): void {
        set_config('filterbuttonslayouts', grid_layout::class, 'block_dash');
        $this->assert_buttons(3, $this->render_filter(3, grid_layout::class));
        $this->assert_buttons(0, $this->render_filter(3, cards_layout::class));
        $this->assert_buttons(0, $this->render_filter(3, 'local_dash\layout\cards_layout'));

        set_config('filterbuttonslayouts', '', 'block_dash');
        $this->assert_buttons(0, $this->render_filter(3, cards_layout::class));
        $this->assert_buttons(0, $this->render_filter(3, grid_layout::class));
    }
}
