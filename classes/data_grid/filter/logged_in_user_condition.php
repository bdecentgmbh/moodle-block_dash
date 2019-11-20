<?php


namespace block_dash\data_grid\filter;


class logged_in_user_condition extends condition
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

    /**
     * @return string
     */
    public function get_label()
    {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('loggedinuser', 'block_dash');
    }

}