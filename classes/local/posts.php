<?php
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
 * Timeline posts class.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Posts class.
 *
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class posts {
    /**
     * Get all available course posts
     *
     * @param $courseid
     *
     * @return array
     *
     * @throws \dml_exception
     */
    public static function get_course_posts($courseid) {
        global $DB;

        $userpicfields = \user_picture::fields('u');

        $sql = "SELECT p.id as pid, p.courseid, p.userid, p.message, p.timecreated, {$userpicfields}
                FROM {format_timeline_posts} p
                INNER JOIN {user} u ON u.id = p.userid
                WHERE p.courseid = :courseid AND p.parent IS NULL AND timedeleted IS NULL";

        $params = ['courseid' => $courseid];

        $posts = array_values($DB->get_records_sql($sql, $params));

        foreach ($posts as $key => $post) {
            $posts[$key]->humantimecreated = userdate($post->timecreated);

            $children = self::get_post_children($post->pid);

            if ($children) {
                $posts[$key]->children = $children;
            }
        }

        return $posts;
    }

    /**
     * Get post children
     *
     * @param $parentid
     *
     * @return array|false
     *
     * @throws \dml_exception
     */
    public static function get_post_children($parentid) {
        global $DB;

        $userpicfields = \user_picture::fields('u');

        $sql = "SELECT p.id as pid, p.message, p.timecreated, {$userpicfields}
                FROM {format_timeline_posts} p
                INNER JOIN {user} u ON u.id = p.userid
                WHERE p.parent = :parent AND timedeleted IS NULL
                ORDER BY pid ASC";

        $params = ['parent' => $parentid];

        $posts = $DB->get_records_sql($sql, $params);

        if ($posts) {
            return array_values($posts);
        }

        return false;
    }
}
