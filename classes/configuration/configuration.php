<?php


namespace block_dash\configuration;

use block_dash\template\placeholder_template;
use block_dash\template\template_factory;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        $parentcontext = \context::instance_by_id($block_instance->instance->parentcontextid);

        $template = null;
        if (isset($block_instance->config->template_idnumber)) {
            $template = template_factory::get_template($block_instance->config->template_idnumber, $parentcontext);
        }

        if (is_null($template)) {
            $template = new placeholder_template($parentcontext);
        }

        if (isset($block_instance->config->preferences) && is_array($block_instance->config->preferences)) {
            $template->set_preferences($block_instance->config->preferences);
        }

        return new configuration($parentcontext, $template);
    }
}
