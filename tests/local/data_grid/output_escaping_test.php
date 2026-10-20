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

namespace block_dash\local\data_grid;

use block_dash\local\dash_framework\structure\field as structure_field;
use block_dash\local\dash_framework\structure\user_table;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\field\attribute\abstract_field_attribute;
use block_dash\local\data_grid\field\attribute\details_button_attribute;
use block_dash\local\data_grid\field\attribute\image_attribute;
use block_dash\local\data_grid\field\attribute\link_attribute;
use block_dash\local\data_grid\field\attribute\linked_data_attribute;

/**
 * Tests that values from the database reach the page cleaned, and markup built by attributes reaches it intact.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dash\local\data_grid\data\field
 * @covers     \block_dash\local\data_grid\field\attribute\abstract_field_attribute
 */
final class output_escaping_test extends \advanced_testcase {
    /** @var string Markup that runs script when it is output as HTML. */
    const PAYLOAD = '<img src="x" onerror="alert(1)"><script>alert(2)</script>';

    public function test_raw_values_are_cleaned(): void {
        $value = (new field('description', 'Hello ' . self::PAYLOAD, true))->get_value();

        $this->assertStringContainsString('Hello', $value);
        $this->assertStringNotContainsString('onerror', $value);
        $this->assertStringNotContainsString('<script', $value);
    }

    public function test_attribute_markup_is_kept(): void {
        $markup = (new details_button_attribute())->transform_data(1, (object) ['id' => 5]);
        $value = (new field('details', $markup, true, '', true))->get_value();

        $this->assertSame($markup, $value);
        $this->assertStringContainsString('data-action="open-details-modal"', $value);
    }

    public function test_numbers_and_urls(): void {
        $this->assertSame('42', (new field('n', '42', true))->get_value());
        $this->assertSame(
            'https://example.com/a?b=1&amp;c=2',
            (new field('u', 'https://example.com/a?b=1&c=2', true))->get_value()
        );

        // Valid as a URL, yet it would end the attribute it is put into.
        $value = (new field('u', 'https://example.com/"onmouseover="alert(1)', true))->get_value();
        $this->assertStringNotContainsString('"', $value);
    }

    public function test_link_label_from_the_record_is_escaped(): void {
        $attribute = new link_attribute(['label_field' => 'name']);
        $html = $attribute->transform_data('https://example.com/', (object) ['name' => self::PAYLOAD]);

        $this->assertTrue($attribute->returns_html());
        $this->assertStringStartsWith('<a href="https://example.com/"', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_link_needs_a_web_address(): void {
        $attribute = new link_attribute(['label_field' => 'name']);
        $html = $attribute->transform_data('javascript:alert(1)', (object) ['name' => 'Click']);

        $this->assertSame('Click', $html);
    }

    public function test_linked_data_escapes_text_but_keeps_markup_of_earlier_attributes(): void {
        $url = new \moodle_url('/user/profile.php', ['id' => 'u_id']);

        $namefield = new structure_field('name', new \lang_string('fullname'), new user_table(), null, [
            new linked_data_attribute(['url' => $url]),
        ]);
        $html = $namefield->transform_data(self::PAYLOAD, (object) ['u_id' => 3]);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script', $html);

        $picturefield = new structure_field('picture', new \lang_string('pictureofuser'), new user_table(), null, [
            new image_attribute(['title' => 'Picture']),
            new linked_data_attribute(['url' => $url]),
        ]);
        $html = $picturefield->transform_data('https://example.com/picture.png', (object) ['u_id' => 3]);
        $this->assertMatchesRegularExpression('~^<a href="[^"]+"><img [^>]*src="https://example.com/picture.png"~', $html);
        $this->assertTrue(abstract_field_attribute::any_returns_html($picturefield->get_attributes()));
    }

    public function test_groups_widget_template_escapes_names(): void {
        global $OUTPUT;

        $html = $OUTPUT->render_from_template('block_dash/widget_groups', [
            'data' => [
                'viewgroups' => true,
                'groups' => [[
                    'id' => 1,
                    'name' => 'Group',
                    'plainname' => 'Group" data-x="1',
                    'course' => 'Course',
                    'members' => [['fullname' => 'a" onload="alert(1)" x="', 'profileimage' => 'https://example.com/p.png']],
                ]],
                'leavegroup' => true,
                'viewmembers' => true,
            ],
        ]);

        $this->assertStringNotContainsString('onload="alert(1)"', $html);
        $this->assertStringNotContainsString('data-x="1"', $html);
    }
}
