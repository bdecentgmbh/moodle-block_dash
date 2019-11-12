<?php

namespace block_dash;

use block_dash\configuration\configuration_interface;
use block_dash\configuration\configuration;
use block_dash\data_grid\configurable_data_grid;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection;
use block_dash\output\renderer;

class block_builder
{
    /**
     * @var configuration_interface
     */
    private $configuration;

    protected function __construct(\block_base $block_instance)
    {
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
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public static function get_all_field_definitions()
    {
        $field_definitions = [];

        if ($pluginsfunction = get_plugins_with_function('register_field_definitions')) {
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginfunction) {
                    foreach ($pluginfunction() as $field_definition) {
                        if (!$field_definition instanceof field_definition_interface) {
                            throw new \coding_exception('Invalid field definition registered. Must implement field_definition_interface');
                        }
                        $field_definitions[] = $field_definition;
                    }
                }
            }
        }

        return $field_definitions;
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
