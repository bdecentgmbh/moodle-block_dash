<?php
// This file is part of Moodle - http://moodle.org/
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

namespace block_dash\data_grid;

use renderer_base;

class paginator
{
    const TEMPLATE = 'block_dash/paginator';
    const PER_PAGE_DEFAULT = 10;

    protected $per_page;

    /**
     * @var int
     */
    protected $current_page;

    /**
     * @var callable Provided function to get count of items.
     */
    protected $count_function;

    /**
     * @var bool If true, a human readable summary will be displayed above paginator (Showing x of x out of x).
     */
    protected $show_page_summary;

    /**
     * paginator constructor.
     * @param callable $count_function
     * @param int $current_page
     * @param int $per_page
     * @param bool $show_page_summary If true, a human readable summary will be displayed above paginator (Showing x of x out of x).
     * @throws \coding_exception
     */
    public function __construct(callable $count_function, $current_page = 0, $per_page = self::PER_PAGE_DEFAULT,
                                $show_page_summary = true)
    {
        if (!is_int($per_page)) {
            throw new \coding_exception('Per page value must be an integer.');
        }

        $this->per_page = $per_page;
        $this->current_page = $current_page;
        $this->count_function = $count_function;
        $this->show_page_summary = $show_page_summary;
    }

    public function get_template()
    {
        return self::TEMPLATE;
    }

    public function get_limit_from()
    {
        return $this->get_current_page() * $this->per_page;
    }

    public function get_per_page()
    {
        return $this->per_page;
    }

    public function set_per_page($perpage)
    {
        $this->per_page = $perpage;
    }

    /**
     * @return int
     */
    public function get_current_page()
    {
        return $this->current_page;
    }

    public function set_current_page($page)
    {
        $this->current_page = $page;
    }

    /**
     * Get count of all records.
     *
     * @return int
     */
    public function get_record_count()
    {
        $function = $this->count_function;
        return $function();
    }

    /**
     * Get number of pages based on per page and count.
     *
     * @return int
     */
    public function get_page_count()
    {
        $count = $this->get_record_count();
        if ($count > 0) {
            return ceil($count / $this->get_per_page());
        }

        // Default to 0 pages
        return 0;
    }

    public function export_for_template(renderer_base $output)
    {
        $count = $this->get_page_count();
        $front_divider = false;
        $back_divider = false;

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[$i] = [
                'index' => $i,
                'page' => $i,
                'label' => $i+1,
                'active' => $this->get_current_page() == $i
            ];
        }

        // There's some hard coded values here. Maybe at some point clean up this algorithm. But for now it works just fine.
        if ($this->get_current_page() >= 5) {
            for ($i = 1; $i < $this->get_current_page()-2; $i++) {
                unset($items[$i]);
            }
            $front_divider = true;
        }

        if ($this->get_page_count()-1 >= $this->get_current_page()+3) {
            $page_count = $this->get_page_count();
            for ($i = $this->get_current_page()+3; $i < $page_count-2; $i++) {
                unset($items[$i]);
                $back_divider = true;
            }
        }

        if ($front_divider) {
            $items[1] = [
                'label' => '...',
                'disabled' => true
            ];
            ksort($items);
        }

        if ($back_divider) {
            end($items);
            $items[key($items)-1] = [
                'label' => '...',
                'disabled' => true
            ];
            ksort($items);
        }

        // Add previous and next buttons
        array_unshift($items, [
            'label' => get_string('previous'),
            'page' => $this->get_current_page()-1,
            'disabled' => $this->get_current_page() == 0
        ]);

        // Next button
        $items[] = [
            'label' => get_string('next'),
            'page' => $this->get_current_page()+1,
            'disabled' => $this->get_current_page() == $count-1 || empty($count)
        ];

        $record_count = $this->get_record_count();
        $limit_to = (int)$this->get_per_page() + (int)$this->get_limit_from();
        if ($limit_to > $record_count) {
            $limit_to = $record_count;
        }

        // If there's no records hide the summary.
        if ($record_count == 0) {
            $this->show_page_summary = false;
        }

        $summary = get_string('pagination_summary', 'block_dash', [
            'total' => $this->get_record_count(),
            'per_page' => $this->get_per_page(),
            'limit_from' => $this->get_limit_from() + 1,
            'limit_to' => $limit_to]);

        return [
            'pages' => $items,
            'show_page_summary' => $this->show_page_summary,
            'summary' => $summary
        ];
    }
}
