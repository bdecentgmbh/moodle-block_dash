<?php

namespace block_dash;

use block_dash\configuration\configuration_interface;
use block_dash\configuration\configuration;
use block_dash\data_grid\data_grid;
use block_dash\data_grid\data_grid_interface;
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

        $this->data_grid = new data_grid($filter_collection, $this->configuration->get_context());
    }

    /**
     * @return \stdClass
     */
    public function get_block_content()
    {
        global $OUTPUT, $PAGE, $CFG;

        try {
            $data = $this->configuration->get_data_source()->get_data_collection();
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }

        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('block_dash');

        try {
            $text = $renderer->render_from_template_string($this->configuration->get_template(), [
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $error = \html_writer::tag('p', get_string('parseerror', 'block_dash'));
            if (is_siteadmin()) {
                $error .= \html_writer::tag('p', $e->getMessage());
            }

            $text = $OUTPUT->notification($error, 'error');
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
}
