<?php
// This file is part of Timeline course format for moodle - http://moodle.org/
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
 * Timeline post renderer
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\output;

use format_timeline\local\posts;
use format_timeline\local\user;
use templatable;
use renderable;
use renderer_base;

/**
 * Post class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post implements templatable, renderable {
    /** @var \stdClass $post post object. */
    protected $post;
    /** @var \stdClass $page page object. */
    protected $page;

    /**
     * Post constructor
     *
     * @param \stdClass $post
     * @param \stdClass $page
     */
    public function __construct($post, $page) {
        $this->post = $post;
        $this->page = $page;
    }

    /**
     * Export renderer
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $DB;

        $user = $DB->get_record('user', ['id' => $this->post->userid]);

        // User image who created the post.
        $this->post->userpic = user::get_userpic($user, $this->page);

        // Current loggedin user image.
        $loggedinuserinfo = $USER;
        $userinfos = user::get_userinfo($loggedinuserinfo, $this->page);
        $loggedinuserinfo->img = $userinfos['img'];
        $loggedinuserinfo->fullname = $userinfos['fullname'];

        $this->post->loggedinuserinfo = $loggedinuserinfo;

        // Human readable time created.
        $this->post->humantimecreated = userdate($this->post->timecreated);

        // Get the user fullname.
        $this->post->fullname = fullname($user);

        if ($this->post->groupid) {
            $group = $DB->get_record('groups', ['id' => $this->post->groupid]);

            $this->post->groupname = $group->name;
        }

        $children = posts::get_post_children($this->post->id, false, $this->page);

        if ($children) {
            $this->post->children = $children;
        }

        return $this->post;
    }
}
