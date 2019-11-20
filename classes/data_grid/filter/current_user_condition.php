<?php


namespace block_dash\data_grid\filter;


class current_user_condition extends condition
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
        global $USER;

        return [$USER->id];
    }

}