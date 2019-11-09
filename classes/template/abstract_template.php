<?php


namespace block_dash\template;

use block_dash\data_grid\configurable_data_grid;
use block_dash\output\renderer;

abstract class abstract_template implements template_interface
{
    /**
     * @var \context
     */
    private $context;

    protected function __construct(\context $context)
    {
        $this->context = $context;
    }

    public final function get_data_grid()
    {
        $data_grid = new configurable_data_grid($this->get_filter_collection(),
            $this->get_context());
        $data_grid->set_query_template($this->get_query_template());
        $data_grid->set_field_definitions($this->get_available_field_definitions());
        $data_grid->init();

        return $data_grid;
    }

    /**
     * @return \context
     */
    public function get_context()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public final function render()
    {
        global $PAGE, $OUTPUT;

        $output = '';

        $data_grid = $this->get_data_grid();

        try {
            $data = $data_grid->get_data();
        } catch (\Exception $e) {
            $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
            if (is_siteadmin()) {
                $error .= \html_writer::tag('p', $e->getMessage());
            }

            $output .= $OUTPUT->notification($error, 'error');
        }

        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('block_dash');

        if (isset($data)) {
            try {
                $output .= $renderer->render_from_template_string($this->get_mustache_template_name(), [
                    'data' => $data
                ]);
            } catch (\Exception $e) {
                $error = \html_writer::tag('p', get_string('parseerror', 'block_dash'));
                if (is_siteadmin()) {
                    $error .= \html_writer::tag('p', $e->getMessage());
                }

                $output .= $OUTPUT->notification($error, 'error');
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_missing';
    }
}
