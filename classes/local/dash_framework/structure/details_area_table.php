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
 * Virtual table providing details area fields for all data sources.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\structure;

use lang_string;
use block_dash\local\data_grid\field\attribute\details_button_attribute;
use block_dash\local\data_grid\field\attribute\details_link_attribute;

/**
 * A virtual table that provides the "Details button" and "Details link" fields.
 *
 * This table does not map to a real database table. It is injected by the layout
 * so that every data source automatically offers fields to open the details area.
 *
 * @package block_dash
 */
class details_area_table extends table {

    /** @var string Table alias used for field prefixes. */
    const ALIAS = 'da';

    /** @var int Block instance ID for unique identification on multi-block pages. */
    private $blockinstanceid = 0;

    /**
     * Construct the virtual details area table.
     *
     * @param int $blockinstanceid The block_instances.id (0 if unknown).
     */
    public function __construct(int $blockinstanceid = 0) {
        parent::__construct('dash_details_area', self::ALIAS);
        $this->blockinstanceid = $blockinstanceid;
    }

    /**
     * Get human-readable title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('details_area', 'block_dash');
    }

    /**
     * Get details area fields.
     *
     * These are virtual fields (SQL select literal '1') with dedicated attributes
     * that render the appropriate UI element for opening the details area.
     *
     * @return field_interface[]
     */
    public function get_fields(): array {
        $attropts = ['blockinstanceid' => $this->blockinstanceid];
        return [
            new field(
                'details_button',
                new lang_string('details_button', 'block_dash'),
                $this,
                "'1'",
                [new details_button_attribute($attropts)]
            ),
            new field(
                'details_link',
                new lang_string('details_link', 'block_dash'),
                $this,
                "'1'",
                [new details_link_attribute($attropts)]
            ),
        ];
    }

    /**
     * No additional joins needed for virtual fields.
     *
     * @return array|null
     */
    public function get_additional_joins(): ?array {
        return [];
    }
}
