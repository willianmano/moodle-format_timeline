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
 * Post page
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '../../../../config.php');

require_login();

$postid = required_param('id', PARAM_INT);

$post = $DB->get_record('format_timeline_posts', array('id' => $postid), '*', MUST_EXIST);

$canview = \format_timeline\local\user::can_view_post($post);

if (!$canview) {
    \core\notification::error(get_string('youcantviewpost', 'format_timeline'));

    redirect(new \moodle_url('/course/view.php', ['id' => $post->courseid]));
}

$course = $DB->get_record('course', ['id' => $post->courseid], '*', MUST_EXIST);

$context = context_course::instance($course->id);

$coursename = format_string($course->fullname, true, ['context' => $context]);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($coursename);
$PAGE->set_title($coursename);
$PAGE->set_url('/course/format/post.php', ['id' => $post->id]);

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('sectionname', 'format_timeline'), new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('coursepost', 'format_timeline'));

$viewrenderable = new format_timeline\output\post($post, $PAGE);

$output = $PAGE->get_renderer('format_timeline');

$output->heading($coursename);

echo $output->header();

echo $output->render($viewrenderable);

echo $output->footer();

