<?php
// This file is part of The Bootstrap Moodle theme
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\filter;

class choice_filter extends select_filter
{
    private $choices;

    public function __construct(\stdClass $user, array $choices, $field_name, $label,
                                $required = self::NOT_REQUIRED, $operation = self::OPERATION_IN_OR_EQUAL,
                                $all_behavior = self::ALL_BEHAVIOR_RETURN_LIST, $include_all_option = true)
    {
        $this->choices = $choices;

        parent::__construct($user, $field_name, $label, $required, $operation, $all_behavior, $include_all_option);
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value()
    {
        if (count($this->choices) > 1) {
            return self::ALL_OPTION;
        } else if (count($this->choices) == 1) {
            return array_keys($this->choices)[0];
        }
        return null;
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init()
    {
        if (count($this->choices) > 1) {
            $this->add_all_option();
        }

        foreach ($this->choices as $key => $value) {
            $this->add_option($key, $value);
        }

        parent::init();
    }
}