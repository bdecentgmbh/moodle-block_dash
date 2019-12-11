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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\configuration\configuration_interface;
use block_dash\configuration\configuration;
use block_dash\output\renderer;

class block_builder
{
    /**
     * @var configuration_interface
     */
    private $configuration;

    /**
     * @var \block_base
     */
    private $block_instance;

    protected function __construct(\block_base $block_instance)
    {
        $this->block_instance = $block_instance;
        $this->configuration = configuration::create_from_instance($block_instance);
    }

    /**
     * @return configuration_interface
     */
    public function get_configuration()
    {
        return $this->configuration;
    }

    /**
     * @return \stdClass
     */
    public function get_block_content()
    {
        global $OUTPUT, $PAGE;

        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('block_dash');

        $text = '';

        if ($this->configuration->is_fully_configured()) {
            $bb = block_builder::create($this->block_instance);

            $bb->get_configuration()->get_data_source()->get_data_grid()->get_paginator()->set_current_page(0);

            $text .= $OUTPUT->render_from_template('block_dash/block', [
                'preloaded' => $renderer->render_data_source($bb->get_configuration()->get_data_source()),
                'block_instance_id' => $this->block_instance->instance->id,
                'block_context_id' => $this->block_instance->context->id,
                'editing' => $PAGE->user_is_editing() &&
                    has_capability('block/dash:addinstance', $this->block_instance->context)
            ]);
        } else {
            $text .= \html_writer::tag('p', get_string('editthisblock', 'block_dash'));
        }

        $content = new \stdClass();
        $content->text = $text;

        if (isset($this->block_instance->config->header_content)) {
            $content->text = format_text($this->block_instance->config->header_content['text'],
                $this->block_instance->config->header_content['format']) . $content->text;
        }

        if (isset($this->block_instance->config->footer_content)) {
            $content->footer = format_text($this->block_instance->config->footer_content['text'],
                $this->block_instance->config->footer_content['format']);
        }

        return $content;
    }

    /**
     * @param \block_base $block_instance
     * @return block_builder
     */
    public static function create(\block_base $block_instance)
    {
        return new block_builder($block_instance);
    }
}
