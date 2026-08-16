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
 * Unit tests for the addon visibility gates used by the block feature picker.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_source\data_source_factory;
use block_dash\local\data_source\users_data_source;

/**
 * The picker must gate on the exact component, never on a substring of the class name.
 *
 * Regression cover for DASH-1287: "wpdashaddon_x" contains "dashaddon_x", so every
 * substring based test silently resolved edition subplugins to a different (usually
 * non-existent) component and hid them.
 *
 * @group block_dash
 * @group bdecent
 * @group visible_addons_test
 */
final class visible_addons_test extends \advanced_testcase {

    /** Data source class of an edition subplugin (need not be installed). */
    private const EDITION_SOURCE = 'wpdashaddon_programs\\local\\block_dash\\programs_data_source';

    /** Widget class of an edition subplugin. */
    private const EDITION_WIDGET = 'wpdashaddon_teaminsights\\widget\\status_widget';

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        require_once($CFG->dirroot . '/blocks/dash/lib.php');
    }

    /**
     * Components that are not dash addon subplugins are never gated.
     */
    public function test_non_addon_components_are_always_visible(): void {
        $this->assertTrue(block_dash_visible_addons(users_data_source::class));
        $this->assertTrue(block_dash_visible_addons('mod_videotime\\local\\block_dash\\videotime_data_source'));
        $this->assertTrue(block_dash_visible_addons('skilladdon_skillprogress\\widget\\progress_widget'));
        $this->assertTrue(block_dash_visible_addons('composeaddon_example\\local\\block_dash\\example_data_source'));
    }

    /**
     * An edition subplugin must not be resolved to the same-named dashaddon component.
     *
     * This is the DASH-1287 regression: with the old regex the "wp" prefix was stripped,
     * dashaddon_programs was looked up instead, and the source disappeared from the picker
     * on every site that does not happen to run that local_dash addon.
     */
    public function test_edition_subplugin_is_not_confused_with_a_dashaddon(): void {
        // Disabling the local_dash addon must not affect the edition subplugin.
        set_config('enabled', '0', 'dashaddon_programs');

        $this->assertTrue(block_dash_visible_addons(self::EDITION_SOURCE));
        $this->assertTrue(block_dash_visible_addons(self::EDITION_WIDGET));
    }

    /**
     * A missing "enabled" config counts as enabled; only an explicit "0" hides.
     *
     * Edition subplugins ship no enable/disable setting, so their config row may never
     * be written. Treating that as disabled would hide them permanently.
     */
    public function test_unset_enabled_config_counts_as_enabled(): void {
        $this->assertFalse(get_config('wpdashaddon_programs', 'enabled'));
        $this->assertTrue(block_dash_visible_addons(self::EDITION_SOURCE));

        set_config('enabled', '0', 'wpdashaddon_programs');
        $this->assertFalse(block_dash_visible_addons(self::EDITION_SOURCE));

        set_config('enabled', '1', 'wpdashaddon_programs');
        $this->assertTrue(block_dash_visible_addons(self::EDITION_SOURCE));
    }

    /**
     * Widgets registered under a custom identifier still resolve to their component.
     *
     * dashaddon_repository registers identifiers such as "dashaddon_repository:my-contacts"
     * instead of class names, so the component must be split on ":" as well as "\".
     */
    public function test_custom_widget_identifiers_resolve_to_their_component(): void {
        $widget = 'dashaddon_repository:my-contacts';

        set_config('enabled', '1', 'dashaddon_repository');
        $this->assertTrue(block_dash_visible_addons($widget));

        set_config('enabled', '0', 'dashaddon_repository');
        $this->assertFalse(block_dash_visible_addons($widget),
            'A disabled addon must also hide the widgets it registers by custom identifier.');
    }

    /**
     * An explicitly disabled dashaddon stays hidden (unchanged behaviour).
     */
    public function test_explicitly_disabled_dashaddon_is_hidden(): void {
        $source = 'dashaddon_courses\\local\\block_dash\\courses_data_source';

        set_config('enabled', '1', 'dashaddon_courses');
        $this->assertTrue(block_dash_visible_addons($source));

        set_config('enabled', '0', 'dashaddon_courses');
        $this->assertFalse(block_dash_visible_addons($source));
    }

    /**
     * $CFG->dashdisabledaddons must match components exactly, not as a substring.
     */
    public function test_disabled_addons_list_does_not_leak_across_components(): void {
        global $CFG;

        $CFG->dashdisabledaddons = ['programs'];
        $options = data_source_factory::get_data_source_form_options();

        $this->assertArrayNotHasKey('dashaddon_programs\\local\\block_dash\\programs_data_source', $options);
        $this->assertArrayHasKey(users_data_source::class, $options,
            'Disabling an addon must not remove unrelated data sources.');
    }

    /**
     * The documented short-name form still disables block_dash's own widgets.
     *
     * config.php documents entries such as "contacts", "groups" and "mylearning", which
     * are namespace segments of the widget class rather than components.
     */
    public function test_disabled_addons_list_still_hides_core_widgets(): void {
        global $CFG;

        $CFG->dashdisabledaddons = ['contacts'];
        $options = data_source_factory::get_data_source_form_options('widget');

        foreach (array_keys($options) as $identifier) {
            $this->assertStringNotContainsString('\\contacts\\', $identifier);
        }
    }

    /**
     * A stray empty entry must not hide every data source.
     *
     * PHP 8 returns 0 from strpos($haystack, ''), so the old substring test skipped the
     * whole registry when the setting contained an empty element.
     */
    public function test_empty_disabled_addons_entry_is_ignored(): void {
        global $CFG;

        $CFG->dashdisabledaddons = ['', 'programs'];
        $options = data_source_factory::get_data_source_form_options();

        $this->assertNotEmpty($options);
        $this->assertArrayHasKey(users_data_source::class, $options);
    }
}
