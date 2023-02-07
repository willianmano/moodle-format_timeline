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
 * Timeline posts class
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

use context_course;
use format_timeline\local\user;

defined('MOODLE_INTERNAL') || die();

/**
 * Posts class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class posts {

    /** @var int Post children limit. */
    const CHILDREN_LIMIT = 5;

    /**
     * Get all available course posts
     *
     * @param \stdClass $course
     * @param boolean $limit
     *
     * @return array
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function get_course_posts($course, $limit = true) {
        $userfields = \core_user\fields::for_userpic();
        $userpicfields = $userfields->get_sql('u', false, '', '', false);

        $posts = self::get_public_posts($course->id, $userpicfields->selects);

        if ($course->groupmode > 0) {
            $groupsposts = self::get_groups_posts($course->id, $userpicfields->selects);

            if ($groupsposts) {
                $posts = array_values(array_merge($posts, $groupsposts));
            }
        }

        foreach ($posts as $key => $post) {
            $posts[$key]->message = format_text($post->message);
            $posts[$key]->humantimecreated = userdate($post->timecreated);

            $children = self::get_post_children($post->pid, $limit);

            if ($children) {
                $childrencount = self::count_post_children($post->pid);

                $posts[$key]->children = $children;
                $posts[$key]->childrencount = $childrencount;
                $posts[$key]->childdiff = $childrencount - self::CHILDREN_LIMIT;
                $posts[$key]->hasmorechildren = $childrencount > self::CHILDREN_LIMIT;
            }
        }

        return $posts;
    }

    /**
     * Returns the groups's posts
     *
     * @param int $courseid
     * @param string $userpicfields
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function get_groups_posts($courseid, $userpicfields) {
        global $USER, $DB;

        if (is_siteadmin() || has_capability('moodle/course:manageactivities', context_course::instance($courseid))) {
            return self::get_all_groups_posts($courseid, $userpicfields);
        }

        $usergroups = user::get_user_course_groups($USER->id, $courseid);

        if ($usergroups) {
            list($insql, $inparams) = $DB->get_in_or_equal($usergroups, SQL_PARAMS_NAMED);

            $sql = "SELECT
                      p.id as pid,
                      p.courseid,
                      p.userid,
                      p.message,
                      p.timecreated,
                      g.id as groupid,
                      g.name as groupname,
                      {$userpicfields}
                    FROM {format_timeline_posts} p
                    INNER JOIN {user} u ON u.id = p.userid
                    INNER JOIN {groups} g ON g.id = p.groupid
                    WHERE
                      p.courseid = :courseid
                      AND p.parent IS NULL
                      AND p.timedeleted IS NULL
                      AND p.groupid {$insql}";

            $params = array_merge($inparams, ['courseid' => $courseid]);

            return array_values($DB->get_records_sql($sql, $params));
        }
    }

    /**
     * Get all posts from all groups
     *
     * @param int $courseid
     * @param int $userpicfields
     *
     * @return array
     *
     * @throws \dml_exception
     */
    protected static function get_all_groups_posts($courseid, $userpicfields) {
        global $DB;

        $sql = "SELECT
                  p.id as pid,
                  p.courseid,
                  p.userid,
                  p.message,
                  p.timecreated,
                  g.id as groupid,
                  g.name as groupname,
                  {$userpicfields}
                FROM {format_timeline_posts} p
                INNER JOIN {user} u ON u.id = p.userid
                INNER JOIN {groups} g ON g.id = p.groupid
                WHERE
                  p.courseid = :courseid
                  AND p.parent IS NULL
                  AND p.timedeleted IS NULL
                  AND p.groupid IS NOT NULL";

        $params = ['courseid' => $courseid];

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get posts that are not assigned to any group
     *
     * @param int $courseid
     * @param string $userpicfields
     *
     * @return array
     *
     * @throws \dml_exception
     */
    protected static function get_public_posts($courseid, $userpicfields) {
        global $DB;

        $sql = "SELECT
                  p.id as pid,
                  p.courseid,
                  p.userid,
                  p.message,
                  p.timecreated,
                  {$userpicfields}
                FROM {format_timeline_posts} p
                INNER JOIN {user} u ON u.id = p.userid
                WHERE
                  p.courseid = :courseid
                  AND p.parent IS NULL
                  AND p.timedeleted IS NULL
                  AND p.groupid IS NULL";

        $params = ['courseid' => $courseid];

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get post children
     *
     * @param int $parentid
     * @param boolean $limit
     * @param \stdClass $page
     *
     * @return array|false
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function get_post_children($parentid, $limit = true, $page = null) {
        global $DB, $PAGE;

        if (!$page) {
            $page = $PAGE;
        }

        $userfields = \core_user\fields::for_userpic();
        $userpicfields = $userfields->get_sql('u', false, '', '', false);

        $sql = "SELECT p.id as pid, p.message, p.timecreated, {$userpicfields->selects}
                FROM {format_timeline_posts} p
                INNER JOIN {user} u ON u.id = p.userid
                WHERE p.parent = :parent AND timedeleted IS NULL
                ORDER BY pid ASC";

        if ($limit) {
            $sql .= " limit " . self::CHILDREN_LIMIT;
        }

        $params = ['parent' => $parentid];

        $posts = $DB->get_records_sql($sql, $params);

        if ($posts) {
            foreach ($posts as $key => $post) {
                $posts[$key]->fullname = fullname($post);
                $posts[$key]->userpic = user::get_userpic($post, $page);
            }

            return array_values($posts);
        }

        return false;
    }

    /**
     * Returns the post children count
     *
     * @param int $parentid
     *
     * @return int
     *
     * @throws \dml_exception
     */
    protected static function count_post_children($parentid) {
        global $DB;

        return $DB->count_records('format_timeline_posts', ['parent' => $parentid]);
    }
}
