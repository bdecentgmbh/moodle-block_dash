define(['jquery', 'core/log', 'core/ajax', 'core/notification'], function($, Log, Ajax, Notification) {

    var DashInstance = function(root, blockInstanceId) {
        this.root = $(root);
        this.blockInstanceId = blockInstanceId;

        this.init();
    };

    DashInstance.prototype.BLOCK_CONTENT_SELECTOR = '.info-dash-block-content';
    DashInstance.prototype.FILTER_FORM_SELECTOR = '.filter-form';

    DashInstance.prototype.init = function() {
        Log.debug('Initializing dash instance', this);

        this.getFilterForm().on('submit', function(e) {
            e.preventDefault();

            Log.debug('Submitting filter form');
            Log.debug(e);
            Log.debug($(e.target).serializeArray());

            this.refresh();
        }.bind(this));
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
        var request = {
            methodname: 'block_dash_get_block_content',
            args: {
                block_instance_id: this.blockInstanceId,
                filter_form_data: JSON.stringify(this.getFilterForm().serializeArray())
            }
        };

        return Ajax.call([request])[0];
    };

    DashInstance.prototype.refresh = function() {
        this.getBlockContent()
            .then(function(response) {
                console.log("RESPONSEEEEEEE", response);
                this.getBlockContentArea().html(response.html);
            }.bind(this))
            .catch(Notification.exception);
    };

    return DashInstance;
});