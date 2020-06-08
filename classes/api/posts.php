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
 * Timeline Social course format.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\api;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use context_course;
use format_timeline\notifications;
use format_timeline\user;

class posts extends external_api {
    public static function create_parameters() {
        return new external_function_parameters([
            'post' => new external_single_structure([
                'course' => new external_value(PARAM_INT, 'The course id', VALUE_REQUIRED),
                'message' => new external_value(PARAM_TEXT, 'The post message', VALUE_REQUIRED),
                'parent' => new external_value(PARAM_INT, 'The parent post', VALUE_OPTIONAL)
            ])
        ]);
    }

    public static function create($post) {
        global $DB, $USER;

        self::validate_parameters(self::create_parameters(), ['post' => $post]);

        $post = (object)$post;

        $course = $DB->get_record('course', ['id' => $post->course], '*', MUST_EXIST);
        $context = context_course::instance($post->course);

        if (!$post->parent) {
            if (!user::can_add_post($context, $USER)) {
                throw new \moodle_exception(get_string('youcantaddpost', 'format_timeline'));
            }
        }

        if (!user::can_comment_on_post($context, $USER)) {
            throw new \moodle_exception(get_string('onlyenrolleduserscancomment', 'format_timeline'));
        }

        // Posts only can have 1 parent level.
        if ($post->parent) {
            $parent = $DB->get_record('format_timeline_posts', ['id' => $post->parent], '*', MUST_EXIST);

            if ($parent->parent) {
                $post->parent = $parent->parent;
            }
        }

        $post->message = trim($post->message);
        $post->userid = $USER->id;
        $post->timecreated = time();
        $post->timemodified = time();

        $postid = $DB->insert_record('format_timeline_posts', $post);

        if (!$post->parent) {
            \core\notification::success(get_string('postcreated', 'format_timeline'));

            $notification = new notifications($course->id, $course->fullname, $postid, $context);
            $notification->send();
        }

        return [
            'status' => 'ok',
            'message' => get_string('postcreated', 'format_timeline')
        ];
    }

    public static function create_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_TEXT, 'Return message')
            )
        );
    }

    public static function delete_parameters() {
        return new external_function_parameters([
            'post' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The post id', VALUE_REQUIRED)
            ])
        ]);
    }

    public static function delete($post) {
        global $DB, $USER;

        self::validate_parameters(self::delete_parameters(), ['post' => $post]);

        $post = (object)$post;

        $dbpost = $DB->get_record('format_timeline_posts', ['id' => $post->id], '*', MUST_EXIST);

        if (!user::can_delete_post($dbpost, $USER)) {
            throw new \moodle_exception(get_string('youcantdeletepost', 'format_timeline'));
        }

        $dbpost->timedeleted = time();

        $DB->update_record('format_timeline_posts', $dbpost);

        return [
            'status' => 'ok',
            'message' => get_string('postdeleted', 'format_timeline')
        ];
    }

    public static function delete_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_TEXT, 'Return message')
            )
        );
    }
}