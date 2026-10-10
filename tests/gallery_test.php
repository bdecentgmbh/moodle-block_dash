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
 * Unit tests for the gallery of ready-made Dash setups.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\gallery\embed;
use block_dash\gallery\gallery;
use block_dash\gallery\item;
use block_dash\hook\gallery_items;
use block_dash\local\data_source\users_data_source;
use block_dash\local\porter;

/**
 * Unit tests for the gallery of ready-made Dash setups.
 *
 * @covers \block_dash\gallery\gallery
 * @covers \block_dash\gallery\item
 * @covers \block_dash\gallery\embed
 */
final class gallery_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        gallery::purge_shipped_items();
    }

    /**
     * Export of a block using the users data source.
     *
     * @param array $config Extra block config.
     * @return array
     */
    private function export_users_block(array $config = []): array {
        $block = $this->getDataGenerator()->get_plugin_generator('block_dash')->create_dash_block([
            'configdata' => (object) ($config + [
                'title' => 'Our people',
                'data_source_idnumber' => users_data_source::class,
            ]),
        ]);
        return porter::export($block);
    }

    /**
     * block_dash ships its starter items, with names from its language strings.
     */
    public function test_block_dash_ships_starter_items(): void {
        $items = gallery::get_items();

        $this->assertArrayHasKey('block_dash:my-learning', $items);
        $this->assertArrayHasKey('block_dash:participants', $items);

        $mylearning = $items['block_dash:my-learning'];
        $this->assertTrue($mylearning->is_shipped());
        $this->assertSame('block_dash', $mylearning->get_source());
        $this->assertSame(get_string('gallery:my-learning', 'block_dash'), $mylearning->get_name());
        $this->assertSame(get_string('gallery:my-learning_desc', 'block_dash'), $mylearning->get_description());
        $this->assertTrue($mylearning->is_available());
        $this->assertTrue($mylearning->is_embeddable());
        $this->assertFalse($items['block_dash:participants']->is_embeddable());
    }

    /**
     * Plugins add items through the gallery_items hook.
     */
    public function test_hook_adds_items(): void {
        global $CFG;

        $this->redirectHook(gallery_items::class, function (gallery_items $hook) use ($CFG) {
            $hook->add_item('block_dash', 'fixture', $CFG->dirroot . '/blocks/dash/tests/fixtures/users_block.json');
            $hook->add_item('block_dash', 'Not Valid', $CFG->dirroot . '/blocks/dash/tests/fixtures/users_block.json');
        });
        gallery::purge_shipped_items();

        $items = gallery::get_items();
        $this->assertDebuggingCalled();

        $this->assertSame(['block_dash:fixture'], array_keys($items));
        $this->assertSame('Imported users', $items['block_dash:fixture']->get_data()['config']['title']);
    }

    /**
     * Site items are created, changed and deleted.
     */
    public function test_site_item_lifecycle(): void {
        $item = gallery::create_site_item('our-people', 'Our people', 'Everyone here.', $this->export_users_block(), [
            'embeddable' => true,
        ]);

        $this->assertFalse($item->is_shipped());
        $this->assertSame('Our people', $item->get_name());
        $this->assertSame('Everyone here.', $item->get_description());
        $this->assertSame(get_string('gallerysourcesite', 'block_dash'), $item->get_source_name());
        $this->assertTrue($item->is_embeddable());
        $this->assertSame(users_data_source::class, gallery::get_item('our-people')->get_data_source());

        $item = gallery::update_settings($item, ['shortname' => 'people', 'name' => 'People', 'embeddable' => false]);
        $this->assertNull(gallery::get_item('our-people'));
        $this->assertSame('People', gallery::get_item('people')->get_name());
        $this->assertFalse(gallery::get_item('people')->is_embeddable());

        $item = gallery::replace_data($item, $this->export_users_block(['title' => 'Staff']));
        $this->assertSame('Staff', gallery::get_item('people')->get_data()['config']['title']);
        $this->assertSame('People', gallery::get_item('people')->get_name());

        gallery::delete($item);
        $this->assertNull(gallery::get_item('people'));
    }

    /**
     * Shortnames of site items are checked.
     */
    public function test_validate_shortname(): void {
        gallery::create_site_item('taken', 'Taken', '', $this->export_users_block());

        $this->assertNull(gallery::validate_shortname('fine-name-2'));
        $this->assertSame('galleryerrorshortname', gallery::validate_shortname('Not fine'));
        $this->assertSame('galleryerrorshortname', gallery::validate_shortname('-leading'));
        $this->assertSame('galleryerrorshortname', gallery::validate_shortname(str_repeat('a', 101)));
        $this->assertSame('galleryerrorshortnametaken', gallery::validate_shortname('taken'));
        $this->assertNull(gallery::validate_shortname('taken', gallery::get_item('taken')->get_record()->get('id')));

        $this->expectException(\moodle_exception::class);
        gallery::create_site_item('taken', 'Again', '', $this->export_users_block());
    }

    /**
     * Shipped items are read-only, but admins can hide them and change the embed settings.
     */
    public function test_shipped_item_settings(): void {
        $item = gallery::get_item('block_dash:my-learning');

        gallery::update_settings($item, ['hidden' => true, 'embeddable' => false, 'name' => 'Ignored']);

        $item = gallery::get_item('block_dash:my-learning');
        $this->assertTrue($item->is_hidden());
        $this->assertFalse($item->is_embeddable());
        $this->assertSame(get_string('gallery:my-learning', 'block_dash'), $item->get_name());
        $context = \context_system::instance();
        $this->assertArrayNotHasKey('block_dash:my-learning', gallery::get_usable_items($context));
        $this->assertArrayNotHasKey('block_dash:my-learning', gallery::get_embeddable_items($context));

        $this->expectException(\moodle_exception::class);
        gallery::delete($item);
    }

    /**
     * Items whose images are too large are refused.
     */
    public function test_files_size_cap(): void {
        $data = $this->export_users_block();
        $data['files'] = [['area' => 'images', 'filename' => 'big.jpg', 'content' => base64_encode(str_repeat('x', 2097153))]];

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('galleryerrortoolarge', 'block_dash', display_size(gallery::MAX_FILES_SIZE)));
        gallery::create_site_item('big', 'Big', '', $data);
    }

    /**
     * Items need the capability for their type of data source to be used in a block.
     */
    public function test_usable_items_need_data_source_capability(): void {
        $context = \context_system::instance();
        $this->assertArrayHasKey('block_dash:participants', gallery::get_usable_items($context));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertArrayNotHasKey('block_dash:participants', gallery::get_usable_items($context));
    }

    /**
     * Embed roles limit who may embed an item, in the context where it is embedded.
     */
    public function test_embed_roles(): void {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $context1 = \context_course::instance($course1->id);
        $context2 = \context_course::instance($course2->id);

        $item = gallery::create_site_item('people', 'People', '', $this->export_users_block(), ['embeddable' => true]);
        $this->setUser($teacher);
        $this->assertTrue($item->can_embed($context2), 'No roles: anyone may embed');

        $item = gallery::update_settings($item, ['embedroles' => [$roleid]]);
        $this->assertTrue($item->can_embed($context1));
        $this->assertFalse($item->can_embed($context2));
        $this->assertArrayHasKey('people', gallery::get_embeddable_items($context1));
        $this->assertArrayNotHasKey('people', gallery::get_embeddable_items($context2));

        $item = gallery::update_settings($item, ['embedrolecontext' => item::ROLES_SYSTEM]);
        $this->assertFalse($item->can_embed($context1), 'The course role doesn\'t count at system level');
    }

    /**
     * Items whose data source isn't installed are unavailable and name what's missing.
     */
    public function test_missing_data_source(): void {
        $data = $this->export_users_block();
        $data['config']['data_source_idnumber'] = 'dashaddon_missing\local\block_dash\missing_data_source';
        $data['requires'] = ['block_dash', 'dashaddon_missing'];

        $item = gallery::create_site_item('missing', 'Missing', '', $data, ['embeddable' => true]);

        $this->assertFalse($item->is_available());
        $this->assertSame(['dashaddon_missing'], $item->get_missing_components());
        $this->assertArrayNotHasKey('missing', gallery::get_usable_items(\context_system::instance()));
        $this->assertSame('', embed::render('missing', \context_system::instance()));
    }

    /**
     * Embeddable items render like a block; others don't render.
     */
    public function test_render(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $html = embed::render('block_dash:my-learning', $context);
        $this->assertStringContainsString('dash-embed', $html);
        $this->assertStringContainsString('data-gallery-item="block_dash:my-learning"', $html);
        $this->assertStringContainsString(get_string('gallery:my-learning', 'block_dash'), $html);

        $this->assertSame('', embed::render('block_dash:participants', $context), 'Not embeddable');
        $this->assertSame('', embed::render('block_dash:nothing', $context), 'Missing');
        $this->assertStringContainsString('dash-embed', embed::render_item(gallery::get_item('block_dash:participants'), $context));
    }

    /**
     * An item's background image is served from the gallery images area.
     */
    public function test_render_serves_background_image(): void {
        global $CFG;

        $data = $this->export_users_block();
        $data['files'] = [[
            'area' => 'images',
            'filename' => 'background.jpg',
            'content' => base64_encode(file_get_contents($CFG->dirroot . '/blocks/dash/tests/assets/background.jpg')),
        ]];
        gallery::create_site_item('pictured', 'Pictured', '', $data, ['embeddable' => true]);

        $html = embed::render('pictured', \context_system::instance());

        $this->assertStringContainsString('/block_dash/galleryimages/0/', $html);
        $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'block_dash', 'galleryimages', 0, 'id', false);
        $this->assertCount(1, $files);
    }
}
