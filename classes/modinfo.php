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

namespace format_timeline;

use cm_info;
use moodle_url;

class modinfo {
    public $cmid;
    public $instanceid;
    public $name;
    public $type = 'activity';
    public $modname;
    public $modfullname;
    public $url;
    public $iconurl;
    public $visible;
    public $humantimecreated;
    public $timecreated;
    public $timemodified;
    public $editicons;
    public $completionbox;
    public $availability;
    public $printcontent = false;
    public $content = null;

    public function __construct(cm_info $cm_info, $editicons = null, $completionbox = null, $availability = null) {
        $this->editicons = $editicons;
        $this->completionbox = $completionbox;
        $this->availability = $availability;
        $this->get_module_metadata($cm_info);
    }

    public function get_module_metadata(cm_info $cm_info) {
        global $DB;

        $moddb = $DB->get_record($cm_info->modname, ['id' => $cm_info->instance], '*', MUST_EXIST);

        $this->cmid = $cm_info->id;
        $this->instanceid = $cm_info->instance;
        $this->name = $cm_info->name;
        $this->modname = $cm_info->modname;
        $this->modfullname = $cm_info->modfullname->out();
        $this->url = new moodle_url('/mod/' . $this->modname . '/view.php', ['id' => $this->cmid]);
        $this->iconurl = $cm_info->get_icon_url();
        $this->visible = $cm_info->visible;
        $this->humantimecreated = userdate($cm_info->added);
        $this->timecreated = $cm_info->added;
        $this->timemodified = $moddb->timemodified;

        if ($this->modname == 'label') {
            $this->printcontent = true;
            $this->content = $cm_info->get_formatted_content(['overflowdiv' => true, 'noclean' => true]);
        }

//        switch ($cm_info->modname) {
//            case 'book':
//                $this->timecreated = $moddb->timecreated;
//                break;
//            case 'resource':
//            case 'folder':
//            case 'imscp':
//            case 'label':
//            case 'page':
//            case 'url':
//                $this->timecreated = $moddb->timemodified; // These resources doen't have the timecreated field.
//        }
    }
}