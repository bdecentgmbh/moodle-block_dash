<?php

namespace block_dash;

use block_dash\configuration\configuration_interface;
use block_dash\configuration\configuration;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\template\custom_template;
use block_dash\template\template_interface;

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

    private static $templates = null;

    private static $all_field_definitions = null;

    protected function __construct(\block_base $block_instance)
    {
        $this->block_instance = $block_instance;
        $this->configuration = configuration::create_from_instance($block_instance);
    }

    /**
     * @return \stdClass
     */
    public function get_block_content()
    {
        $text = '';

        if ($this->configuration->is_fully_configured()) {
            $text .= $this->configuration->get_template()->render();
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

    /**
     * @return template_interface[]
     * @throws \coding_exception | \dml_exception
     */
    public static function get_all_templates()
    {
        global $DB;

        if (is_null(self::$templates)) {
            self::$templates = [];
            if ($pluginsfunction = get_plugins_with_function('register_templates')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $template) {
                            if (!$template instanceof template_interface) {
                                throw new \coding_exception('Invalid field definition registered. Must implement field_definition_interface');
                            }
                            self::$templates[] = $template;
                        }
                    }
                }
            }

            foreach ($DB->get_records('dash_template') as $record) {
                self::$templates[] = custom_template::create($record, \context_system::instance());
            }
        }

        return self::$templates;
    }

    /**
     * @param string $idnumber Template idnumber to retrieve.
     * @return template_interface
     * @throws \coding_exception | \dml_exception
     */
    public static function get_template($idnumber)
    {
        foreach (self::get_all_templates() as $template) {
            if ($template->get_idnumber() == $idnumber) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public static function get_all_field_definitions()
    {
        if (is_null(self::$all_field_definitions)) {
            self::$all_field_definitions = [];
            if ($pluginsfunction = get_plugins_with_function('register_field_definitions')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $field_definition) {
                            if (!$field_definition instanceof field_definition_interface) {
                                throw new \coding_exception('Invalid field definition registered. Must implement field_definition_interface');
                            }
                            self::$all_field_definitions[] = $field_definition;
                        }
                    }
                }
            }
        }

        return self::$all_field_definitions;
    }

    /**
     * @param string[] $names Field definition names to retrieve.
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public static function get_field_definitions(array $names)
    {
        $field_definitions = [];

        foreach (self::get_all_field_definitions() as $field_definition) {
            if (in_array($field_definition->get_name(), $names)) {
                $field_definitions[] = $field_definition;
            }
        }

        return $field_definitions;
    }

    /**
     * @param string $name Field definition name to retrieve.
     * @return field_definition_interface
     * @throws \coding_exception
     */
    public static function get_field_definition($name)
    {
        foreach (self::get_all_field_definitions() as $field_definition) {
            if ($field_definition->get_name() == $name) {
                return $field_definition;
            }
        }

        return null;
    }
}
