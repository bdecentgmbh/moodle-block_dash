<?php


namespace block_dash\template;

use block_dash\data_grid\configurable_data_grid;
use block_dash\data_grid\data_grid_interface;
use block_dash\output\renderer;
use block_dash\data_grid\filter\form\filter_form;

abstract class abstract_template implements template_interface
{
    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_grid_interface
     */
    private $data_grid;

    public function __construct(\context $context)
    {
        $this->context = $context;
    }

    public final function get_data_grid()
    {
        if (is_null($this->data_grid)) {
            $this->data_grid = new configurable_data_grid($this->get_context());
            $this->data_grid->set_query_template($this->get_query_template());
            $this->data_grid->set_field_definitions($this->get_available_field_definitions());
            $this->data_grid->init();
        }

        return $this->data_grid;
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
        global $PAGE, $OUTPUT, $USER;

        $output = '';

        $data_grid = $this->get_data_grid();
        $filter_collection = $this->get_filter_collection();

        $filter_form = new filter_form($filter_collection);

        $filter_values = new \stdClass();

        if ($filter_form->is_cancelled()) {
            $filter_collection->delete_cache($USER);
        }

        if ($data = $filter_form->get_data()) {
            // Delete any old filter data.
            $filter_collection->delete_cache($USER);

            // Clean filter data.
            foreach ($data as $key => $value) {
                if (is_null($value) || $value == '') {
                    unset($data->$key);
                }
            }

            $filter_values = $data;
        } else {
            if ($filter_data = $filter_collection->get_cache($USER)) {
                $filter_values = (object)$filter_collection->get_cache($USER);
            }
        }

        // Allow filter values to be submitted from query parameters.
        foreach ($_GET as $param => $value) {
            if (strpos($param, 'filter_') == 0) {
                $filter_name = str_replace('filter_', '', $param);
                $filter_values->$filter_name = $value;
            }
        }

        // Set data on form now that everything is aggregated.
        $filter_form->set_data($filter_values);

        foreach ($filter_values as $key => $data) {
            $filter_collection->apply_filter($key, $data);
        }

        $filter_collection->cache($USER);

        ob_start();
        $filter_form->display();
        $filter_form_html = ob_get_clean();

        try {
            $data = $data_grid->get_data($filter_collection);
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
                $output .= $renderer->render_from_template($this->get_mustache_template_name(), [
                    'filter_form_html' => $filter_form_html,
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

    /**
     * Get unique idnumber for this template.
     *
     * @return string
     */
    public function get_idnumber()
    {
        return self::class;
    }
}
