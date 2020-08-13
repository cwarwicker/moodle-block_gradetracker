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
 * Grade Tracker block
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

class block_gradetracker extends block_base {

    public function init() {

        global $CFG;

        $GT = new \block_gradetracker\GradeTracker();
        $this->title = get_string('pluginname', 'block_gradetracker');
        $this->www = $CFG->wwwroot . '/blocks/gradetracker/';
        $this->imgdir = $CFG->wwwroot . '/blocks/gradetracker/pix/';
        $this->GT = $GT;

    }

    public function get_content() {

        global $COURSE, $USER;

        $context = context_course::instance($COURSE->id);
        $course = new \block_gradetracker\Course($COURSE->id);

        if ($this->content !== null || !$USER || is_guest($context, $USER)) {
            return $this->content;
        }

        $User = new \block_gradetracker\User($USER->id);

        $this->content = new stdClass;
        $this->content->text = "";
        $this->content->text .= '<ul class="gt gt_list_none">';

        //dashboard link
        $this->content->text .= '<li><img src="'.$this->imgdir.'icons/blank_report.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'dashboard.php">'.get_string('mydashboard', 'block_gradetracker').'</a></li>';

        // View By
        if ($User->hasCapability('block/gradetracker:view_student_grids', false, $context)) {
            if ($COURSE->id <> SITEID) {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/user_student.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=student&myCourseID='.$COURSE->id.'">'.get_string('viewbystudent', 'block_gradetracker').'</a></li>';
            } else {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/user_student.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=student">'.get_string('viewbystudent', 'block_gradetracker').'</a></li>';
            }
        }

        if ($User->hasCapability('block/gradetracker:view_unit_grids', false, $context)) {
            if ($COURSE->id <> SITEID) {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/category.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=unit&myCourseID='.$COURSE->id.'">'.get_string('viewbyunit', 'block_gradetracker').'</a></li>';
            } else {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/category.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=unit">'.get_string('viewbyunit', 'block_gradetracker').'</a></li>';
            }
        }

        if ($User->hasCapability('block/gradetracker:view_class_grids', false, $context)) {
            if ($COURSE->id <> SITEID) {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/users_4.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=class&myCourseID='.$COURSE->id.'">'.get_string('viewbyclass', 'block_gradetracker').'</a></li>';
            } else {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/users_4.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=class">'.get_string('viewbyclass', 'block_gradetracker').'</a></li>';
            }
        }

        // Edit quals on course
        if ($COURSE->id <> SITEID && $User->hasCapability('block/gradetracker:edit_course_quals', false, $context)) {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/database_link.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=course&id='.$COURSE->id.'&section=quals">'.get_string('qualsoncourse:short', 'block_gradetracker').' ['.$course->countCourseQualifications(true).']</a></li>';
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/group_link.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=course&id='.$COURSE->id.'&section=userquals">'.get_string('userquals', 'block_gradetracker').'</a></li>';
        }

        // Activity links
        if ($COURSE->id <> SITEID && $User->hasCapability('block/gradetracker:edit_course_activity_refs', false, $context)) {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/document_prepare.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=course&id='.$COURSE->id.'&section=activities">'.get_string('manageactivityrefs', 'block_gradetracker').'</a></li>';
        }

        // Reporting
        if ($User->hasCapability('block/gradetracker:configure_reporting')) {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/reports.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=reporting">'.get_string('reporting', 'block_gradetracker').'</a></li>';
        }

        // Configuration
        if ($User->hasCapability('block/gradetracker:configure')) {
            $this->content->text .= '<li><br></li>';
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/cog_edit.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php">'.get_string('config', 'block_gradetracker').'</a></li>';
        }

        $this->content->text .= "</ul>";

        return $this->content;

    }

}