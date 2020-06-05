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
 * Delete post js logic.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/ajax', 'format_timeline/sweetalert'], function($, Notification, Ajax, Swal) {
    var DeletePost = function() {
        this.registerEventListeners();
    };

    DeletePost.prototype.registerEventListeners = function() {
        $(".delete-post").click(function(event) {
            event.preventDefault();

            var eventTarget = $(event.currentTarget);

            Swal.fire({
                title: 'Você tem certeza disso?',
                text: "Depois de excluída a publicação não poderá ser recuperada!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, pode excluir!',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.value) {
                    this.deletePost(eventTarget);
                }
            }.bind(this));
        }.bind(this));
    };

    DeletePost.prototype.deletePost = function(eventTarget) {
        var request = Ajax.call([{
            methodname: 'format_timeline_deletepost',
            args: {
                post: {
                    id: eventTarget.data('id')
                }
            }
        }]);

        request[0].done(function() {
            this.removeDiscuss(eventTarget);
        }.bind(this)).fail(function(error) {
            var message = error.message;

            if (!message) {
                message = error.error;
            }

            this.showToast('error', message);
        });
    };

    DeletePost.prototype.removeDiscuss = function(eventTarget) {
        var discussDiv = eventTarget.closest('div.discuss');

        discussDiv.fadeOut("normal", function() {
            $(this).remove();
        });

        this.showToast('success', 'Publicação excluída com sucesso.');
    };

    DeletePost.prototype.showToast = function(type, message) {
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 8000,
            timerProgressBar: true,
            onOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    };

    return {
        'init': function() {
            return new DeletePost();
        }
    };
});