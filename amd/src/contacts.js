define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/templates'],
    function($, Str, Modal, ModalEvents, Fragment, Templates) {

    return {
        init: function(contextID) {
            var groupModal = document.getElementsByClassName('contact-widget-viewgroup');
            var contactUser;
            Array.from(groupModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = e.target.closest('a');
                    contactUser = target.getAttribute('data-userid');
                    Modal.create({
                        title: Str.get_string('groups', 'core')
                    }).then(function(modal) {
                        modal.show();
                        modal.getRoot().on(ModalEvents.shown, function() {
                            var args = JSON.stringify({contactuser: contactUser});
                            var params = {widget: 'contacts', method: 'load_groups', args: args };
                            Fragment.loadFragment('block_dash', 'loadwidget', contextID, params).then((html, js) => {
                                modal.setBody(html);
                                Templates.runTemplateJS(js);
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