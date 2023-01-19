define(['jquery', 'jqueryui', 'core/log', 'core/ajax', 'core/notification', 'core/modal_events',
    'block_dash/preferences_modal', 'block_dash/datepicker', 'block_dash/select2', 'core/fragment', 'core/templates'],
    function($, UI, Log, Ajax, Notification, ModalEvents, PreferencesModal, DatePicker, Select2, Fragment, Templates) {

        var DashInstance = function(root, blockInstanceId, blockContextid, editing, istotara, pagelayout) {
            this.root = $(root);
            this.blockInstanceId = blockInstanceId;
            this.blockContextid = blockContextid;
            this.currentPage = 0;
            this.blockPreferencesModal = null;
            this.editing = editing;
            this.sortField = null;
            this.sortDirections = {};
            this.isTotara = istotara;
            this.pageLayout = pagelayout;
            this.init();
        };

        DashInstance.prototype.BLOCK_CONTENT_SELECTOR = '.dash-block-content';
        DashInstance.prototype.FILTER_FORM_SELECTOR = '.filter-form';

        DashInstance.prototype.init = function() {
            Log.debug('Initializing dash instance', this);

            this.initDatePickers();
            this.initSelect2();

            if (this.editing) {
                this.blockPreferencesModal = new PreferencesModal(this.getRoot().find('.dash-edit-preferences'),
                    this.blockContextid, function() {
                        // Preferences changed, go back to first page.
                        this.currentPage = 0;
                        this.refresh();
                    }.bind(this));
            }
            this.getRoot().on('change', 'select:not(.norefresh), input:not(.select2-search__field)',
                function(e) {
                e.preventDefault();

                Log.debug('Submitting filter form');
                Log.debug(e);
                Log.debug($(e.target).serializeArray());

                // Filter results, go back to first page.
                this.currentPage = 0;
                this.refresh();
            }.bind(this));

            this.getBlockContentArea().on('click', '.page-link', function(e) {
                e.preventDefault();
                this.currentPage = $(e.target).data('page');
                this.refresh();
            }.bind(this));

            this.getBlockContentArea().on('click', '.dash-sort', function(e) {
                const $target = $(e.target);
                this.sortField = $target.data('sort');

                // Set sorting to asc by default.
                if (!this.sortDirections.hasOwnProperty(this.sortField)) {
                    this.sortDirections[this.sortField] = 'asc';
                } else {
                    // Toggle sort direction on field.
                    this.sortDirections[this.sortField] = this.sortDirections[this.sortField] === 'asc' ? 'desc' : 'asc';
                }
                this.refresh();
            }.bind(this));

            if (this.isTotara) {
                this.setDynamicTable();
            }
        };

        /**
         * Get the root element of this dash instance.
         *
         * @method getRoot
         * @return {object} jQuery object
         */
        DashInstance.prototype.getRoot = function() {
            return this.root;
        };

        /**
         * Get the content element of this dash instance.
         *
         * @method getRoot
         * @return {object} jQuery object
         */
        DashInstance.prototype.getBlockContentArea = function() {
            return this.getRoot().find(this.BLOCK_CONTENT_SELECTOR);
        };

        /**
         * Get filter form element.
         *
         * @returns {object} jQuery object
         */
        DashInstance.prototype.getFilterForm = function() {
            return this.getRoot().find(this.FILTER_FORM_SELECTOR);
        };

        DashInstance.prototype.getBlockContent = function() {
            let sortDirection = null;
            if (this.sortField && this.sortDirections.hasOwnProperty(this.sortField)) {
                sortDirection = this.sortDirections[this.sortField];
            }

            var request = {
                methodname: 'block_dash_get_block_content',
                args: {
                    "block_instance_id": this.blockInstanceId,
                    "filter_form_data": JSON.stringify(this.getFilterForm().serializeArray()),
                    "page": this.currentPage,
                    "sort_field": this.sortField,
                    "sort_direction": sortDirection,
                    "pagelayout" : this.pageLayout,
                }
            };

            return Ajax.call([request])[0];
        };

        DashInstance.prototype.refresh = function() {
            this.getBlockContentArea().css('opacity', 0.5);
            this.getBlockContent()
                .then(function(response) {
                    this.getBlockContentArea().html(response.html);
                    this.getBlockContentArea().css('opacity', 1);
                    this.initDatePickers();
                    this.initSelect2();
                }.bind(this))
                .catch(Notification.exception);
        };

        DashInstance.prototype.initDatePickers = function() {
            this.getRoot().find('.datepicker').datepicker2({
                autoclose: true,
                format: "dd/mm/yyyy"
            });
        };

        DashInstance.prototype.initSelect2 = function() {
            this.getRoot().find('.select2').each(function(index, element) {
                let placeholder = null;
                if ($(element).find("option[value='-1']")) {
                    placeholder = {
                        id: '-1', // The value of the option.
                        text: $(element).find("option[value='-1']").text()
                    };
                }
                $(element).select2({
                    dropdownParent: this.getRoot(),
                    allowClear: true,
                    theme: 'bootstrap4',
                    placeholder: placeholder
                }).on('select2:unselecting', function() {
                    $(this).data('unselecting', true);
                }).on('select2:opening', function(e) {
                    if ($(this).data('unselecting')) {
                        $(this).removeData('unselecting');
                        e.preventDefault();
                    }
                });
            }.bind(this));
        };

        DashInstance.prototype.setDynamicTable = function() {

            $('body').delegate('[data-table-dynamic="true"] thead th a', 'click', function(e) {
                e.preventDefault();
                updateTable($(this));
            });

            $('body').delegate('.modal-body .paging a', 'click', function(e) {
                e.preventDefault();
                updateTable($(this));
            });

            var updateTable = function(element) {
                var table = element.parents('.modal-body').find('table');

                var href = element.attr('href');
                var params = new URL(href).searchParams;
                var page = params.get('page');
                var sortfield = params.get('tsort');
                if (sortfield == '') {
                    sortfield = table.data('table-sort');
                }

                var tablehandler = table.data('table-handler');
                var filter = table.data('table-filter');
                var uniqueid = table.data('table-uniqueid');
                var context = table.data('table-context');

                var data = {
                    handler: tablehandler,
                    filter: filter,
                    uniqueid: uniqueid,
                    sort: sortfield,
                    page: page
                };

                Fragment.loadFragment('block_dash', 'loadtable', context, data).then((html, js) => {
                    $('.modal-body').html(html);
                    Templates.runTemplateJS(js);
                    return html;
                }).catch(Notification.exception);
            };
        };

        return DashInstance;
    });