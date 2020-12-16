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
 * Dash block class.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\block_builder;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/blocks/dash/lib.php");

/**
 * Dash block class.
 */
class block_dash extends block_base {

    /**
     * Initialize block instance.
     *
     * @throws coding_exception
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_dash');
    }

    /**
     * This block supports configuration fields.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     *
     * @throws coding_exception
     */
    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('newblock', 'block_dash');
        }
    }

    /**
     * Multiple dashes can be added to a single page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Dashes are suitable on all page types.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Return block content. Build dash.
     *
     * @return \stdClass
     */
    public function get_content() {
        global $PAGE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new \stdClass();

        if (block_dash_is_disabled()) {
            $this->content->text = is_siteadmin() ? get_string('disableallmessage', 'block_dash') : '';
            return $this->content;
        }

        try {
            $bb = block_builder::create($this);

            // Conditionally hide the block when empty.
            if (isset($this->config->hide_when_empty) && $this->config->hide_when_empty
                && $bb->get_configuration()->get_data_source()->get_data()->is_empty() && !$PAGE->user_is_editing()) {
                return $this->content;
            }

            $this->content = $bb->get_block_content();
        } catch (\Exception $e) {
            $this->content->text = $OUTPUT->notification($e->getMessage() . $e->getTraceAsString(), 'error');
        }

        return $this->content;
    }

    /**
     * Add block width CSS classes.
     *
     * @return array
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();
        if (isset($this->config->css_class)) {
            $attributes['class'] .= ' ' . $this->config->css_class;
        }
        if (isset($this->config->width)) {
            $attributes['class'] .= ' dash-block-width-' . $this->config->width;
        } else {
            $attributes['class'] .= ' dash-block-width-100';
        }

        if (isset($this->config->preferences['layout'])) {
            $attributes['class'] .= ' ' . str_replace('\\', '-', $this->config->preferences['layout']);
        }

        return $attributes;
    }

    /**
     * Set dash sorting.
     *
     * @param string $fieldname
     * @param string|null $direction
     * @throws coding_exception
     */
    public function set_sort($fieldname, $direction = null) {
        global $USER;

        $key = $USER->id . '_' . $this->instance->id;

        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'sort');

        if (!$cache->has($key)) {
            $sorting = [];
        } else {
            $sorting = $cache->get($key);
        }

        if (isset($sorting[$fieldname]) && !$direction) {
            if ($sorting[$fieldname] == 'asc') {
                $sorting[$fieldname] = 'desc';
            } else {
                $sorting[$fieldname] = 'asc';
            }
        } else {
            if ($direction) {
                $sorting[$fieldname] = $direction;
            } else {
                $sorting[$fieldname] = 'asc';
            }
        }

        $cache->set($key, [$fieldname => $sorting[$fieldname]]);
    }
}


