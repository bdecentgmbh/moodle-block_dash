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
 * Open the import form when an Import link or button of a Dash block is clicked.
 *
 * @module     block_dash/import_block
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getString} from 'core/str';

let initialised = false;

/**
 * Listen for clicks on Import links and buttons on the page.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-action="block_dash-import"]');
        if (!trigger) {
            return;
        }
        e.preventDefault();

        const modal = new ModalForm({
            formClass: 'block_dash\\form\\import_block',
            args: {blockid: trigger.dataset.blockid},
            modalConfig: {title: getString('importblocktitle', 'block_dash')},
            saveButtonText: getString('importblock', 'block_dash'),
            returnFocus: trigger,
        });
        modal.addEventListener(modal.events.FORM_SUBMITTED, () => window.location.reload());
        modal.show();
    });
};
