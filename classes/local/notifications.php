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
 * Timeline Social notifications
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

defined('MOODLE_INTERNAL') || die();

use core\message\message;
use moodle_url;

/**
 * Notifications class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {
    /** @var int The course ID. */
    public $courseid;
    /** @var string The course name. */
    public $coursename;
    /** @var int The post ID. */
    public $postid;
    /** @var \context Course context. */
    public $context;

    /**
     * Constructor.
     *
     * @param int $courseid
     * @param string $coursename
     * @param int $postid
     * @param \context $context
     */
    public function __construct($courseid, $coursename, $postid, $context) {
        $this->courseid = $courseid;
        $this->coursename = $coursename;
        $this->postid = $postid;
        $this->context = $context;
    }

    /**
     * Send the message
     *
     * @return bool
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function send_newpost_notifications() {
        $users = $this->get_users_to_notify();

        if (empty($users)) {
            return true;
        }

        $messagedata = $this->get_newpost_message_data();

        foreach ($users as $user) {
            $messagedata->userto = $user;

            message_send($messagedata);
        }

        return true;
    }

    /**
     * Get the list of users to be notifiable
     *
     * @return array
     *
     * @throws \dml_exception
     */
    protected function get_users_to_notify() {
        global $DB;

        $post = $DB->get_record('format_timeline_posts', ['id' => $this->postid]);

        if ($post->groupid) {
            $users = get_enrolled_users($this->context, '', $post->groupid);
        } else {
            $users = get_enrolled_users($this->context);
        }

        if (!$users) {
            return [];
        }

        return $users;
    }

    /**
     * Get the notification message data
     *
     * @return message
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function get_newpost_message_data() {
        global $USER;

        $newpostinthecourse = get_string('message_newpostinthecourse', 'format_timeline');
        $newpostinacourse = get_string('message_newpostinacourse', 'format_timeline');
        $clicktoaccesspost = get_string('message_clicktoaccesspost', 'format_timeline');

        $url = new moodle_url("/course/format/timeline/post.php?id={$this->postid}");

        $message = new message();
        $message->component = 'format_timeline';
        $message->name = 'timelineposts';
        $message->userfrom = $USER;
        $message->subject = $newpostinacourse;
        $message->fullmessage = "{$newpostinthecourse} {$this->coursename}";
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>'.$newpostinthecourse.' <b>'.$this->coursename.'</b>.</p>';
        $message->fullmessagehtml .= '<p><a class="btn btn-primary" href="'.$url.'">'.$clicktoaccesspost.'</a></p>';
        $message->smallmessage = $newpostinacourse;
        $message->contexturl = $url;
        $message->contexturlname = get_string('message_newpostcontextname', 'format_timeline');
        $message->courseid = $this->courseid;
        $message->notification = 1;

        return $message;
    }

    /**
     * Send the message
     *
     * @param array $users A list of users ids to be notifiable
     *
     * @return bool
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function send_mentions_notifications(array $users) {

        $messagedata = $this->get_mention_message_data();

        foreach ($users as $user) {
            $messagedata->userto = $user;

            message_send($messagedata);
        }

        return true;
    }

    /**
     * Get the notification message data
     *
     * @return message
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function get_mention_message_data() {
        global $USER;

        $youwerementioned = get_string('message_mentionuserementioned', 'format_timeline');
        $youwerementionedincourse = get_string('message_mentionuserementionedincourse', 'format_timeline', $this->coursename);
        $clicktoaccesspost = get_string('message_clicktoaccesspost', 'format_timeline');

        $url = new moodle_url("/course/format/timeline/post.php?id={$this->postid}");

        $message = new message();
        $message->component = 'format_timeline';
        $message->name = 'postmention';
        $message->userfrom = $USER;
        $message->subject = $youwerementioned;
        $message->fullmessage = "{$youwerementioned}: {$this->coursename}";
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>'.$youwerementionedincourse.'</p>';
        $message->fullmessagehtml .= '<p><a class="btn btn-primary" href="'.$url.'">'.$clicktoaccesspost.'</a></p>';
        $message->smallmessage = $youwerementioned;
        $message->contexturl = $url;
        $message->contexturlname = get_string('message_mentioncontextname', 'format_timeline');
        $message->courseid = $this->courseid;
        $message->notification = 1;

        return $message;
    }
}
