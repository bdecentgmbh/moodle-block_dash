
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/templates', 'core/ajax'],
function($, Str, Modal, ModalEvents, Fragment, Templates, AJAX) {

    return {
        init: function(contextID) {

            var groupModal = document.getElementsByClassName('group-widget-viewmembers');
            Array.from(groupModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = e.target;
                    var group = target.getAttribute('data-group');
                    Modal.create({
                        title: Str.get_string('groups', 'core')
                    }).then(function(modal) {
                        modal.show();
                        var args = JSON.stringify({group: group});
                        var params = {widget: 'groups', method: 'viewmembers', args: args};
                        Fragment.loadFragment('block_dash', 'loadwidget', contextID, params).then((html, js) => {
                            modal.setBody(html);
                            Templates.runTemplateJS(js);
                        });
                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                    });
                });
            });

            var groupUsers = document.getElementsByClassName('add-group-users');
            Array.from(groupUsers).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = e.target;
                    var group = target.getAttribute('data-group');
                    Modal.create({
                        type: Modal.types.SAVE_CANCEL,
                        title: Str.get_string('widget:groups:adduser', 'block_dash'),
                    }).then(function(modal) {
                        modal.setLarge(true);
                        modal.show();

                        var args = JSON.stringify({group: group});
                        var params = {widget: 'groups', method: 'addmembers', args: args};
                        Fragment.loadFragment('block_dash', 'loadwidget', contextID, params).then((html, js) => {
                            modal.setBody(html);

                            modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    var formdata = new FormData(e.target);
                                    if (e.target.querySelector('[name="users[]"]').value == '') {
                                        return false;
                                    }

                                    var formdatastr = new URLSearchParams(formdata).toString();
                                    var promises = AJAX.call([{
                                        methodname: 'block_dash_groups_add_members',
                                        args: {formdata: formdatastr}
                                    }]);

                                    promises[0].done((response) => {
                                        if (response == true) {
                                            window.location.reload();
                                        } else {
                                            // TODO: Error Notification.
                                        }
                                    });
                                });
                            });

                            Templates.runTemplateJS(js);
                        });

                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });

                        // Apply and save method.
                        modal.getRoot().on(ModalEvents.save, (e) => {
                            e.preventDefault();
                            modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                                form.querySelector('#id_submitbutton').click();
                            });
                        });
                    });
                });
            });

            // Leave group.
            var leaveGroupModal = document.getElementsByClassName('group-widget-leavegroup');
            Array.from(leaveGroupModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = e.target;
                    var group = target.getAttribute('data-group');
                    var groupname = target.getAttribute('data-groupname');

                    Modal.create({
                        type: Modal.types.SAVE_CANCEL,
                        title: Str.get_string('groups', 'core' ),
                    }).then(function(modal) {
                        Str.get_string('confirm', 'core').then((html) => {
                            modal.setSaveButtonText(html);
                        });
                        modal.show();

                        Str.get_string('confirmleavegroup', 'block_dash', groupname).then((html) => {
                            modal.setBody(html);
                        });

                        modal.getRoot().on(ModalEvents.save, (e) => {
                            e.preventDefault();
                            var promises = AJAX.call([{
                                methodname: 'block_dash_groups_leave_group',
                                args: {groupid: group}
                            }]);

                            promises[0].done((response) => {
                                if (response == true) {
                                    window.location.reload();
                                } else {
                                    // TODO: Error Notification.
                                }
                            });
                        });

                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });

                        modal.getRoot().on(ModalEvents.destroyed, function() {
                            modal.remove();
                            modal.attachmentPoint.remove();
                        });
                    });
                });
            });

            // Create group.
            var createGroupModal = document.getElementsByClassName('create-group');
            Array.from(createGroupModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    Modal.create({
                        type: Modal.types.SAVE_CANCEL,
                        title: Str.get_string('groups', 'core'),
                    }).then(function(modal) {
                        modal.show();

                        var args = "";
                        var params = {widget: 'groups', method: 'creategroup', args: args};
                        Fragment.loadFragment('block_dash', 'loadwidget', contextID, params).then((html, js) => {
                            modal.setBody(html);
                            Templates.runTemplateJS(js);
                            modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    var formdata = new FormData(e.target);
                                    if (e.target.querySelector('[name="name"]').value == ""
                                        || e.target.querySelector('[name="courseid"]').value == '') {
                                        return false;
                                    }
                                    var formdatastr = new URLSearchParams(formdata).toString();
                                    var promises = AJAX.call([{
                                        methodname: 'block_dash_groups_create_group',
                                        args: {formdata: formdatastr}
                                    }]);

                                    promises[0].done((response) => {
                                        if (response == true) {
                                            window.location.reload();
                                        } else {
                                            // TODO: Error Notification.
                                        }
                                    });
                                });
                            });
                        });

                        // Apply and save method.
                        modal.getRoot().on(ModalEvents.save, (e) => {
                            e.preventDefault();
                            modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                                form.querySelector('#id_submitbutton').click();
                            });
                        });

                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                    });
                });
            });
        }
    };
});
