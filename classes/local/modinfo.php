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

/**
 * Mod info class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modinfo {
    /** @var string The course module html output. */
    public $moduleoutput;
    /** @var string The activity edit icons. */
    public $editicons;
    /** @var string Module name. */
    public $modname;
    /** @var boolean Course module visibility. */
    public $visible;
    /** @var string Module purpose. */
    public $purpose;
    /** @var int Time created. */
    public $timecreated;

    /**
     * Constructor.
     *
     * @param cm_info $cminfo
     * @param null $editicons
     * @param null $availability
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct($mod, $moduleoutput, $editicons = null) {
        $this->moduleoutput = $moduleoutput;

        $this->editicons = $editicons;

        $this->modname = $mod->modname;

        $this->visible = (bool) $mod->visible;

        $this->purpose = plugin_supports('mod', $mod->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER);

        $this->timecreated = $mod->added;
    }
}
