<?php


namespace block_dash\configuration;

use block_dash\template\custom_template;
use block_dash\template\placeholder_template;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        global $DB;

        $template = null;
        if (isset($block_instance->config->template_idnumber)) {
            if ($record = $DB->get_record('dash_template', ['idnumber' => $block_instance->config->template_idnumber])) {
                $template = custom_template::create($record, $block_instance->context);
            }
        }

        if (is_null($template)) {
            $template = new placeholder_template(\context_system::instance());
        }

        return new configuration($block_instance->context, $template);
    }
}
