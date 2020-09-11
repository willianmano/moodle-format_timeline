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
 * Timeline post item renderer
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\output;

use format_timeline\local\user;
use templatable;
use renderable;
use renderer_base;

/**
 * Timeline post renderer class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeline_post_item implements templatable, renderable {
    /** @var \stdClass Curse post. */
    protected $item;
    /** @var \stdClass The current course page. */
    protected $page;
    /** @var \stdClass The current logged in user. */
    protected $loggedinuserinfo;

    /**
     * Constructor.
     *
     * @param \stdClass $post
     * @param \stdClass $page
     * @param \stdClass $loggedinuserinfo
     */
    public function __construct($post, $page, $loggedinuserinfo) {
        $this->item = $post;
        $this->page = $page;
        $this->loggedinuserinfo = $loggedinuserinfo;
    }

    /**
     * Export method
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        // User image who created the post.
        $this->item->userpic = user::get_userpic($this->item, $this->page);

        // Current loggedin user image.
        $this->item->loggedinuserinfo = $this->loggedinuserinfo;

        // Human readable time created.
        $this->item->humantimecreated = userdate($this->item->timecreated);

        // Can user delete the post?
        $this->item->candelete = user::can_delete_post($this->item);

        // Get the user fullname.
        $this->item->fullname = fullname($this->item);

        return $this->item;
    }
}
