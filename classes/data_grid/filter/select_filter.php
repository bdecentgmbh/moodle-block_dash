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

abstract class select_filter extends filter
{
    const ALL_OPTION = -1;

    /**
     * @var array
     */
    private $options = [];

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init()
    {
        $this->add_all_option();

        parent::init();
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations()
    {
        return [
            self::OPERATION_EQUAL,
            self::OPERATION_IN_OR_EQUAL,
            self::OPERATION_LIKE,
            self::OPERATION_LIKE_WILDCARD
        ];
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value()
    {
        return null;
    }

    /**
     * Conditionally add an "All" option.
     */
    public function add_all_option()
    {
        $this->add_option(self::ALL_OPTION, get_string('all'));
    }

    /**
     * @param mixed $value
     * @param string $label
     */
    public function add_option($value, $label)
    {
        $this->options[$value] = $label;
    }

    /**
     * Add multiple options.
     *
     * @param $options
     */
    public function add_options($options)
    {
        foreach ($options as $key => $option) {
            $this->options[$key] = $option;
        }
    }

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values()
    {
        $values = parent::get_values();

        // If 'All' was selected.
        if (count($values) == 1 && $values[0] == self::ALL_OPTION) {
            return [];
        }

        return $values;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param \MoodleQuickForm $form
     * @param filter_collection_interface $filter_collection
     * @param string $element_name_prefix
     * @throws \Exception
     */
    public function create_form_element(\MoodleQuickForm &$form, filter_collection_interface $filter_collection,
                                        $element_name_prefix = '')
    {
        $options = $this->options;
        asort($options);

        // If All option is present, send it to top.
        if (isset($options[self::ALL_OPTION])) {
            $options = array(self::ALL_OPTION => $options[self::ALL_OPTION]) + $options;
        }

        $name = $element_name_prefix . $this->get_name();

        $form->addElement('select', $name, $this->get_label(), $options, ['class' => 'chosen-select']);

        parent::create_form_element($form, $filter_collection, $element_name_prefix);
    }
}