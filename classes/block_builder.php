<?php

namespace block_dash;

use block_dash\configuration\configuration_interface;
use block_dash\configuration\configuration;

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
        global $OUTPUT;

        $data = $this->configuration->get_data_source()->get_data_collection();

        $content = new \stdClass();
        $content->text = $OUTPUT->render_from_template($this->configuration->get_template(), [
            'data' => $data
        ]);

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
