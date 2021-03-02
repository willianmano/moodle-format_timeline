<?php

class bkp {
    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * @param \stdClass $course
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
     * @param \stdClass $course
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
     * @param array $modules A set of modules as returned form see get_module_metadata
     * @param object $course The course that will be displayed
     *
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