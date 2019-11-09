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

    /**
     * @var \block_base
     */
    private $block_instance;

    /**
     * @var data_grid_interface
     */
    private $data_grid;

    protected function __construct(\block_base $block_instance)
    {
        $this->configuration = configuration::create_from_instance($block_instance);
    }

    public function build_data_grid()
    {
        $filter_collection = new filter_collection();

        $this->data_grid = new configurable_data_grid($filter_collection, $this->configuration->get_context());
        $this->data_grid->set_query_template($this->configuration->get_sql());
        $this->data_grid->set_field_definitions(self::get_all_field_definitions());
        $this->data_grid->init();
    }

    /**
     * @return \stdClass
     */
    public function get_block_content()
    {
        global $OUTPUT, $PAGE, $CFG;

        $text = '';

        if ($this->configuration->is_fully_configured()) {

            $this->build_data_grid();

            try {
                $data = $this->data_grid->get_data();
            } catch (\Exception $e) {
                $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
                if (is_siteadmin()) {
                    $error .= \html_writer::tag('p', $e->getMessage());
                }

                $text .= $OUTPUT->notification($error, 'error');
            }

            /** @var renderer $renderer */
            $renderer = $PAGE->get_renderer('block_dash');

            if (isset($data)) {
                try {
                    $text .= $OUTPUT->render_from_template('block_dash/layout_grid', [
                        'data' => $data
                    ]);
                } catch (\Exception $e) {
                    $error = \html_writer::tag('p', get_string('parseerror', 'block_dash'));
                    if (is_siteadmin()) {
                        $error .= \html_writer::tag('p', $e->getMessage());
                    }

                    $text .= $OUTPUT->notification($error, 'error');
                }
            }
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
}
