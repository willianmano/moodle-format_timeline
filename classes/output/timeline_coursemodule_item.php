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
 * Timeline course module item
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\output;

use templatable;
use renderable;
use renderer_base;
use format_timeline\local\modinfo;

/**
 * Timeline course item renderer class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeline_coursemodule_item implements templatable, renderable {
    /** @var \stdClass Course module item to render. */
    protected $item;

    /**
     * Class constructor.
     *
     * @param modinfo $modinfo
     */
    public function __construct(modinfo $modinfo) {
        $this->item = $modinfo;
    }

    /**
     * Export method
     *
     * @param renderer_base $output
     *
     * @return array|modinfo|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        return $this->item;
    }
}
