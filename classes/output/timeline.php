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
 * Timeline main class renderer.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\output;

use format_timeline\activities;
use format_timeline\modinfo;
use format_timeline\posts;
use format_timeline\user;
use templatable;
use renderable;
use renderer_base;
use context_course;
use html_writer;
use url_select;

/**
 * Timeline renderer class.
 *
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeline implements templatable, renderable {
    /** @var \stdClass The course object. */
    protected $course;
    /** @var \stdClass The page object. */
    protected $page;
    /** @var \stdClass The course renderer. */
    protected $courserenderer;
    /** @var \stdClass The output renderer. */
    protected $output;
    /** @var \stdClass The current user info. */
    protected $userinfo;
    /** @var \stdClass The view options. */
    protected $viewoptions;

    /**
     * Timeline constructor.
     *
     * @param $course
     * @param $page
     * @param null $viewoptions
     */
    public function __construct($course, $page, $viewoptions = null) {
        $this->course = $course;
        $this->page = $page;
        $this->viewoptions = $viewoptions;
        $this->courserenderer = $this->page->get_renderer('core', 'course');
    }

    /**
     * Export renderer
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $this->output = $output;

        $this->userinfo = user::get_userinfo($USER, $this->page);

        $openactivitychoooser = $this->get_activitychooser($this->course, 0);
        $canaddpost = user::can_add_post($this->page->context, $USER);
        $timelineitems = $this->get_course_timeline_items();

        $hasactions = true;
        if (!$openactivitychoooser || !$canaddpost) {
            $hasactions = false;
        }

        return [
            'courseid' => $this->course->id,
            'userinfo' => $this->userinfo,
            'openactivitychoooser' => $openactivitychoooser,
            'canaddpost' => $canaddpost,
            'hasactions' => $hasactions,
            'filterlinks' => $this->get_filters_links(),
            'timelineitems' => $timelineitems
        ];
    }

    /**
     * Get the course format filters
     *
     * @return array
     *
     * @throws \coding_exception
     */
    private function get_filters_links() {
        $links = $this->get_fiters_links();
        $links['orderlink'] = $this->get_link_order();

        return $links;
    }

    /**
     * Get the order text and link
     *
     * @return string
     *
     * @throws \coding_exception
     */
    private function get_link_order() {
        $ordertext = '<i class="fa fa-sort"></i> ';

        $url = $this->page->url;

        if (isset($this->viewoptions['filter'])) {
            $url->param('filter', $this->viewoptions['filter']);
        }

        $url->param('order', 'desc');

        if (isset($this->viewoptions['order']) && $this->viewoptions['order'] == 'desc') {
            $url->param('order', 'asc');
        }

        if (isset($this->viewoptions['order']) && $this->viewoptions['order'] == 'desc') {
            $this->page->url->order = 'asc';
            $ordertext .= get_string('orderasc', 'format_timeline');

            return html_writer::link($url, $ordertext);
        }

        $ordertext .= get_string('orderdesc', 'format_timeline');

        return html_writer::link($url, $ordertext);
    }

    /**
     * Get the filters texts and links
     *
     * @return array
     */
    private function get_fiters_links() {
        $showallurl = $this->page->url;
        $showallurl->param('filter', 'showall');

        $onlyactivitiesurl = $this->page->url;
        $onlyactivitiesurl->param('filter', 'onlyactivities');

        $onlypostsurl = $this->page->url;
        $onlypostsurl->param('filter', 'onlyposts');

        if (isset($this->viewoptions['order'])) {
            $showallurl->param('order', $this->viewoptions['order']);
            $onlyactivitiesurl->param('order', $this->viewoptions['order']);
            $onlypostsurl->param('order', $this->viewoptions['order']);
        }

        return [
            'showall' => $showallurl,
            'onlyactivities' => $onlyactivitiesurl,
            'onlyposts' => $onlypostsurl
        ];
    }

    /**
     * Get and filter the course posts, activities and resources
     *
     * @return string
     */
    protected function get_course_timeline_items() {
        global $USER;

        $output = '';

        $timelineitems = $this->order_timelineitems($this->get_timelineitems());

        $cancommentonposts = user::can_comment_on_post($this->page->context, $USER);
        foreach ($timelineitems as $item) {
            if ($item instanceof modinfo) {
                $itemrenderer = new timeline_coursemodule_item($item);

                $output .= $this->output->render($itemrenderer);

                continue;
            }

            // Injects the permission to comment or not in the posts.
            $item->cancommentonposts = $cancommentonposts;

            $itemrenderer = new timeline_post_item($item, $this->page, $this->userinfo);

            $output .= $this->output->render($itemrenderer);
        }

        return $output;
    }

    /**
     * Get all course items based on filters
     *
     * @return array
     */
    protected function get_timelineitems() {
        // Get all course activities and posts.
        if (!isset($this->viewoptions['filter']) ||
            (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'showall')) {
            $coursemodules = activities::get_course_activities($this->course, $this->courserenderer);

            $courseposts = posts::get_course_posts($this->course->id);

            return array_merge($coursemodules, $courseposts);
        }

        // Get only course activities.
        if (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'onlyactivities') {
            return activities::get_course_activities($this->course, $this->courserenderer);
        }

        // Get only course posts.
        if (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'onlyposts') {
            return posts::get_course_posts($this->course->id);
        }
    }

    /**
     * Order timeline items
     *
     * @param $timelineitems
     *
     * @return mixed
     */
    protected function order_timelineitems($timelineitems) {
        if (isset($this->viewoptions['order']) && $this->viewoptions['order'] == 'desc') {
            // Ordem decrescente.
            usort($timelineitems, function($a, $b) {
                return $a->timecreated > $b->timecreated;
            });
        } else {
            usort($timelineitems, function($a, $b) {
                return $a->timecreated < $b->timecreated;
            });
        }

        return $timelineitems;
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     *
     * @return string
     *
     * @throws \coding_exception
     */
    private function get_activitychooser($course, $section = 0, $sectionreturn = null, $displayoptions = []) {
        global $CFG;

        $vertical = !empty($displayoptions['inblock']);

        // Check to see if user can add menus and there are modules to add.
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
            || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }

        // Retrieve all modules with associated metadata.
        $modules = get_module_metadata($course, $modnames, $sectionreturn);
        $urlparams = array('section' => $section);

        // We'll sort resources and activities into two lists.
        $activities = array(MOD_CLASS_ACTIVITY => array(), MOD_CLASS_RESOURCE => array());

        foreach ($modules as $module) {
            $activityclass = MOD_CLASS_ACTIVITY;
            if ($module->archetype == MOD_ARCHETYPE_RESOURCE) {
                $activityclass = MOD_CLASS_RESOURCE;
            } else if ($module->archetype === MOD_ARCHETYPE_SYSTEM) {
                // System modules cannot be added by user, do not add to dropdown.
                continue;
            }
            $link = $module->link->out(true, $urlparams);
            $activities[$activityclass][$link] = $module->title;
        }

        $straddactivity = get_string('addactivity');
        $straddresource = get_string('addresource');
        $sectionname = get_section_name($course, $section);
        $strresourcelabel = get_string('addresourcetosection', null, $sectionname);
        $stractivitylabel = get_string('addactivitytosection', null, $sectionname);

        $output = html_writer::start_tag('div', array('class' => 'section_add_menus', 'id' => 'add_menus-section-' . $section));

        if (!$vertical) {
            $output .= html_writer::start_tag('div', array('class' => 'horizontal'));
        }

        if (!empty($activities[MOD_CLASS_RESOURCE])) {
            $select = new url_select($activities[MOD_CLASS_RESOURCE], '', ['' => $straddresource], "ressection$section");
            $select->set_help_icon('resources');
            $select->set_label($strresourcelabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!empty($activities[MOD_CLASS_ACTIVITY])) {
            $select = new url_select($activities[MOD_CLASS_ACTIVITY], '', ['' => $straddactivity], "section$section");
            $select->set_help_icon('activities');
            $select->set_label($stractivitylabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!$vertical) {
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div');

        if ($this->course_ajax_enabled($course) && $course->id == $this->page->course->id) {
            // Modchooser can be added only for the current course set on the page!
            $straddeither = "<i class='fa fa-plus-circle'></i> " . get_string('addresourceoractivity');

            // The module chooser link.
            $span = html_writer::tag('span', $straddeither, array('class' => 'section-modchooser-text'));

            $modchooser = html_writer::start_tag('div', [
                'class' => 'section btn btn-success',
                'id' => 'section-' . $section,
                'role' => 'region',
                'aria-label' => $sectionname
            ]);

            $modchooser .= html_writer::tag('span', $span, array('class' => 'section-modchooser-link'));

            $modchooser .= html_writer::end_tag('div');

            // Wrap the normal output in a noscript div.
            $usemodchooser = get_user_preferences('usemodchooser', $CFG->modchooserdefault);
            if ($usemodchooser) {
                $output = html_writer::tag('div', $output, array('class' => 'hiddenifjs addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'visibleifjs addresourcemodchooser'));
            } else {
                // If the module chooser is disabled, we need to ensure that the dropdowns are shown even if javascript is disabled.
                $output = html_writer::tag('div', $output, array('class' => 'show addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'hide addresourcemodchooser'));
            }
            $output = $this->course_modchooser($modules, $course) . $modchooser . $output;
        }

        return $output;
    }

    /**
     * Verify if ajax is enabled
     *
     * @param $course
     *
     * @return bool
     */
    protected function course_ajax_enabled($course) {
        global $PAGE, $SITE;

        // Check that the theme suports.
        if (!$PAGE->theme->enablecourseajax) {
            return false;
        }

        // Check that the course format supports ajax functionality.
        // The site 'format' doesn't have information on course format support.
        if ($SITE->id !== $course->id) {
            $courseformatajaxsupport = course_format_ajax_support($course->format);
            if (!$courseformatajaxsupport->capable) {
                return false;
            }
        }

        // All conditions have been met so course ajax should be enabled.
        return true;
    }

    /**
     * Build the HTML for the module chooser javascript popup
     *
     * @param array $modules A set of modules as returned form @see
     * get_module_metadata
     * @param object $course The course that will be displayed
     * @return string The composed HTML for the module
     */
    private function course_modchooser($modules, $course) {
        if (!$this->page->requires->should_create_one_time_item_now('core_course_modchooser')) {
            return '';
        }

        $modchooser = new \core_course\output\modchooser($course, $modules);

        return $this->output->render($modchooser);
    }
}
