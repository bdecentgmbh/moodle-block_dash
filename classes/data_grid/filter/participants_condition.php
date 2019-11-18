<?php


namespace block_dash\data_grid\filter;


class participants_condition extends condition
{
    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values()
    {
        if (!$course_context = $this->get_context()->get_course_context(false)) {
            return [null];
        }

        return [$course_context->instanceid];
    }

}