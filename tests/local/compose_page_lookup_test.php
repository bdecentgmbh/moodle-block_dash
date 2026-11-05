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
 * Tests for recognising blocks on a Compose page.
 *
 * @package   block_dash
 * @copyright 2026 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;

/**
 * Tests for recognising blocks on a Compose page.
 *
 * A block on a Compose page that is not recognised is fetched behind a login check, which answers "You are not
 * logged in" to a visitor of the page as soon as the paginator reloads the block.
 *
 * @group block_dash
 * @group bdecent
 */
final class compose_page_lookup_test extends \advanced_testcase {
    /** @var \stdClass A block on the page /p/members. */
    protected \stdClass $block;

    /**
     * Set up the test.
     */
    public function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
        $CFG->forcelogin = 0;
        $this->block = (object) ['pagetypepattern' => 'dashaddon-pages-members'];
    }

    /**
     * Skip the test unless the Pages add-on of Compose is installed.
     */
    protected function require_pages_addon(): void {
        if (!class_exists('\composeaddon_pages\model\page')) {
            $this->markTestSkipped('The Pages add-on of Compose (composeaddon_pages) is not installed.');
        }
        set_config('enabled', 1, 'composeaddon_pages');
    }

    /**
     * A block on a Compose page is public.
     *
     * @covers \block_dash\local\compose_page_lookup::is_public_page
     */
    public function test_block_on_a_page_is_public(): void {
        $this->require_pages_addon();

        $this->assertTrue(compose_page_lookup::is_public_page($this->block));
    }

    /**
     * A block that is not on a Compose page is not public.
     *
     * @covers \block_dash\local\compose_page_lookup::is_public_page
     */
    public function test_block_elsewhere_is_not_public(): void {
        $this->assertFalse(compose_page_lookup::is_public_page((object) ['pagetypepattern' => 'my-index']));
        $this->assertFalse(compose_page_lookup::is_public_page(
            (object) ['pagetypepattern' => 'course-view-dashaddon-pages-members']
        ));
    }

    /**
     * A block on a Compose page is not public while the site forces login, as the page then requires a login too.
     *
     * @covers \block_dash\local\compose_page_lookup::is_public_page
     */
    public function test_block_on_a_page_is_not_public_when_the_site_forces_login(): void {
        global $CFG;

        $CFG->forcelogin = 1;

        $this->assertFalse(compose_page_lookup::is_public_page($this->block));
    }

    /**
     * A block on a Compose page is not public while the Pages add-on is switched off, as the page then does not answer.
     *
     * @covers \block_dash\local\compose_page_lookup::is_public_page
     */
    public function test_block_on_a_page_is_not_public_while_pages_is_switched_off(): void {
        $this->require_pages_addon();
        set_config('enabled', 0, 'composeaddon_pages');

        $this->assertFalse(compose_page_lookup::is_public_page($this->block));
    }
}
