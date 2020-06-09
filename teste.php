<?php

require_once(dirname(__FILE__) . '../../../../config.php');

global $DB, $PAGE;


$course = $DB->get_record('course', ['id' => 10], '*', MUST_EXIST);

$context = context_course::instance($course->id);

if (!is_enrolled($context) && !is_siteadmin()) {
    return [];
}

$users = \format_timeline\user::getall_by_name('aluno', $context);

$returndata = [];

foreach ($users as $user) {
    $userpicture = new \user_picture($user);
    $returndata[] = [
        'id' => $user->id,
        'username' => $user->username,
        'fullname' => fullname($user),
        'picture' => $userpicture->get_url($PAGE)->out()
    ];
}
echo "<pre>";
print_r($returndata);
exit;