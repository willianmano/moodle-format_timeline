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
 * Tribute JS initialization
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/config', 'format_timeline/tribute', 'core/ajax'], function(mdlcfg, Tribute, Ajax) {
    var TributeInit = function() {
        var tribute = new Tribute({
            values: function(text, cb) {
                this.remoteSearch(text, users => cb(users));
            }.bind(this),
            selectTemplate: function(item) {
                if (typeof item === "undefined") {
                    return null;
                }

                if (this.range.isContentEditable(this.current.element)) {
                    const courseid = document.getElementById("timeline-main").dataset.courseid;

                    return (
                        '<span contenteditable="false">' +
                        '<a href="' + mdlcfg.wwwroot + '/user/view.php?id=' + item.original.id + '&course=' + courseid + '"' +
                        ' target="_blank" class="usermentioned" data-uid="' + item.original.id + '">' + item.original.fullname +
                        "</a></span>"
                    );
                }

                return '@' + item.original.fullname + '@';
            },
            noMatchTemplate: function() {
                return '<span style="visibility: hidden;"></span>';
            },
            menuItemTemplate: function(item) {
                return '<img src="' + item.original.picture + '">' + item.string;
            },
            requireLeadingSpace: false,
            allowSpaces: true,
            menuShowMinLength: 3,
            lookup: 'fullname'
        });

        tribute.attach(document.querySelectorAll(".post-comment-input"));
    };

    TributeInit.prototype.remoteSearch = function(text, cb) {
        const courseid = document.getElementById("timeline-main").dataset.courseid;

        var request = Ajax.call([{
            methodname: 'format_timeline_enrolledusers',
            args: {
                search: {
                    courseid: courseid,
                    name: text
                }
            }
        }]);

        request[0].done(function(data) {
            cb(data.users);
        });
    };

    return {
        'init': function() {
            return new TributeInit();
        }
    };
});
