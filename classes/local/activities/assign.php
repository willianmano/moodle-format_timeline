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
 * Timeline Social assign activity
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local\activities;

defined('MOODLE_INTERNAL') || die();

/**
 * Assign
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign {
    /** @var \stdClass Module instance. */
    protected $instance;
    /** @var \context Context instance. */
    protected $context;
    /** @var string Activity status. */
    public $status;
    /** @var string Extra activity status. */
    public $statusextra;
    /** @var string Submission status. */
    public $submissionstatus;

    /**
     * Assign constructor
     *
     * @param \stdClass $instance
     * @param \context $context
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($instance, $context) {
        $this->instance = $instance;
        $this->context = $context;

        $this->submission_status_meta();

        $this->status_meta($instance->allowsubmissionsfromdate, $instance->duedate, $instance->cutoffdate);
    }

    /**
     * Returns activity status meta info
     *
     * @param string $allowsubmissionsfromdate
     * @param string $duedate
     * @param string $cutoffdate
     *
     * @throws \coding_exception
     */
    public function status_meta($allowsubmissionsfromdate, $duedate, $cutoffdate) {
        if (!has_capability('mod/assign:submit', $this->context)) {
            return;
        }

        if ($allowsubmissionsfromdate > time()) {
            $this->status = "<span class='badge badge-danger'>" .
                                get_string('notopenedyet', 'format_timeline') .
                            "</span>";

            $date = userdate($allowsubmissionsfromdate);
            $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionsfromdatesummary', 'format_timeline', $date));

            return;
        }

        if ($this->submissionstatus == 'submitted') {
            $this->status = "<span class='badge badge-success'>" .
                                get_string('submissionstatus_' . $this->submissionstatus, 'assign') .
                            "</span>";

            return;
        }

        if ($cutoffdate < time()) {
            $this->status = "<span class='badge badge-danger'>" .
                                get_string('closed', 'format_timeline') .
                            "</span>";

            $this->status .= " <span class='badge badge-dark'>" .
                                get_string('submissionstatus_' . $this->submissionstatus, 'assign') .
                            "</span>";

            $this->statusextra = '';

            return;
        }

        if ($duedate < time()) {
            $this->status = "<span class='badge badge-warning'>" .
                                get_string('delayed', 'format_timeline') .
                            "</span>";

            $this->status .= " <span class='badge badge-dark'>" .
                                get_string('submissionstatus_' . $this->submissionstatus, 'assign') .
                            "</span>";

            $date = userdate($duedate);

            $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionscutoffdatesummary', 'format_timeline', $date));

            return;
        }

        $this->status = "<span class='badge badge-success'>" .
                            get_string('open', 'format_timeline') .
                        "</span>";

        $this->status .= " <span class='badge badge-dark'>" .
                            get_string('submissionstatus_' . $this->submissionstatus, 'assign') .
                        "</span>";

        $date = userdate($duedate);

        $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionsuntildatesummary', 'format_timeline', $date));
    }

    /**
     * Returns activity submission status meta info
     *
     * @return bool
     *
     * @throws \dml_exception
     */
    protected function submission_status_meta() {
        global $DB, $USER;

        $params = ['assignment' => $this->instance->id, 'userid' => $USER->id, 'groupid' => 0];

        $usersubmission = $DB->get_records('assign_submission', $params, 'attemptnumber DESC', '*', 0, 1);

        if (!$usersubmission) {
            return false;
        }

        $usersubmission = reset($usersubmission);

        $this->submissionstatus = $usersubmission->status;
    }
}
