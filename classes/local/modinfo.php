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
 * Timeline Social module info
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

defined('MOODLE_INTERNAL') || die();

use cm_info;
use format_timeline\local\activities\assign;
use moodle_url;

/**
 * Mod info class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modinfo {
    /** @var int Curse module ID. */
    public $cmid;
    /** @var int The module instance. */
    public $instanceid;
    /** @var string The instance name. */
    public $name;
    /** @var string Module type. */
    public $type = 'activity';
    /** @var string Module name. */
    public $modname;
    /** @var string Module fullname. */
    public $modfullname;
    /** @var string Module URL. */
    public $url;
    /** @var string Icon URL. */
    public $iconurl;
    /** @var boolean Course module visibility. */
    public $visible;
    /** @var string Time created for humans. */
    public $humantimecreated;
    /** @var int Time created. */
    public $timecreated;
    /** @var int Time modified. */
    public $timemodified;
    /** @var string Edit icons. */
    public $editicons;
    /** @var string Completion box. */
    public $completionbox;
    /** @var boolean Course module availability. */
    public $availability;
    /** @var boolean Course content is printable ex. label. */
    public $printcontent = false;
    /** @var string Content. */
    public $content = null;
    /** @var boolean Show intro. */
    public $showintro = false;
    /** @var string Intro content. */
    public $intro = null;
    /** @var string Status. */
    public $activitystatus;
    /** @var string Extra data. */
    public $activitystatusextra;
    /** @var string Submission status. */
    public $activitysubmissionstatus;

    /**
     * Constructor.
     *
     * @param cm_info $cminfo
     * @param null $editicons
     * @param null $completionbox
     * @param null $availability
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(cm_info $cminfo, $editicons = null, $completionbox = null, $availability = null) {
        $this->editicons = $editicons;
        $this->completionbox = $completionbox;
        $this->availability = $availability;
        $this->get_module_metadata($cminfo);
    }

    /**
     * Get coure module medatada
     *
     * @param cm_info $cminfo
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_module_metadata(cm_info $cminfo) {
        global $DB;

        $moddb = $DB->get_record($cminfo->modname, ['id' => $cminfo->instance], '*', MUST_EXIST);

        $this->cmid = $cminfo->id;
        $this->instanceid = $cminfo->instance;
        $this->name = $cminfo->name;
        $this->modname = $cminfo->modname;
        $this->modfullname = $cminfo->modfullname->out();
        $this->url = new moodle_url('/mod/' . $this->modname . '/view.php', ['id' => $this->cmid]);
        $this->iconurl = $cminfo->get_icon_url();
        $this->visible = $cminfo->visible;
        $this->humantimecreated = userdate($cminfo->added);
        $this->timecreated = $cminfo->added;
        $this->timemodified = $moddb->timemodified;
        $this->showintro = $cminfo->showdescription;
        $this->intro = $cminfo->get_formatted_content();

        if ($this->modname == 'label') {
            $this->printcontent = true;
            $this->content = $cminfo->get_formatted_content(['overflowdiv' => true, 'noclean' => true]);
        }

        if ($this->modname == 'assign') {
            $instance = $DB->get_record('assign', ['id' => $cminfo->instance]);

            $activity = new assign($instance, $cminfo->context);

            $this->activitystatus = $activity->status;
            $this->activitystatusextra = $activity->statusextra;
            $this->activitysubmissionstatus = $activity->submissionstatus;
        }
    }
}
