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
 * Pick a gallery item for a Dash block, with a live preview; and preview items on the gallery page.
 *
 * Triggers: data-action="block_dash-gallery-choose" with data-blockid, and
 * data-action="block_dash-gallery-preview" with data-item.
 *
 * @module     block_dash/gallery_picker
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import ModalSaveCancel from 'core/modal_save_cancel';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

let initialised = false;

/**
 * Make a gallery modal use most of the page width, then show it.
 *
 * @param {Modal} modal
 * @returns {Modal}
 */
const showWide = (modal) => {
    modal.getRoot().addClass('block-dash-gallery-modal');
    modal.show();
    return modal;
};

/**
 * Render an item.
 *
 * @param {String} shortname
 * @param {Number} blockid Block it would go into, 0 for the gallery page.
 * @returns {Promise<String>} HTML.
 */
const renderItem = (shortname, blockid) => Ajax.call([{
    methodname: 'block_dash_gallery_preview',
    args: {shortname, blockid},
}])[0].then((result) => result.html);

/**
 * Show the picker for a block.
 *
 * @param {HTMLElement} trigger
 */
const openPicker = async(trigger) => {
    const blockid = parseInt(trigger.dataset.blockid, 10);
    const {items} = await Ajax.call([{methodname: 'block_dash_gallery_list', args: {blockid}}])[0];

    const modal = await ModalSaveCancel.create({
        title: getString('gallerychoose', 'block_dash'),
        body: Templates.render('block_dash/gallery_picker', {items, hasitems: items.length > 0}),
        buttons: {save: getString('galleryuse', 'block_dash')},
        large: true,
        removeOnClose: true,
        returnElement: trigger,
    });
    showWide(modal);
    await modal.getBodyPromise();

    const root = modal.getRoot()[0];
    const save = modal.getFooter()[0].querySelector('[data-action="save"]');
    const preview = root.querySelector('[data-gallery-preview]');
    const description = root.querySelector('[data-gallery-description]');
    let selected = null;
    save.disabled = true;

    root.addEventListener('click', (e) => {
        const option = e.target.closest('[data-gallery-option]');
        if (!option) {
            return;
        }
        root.querySelectorAll('[data-gallery-option]').forEach((other) => {
            other.classList.toggle('active', other === option);
            other.setAttribute('aria-selected', other === option ? 'true' : 'false');
        });
        selected = option.dataset.galleryOption;
        save.disabled = false;
        description.textContent = option.dataset.description;
        renderItem(selected, blockid).then((html) => {
            if (selected === option.dataset.galleryOption) {
                preview.innerHTML = html;
            }
            return html;
        }).catch(Notification.exception);
    });

    const search = root.querySelector('[data-gallery-search]');
    if (search) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            root.querySelectorAll('[data-gallery-option]').forEach((option) => {
                option.hidden = query !== '' && !option.dataset.search.toLowerCase().includes(query);
            });
        });
    }

    modal.getRoot().on(ModalEvents.save, (e) => {
        e.preventDefault();
        if (!selected) {
            return;
        }
        save.disabled = true;
        Ajax.call([{methodname: 'block_dash_gallery_use', args: {blockid, shortname: selected}}])[0]
            .then(() => window.location.reload())
            .catch((error) => {
                save.disabled = false;
                return Notification.exception(error);
            });
    });
};

/**
 * Preview an item on the gallery page.
 *
 * @param {HTMLElement} trigger
 */
const openPreview = async(trigger) => {
    showWide(await Modal.create({
        title: trigger.dataset.title,
        body: renderItem(trigger.dataset.item, 0),
        large: true,
        removeOnClose: true,
        returnElement: trigger,
    }));
};

/**
 * Listen for clicks on gallery triggers on the page.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('click', (e) => {
        const choose = e.target.closest('[data-action="block_dash-gallery-choose"]');
        const preview = e.target.closest('[data-action="block_dash-gallery-preview"]');
        if (choose) {
            e.preventDefault();
            openPicker(choose).catch(Notification.exception);
        } else if (preview) {
            e.preventDefault();
            openPreview(preview).catch(Notification.exception);
        }
    });
};
