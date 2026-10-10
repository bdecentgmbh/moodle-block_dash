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
 * Unit tests for gallery items embedded outside a block region.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\gallery\embed;
use block_dash\gallery\embed_block;
use block_dash\gallery\gallery;

/**
 * Unit tests for gallery items embedded outside a block region.
 *
 * @covers \block_dash\gallery\embed
 * @covers \block_dash\gallery\embed_block
 * @covers \block_dash\external::get_block_content
 */
final class gallery_embed_test extends \advanced_testcase {
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
     * Ask the web service to render an embedded item again, as paging, filters and sorting do.
     *
     * @param string $shortname
     * @param \context $context
     * @param bool $pagination Ask for the pagination only.
     * @return array
     */
    private function fetch(string $shortname, \context $context, bool $pagination = false): array {
        if ($pagination) {
            return external::get_block_pagination(-1, '[]', 0, null, null, '', 0, $shortname, $context->id);
        }
        return external::get_block_content(-1, '[]', 0, null, null, '', 0, $shortname, $context->id);
    }

    /**
     * Embeds carry the gallery item to the JavaScript that drives paging, filters and sorting.
     */
    public function test_render_starts_javascript_with_the_item(): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $html = embed::render('block_dash:my-learning', \context_course::instance($course->id));

        $this->assertMatchesRegularExpression('/id="inst-\d+"/', $html);
        $this->assertStringContainsString('"block_dash:my-learning"', $PAGE->requires->get_end_code());
    }

    /**
     * The web service renders an embeddable item again in its context.
     *
     * The legacy external class loads lib/externallib.php, which needs its own process.
     *
     * @runInSeparateProcess
     */
    public function test_web_service_renders_embedded_item(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->assertArrayHasKey('html', $this->fetch('block_dash:my-learning', $context));
        $this->assertArrayHasKey('html', $this->fetch('block_dash:my-learning', $context, true));
    }

    /**
     * Items that aren't embeddable can't be fetched through the web service.
     *
     * The legacy external class loads lib/externallib.php, which needs its own process.
     *
     * @runInSeparateProcess
     */
    public function test_web_service_refuses_items_not_embeddable(): void {
        $course = $this->getDataGenerator()->create_course();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('galleryerrornotusable', 'block_dash'));
        $this->fetch('block_dash:participants', \context_course::instance($course->id));
    }

    /**
     * The web service applies the same access rules as the page the item is embedded in.
     *
     * The legacy external class loads lib/externallib.php, which needs its own process.
     *
     * @runInSeparateProcess
     */
    public function test_web_service_checks_the_context(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\require_login_exception::class);
        $this->fetch('block_dash:my-learning', \context_course::instance($course->id));
    }

    /**
     * Embeds offer no data download, which would need a block row.
     */
    public function test_embed_disables_data_download(): void {
        global $PAGE;

        $data = gallery::get_item('block_dash:participants')->get_data();
        $data['config']['preferences']['exportdata'] = 1;
        $item = gallery::create_site_item('downloadable', 'Downloadable', '', $data, ['embeddable' => true]);

        $block = embed_block::create($item, \context_system::instance(), $PAGE);

        $this->assertSame(0, $block->config->preferences['exportdata']);
        $this->assertLessThan(0, $block->instance->id);
        $this->assertSame(-7, embed_block::create($item, \context_system::instance(), $PAGE, -7)->instance->id);
    }
}
