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
 * Timeline main class renderer
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline\output;

use format_timeline\local\activities;
use format_timeline\local\modinfo;
use format_timeline\local\posts;
use format_timeline\local\user;
use templatable;
use renderable;
use renderer_base;
use context_course;
use html_writer;
use url_select;

/**
 * Timeline renderer class
 *
 * @copyright  2020 onwards Willian Mano {@link https://conecti.me}
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
     * @param \stdClass $course
     * @param \stdClass $page
     * @param array $viewoptions
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
     *
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $this->output = $output;

        $this->userinfo = user::get_userinfo($USER, $this->page);

        $openactivitychoooser = $this->get_activitychooser($this->course);
        $canaddpost = user::can_add_post($this->page->context, $USER);
        $timelineitems = $this->get_course_timeline_items();

        $hasactions = false;
        if ($openactivitychoooser && $canaddpost) {
            $hasactions = true;
        }

        return [
            'courseid' => $this->course->id,
            'userinfo' => $this->userinfo,
            'openactivitychoooser' => $openactivitychoooser,
            'canaddpost' => $canaddpost,
            'hasactions' => $hasactions,
            'filterlinks' => $this->get_filters_links(),
            'timelineitems' => $timelineitems,
            'contextid' => $this->page->context->id
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
        $ordertext = '<i class="fa fa-sort-desc"></i> ';

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
            $ordertext = '<i class="fa fa-sort-asc"></i> ';
            $ordertext .= get_string('orderasc', 'format_timeline');

            return html_writer::link($url, $ordertext, ['class' => 'btn btn-outline-primary']);
        }

        $ordertext .= get_string('orderdesc', 'format_timeline');

        return html_writer::link($url, $ordertext, ['class' => 'btn btn-outline-primary']);
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
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
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
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_timelineitems() {
        // Get all course activities and posts.
        if (!isset($this->viewoptions['filter']) ||
            (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'showall')) {
            $coursemodules = activities::get_course_activities($this->course, $this->courserenderer);

            $courseposts = posts::get_course_posts($this->course);

            return array_merge($coursemodules, $courseposts);
        }

        // Get only course activities.
        if (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'onlyactivities') {
            return activities::get_course_activities($this->course, $this->courserenderer);
        }

        // Get only course posts.
        if (isset($this->viewoptions['filter']) && $this->viewoptions['filter'] == 'onlyposts') {
            return posts::get_course_posts($this->course);
        }
    }

    /**
     * Order timeline items
     *
     * @param array $timelineitems
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
     * @param \stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    private function get_activitychooser($course, $section = 0, $sectionreturn = null, $displayoptions = []) {
        global $CFG, $USER;

        // The returned control HTML can be one of the following:
        // - Only the non-ajax control (select menus of activities and resources) with a noscript fallback for non js clients.
        // - Only the ajax control (the link which when clicked produces the activity chooser modal). No noscript fallback.
        // - [Behat only]: The non-ajax control and optionally the ajax control (depending on site settings). If included, the link
        // takes priority and the non-ajax control is wrapped in a <noscript>.
        // Behat requires the third case because some features run with JS, some do not. We must include the noscript fallback.
        $behatsite = defined('BEHAT_SITE_RUNNING');
        $nonajaxcontrol = '';
        $ajaxcontrol = '';
        $courseajaxenabled = $this->course_ajax_enabled($course);
        $userchooserenabled = get_user_preferences('usemodchooser', $CFG->modchooserdefault);
        $sectionname = get_section_name($course, $section);

        // Decide what combination of controls to output:
        // During behat runs, both controls can be used in conjunction to provide non-js fallback.
        // During normal use only one control or the other will be output. No non-js fallback is needed.
        $rendernonajaxcontrol = $behatsite || !$courseajaxenabled || !$userchooserenabled || $course->id != $this->page->course->id;
        $renderajaxcontrol = $courseajaxenabled && $userchooserenabled && $course->id == $this->page->course->id;

        // The non-ajax control, which includes an entirely non-js (<noscript>) fallback too.
        if ($rendernonajaxcontrol) {
            $vertical = !empty($displayoptions['inblock']);

            // Check to see if user can add menus.
            if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
                || !($modnames = get_module_types_names()) || empty($modnames)) {
                return '';
            }

            // Retrieve all modules with associated metadata.
            $contentitemservice = \core_course\local\factory\content_item_service_factory::get_content_item_service();
            $urlparams = ['section' => $section];
            if (!is_null($sectionreturn)) {
                $urlparams['sr'] = $sectionreturn;
            }
            $modules = $contentitemservice->get_content_items_for_user_in_course($USER, $course, $urlparams);

            // Return if there are no content items to add.
            if (empty($modules)) {
                return '';
            }

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
                $link = $module->link;
                $activities[$activityclass][$link] = $module->title;
            }

            $straddactivity = get_string('addactivity');
            $straddresource = get_string('addresource');
            $strresourcelabel = get_string('addresourcetosection', null, $sectionname);
            $stractivitylabel = get_string('addactivitytosection', null, $sectionname);

            $nonajaxcontrol = html_writer::start_tag('div', array('class' => 'section_add_menus', 'id' => 'add_menus-section-'
                . $section));

            if (!$vertical) {
                $nonajaxcontrol .= html_writer::start_tag('div', array('class' => 'horizontal'));
            }

            if (!empty($activities[MOD_CLASS_RESOURCE])) {
                $select = new url_select($activities[MOD_CLASS_RESOURCE], '', array('' => $straddresource), "ressection$section");
                $select->set_help_icon('resources');
                $select->set_label($strresourcelabel, array('class' => 'accesshide'));
                $nonajaxcontrol .= $this->output->render($select);
            }

            if (!empty($activities[MOD_CLASS_ACTIVITY])) {
                $select = new url_select($activities[MOD_CLASS_ACTIVITY], '', array('' => $straddactivity), "section$section");
                $select->set_help_icon('activities');
                $select->set_label($stractivitylabel, array('class' => 'accesshide'));
                $nonajaxcontrol .= $this->output->render($select);
            }

            if (!$vertical) {
                $nonajaxcontrol .= html_writer::end_tag('div');
            }

            $nonajaxcontrol .= html_writer::end_tag('div');
        }

        // The ajax control - the 'Add an activity or resource' link.
        if ($renderajaxcontrol) {
            // The module chooser link.
            $straddeither = "<i class='fa fa-plus-circle'></i> " . get_string('addresourceoractivity');

            $ajaxcontrol .= html_writer::tag('button', $straddeither, array(
                'class' => 'section-modchooser-link btn btn-success',
                'data-action' => 'open-chooser',
                'data-sectionid' => $section,
                'data-sectionreturnid' => $sectionreturn,
            ));

            // Load the JS for the modal.
            $courseactivitychooser = $this->course_activitychooser($course->id);
        }

        // Behat only: If both controls are being included in the HTML,
        // show the link by default and only fall back to the selects if js is disabled.
        if ($behatsite && $renderajaxcontrol) {
            $nonajaxcontrol = html_writer::tag('div', $nonajaxcontrol, array('class' => 'hiddenifjs addresourcedropdown'));
            $ajaxcontrol = html_writer::tag('div', $ajaxcontrol, array('class' => 'visibleifjs addresourcemodchooser'));
        }

        // If behat is running, we should have the non-ajax control + the ajax control.
        // Otherwise, we'll have one or the other.
        return $ajaxcontrol . $nonajaxcontrol . $courseactivitychooser;
    }

    /**
     * Build the HTML for the module chooser javascript popup.
     *
     * @param int $courseid The course id to fetch modules for.
     * @return string
     */
    protected function course_activitychooser($courseid) {

        if (!$this->page->requires->should_create_one_time_item_now('core_course_modchooser')) {
            return '';
        }

        // Build an object of config settings that we can then hook into in the Activity Chooser.
        $chooserconfig = (object) [
            'tabmode' => get_config('core', 'activitychoosertabmode'),
        ];
        $this->page->requires->js_call_amd('core_course/activitychooser', 'init', [$courseid, $chooserconfig]);

        return '';
    }

    /**
     * Determine whether course ajax should be enabled for the specified course
     *
     * @param stdClass $course The course to test against
     * @return boolean Whether course ajax is enabled or note
     */
    private function course_ajax_enabled($course) {
        global $PAGE, $SITE;

        // Check that the theme suports
        if (!$PAGE->theme->enablecourseajax) {
            return false;
        }

        // Check that the course format supports ajax functionality
        // The site 'format' doesn't have information on course format support
        if ($SITE->id !== $course->id) {
            $courseformatajaxsupport = course_format_ajax_support($course->format);
            if (!$courseformatajaxsupport->capable) {
                return false;
            }
        }

        // All conditions have been met so course ajax should be enabled
        return true;
    }
}
