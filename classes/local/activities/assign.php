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
 * Timeline Social assign activity.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local\activities;

defined('MOODLE_INTERNAL') || die();

class assign {
    protected $instance;
    protected $context;

    public $status;
    public $statusextra;
    public $submissionstatus;

    public function __construct($instance, $context) {
        $this->instance = $instance;
        $this->context = $context;

        $this->submission_status_meta();

        $this->status_meta($instance->allowsubmissionsfromdate, $instance->duedate, $instance->cutoffdate);
    }

    public function status_meta($allowsubmissionsfromdate, $duedate, $cutoffdate) {
        if (!has_capability('mod/assign:submit', $this->context)) {
            return;
        }

        if ($allowsubmissionsfromdate > time()) {
            $this->status = "<span class='badge badge-danger'>not opened yet</span>";

            $date = userdate($allowsubmissionsfromdate);
            $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionsfromdatesummary', 'format_timeline', $date));

            return;
        }

        if ($this->submissionstatus == 'submitted') {
            $this->status = "<span class='badge badge-success'>".get_string('submissionstatus_' . $this->submissionstatus, 'assign')."</span>";

            return;
        }

        if ($cutoffdate < time()) {
            $this->status = "<span class='badge badge-danger'>closed</span>";
            $this->status .= " <span class='badge badge-dark'>".get_string('submissionstatus_' . $this->submissionstatus, 'assign')."</span>";
            $this->statusextra = '';

            return;
        }

        if ($duedate < time()) {
            $this->status = "<span class='badge badge-warning'>delayed</span>";
            $this->status .= " <span class='badge badge-dark'>".get_string('submissionstatus_' . $this->submissionstatus, 'assign')."</span>";

            $date = userdate($duedate);
            $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionscutoffdatesummary', 'format_timeline', $date));

            return;
        }

        $this->status = "<span class='badge badge-success'>open to submit</span>";
        $this->status .= " <span class='badge badge-dark'>".get_string('submissionstatus_' . $this->submissionstatus, 'assign')."</span>";

        $date = userdate($duedate);
        $this->statusextra = \html_writer::tag('p', get_string('allowsubmissionsuntildatesummary', 'format_timeline', $date));
    }

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
