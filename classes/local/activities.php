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

/**
 * Activities class.
 *
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities {
    /**
     * Get all course activities
     *
     * @param $course
     * @param $courserenderer
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_course_activities($course, $courserenderer) {
        $modinfo = get_fast_modinfo($course);

        $section = $modinfo->get_section_info(0);

        $coursemodules = [];

        $completioninfo = new completion_info($course);

        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if (!$mod->is_visible_on_course_page()) {
                    continue;
                }

                $editactions = course_get_cm_edit_actions($mod);
                $editicons = self::course_section_cm_edit_actions($editactions, $courserenderer, $mod);
                $editicons .= $mod->afterediticons;

                $completionbox = $courserenderer->course_section_cm_completion($course, $completioninfo, $mod);

                $availability = $courserenderer->course_section_cm_availability($mod);

                $coursemodules[] = new modinfo($mod, $editicons, $completionbox, $availability);
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
     * @param $courserenderer The course renderer
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
        $menu->set_alignment(action_menu::TR, action_menu::BR);
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
