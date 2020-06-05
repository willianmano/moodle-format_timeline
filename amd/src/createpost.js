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
 * Create post js logic.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal_factory', 'core/modal_events', 'format_timeline/createpost_modal'],
    function($, ModalFactory, ModalEvents, CreatePostModal) {

        var SELECTORS = {
            TOGGLE_REGION: '#create-post-btn'
        };

        /**
         * Constructor for the CreatePost.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var CreatePost = function() {
            this.registerEventListeners();
        };

        /**
         * Open / close the blocks drawer.
         *
         * @method toggleCreatePost
         * @param {Event} e
         */
        CreatePost.prototype.openCreatePost = function() {
            ModalFactory.create({
                type: CreatePostModal.TYPE
            })
            .then(function(modal) {
                modal.show();
            });
        };

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        CreatePost.prototype.registerEventListeners = function() {
            $(SELECTORS.TOGGLE_REGION).click(function(e) {
                this.openCreatePost();
                e.preventDefault();
            }.bind(this));
        };

        return {
            'init': function() {
                return new CreatePost();
            }
        };
    }
);
