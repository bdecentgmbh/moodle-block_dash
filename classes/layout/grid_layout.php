<?php

namespace block_dash\layout;

class grid_layout extends abstract_layout
{
    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_grid';
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination()
    {
        return true;
    }

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility()
    {
        return true;
    }

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering()
    {
        return true;
    }
}