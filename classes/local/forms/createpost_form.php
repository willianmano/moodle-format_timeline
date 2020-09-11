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
 * The mform for creating a course post
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_timeline\local\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * The mform class for creating a post
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class createpost_form extends \moodleform {

    /**
     * Class constructor.
     *
     * @param array $formdata
     * @param array $customodata
     */
    public function __construct($formdata, $customodata = null) {
        parent::__construct(null, $customodata, 'post',  '', null, true, $formdata);

        $this->set_display_vertical();
    }

    /**
     * The form definition.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $courseid = !(empty($this->_customdata['courseid'])) ? $this->_customdata['courseid'] : null;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        $mform->addElement('hidden', 'courseid', $courseid);

        $mform->addElement('textarea', 'message', get_string('writethepost', 'format_timeline'), 'rows="4" ');
        $mform->addRule('message', get_string('required'), 'required', null, 'client');
        $mform->setType('message', PARAM_TEXT);

        if ($course->groupmode) {
            $groups = groups_get_all_groups($course->id);

            if ($groups) {
                $options[0] = get_string('allparticipants');

                foreach ($groups as $group) {
                    $options[$group->id] = $group->name;
                }

                $mform->addElement('select', 'groupid', get_string('group'), $options);
            }
        }
    }

    /**
     * A bit of custom validation for this form
     *
     * @param array $data An assoc array of field=>value
     * @param array $files An array of files
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $courseid = isset($data['courseid']) ? $data['courseid'] : null;
        $message = isset($data['message']) ? $data['message'] : null;

        if ($this->is_submitted() && (empty($message) || strlen($message) < 10)) {
            $errors['message'] = get_string('createpost_validator_message', 'format_timeline');
        }

        if (!$courseid) {
            $errors['courseid'] = get_string('invalidcourse', 'error');
        }

        if ($courseid && $courseid > 0) {
            if (!$course = $DB->get_record('course', ['id' => $courseid])) {
                $errors['courseid'] = get_string('invalidcourse', 'error');
            }
        }

        return $errors;
    }
}
