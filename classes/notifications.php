<?php

namespace format_timeline;

use core\message\message;

use moodle_url;

class notifications {
    public $courseid;
    public $coursename;
    public $postid;
    public $context;

    public function __construct($courseid, $coursename, $postid, $context) {
        $this->courseid = $courseid;
        $this->coursename = $coursename;
        $this->postid = $postid;
        $this->context = $context;
    }

    protected function get_users_to_notify() {
        $users = get_enrolled_users($this->context);

        if (!$users) {
            return [];
        }

        return $users;
    }

    protected function get_message_data() {
        global $USER;

        $newpostinthecourse = get_string('message_newpostinthecourse', 'format_timeline');
        $newpostinacourse = get_string('message_newpostinacourse', 'format_timeline');
        $clicktoaccesspost = get_string('message_clicktoaccesspost', 'format_timeline');

        $url = new moodle_url("/course/view.php?id={$this->courseid}#discuss-{$this->postid}");

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
        $message->contexturlname = get_string('message_contextname', 'format_timeline');
        $message->courseid = $this->courseid;

        return $message;
    }

    public function send() {
        $users = $this->get_users_to_notify();

        if (empty($users)) {
            return true;
        }

        $messagedata = $this->get_message_data();

        foreach ($users as $user) {
            $messagedata->userto = $user;

            message_send($messagedata);
        }

        return true;
    }
}