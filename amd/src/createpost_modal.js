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
 * Create post modal js.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry', 'core/ajax'],
    function($, Notification, CustomEvents, Modal, ModalRegistry, Ajax) {
        var registered = false;
        var SELECTORS = {
            SAVE_BUTTON: '[data-action="save"]',
            CANCEL_BUTTON: '[data-action="cancel"]'
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var CreatePostModal = function(root) {
            Modal.call(this, root);
        };

        CreatePostModal.TYPE = 'format_timeline-create_post_modal';
        CreatePostModal.prototype = Object.create(Modal.prototype);
        CreatePostModal.prototype.constructor = CreatePostModal;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        CreatePostModal.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function() {
                var message = $('#post-message').val();

                if (message === '' || message.length < 10) {
                    $('#post-message').addClass('is-invalid');
                    $('#post-message-feedback').removeClass('d-none');

                    return;
                }

                var postbtn = $("#create-post-btn");
                var request = Ajax.call([{
                    methodname: 'format_timeline_createpost',
                    args: {
                        post: {
                            courseid: postbtn.data('courseid'),
                            message: $('#post-message').val()
                        }
                    }
                }]);

                request[0].done(function() {
                    document.location.reload(true);
                }.bind(this)).fail(function(error) {
                    var message = error.message;

                    if (!message) {
                        message = error.error;
                    }

                    Notification.addNotification({
                        message: message,
                        type: 'error'
                    });

                    this.hide();

                    this.destroy();
                }.bind(this));
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function() {
                this.hide();
                this.destroy();
            }.bind(this));
        };

        // Automatically register with the modal registry the first time this module is imported so that you can create modals
        // of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(CreatePostModal.TYPE, CreatePostModal, 'format_timeline/createpost_modal');
            registered = true;
        }

        return CreatePostModal;
    });