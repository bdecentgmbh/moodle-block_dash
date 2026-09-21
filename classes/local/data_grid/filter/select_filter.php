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
 * Class select_filter.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

use block_dash\local\layout\cards_layout;
use block_dash\local\layout\cards_masonry_layout;
use block_dash\local\layout\cards_slider_layout;
use block_dash\local\layout\layout_factory;

/**
 * Class select_filter.
 *
 * @package block_dash
 */
abstract class select_filter extends filter {
    /**
     * All option value.
     */
    const ALL_OPTION = -1;

    /**
     * Default for the highest number of options that are still displayed as buttons.
     */
    const DEFAULT_BUTTONS_COUNT = 4;

    /**
     * Default for the layouts that display filters with few options as buttons.
     */
    const DEFAULT_BUTTONS_LAYOUTS = [
        cards_layout::class,
        cards_slider_layout::class,
        cards_masonry_layout::class,
    ];

    /**
     * Select options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        $this->add_all_option();

        parent::init();
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations() {
        return [
            self::OPERATION_EQUAL,
            self::OPERATION_IN_OR_EQUAL,
            self::OPERATION_LIKE,
            self::OPERATION_LIKE_WILDCARD,
        ];
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value() {
        return null;
    }

    /**
     * Conditionally add an "All" option.
     * @throws \coding_exception
     */
    public function add_all_option() {
        $this->add_option(self::ALL_OPTION, get_string('all') . ' ' . $this->get_label());
    }

    /**
     * Add select option.
     *
     * @param mixed $value
     * @param string $label
     */
    public function add_option($value, $label) {
        $this->options[$value] = format_string($label, false);
    }

    /**
     * Add multiple options.
     *
     * @param array $options
     */
    public function add_options($options) {
        foreach ($options as $key => $option) {
            $this->options[$key] = format_string($option, false);
        }
    }

    /**
     * Get selected options.
     * @return array
     */
    public function get_selected_options() {
        // Return raw values.
        return parent::get_values();
    }

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values() {
        $values = parent::get_values();

        // If 'All' was selected.
        if (count($values) == 1 && $values[0] == self::ALL_OPTION) {
            return [];
        }

        return $values;
    }

    /**
     * Return the option values that should be visible in this filter's dropdown.
     *
     * @param filter_collection_interface $filtercollection
     * @return array|null Valid option values, or null to show all options.
     */
    protected function get_active_option_values(filter_collection_interface $filtercollection): ?array {
        return null;
    }

    /**
     * Whether the options are displayed as buttons instead of a select box.
     *
     * The layout of the block has to be enabled for buttons, and there must be no more options than the configured
     * maximum. The "All" option is not counted, as it is not displayed as a button.
     *
     * @param filter_collection_interface $filtercollection
     * @param array $options The visible options, keyed by value.
     * @return bool
     */
    protected function display_as_buttons(filter_collection_interface $filtercollection, array $options): bool {
        $maxcount = get_config('block_dash', 'filterbuttonscount');
        $maxcount = ($maxcount === false) ? self::DEFAULT_BUTTONS_COUNT : (int) $maxcount;

        $layouts = get_config('block_dash', 'filterbuttonslayouts');
        $layouts = ($layouts === false) ? self::DEFAULT_BUTTONS_LAYOUTS : array_filter(explode(',', $layouts));

        $layout = layout_factory::normalise_identifier((string) ($filtercollection->layout ?? ''));
        if ($maxcount <= 0 || !in_array($layout, $layouts)) {
            return false;
        }

        $buttons = $options;
        unset($buttons[self::ALL_OPTION]);

        return count($options) > 1 && count($buttons) <= $maxcount;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filtercollection
     * @param string $elementnameprefix
     * @throws \Exception
     * @return string
     */
    public function create_form_element(
        filter_collection_interface $filtercollection,
        $elementnameprefix = ''
    ) {
        global $OUTPUT;

        $activevalues = $this->get_active_option_values($filtercollection);
        $options = array_filter($this->options);

        if ($activevalues !== null) {
            $alloption = isset($options[self::ALL_OPTION]) ? [self::ALL_OPTION => $options[self::ALL_OPTION]] : [];
            $options = $alloption + array_intersect_key($options, array_flip($activevalues));
        }

        $tags = $this->display_as_buttons($filtercollection, $options);

        // If All option is present, send it to top.
        if (isset($options[self::ALL_OPTION])) {
            $options = [self::ALL_OPTION => $options[self::ALL_OPTION]] + $options;

            if (isset($options[self::ALL_OPTION]) && $tags) {
                $expstring = explode(" ", $options[self::ALL_OPTION]);
                if (isset($expstring[1])) {
                    array_shift($expstring); // Remove first string.
                    $selectlabel = implode(" ", $expstring);
                }
                unset($options[self::ALL_OPTION]);
            }
        }

        $newoptions = [];
        foreach ($options as $value => $label) {
            $newoptions[] = ['value' => $value, 'label' => $label, 'selected' => in_array($value, $this->get_selected_options())];
        }

        $name = $elementnameprefix . $this->get_name();
        return $OUTPUT->render_from_template('block_dash/filter_select', [
            'name' => $name,
            'options' => $newoptions,
            'multiple' => true,
            'tabs' => $tags,
            'label' => $selectlabel ?? '',
        ]);
    }
}
