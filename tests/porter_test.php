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
 * Unit tests for exporting and importing Dash blocks.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_source\data_source_factory;
use block_dash\local\data_source\users_data_source;
use block_dash\local\layout\grid_layout;
use block_dash\local\porter;

/**
 * Unit tests for exporting and importing Dash blocks.
 *
 * @covers \block_dash\local\porter
 */
final class porter_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        require_once($CFG->dirroot . '/blocks/dash/tests/fixtures/porter_data_source.php');
        porter_test_data_source::$imported = null;
    }

    /**
     * Forget the test data source registered by some tests.
     */
    protected function tearDown(): void {
        $registry = new \ReflectionProperty(data_source_factory::class, 'datasourceregistry');
        $registry->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Create a Dash block.
     *
     * @param array $config Block config.
     * @param \context|null $parent Context the block lives in, system by default.
     * @return \block_base
     */
    private function create_block(array $config = [], ?\context $parent = null): \block_base {
        $parent = $parent ?? \context_system::instance();
        return $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([
            'parentcontextid' => $parent->id,
            'pagetypepattern' => $parent->contextlevel == CONTEXT_COURSE ? 'course-view-*' : 'my-index',
            'configdata' => (object) $config,
        ]);
    }

    /**
     * Load a block again, as stored.
     *
     * @param \block_base $block
     * @return \block_base
     */
    private function reload(\block_base $block): \block_base {
        global $DB;
        return block_instance('dash', $DB->get_record('block_instances', ['id' => $block->instance->id]));
    }

    /**
     * Config of a block using the users data source, with values that only make sense on this site.
     *
     * @return array
     */
    private function sample_config(): array {
        global $DB;

        return [
            'title' => 'My users',
            'data_source_idnumber' => users_data_source::class,
            'preferences' => ['layout' => grid_layout::class, 'perpage' => 10],
            'header_content' => ['text' => '<p>Hello</p>', 'format' => FORMAT_HTML, 'itemid' => 123],
            'width' => 50,
            'backgroundimage' => 456789,
            'orgparentblkcontextid' => 99,
            'dash_configure_options' => 1,
            'restrict_roles' => [$DB->get_field('role', 'id', ['shortname' => 'student'])],
            'restrict_cohorts' => [$this->getDataGenerator()->create_cohort()->id],
        ];
    }

    /**
     * Give a block a background image.
     *
     * @param \block_base $block
     * @return \stored_file
     */
    private function add_background_image(\block_base $block): \stored_file {
        global $CFG;

        return get_file_storage()->create_file_from_pathname([
            'contextid' => $block->context->id,
            'component' => 'block_dash',
            'filearea' => 'images',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'background.jpg',
        ], $CFG->dirroot . '/blocks/dash/tests/assets/background.jpg');
    }

    /**
     * Register the test data source with the optional export and import methods.
     */
    private function register_test_data_source(): void {
        data_source_factory::exists(porter_test_data_source::class);

        $registry = new \ReflectionProperty(data_source_factory::class, 'datasourceregistry');
        $entries = $registry->getValue(null);
        $entries[porter_test_data_source::class] = [
            'identifier' => porter_test_data_source::class,
            'name' => 'Porter test data source',
        ];
        $registry->setValue(null, $entries);
    }

    /**
     * Export leaves out values that only make sense on this site, and carries the background image.
     */
    public function test_export_strips_site_only_values(): void {
        $block = $this->create_block($this->sample_config());
        $image = $this->add_background_image($block);

        $data = porter::export($block);

        $this->assertSame('block_dash', $data['format']);
        $this->assertSame(1, $data['version']);
        $this->assertSame(porter::site_hash(), $data['site']);
        $this->assertSame('My users', $data['name']);
        $this->assertContains('block_dash', $data['requires']);
        $this->assertSame(users_data_source::class, $data['config']['data_source_idnumber']);
        $this->assertSame(['text' => '<p>Hello</p>', 'format' => FORMAT_HTML], $data['config']['header_content']);
        foreach (['backgroundimage', 'orgparentblkcontextid', 'dash_configure_options'] as $key) {
            $this->assertArrayNotHasKey($key, $data['config']);
        }
        $this->assertCount(1, $data['files']);
        $this->assertSame('background.jpg', $data['files'][0]['filename']);
        $this->assertSame($image->get_content(), base64_decode($data['files'][0]['content']));
    }

    /**
     * The export survives a round trip through JSON and the file name follows the title.
     */
    public function test_export_file(): void {
        $block = $this->create_block($this->sample_config());
        $data = porter::export($block);

        $this->assertSame($data, porter::decode(json_encode($data)));
        $this->assertSame('my-users-dash-' . gmdate('Ymd') . '.json', porter::get_filename($block));

        $untitled = $this->create_block(['data_source_idnumber' => users_data_source::class]);
        $this->assertSame('dash-' . gmdate('Ymd') . '.json', porter::get_filename($untitled));
    }

    /**
     * On the same site the whole setup is carried over.
     */
    public function test_import_on_same_site_keeps_setup(): void {
        $source = $this->create_block($this->sample_config());
        $image = $this->add_background_image($source);
        $target = $this->create_block();

        $warnings = porter::import($target, porter::export($source));

        $this->assertSame([], $warnings);
        $target = $this->reload($target);
        $this->assertSame('My users', $target->config->title);
        $this->assertSame(users_data_source::class, $target->config->data_source_idnumber);
        $this->assertSame($source->config->preferences, $target->config->preferences);
        $this->assertEquals($source->config->restrict_roles, $target->config->restrict_roles);
        $this->assertEquals($source->config->restrict_cohorts, $target->config->restrict_cohorts);
        $this->assertSame($target->context->id, $target->config->orgparentblkcontextid);
        $this->assertFalse(property_exists($target->config, 'backgroundimage'));

        $files = get_file_storage()->get_area_files($target->context->id, 'block_dash', 'images', 0, 'id', false);
        $this->assertCount(1, $files);
        $this->assertSame($image->get_contenthash(), reset($files)->get_contenthash());
    }

    /**
     * Import replaces the setup of a configured block, including its background image.
     */
    public function test_import_replaces_existing_setup(): void {
        $source = $this->create_block(['title' => 'New', 'data_source_idnumber' => users_data_source::class]);
        $target = $this->create_block($this->sample_config());
        $this->add_background_image($target);

        porter::import($target, porter::export($source));

        $target = $this->reload($target);
        $this->assertSame('New', $target->config->title);
        $this->assertFalse(property_exists($target->config, 'restrict_roles'));
        $this->assertEmpty(get_file_storage()->get_area_files($target->context->id, 'block_dash', 'images', 0, 'id', false));
    }

    /**
     * From another site, access restrictions by id are removed and preferences are flagged for review.
     */
    public function test_import_from_another_site_removes_restrictions(): void {
        $data = porter::export($this->create_block($this->sample_config()));
        $data['site'] = sha1('another site');
        $target = $this->create_block();

        $warnings = porter::import($target, $data);

        $target = $this->reload($target);
        $this->assertSame([], $target->config->restrict_roles);
        $this->assertSame([], $target->config->restrict_cohorts);
        $this->assertSame(['layout' => grid_layout::class, 'perpage' => 10], $target->config->preferences);
        $this->assertCount(2, $warnings);
        $this->assertStringContainsString(get_string('restrictbyrole', 'block_dash'), $warnings[0]);
        $this->assertStringContainsString(get_string('restrictbycohort', 'block_dash'), $warnings[0]);
        $this->assertSame(get_string('importwarningpreferences', 'block_dash'), $warnings[1]);
    }

    /**
     * Files without a site, like gallery items, are taken as portable.
     */
    public function test_import_without_site_keeps_restrictions(): void {
        $source = $this->create_block($this->sample_config());
        $data = porter::export($source);
        unset($data['site']);
        $target = $this->create_block();

        $this->assertSame([], porter::import($target, $data));
        $this->assertEquals($source->config->restrict_roles, $this->reload($target)->config->restrict_roles);
    }

    /**
     * Group and activity restrictions are kept only when they belong to the block's course.
     */
    public function test_import_keeps_only_restrictions_of_the_course(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $othergroup = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course2->id]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course2->id]);

        $data = porter::export($this->create_block(['data_source_idnumber' => users_data_source::class]));
        $data['config']['restrict_groups'] = [$othergroup->id, $group->id];
        $data['config']['restrict_modules'] = [$page->cmid];
        $target = $this->create_block([], \context_course::instance($course2->id));

        $warnings = porter::import($target, $data);

        $target = $this->reload($target);
        $this->assertEquals([$group->id], $target->config->restrict_groups);
        $this->assertEquals([$page->cmid], $target->config->restrict_modules);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString(get_string('restrictbygroup', 'block_dash'), $warnings[0]);
    }

    /**
     * A layout that isn't registered falls back to the default.
     */
    public function test_import_unknown_layout_falls_back_to_default(): void {
        $data = porter::export($this->create_block($this->sample_config()));
        $data['config']['preferences']['layout'] = 'dashaddon_missing\layout\missing_layout';
        $target = $this->create_block();

        $warnings = porter::import($target, $data);

        $this->assertArrayNotHasKey('layout', $this->reload($target)->config->preferences);
        $this->assertSame([get_string('importwarninglayout', 'block_dash')], $warnings);
    }

    /**
     * Imported HTML is cleaned unless the user may add untrusted content.
     */
    public function test_import_cleans_html_for_untrusted_users(): void {
        $data = porter::export($this->create_block($this->sample_config()));
        $data['config']['header_content']['text'] = '<p>Hi</p><script>alert(1);</script>';

        $trusted = $this->create_block();
        porter::import($trusted, $data);
        $this->assertStringContainsString('<script>', $this->reload($trusted)->config->header_content['text']);

        $this->setUser($this->getDataGenerator()->create_user());
        $untrusted = $this->create_block();
        porter::import($untrusted, $data);
        $text = $this->reload($untrusted)->config->header_content['text'];
        $this->assertStringContainsString('<p>Hi</p>', $text);
        $this->assertStringNotContainsString('<script>', $text);
    }

    /**
     * Data sources export and import their own data through the optional methods.
     */
    public function test_data_source_methods_are_called(): void {
        $this->register_test_data_source();
        $source = $this->create_block(['data_source_idnumber' => porter_test_data_source::class]);
        $target = $this->create_block();

        $data = porter::export($source);
        $warnings = porter::import($target, $data);

        $this->assertSame(['zones' => [['zoneid' => 'z1', 'courseid' => 7]]], $data['datasource_data']);
        $this->assertSame([
            'data' => $data['datasource_data'],
            'samesite' => true,
            'blockid' => $target->instance->id,
        ], porter_test_data_source::$imported);
        $this->assertSame(['Data source warning'], $warnings);
    }

    /**
     * Files that aren't Dash exports, or come from a newer version, are refused.
     *
     * @dataProvider invalid_file_provider
     * @param string $content
     * @param string $error
     */
    public function test_decode_refuses_invalid_files(string $content, string $error): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string($error, 'block_dash'));
        porter::decode($content);
    }

    /**
     * Invalid files and the error they cause.
     *
     * @return array
     */
    public static function invalid_file_provider(): array {
        $file = ['format' => 'block_dash', 'version' => 1, 'config' => ['data_source_idnumber' => users_data_source::class]];
        return [
            'not JSON' => ['not a file', 'importerrorinvalid'],
            'another format' => [json_encode(['format' => 'other'] + $file), 'importerrorinvalid'],
            'no data source' => [json_encode(['config' => []] + $file), 'importerrorinvalid'],
            'newer version' => [json_encode(['version' => 99] + $file), 'importerrorversion'],
        ];
    }

    /**
     * A file needing a data source that isn't installed is refused, naming what is missing.
     */
    public function test_validate_refuses_missing_data_source(): void {
        $data = porter::export($this->create_block(['data_source_idnumber' => users_data_source::class]));
        $data['config']['data_source_idnumber'] = 'dashaddon_missing\local\block_dash\missing_data_source';
        $data['requires'] = ['block_dash', 'dashaddon_missing'];

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('importerrordatasource', 'block_dash', 'dashaddon_missing'));
        porter::validate($data, $this->create_block());
    }

    /**
     * Importing needs the same capability as choosing the data source in the block settings.
     */
    public function test_validate_requires_data_source_capability(): void {
        $data = porter::export($this->create_block(['data_source_idnumber' => users_data_source::class]));
        $target = $this->create_block();

        porter::validate($data, $target);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('importerrorcapability', 'block_dash'));
        porter::validate($data, $target);
    }

    /**
     * Only users who may edit a block can export it or import into it.
     */
    public function test_can_edit_block(): void {
        $block = $this->create_block();
        $this->assertTrue(porter::can_edit_block($block));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(porter::can_edit_block($block));
    }
}
