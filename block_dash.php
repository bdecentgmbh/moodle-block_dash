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
 * Dash block
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\block_builder;

class block_dash extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_dash');
    }

    public function has_config() {
        return true;
    }

    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('newblock', 'block_dash');
        }
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function get_content()
    {
        global $PAGE;

        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new \stdClass();

        $bb = block_builder::create($this);

        // Conditionally hide the block when empty.
        if (isset($this->config->hide_when_empty) && $this->config->hide_when_empty
            && $bb->get_configuration()->get_data_source()->get_data()->is_empty() && !$PAGE->user_is_editing()) {
           return $this->content;
        }

        $this->content = $bb->get_block_content();

        return $this->content;
    }

    public function html_attributes()
    {
        $attributes = parent::html_attributes();
        if (isset($this->config->css_class)) {
            $attributes['class'] .= ' ' . $this->config->css_class;
        }
        return $attributes;
    }
}


