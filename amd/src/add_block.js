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
 * Dash's "Add a block" chooser: the blocks that can be added and the Dash gallery, in one list.
 *
 * Selecting a block or a gallery item shows it on the right, gallery items with a live preview, and
 * "Add to page" adds it. Blocks are added as in core_block/add_modal, those with a form after it.
 * A gallery item adds a Dash block through core's URL for adding blocks, naming the item in an
 * extra parameter that the new block imports.
 *
 * Trigger: data-action="block_dash-addblock" with data-url, data-pagehash and data-blockregion.
 *
 * @module     block_dash/add_block
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import ModalSaveCancel from 'core/modal_save_cancel';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {bindPicker, renderItem, showWide} from 'block_dash/gallery_picker';
import {getString} from 'core/str';

let initialised = false;

/**
 * Show the chooser.
 *
 * @param {HTMLElement} trigger The "Add a block" button.
 */
const openChooser = async(trigger) => {
    const {url, pagehash, blockregion} = trigger.dataset;
    const [blocks, gallery] = await Promise.all(Ajax.call([
        {
            methodname: 'core_block_fetch_addable_blocks',
            args: {pagecontextid: 0, pagetype: '', pagelayout: '', subpage: '', pagehash},
        },
        {methodname: 'block_dash_gallery_list', args: {pagehash}},
    ]));
    const items = blocks.some((block) => block.name === 'dash') ? gallery.items : [];

    const modal = await ModalSaveCancel.create({
        title: getString('addblock'),
        body: Templates.render('block_dash/add_block_chooser', {items, hasitems: items.length > 0, blocks}),
        buttons: {save: getString('addblockadd', 'block_dash')},
        large: true,
        returnElement: trigger,
    });
    // The chooser hides while a block's form is open, and comes back when that is cancelled.
    let formopen = false;
    modal.getRoot().on(ModalEvents.hidden, () => {
        if (!formopen) {
            modal.destroy();
        }
    });
    showWide(modal);
    await modal.getBodyPromise();

    const save = modal.getFooter()[0].querySelector('[data-action="save"]');
    let selected = null;
    save.disabled = true;
    bindPicker(modal.getRoot()[0], (option) => {
        if (option.dataset.blockname) {
            return Templates.render('block_dash/add_block_info', {
                title: option.dataset.blocktitle,
                blockform: !!option.dataset.blockform,
            });
        }
        return renderItem({shortname: option.dataset.pickerOption, pagehash});
    }, (option) => {
        selected = option;
        save.disabled = false;
    });

    /**
     * Open a block's form instead of the chooser, as core does for blocks that ask for it.
     */
    const showBlockForm = () => {
        const form = new ModalForm({
            modalConfig: {title: getString('addblock', 'core_block', selected.dataset.blocktitle)},
            args: {blockname: selected.dataset.blockname, pagehash, blockregion},
            formClass: selected.dataset.blockform,
        });
        let submitted = false;
        form.addEventListener(form.events.FORM_SUBMITTED, () => {
            submitted = true;
            window.location.reload();
        });
        formopen = true;
        modal.hide();
        form.show().then(() => form.modal.getRoot().on(ModalEvents.hidden, () => {
            formopen = false;
            if (!submitted) {
                modal.show();
            }
        })).catch(Notification.exception);
    };

    modal.getRoot().on(ModalEvents.save, (e) => {
        e.preventDefault();
        if (!selected) {
            return;
        }
        if (selected.dataset.blockform) {
            showBlockForm();
            return;
        }
        save.disabled = true;
        const params = selected.dataset.blockname
            ? `&bui_addblock=${encodeURIComponent(selected.dataset.blockname)}`
            : `&bui_addblock=dash&dashgalleryitem=${encodeURIComponent(selected.dataset.pickerOption)}`;
        window.location.href = url + params;
    });
};

/**
 * Listen for clicks on Dash's "Add a block" buttons.
 *
 * The theme renders core's button before Dash replaces it, which loads core_block/add_modal too.
 * Its listener also matches Dash's buttons, so Dash listens first and keeps the click from it.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-action="block_dash-addblock"]');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            openChooser(trigger).catch(Notification.exception);
        }
    }, true);
};
