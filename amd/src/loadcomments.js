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
 * Load comments js logic.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {
    var LoadComments = function() {
        this.registerEventListeners();
    };

    LoadComments.prototype.registerEventListeners = function() {
        $(".loadallcomments").click(function(event) {
            event.preventDefault();

            var target = $(event.currentTarget);

            var postid = target.data('id');

            var cardbody = target.closest('.card-body');

            var request = Ajax.call([{
                methodname: 'format_timeline_getpostcomments',
                args: {
                    post: {
                        id: postid
                    }
                }
            }]);

            request[0].done(function(data) {
                cardbody.children().not('.mainpost').not('.add-comment').remove();

                data.comments.forEach(function(item) {
                    $("<div class='post fadeIn'><div class='userimg'><img src='" + item.userpic + "'></div>" +
                        "<div class='entry'><div class='entry-content'>" +
                        "<p class='name'>" + item.fullname + "</p>" +
                        "<p class='text'>" + item.message + "</p>" +
                        "</div></div></div>").insertBefore(cardbody.find('.add-comment'));
                });
            });
        });
    };

    return {
        'init': function() {
            return new LoadComments();
        }
    };
});
