/**
 * Details area module — provides expanding, floating & modal support for non-cards layouts.
 *
 * Usage from a Mustache template:
 *   require(['block_dash/details_area'], function(DetailsArea) {
 *       DetailsArea.init('#dash-grid-123', {
 *           detailsBgColor: '{{preferences.details_bg_color}}',
 *           detailsTextColor: '{{preferences.details_text_color}}',
 *           rowSelector: 'tbody tr[data-detailheader]',
 *           mode: '{{preferences.details_area_mode}}'   // expanding | floating | modal
 *       });
 *   });
 *
 * Trigger paths (all three modes):
 *  1. Explicit — click on [data-action="open-details-modal"] (details button / details link)
 *  2. For floating mode, hover on [data-action="open-details-modal"] also shows the panel
 *
 * The details area requires a "Details button" or "Details link" field to be
 * enabled in the Fields tab.  Row-level click/hover fallbacks have been removed
 * so that only the dedicated field triggers can open the details area.
 *
 * @module     block_dash/details_area
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',
    'core/str',
    'core/notification'
], function($, Modal, ModalEvents, Templates, Str, Notification) {

    /** Track which containers have been initialised to prevent double-bind. */
    var initialisedContainers = {};

    /** Whether page-level hash handlers have been bound (idempotent guard). */
    var hashInitialised = false;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Read detail data attributes from an element (or its child .card-info / .detail-info).
     * Always reads DIRECTLY from the DOM element that was clicked/hovered — never cached.
     *
     * @param {jQuery}  $element The row / container element.
     * @param {Object}  options  Init options.
     * @returns {Object} Template context for block_dash/layouts_cards_detailarea.
     */
    var getDetailContext = function($element, options) {
        // For non-cards layouts the data attrs sit directly on the row element (tr, li, .card).
        // For cards layouts they sit on a child .card-info div.
        var $info = $element.find('.card-info').first();
        if (!$info.length) {
            $info = $element.find('.detail-info').first();
        }
        if (!$info.length) {
            $info = $element;
        }

        var context = {
            detailheader: $info.attr('data-detailheader') || '',
            detailheading: $info.attr('data-detailheading') || '',
            detailbody: $info.attr('data-detailbody') || '',
            detailbody2: $info.attr('data-detailbody2') || '',
            detailbody3: $info.attr('data-detailbody3') || '',
            detailfooter: $info.attr('data-detailfooter') || '',
            detailfooterright: $info.attr('data-detailfooterright') || '',
            customcontent: $info.attr('data-customcontent') || '',
            details_bg_color: options.detailsBgColor || '',
            details_text_color: options.detailsTextColor || '',
            customcontentheight: options.customContentHeight || '',
            detailmodal: options.mode === 'modal' ? 'true' : ''
        };

        return context;
    };

    /**
     * Render the detail area partial.
     *
     * @param {Object} context Template context.
     * @returns {Promise<string>} Rendered HTML.
     */
    var renderDetailArea = function(context) {
        return Templates.render('block_dash/layouts_cards_detailarea', context);
    };

    /**
     * Locate the logical row element that holds the data-detail attributes.
     * Every layout places data-detailheader directly on the row element
     * (tr, li, .card, .panel), so we simply walk up to that attribute.
     *
     * When a CSS stretched-link (or similar absolute positioning) causes
     * the event target to belong to a different row than the one visually
     * clicked, the function falls back to coordinate-based detection.
     *
     * @param {jQuery}  $trigger   The clicked element (button/link inside a row).
     * @param {jQuery=} $container The layout container (used for coordinate fallback).
     * @param {Event=}  e          The original DOM event (used for coordinate fallback).
     * @returns {jQuery} The row element.
     */
    var findRow = function($trigger, $container, e) {
        // Walk up from the clicked button/link to the nearest element with data-detailheader.
        var $row = $trigger.closest('[data-detailheader]');

        // Guard: verify the found row actually contains the click point.
        // This catches CSS positioning issues (e.g. stretched-link ::after intercepting
        // clicks on behalf of a different row).
        if ($row.length && e && $container && typeof e.clientX === 'number') {
            var rect = $row[0].getBoundingClientRect();
            var inBounds = (e.clientX >= rect.left && e.clientX <= rect.right &&
                            e.clientY >= rect.top && e.clientY <= rect.bottom);
            if (!inBounds) {
                var $correct = $();
                $container.find('[data-detailheader]').each(function() {
                    var r = this.getBoundingClientRect();
                    if (e.clientX >= r.left && e.clientX <= r.right &&
                        e.clientY >= r.top && e.clientY <= r.bottom) {
                        $correct = $(this);
                        return false; // break
                    }
                });
                if ($correct.length) {
                    $row = $correct;
                }
            }
        }

        if (!$row.length) {
            window.console.warn('[DASH details_area] findRow: no [data-detailheader] ancestor for', $trigger[0]);
        }
        return $row;
    };


    /**
     * Update the URL hash to the detail-id of the clicked trigger, so the URL
     * can be copied/shared and will auto-open the same details area on load.
     *
     * Uses replaceState to avoid adding a history entry for every click.
     *
     * @param {jQuery} $trigger The clicked button/link element.
     */
    var updateHash = function($trigger) {
        var detailId = $trigger.attr('data-detail-id');
        if (detailId && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + detailId);
        }
    };

    // -------------------------------------------------------------------------
    // MODAL mode
    // -------------------------------------------------------------------------

    /**
     * Open a Moodle modal with the details area content.
     *
     * @param {Object} templatecontext Rendered context.
     */
    var openModal = function(templatecontext) {
        Modal.create({
            title: Str.get_string('strinfo', 'block_dash'),
            type: Modal.types.CANCEL,
            body: renderDetailArea(templatecontext),
            large: true
        }).then(function(modal) {
            modal.show();
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
            return modal;
        }).catch(Notification.exception);
    };

    /**
     * Bind modal-mode handlers on a container.
     *
     * @param {jQuery} $container
     * @param {Object} options
     */
    var initModal = function($container, options) {
        var handler = function($row) {
            openModal(getDetailContext($row, options));
        };

        // Explicit button / link clicks.
        $container.on('click', '[data-action="open-details-modal"]', function(e) {
            // so the real URL can be opened in a new tab or copied.
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            var $clicked = $(this);
            var $row = findRow($clicked, $container, e);
            if ($row.length) {
                handler($row);
                updateHash($clicked);
            }
        });

    };

    // -------------------------------------------------------------------------
    // EXPANDING mode
    // -------------------------------------------------------------------------

    /**
     * Remove any existing expand panel owned by a row and clean up its state.
     *
     * @param {jQuery}  $row
     * @param {boolean} [animate=false] When true, slide the panel up before removing.
     */
    var removeExpandPanel = function($row, animate) {
        var $panel = $row.data('dash-detail-panel');
        $row.removeData('dash-detail-panel');
        $row.removeClass('dash-details-expanded');
        if ($panel && $panel.length) {
            if (animate) {
                // For <tr> panels, animate the inner content div (see handleExpand).
                var $target = $panel.is('tr') ? $panel.find('.dash-details-expand-content') : $panel;
                $target.slideUp(250, function() {
                    $panel.remove();
                });
            } else {
                $panel.remove();
            }
        }
    };

    /**
     * Collapse and remove all expanded panels inside the container, optionally excluding one row.
     *
     * @param {jQuery}  $container
     * @param {jQuery=} $except  Row to skip.
     */
    var collapseAll = function($container, $except) {
        $container.find('.dash-details-expanded').each(function() {
            if ($except && $except.is(this)) {
                return;
            }
            removeExpandPanel($(this));
        });
        // Also remove any orphaned expand rows/panels that might remain.
        $container.find('.dash-details-expand-row, .dash-details-expand-panel, .dash-details-expand-li').each(function() {
            $(this).remove();
        });
    };

    /**
     * Toggle an inline detail panel below the clicked row.
     * Always renders fresh content from the row's current data attributes.
     *
     * @param {jQuery} $row      The clicked row element.
     * @param {jQuery} $container The layout container.
     * @param {Object} options   Init options.
     * @param {jQuery} $trigger  The clicked element (button or link).
     */
    var handleExpand = function($row, $container, options, $trigger) {
        // Keep reference to the original row for storing panel data.
        var $originalRow = $row;

        // If this row already has a visible panel, toggle it OFF.
        if ($originalRow.hasClass('dash-details-expanded')) {
            removeExpandPanel($originalRow, true);
            return;
        }

        // Collapse and remove ALL other panels.
        collapseAll($container);

        // Always read fresh context from DOM attributes.
        var context = getDetailContext($originalRow, options);
        var sizeClass = (options.detailsAreaSize === 'fit_content')
            ? 'dash-details-size-fit-content'
            : 'dash-details-size-like-item';
        renderDetailArea(context).then(function(html) {
            var $panel;
            var $insertPoint = $originalRow; // Separate variable for insertion point.
            var tagName = ($originalRow[0].tagName || '').toLowerCase();
            if (tagName === 'tr') {
                // Table layouts (grid, accordion-with-tables, custom tables).
                if (options.detailsAreaSize === 'fit_content') {
                    var colCount = $originalRow.find('td').length || $originalRow.closest('table').find('thead th').length || 1;
                    $panel = $('<tr class="dash-details-expand-row ' + sizeClass + '">' +
                        '<td colspan="' + colCount + '">' +
                        '<div class="dash-details-expand-content">' + html + '</div>' +
                        '</td></tr>');
                } else {
                    // Use the trigger element (button or link) that was clicked.
                    if ($trigger && $trigger.length &&
                        ($trigger.hasClass('dash-details-open-link') || $trigger.hasClass('dash-details-open-btn'))) {
                        $insertPoint = $trigger;
                    } else {
                        $insertPoint = $originalRow.find('.dash-details-open-btn, .dash-details-open-link').first();
                    }
                    if (!$insertPoint.length) {
                        $insertPoint = $originalRow;
                    }
                    $panel = $('<div class="dash-details-expand-row ' + sizeClass + '">' +
                        '<div class="dash-details-expand-content">' + html + '</div>' +
                        '</div>');
                }
            } else if (tagName === 'li') {
                $insertPoint = $originalRow.find('.timeline-info').first();
                // Timeline layout: wrap in <div> so it stays valid inside <ul>.
                $panel = $('<div class="dash-details-expand-li ' + sizeClass + '">' +
                    '<div class="dash-details-expand-content">' + html + '</div>' +
                    '</div>');
            } else {
                console.log($originalRow);
                // Accordion2 has .floating-details-show; custom layouts may not.
                var $floating = $originalRow.find('.floating-details-show').first();
                console.log($floating);
                if ($floating.length) {
                    $insertPoint = $floating;
                }
                // Accordion2 (.card / .panel) and any custom layout: wrap in <div>.
                $panel = $('<div class="dash-details-expand-panel ' + sizeClass + '">' + html + '</div>');
            }
            $insertPoint.after($panel);
            // Store panel reference on the ORIGINAL row so toggle-off works correctly.
            $originalRow.data('dash-detail-panel', $panel);
            $originalRow.addClass('dash-details-expanded');
            // For <tr> panels (fit_content table layout), animate the inner
            // content div instead of the <tr> itself.  jQuery's slideDown on
            // <tr> elements is unreliable because display:table-row does not
            // support height animation or overflow:hidden the way block
            // elements do, which can leave the row hidden.
            if ($panel.is('tr')) {
                $panel.find('.dash-details-expand-content').hide().slideDown(250);
            } else {
                $panel.hide().slideDown(250);
            }
            return;
        }).catch(Notification.exception);
    };

    /**
     * Bind expanding-mode handlers on a container.
     *
     * @param {jQuery} $container
     * @param {Object} options
     */
    var initExpanding = function($container, options) {
        var handler = function($row, $trigger) {
            handleExpand($row, $container, options, $trigger);
        };
        // Explicit button / link clicks.
        $container.on('click', '[data-action="open-details-modal"]', function(e) {
            // Let the browser handle modifier clicks so the real URL can be followed.
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            var $clicked = $(this);
            var $row = findRow($clicked, $container, e);
            if ($row.length) {
                handler($row, $clicked);
                updateHash($clicked);
            }
        });

    };

    // -------------------------------------------------------------------------
    // FLOATING mode
    // -------------------------------------------------------------------------

    /**
     * Bind floating-mode handlers on a container.
     *
     * @param {jQuery} $container
     * @param {Object} options
     */
    var initFloating = function($container, options) {
        var sizeClass = (options.detailsAreaSize === 'fit_content')
            ? 'dash-details-size-fit-content'
            : 'dash-details-size-like-item';
        var hideTimeout = null;
        var showTimeout = null;
        var currentRow = null;
        var pendingRow = null;
        var $currentPanel = null;
        var $currentTrigger = null;
        var $pendingTrigger = null;
        var isRendering = false;

        // Timing configuration (in milliseconds).
        var SHOW_DELAY = 150; // Delay before showing (prevents accidental triggers).
        var HIDE_DELAY = 400; // Delay before hiding (allows mouse to move to panel).

        /**
         * Remove the floating panel from its current location.
         */
        var removePanel = function() {
            if ($currentPanel && $currentPanel.length) {
                $currentPanel.remove();
                $currentPanel = null;
            }
            $currentTrigger = null;
        };

        /**
         * Hide panel with transition, then remove.
         */
        var hidePanel = function() {
            if ($currentPanel && $currentPanel.length) {
                $currentPanel.removeClass('show');
                var $panelToRemove = $currentPanel;
                setTimeout(function() {
                    if ($panelToRemove === $currentPanel) {
                        removePanel();
                        currentRow = null;
                    }
                }, 300);
            } else {
                currentRow = null;
            }
        };

        /**
         * Cancel all pending timeouts.
         */
        var cancelAllTimeouts = function() {
            clearTimeout(hideTimeout);
            clearTimeout(showTimeout);
            pendingRow = null;
            $pendingTrigger = null;
        };

        /**
         * Schedule hiding the panel after a delay.
         */
        var scheduleHide = function() {
            // Cancel any pending show.
            clearTimeout(showTimeout);
            pendingRow = null;
            $pendingTrigger = null;

            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(function() {
                hidePanel();
            }, HIDE_DELAY);
        };

        /**
         * Check if element is part of the current floating (trigger or panel).
         *
         * @param {Element} element The element to check.
         * @returns {boolean} True if element is part of current floating.
         */
        var isPartOfCurrentFloating = function(element) {
            if (!element) {
                return false;
            }
            // Check against current panel.
            if ($currentPanel && $currentPanel.length) {
                if ($currentPanel[0] === element || $.contains($currentPanel[0], element)) {
                    return true;
                }
            }
            // Check against current trigger.
            if ($currentTrigger && $currentTrigger.length) {
                if ($currentTrigger[0] === element || $.contains($currentTrigger[0], element)) {
                    return true;
                }
            }
            // Check against pending trigger.
            if ($pendingTrigger && $pendingTrigger.length) {
                if ($pendingTrigger[0] === element || $.contains($pendingTrigger[0], element)) {
                    return true;
                }
            }
            return false;
        };

        /**
         * Actually render and show the panel.
         *
         * @param {jQuery} $row The row element.
         * @param {jQuery} $trigger The trigger element.
         */
        var doShow = function($row, $trigger) {
            // If already showing for this row, just ensure it stays visible.
            if (currentRow === $row[0] && $currentPanel && $currentPanel.length) {
                cancelAllTimeouts();
                if (!$currentPanel.hasClass('show')) {
                    $currentPanel.addClass('show');
                }
                return;
            }

            // Prevent concurrent renders.
            if (isRendering) {
                return;
            }
            isRendering = true;

            currentRow = $row[0];
            $currentTrigger = $trigger;
            cancelAllTimeouts();

            var context = getDetailContext($row, options);
            renderDetailArea(context).then(function(html) {
                isRendering = false;

                // Guard: user may have moved away while render was in flight.
                if (currentRow !== $row[0]) {
                    return;
                }

                // Remove any existing panel first.
                removePanel();

                // Create the new panel element with a hover bridge.
                $currentPanel = $('<div class="dash-details-floating-panel ' + sizeClass + '">' +
                    '<div class="dash-floating-hover-bridge"></div>' +
                    html + '</div>');

                // Find the appropriate injection point within the row.
                var $card = $row.hasClass('floating-details-show') ? $row : $row.find('.floating-details-show');

                if (!$card.length) {
                    if ($row.hasClass('card-table')) {
                        if (options.detailsAreaSize === 'fit_content') {
                            $card = $row.closest('.card-table');
                        } else {
                            if ($trigger && $trigger.length && $trigger.hasClass('dash-details-open-link')) {
                                $card = $trigger;
                            } else if ($trigger && $trigger.length && $trigger.hasClass('dash-details-open-btn')) {
                                $card = $trigger;
                            } else {
                                $card = $row.find('.dash-details-open-btn, .dash-details-open-link').first();
                            }
                        }
                    } else {
                        $card = $row.closest('.floating-details-show');
                    }
                }

                if ($card.length) {
                    var $cardBody = $card.find('.card').first();
                    if ($cardBody.length) {
                        $cardBody.after($currentPanel);
                    } else {
                        $card.after($currentPanel);
                    }
                } else {
                    $row.after($currentPanel);
                }

                // Bind hover events on the panel.
                $currentPanel.on('mouseenter', function() {
                    cancelAllTimeouts();
                });
                $currentPanel.on('mouseleave', function(e) {
                    if (isPartOfCurrentFloating(e.relatedTarget)) {
                        return;
                    }
                    scheduleHide();
                });

                // Force reflow before adding show class for CSS transition.
                void $currentPanel[0].offsetHeight;
                $currentPanel.addClass('show');
                return;
            }).catch(function(err) {
                isRendering = false;
                Notification.exception(err);
            });
        };

        /**
         * Schedule showing the panel with a delay.
         *
         * @param {jQuery} $row The row element.
         * @param {jQuery} $trigger The trigger element.
         */
        var scheduleShow = function($row, $trigger) {
            // Cancel any pending hide.
            clearTimeout(hideTimeout);

            // If already showing this row, nothing to do.
            if (currentRow === $row[0] && $currentPanel && $currentPanel.length) {
                return;
            }

            // If already pending show for same row, nothing to do.
            if (pendingRow === $row[0]) {
                return;
            }

            // Cancel any previous pending show.
            clearTimeout(showTimeout);
            pendingRow = $row[0];
            $pendingTrigger = $trigger;

            showTimeout = setTimeout(function() {
                if (pendingRow === $row[0]) {
                    doShow($row, $trigger);
                    pendingRow = null;
                    $pendingTrigger = null;
                }
            }, SHOW_DELAY);
        };

        // Hover on explicit details triggers - schedule show with delay.
        $container.on('mouseenter', '[data-action="open-details-modal"]', function() {
            var $trigger = $(this);
            var $row = findRow($trigger, $container);
            if ($row.length) {
                scheduleShow($row, $trigger);
            }
        });

        // Mouseleave on trigger - schedule hide unless moving to panel.
        $container.on('mouseleave', '[data-action="open-details-modal"]', function(e) {
            if (isPartOfCurrentFloating(e.relatedTarget)) {
                return;
            }
            scheduleHide();
        });

        // Explicit button / link clicks - show immediately.
        $container.on('click', '[data-action="open-details-modal"]', function(e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            var $clicked = $(this);
            var $row = findRow($clicked, $container, e);
            if ($row.length) {
                cancelAllTimeouts();
                doShow($row, $clicked);
                updateHash($clicked);
            }
        });

    };

    // -------------------------------------------------------------------------
    // Hash-based auto-open & page-level hash management
    // -------------------------------------------------------------------------

    /**
     * If location.hash matches a data-detail-id anywhere on the page,
     * programmatically click the trigger to open its details area.
     *
     * This enables shareable URLs like  https://site.com/my/#dash-detail-b5-c42 :
     * when a user follows such a link the page loads and the correct details
     * area opens automatically.
     *
     * The search is page-level (not scoped to a single container) so it works
     * for ALL layout types — grid, cards, accordion, timeline, etc.
     */
    var autoOpenFromHash = function() {
        var hash = window.location.hash;
        if (!hash || hash.indexOf('#dash-detail-') !== 0) {
            return;
        }
        var detailId = hash.substring(1); // Strip leading '#'.

        // Validate format to prevent selector injection: only allow alphanumeric, dash, underscore.
        if (!/^dash-detail-[\w-]+$/.test(detailId)) {
            return;
        }

        var $trigger = $(document).find('[data-detail-id="' + detailId + '"]').first();
        if ($trigger.length) {
            // Small delay so the layout is fully rendered before triggering.
            setTimeout(function() {
                $trigger[0].click();
                // Scroll the trigger into view.
                if ($trigger[0].scrollIntoView) {
                    $trigger[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            }, 500);
        }
    };

    /**
     * Set up page-level hash management (idempotent — safe to call many times).
     *
     * Binds:
     * 1. A delegated click handler on [data-detail-id] that updates location.hash.
     * 2. A one-time auto-open from the current URL hash.
     */
    var initHash = function() {
        if (hashInitialised) {
            return;
        }
        hashInitialised = true;

        // Page-level delegated handler: update hash when ANY details trigger is clicked.
        // This works for all layouts (grid, cards, accordion, timeline, etc.).
        $(document).on('click', '[data-detail-id]', function(e) {
            // Let modifier-clicks pass through (Ctrl/Cmd/Shift/middle-click).
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
                return;
            }
            // Check if details area is disabled on this element or a parent.
            var $el = $(this);
            var status = $el.data('status') || $el.closest('[data-status]').data('status');
            if (status === 'disabled') {
                return;
            }
            updateHash($(this));
        });

        // Auto-open the details area that matches the URL hash.
        autoOpenFromHash();
    };

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    return {
        /**
         * Initialise details area handling for a container.
         *
         * @param {string} containerSelector CSS selector for the layout container.
         * @param {Object} options Configuration options.
         * @param {string} [options.mode='modal']  Display mode: expanding | floating | modal.
         * @param {string} [options.detailsBgColor]  Background colour for the details panel.
         * @param {string} [options.detailsTextColor]  Text colour for the details panel.
         * @param {string} [options.rowSelector]  CSS selector for clickable/hoverable row elements.
         * @param {string} [options.detailsAreaSize='like_item']  Size: like_item | fit_content.
         */
        init: function(containerSelector, options) {
            // Prevent double-init on the same container (e.g. after AJAX pagination).
            /* if (initialisedContainers[containerSelector]) {
                return;
            }
            initialisedContainers[containerSelector] = true; */

            options = options || {};
            var mode = options.mode || 'modal';
            var $container = $(containerSelector);

            if (!$container.length) {
                return;
            }

            // Remove any stretched-link classes that cause ::after pseudo-elements
            // to intercept clicks on the wrong row (the ::after covers the nearest
            // position:relative ancestor, which in tables may be the entire table).
            // Preserve stretched-link on the details open link itself.
            $container.find('.stretched-link').not('.dash-details-open-link').removeClass('stretched-link');

            // Apply CSS marker classes for mode and size on the container
            // so that CSS can style the layout appropriately.
            $container.addClass('dash-details-enabled');
            $container.addClass('dash-details-mode-' + mode);

            if (mode !== 'modal') {
                var sizeClass = (options.detailsAreaSize === 'fit_content')
                    ? 'dash-details-size-fit-content'
                    : 'dash-details-size-like-item';
                $container.addClass(sizeClass);
            }

            switch (mode) {
                case 'expanding':
                    initExpanding($container, options);
                    break;
                case 'floating':
                    initFloating($container, options);
                    break;
                case 'modal':
                default:
                    initModal($container, options);
                    break;
            }

            // Set up page-level hash management (auto-open from URL hash,
            // update hash on click).  Idempotent — safe to call from every init.
            initHash();
        },

        /**
         * Initialise ONLY hash-based features (auto-open & hash-update on click)
         * without binding any mode handlers.
         *
         * Call this from layouts that manage their own click/hover handling
         * (e.g. the cards layout) so the shareable-URL feature still works.
         *
         * Usage:
         *   require(['block_dash/details_area'], function(DetailsArea) {
         *       DetailsArea.hashInit();
         *   });
         */
        hashInit: function() {
            initHash();
        }
    };
});
