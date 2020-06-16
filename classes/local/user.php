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
 * Timeline user info.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

defined('MOODLE_INTERNAL') || die();

use user_picture;
use context_course;

/**
 * User info class.
 *
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user {
    /**
     * Get user info
     *
     * @param $user
     * @param $page
     *
     * @return array
     *
     * @throws \coding_exception
     */
    public static function get_userinfo($user, $page) {
        $userimg = new user_picture($user);

        $userimg->size = true;

        return [
            'img' => $userimg->get_url($page),
            'fullname' => fullname($user)
        ];
    }

    /**
     * Get user picture
     *
     * @param $user
     * @param $page
     *
     * @return \moodle_url
     *
     * @throws \coding_exception
     */
    public static function get_userpic($user, $page) {
        $userimg = new user_picture($user);

        $userimg->size = true;

        return $userimg->get_url($page);
    }

    /**
     * Check if the user can add a post into the course
     *
     * @param $context
     * @param null $user
     *
     * @return bool
     *
     * @throws \coding_exception
     */
    public static function can_add_post($context, $user = null) {
        return has_capability('format/timeline:createpost', $context, $user);
    }

    /**
     * Check if the user can delete a post into the course
     *
     * @param $post
     * @param null $user
     *
     * @return bool
     */
    public static function can_delete_post($post, $user = null) {
        global $USER;

        if (!$user) {
            $user = $USER;
        }

        if ($post->userid == $user->id) {
            return true;
        }

        if (is_siteadmin($user)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user can comment in a course post
     *
     * @param $context
     * @param null $user
     *
     * @return bool
     */
    public static function can_comment_on_post($context, $user = null) {
        return is_enrolled($context, $user) || is_siteadmin($user);
    }

    /**
     * Get all users enrolled in a course by id
     *
     * @param int $userid
     * @param context_course $context
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public static function get_by_id($userid, context_course $context) {
        global $DB;

        $ufields = user_picture::fields('u');

        list($esql, $enrolledparams) = get_enrolled_sql($context);

        $sql = "SELECT $ufields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                WHERE u.id = :userid";

        $params = array_merge($enrolledparams, ['userid' => $userid]);

        return $DB->get_record_sql($sql, $params, MUST_EXIST);
    }

    /**
     * Get all users enrolled in a course by name
     *
     * @param string $name
     * @param \stdClass $course
     * @param context_course $context
     *
     * @return array
     * @throws \dml_exception
     */
    public static function getall_by_name($name, $course, context_course $context) {
        global $DB, $USER;

        list($ufields, $searchparams, $wherecondition) = self::get_basic_search_conditions($name, $context);

        list($esql, $enrolledparams) = get_enrolled_sql($context);

        $sql = "SELECT $ufields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                WHERE $wherecondition";

        $inparams = [];
        if ($course->groupmode > 0 && (!is_siteadmin() || !has_capability('moodle/course:manageactivities', $context))) {
            $usergroups = self::get_user_course_groups($USER->id, $course->id);

            if ($usergroups) {
                list($insql, $inparams) = $DB->get_in_or_equal($usergroups, SQL_PARAMS_NAMED);

                $sql = "SELECT
                          $ufields
                        FROM {user} u
                        JOIN ($esql) je ON je.id = u.id
                        JOIN {groups_members} gm ON gm.userid = u.id 
                        WHERE
                          $wherecondition
                          AND gm.groupid {$insql}";
            }
        }

        list($sort, $sortparams) = users_order_by_sql('u');
        $sql = "$sql ORDER BY $sort";

        $params = array_merge($searchparams, $enrolledparams, $sortparams, $inparams);

        $users = $DB->get_records_sql($sql, $params, 0, 10);

        if (!$users) {
            return false;
        }

        return array_values($users);
    }

    /**
     * Helper method used by getall_by_name().
     *
     * @param string $search the search term, if any.
     * @param context_course $context course context
     * @return array with three elements:
     *     string list of fields to SELECT,
     *     array query params. Note that the SQL snippets use named parameters,
     *     string contents of SQL WHERE clause.
     */
    protected static function get_basic_search_conditions($search, context_course $context) {
        global $DB, $CFG, $USER;

        // Add some additional sensible conditions.
        $tests = ["u.id <> :guestid", "u.deleted = 0", "u.confirmed = 1", "u.id <> :loggedinuser"];
        $params = [
            'guestid' => $CFG->siteguest,
            'loggedinuser' => $USER->id
        ];

        if (!empty($search)) {
            $conditions = get_extra_user_fields($context);
            foreach (get_all_user_name_fields() as $field) {
                $conditions[] = 'u.'.$field;
            }

            $conditions[] = $DB->sql_fullname('u.firstname', 'u.lastname');

            $searchparam = '%' . $search . '%';

            $i = 0;
            foreach ($conditions as $key => $condition) {
                $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }

            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }

        $wherecondition = implode(' AND ', $tests);

        $extrafields = get_extra_user_fields($context, ['username', 'lastaccess']);
        $extrafields[] = 'username';
        $extrafields[] = 'lastaccess';
        $extrafields[] = 'maildisplay';

        $ufields = user_picture::fields('u', $extrafields);

        return [$ufields, $params, $wherecondition];
    }

    /**
     * Returns the groups ids were the user is in
     *
     * @param int $userid
     * @param int $courseid
     *
     * @return array|null
     *
     * @throws \dml_exception
     */
    public static function get_user_course_groups($userid, $courseid) {
        global $DB;

        $sql = "SELECT g.id
                FROM {groups_members} gm
                JOIN {groups} g ON g.id = gm.groupid
                WHERE gm.userid = :userid AND g.courseid = :courseid";

        $groups = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

        if (!$groups) {
            return null;
        }

        $returngroups = [];
        foreach ($groups as $group) {
            $returngroups[] = $group->id;
        }

        return $returngroups;
    }
}