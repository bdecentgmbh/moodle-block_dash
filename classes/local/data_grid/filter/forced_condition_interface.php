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
 * Marker interface for conditions that must never be removed from a data source.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

/**
 * Conditions implementing this interface always apply, regardless of block preferences.
 *
 * Regular filters and conditions are stripped in before_data() unless they are enabled
 * in the block instance preferences. A forced condition (for example a tenant restriction
 * injected via the dash_augment_filter_collection callback) must survive that stripping,
 * otherwise it would silently fail open.
 */
interface forced_condition_interface {
}
