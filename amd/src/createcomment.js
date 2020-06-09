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
 * Create comment js logic.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'format_timeline/sweetalert'], function($, Ajax, Swal) {
    var CreateComment = function() {
        this.registerEventListeners();
    };

    CreateComment.prototype.registerEventListeners = function() {
        $(".post-comment-input").keypress(function(event) {
            var keycode = (event.keyCode ? event.keyCode : event.which);

            if (keycode === 13) {
                var target = $(event.currentTarget);

                this.saveComment(target, target.val());

                target.val('');
            }
        }.bind(this));

        $(".post-comment-btn").click(function(event) {
            var target = $(event.currentTarget).closest('.input-group').children('.post-comment-input');

            this.saveComment(target, target.val());

            target.val('');
        }.bind(this));
    };

    CreateComment.prototype.saveComment = function(target, value) {
        if (value === '') {
            return;
        }

        var discussdiv = target.closest('.discuss');
        if (discussdiv.length === 0 || discussdiv.length > 1) {
            this.showToast('error', 'Erro ao tentar localizar a discussão para este comentário.');

            return;
        }

        var id = discussdiv.data('id');
        var course = discussdiv.data('course');
        if (typeof id === undefined || typeof course === undefined) {
            this.showInvalidDiscussNotification();
        }

        var request = Ajax.call([{
            methodname: 'format_timeline_createpost',
            args: {
                post: {
                    course: course,
                    message: value,
                    parent: id
                }
            }
        }]);

        request[0].done(function() {
            this.addCommentToDiscuss(discussdiv, value);
        }.bind(this)).fail(function(error) {
            var message = error.message;

            if (!message) {
                message = error.error;
            }

            this.showToast('error', message);
        }.bind(this));
    };

    CreateComment.prototype.showToast = function(type, message) {
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

    CreateComment.prototype.addCommentToDiscuss = function(discussdiv, value) {
        var userimg = discussdiv.find('.add-comment .userimg').clone();
        var userfullname = userimg.attr('alt');

        $("<div class='post fadeIn'><div class='userimg'>" + $('<div/>').append(userimg).html() + "</div>" +
          "<div class='entry'><div class='entry-content'>" +
          "<p class='name'>" + userfullname + "</p>" +
          "<p class='text'>" + value + "</p>" +
          "</div></div></div>").insertBefore(discussdiv.find('.add-comment'));
    };

    return {
        'init': function() {
            return new CreateComment();
        }
    };
});