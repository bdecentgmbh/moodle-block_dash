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
 * A field is a simple container for a single value within a row/collection.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data;
/**
 * A field is a simple container for a single value within a row/collection.
 *
 * @package block_dash
 */
class field implements field_interface {
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed|string
     */
    private $value;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $visible;

    /**
     * @var bool Whether the value is markup built by an attribute that escapes what it contains.
     */
    private $ishtml;

    /**
     * Create a new field.
     *
     * @param string $name
     * @param string $value
     * @param string $visible
     * @param string $label
     * @param bool $ishtml Whether the value is markup built by an attribute that escapes what it contains, see
     *                     {@see \block_dash\local\data_grid\field\attribute\abstract_field_attribute::returns_html()}.
     *                     Other values are cleaned when they are output.
     */
    public function __construct($name, $value, $visible, $label = '', bool $ishtml = false) {
        $this->name = $name;
        $this->value = $value;
        $this->visible = $visible;
        $this->label = $label;
        $this->ishtml = $ishtml;
    }

    /**
     * Get field name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get field value.
     *
     * @return mixed|string
     */
    public function get_value() {
        if ($this->ishtml || !is_string($this->value) || is_numeric($this->value) || $this->value === '') {
            // Markup of an attribute, numbers, and objects that escape themselves, such as moodle_url.
            return $this->value;
        }
        if (preg_match('~^https?://~i', $this->value) && filter_var($this->value, FILTER_VALIDATE_URL)) {
            return self::escape_url($this->value);
        }
        // Values from the database can contain anything their authors typed, so they are cleaned like any user text.
        return format_text($this->value, FORMAT_HTML);
    }

    /**
     * Escapes a URL for HTML text, HTML attributes and CSS url() alike.
     *
     * @param string $url
     * @return string
     */
    protected static function escape_url(string $url): string {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = str_replace(['"', "'", '<', '>', '(', ')', '\\', '`'], ['%22', '%27', '%3C', '%3E', '%28', '%29', '%5C', '%60'], $url);
        return s($url);
    }

    /**
     * Get label of field definition.
     *
     * @return string|null
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Check the field is visible.
     * @return bool
     */
    public function is_visible() {
        return $this->visible;
    }
}
