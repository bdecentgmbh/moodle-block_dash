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
 * Dash's "Add a block" chooser: the blocks that can be added, and the Dash gallery with a live preview.
 *
 * Blocks are added as in core_block/add_modal. A gallery item adds a Dash block through core's URL
 * for adding blocks, naming the item in an extra parameter that the new block imports.
 *
 * Trigger: data-action="block_dash-addblock" with data-url, data-pagehash and data-blockregion.
 *
 * @module     block_dash/add_block
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import CancelModal from 'core/modal_cancel';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import ModalSaveCancel from 'core/modal_save_cancel';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {bindPicker, renderItem, showWide} from 'block_dash/gallery_picker';
import {getString} from 'core/str';

let initialised = false;

/**
 * Open a block's form before adding it, as core does for blocks that ask for it.
 *
 * @param {HTMLElement} link The block in the list.
 * @param {String} pagehash
 * @param {Modal} chooser
 */
const showBlockForm = (link, pagehash, chooser) => {
    const form = new ModalForm({
        modalConfig: {title: getString('addblock', 'core_block', link.dataset.blocktitle)},
        args: {blockname: link.dataset.blockname, pagehash, blockregion: link.dataset.blockregion},
        formClass: link.dataset.blockform,
        returnFocus: link,
    });
    form.addEventListener(form.events.FORM_SUBMITTED, () => {
        chooser.destroy();
        window.location.reload();
    });
    form.show();
};

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
    const hasitems = items.length > 0;

    const config = {
        title: getString('addblock'),
        body: Templates.render('block_dash/add_block_chooser', {items, hasitems, blocks, url, blockregion, pagehash}),
        removeOnClose: true,
        returnElement: trigger,
    };
    const modal = hasitems
        ? await ModalSaveCancel.create({...config, large: true, buttons: {save: getString('addblockadd', 'block_dash')}})
        : await CancelModal.create(config);
    if (hasitems) {
        showWide(modal);
    } else {
        modal.show();
    }
    await modal.getBodyPromise();

    const root = modal.getRoot()[0];
    root.addEventListener('click', (e) => {
        const link = e.target.closest('[data-action="showaddblockform"]');
        if (link) {
            e.preventDefault();
            // Core's listener, see init(), would open the form a second time.
            e.stopPropagation();
            showBlockForm(link, pagehash, modal);
        }
    });
    if (!hasitems) {
        return;
    }

    const save = modal.getFooter()[0].querySelector('[data-action="save"]');
    let selected = null;
    save.disabled = true;
    bindPicker(root, (shortname) => renderItem({shortname, pagehash}), (shortname) => {
        selected = shortname;
        save.disabled = false;
    });

    modal.getRoot().on(ModalEvents.save, (e) => {
        e.preventDefault();
        if (selected) {
            save.disabled = true;
            window.location.href = `${url}&bui_addblock=dash&dashgalleryitem=${encodeURIComponent(selected)}`;
        }
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
