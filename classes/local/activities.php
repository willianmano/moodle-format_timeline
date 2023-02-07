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
 * Timeline Social course format
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\local;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use cm_info;
use action_menu;
use action_menu_link;
use action_link;
use pix_icon;
use core_courseformat\output\local\content\cm\availability;

/**
 * Activities class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities {
    /**
     * Get all course activities
     *
     * @param \stdClass $course
     * @param object $courserenderer
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_course_activities($course, $courserenderer) {
        $courseformat = course_get_format($course);

        $modinfo = $courseformat->get_modinfo();

        $section = $modinfo->get_section_info(0);

        $coursemodules = [];
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if (!$mod->is_visible_on_course_page()) {
                    continue;
                }

                $editactions = course_get_cm_edit_actions($mod);
                $editicons = self::course_section_cm_edit_actions($editactions, $courserenderer, $mod);
                $editicons .= $mod->afterediticons;

                $cmitemclass = $courseformat->get_output_classname('content\\section\\cmitem');
                $cmitem = new $cmitemclass($courseformat, $section, $mod);
                $moduleoutput = $courserenderer->render($cmitem);

                $coursemodules[] = new modinfo($mod, $moduleoutput, $editicons);
            }
        }

        return $coursemodules;
    }

    /**
     * Renders HTML for displaying the sequence of course module editing buttons
     *
     * @see course_get_cm_edit_actions()
     *
     * @param action_link[] $actions Array of action_link objects
     * @param object $courserenderer Course renderer
     * @param cm_info $mod The module we are displaying actions for.
     *
     * @return string
     *
     * @throws \coding_exception
     */
    public static function course_section_cm_edit_actions($actions, $courserenderer, cm_info $mod = null) {
        if (empty($actions)) {
            return '';
        }

        if ($mod) {
            $ownerselector = '#module-'.$mod->id;
        } else {
            debugging('You should upgrade your call to '.__FUNCTION__.' and provide $mod', DEBUG_DEVELOPER);
            $ownerselector = 'li.activity';
        }

        $icon = new pix_icon('t/edit', get_string('edit'));
        $menu = new action_menu();
        $menu->set_owner_selector($ownerselector);
        $menu->set_constraint('.course-content');
        $menu->set_menu_trigger($courserenderer->render($icon));

        foreach ($actions as $action) {
            if ($action instanceof action_menu_link) {
                $action->add_class('cm-edit-action');
            }
            $menu->add($action);
        }

        $menu->attributes['class'] .= ' section-cm-edit-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        return $courserenderer->render($menu);
    }
}
