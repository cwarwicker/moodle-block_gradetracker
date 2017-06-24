<?php
/**
 * Grade Tracker block
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com> <moodlesupport@bedford.ac.uk>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

class block_gradetracker extends block_base {
    
    public function init() {
        
        global $CFG;
        
        $GT = new \GT\GradeTracker();
        $this->title = get_string('pluginname', 'block_gradetracker');
        $this->www = $CFG->wwwroot . '/blocks/gradetracker/';
        $this->imgdir = $CFG->wwwroot . '/blocks/gradetracker/pix/';
        $this->GT = $GT;
        
    }
       
    public function get_content() {
        
        global $COURSE, $USER;
                 
        $context = context_course::instance($COURSE->id);
        $course = new \GT\Course($COURSE->id);
        
        if ($this->content !== null || !$USER || $USER->id < 1) {
            return $this->content;
        }
        
        $User = new \GT\User($USER->id);
        
        $this->content =  new stdClass;
        $this->content->text = "";
        $this->content->text .= '<ul class="gt gt_list_none">';
        
        
        //dashboard link
        $this->content->text .= '<li><img src="'.$this->imgdir.'icons/blank_report.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'dashboard.php">'.get_string('mydashboard', 'block_gradetracker').'</a></li>';
        
        
        // View By
        if ($User->hasCapability('block/gradetracker:view_student_grids', false, $context))
        {
            if ($COURSE->id <> SITEID)
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/user_student.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=student&myCourseID='.$COURSE->id.'">'.get_string('viewbystudent', 'block_gradetracker').'</a></li>';
            }
            else
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/user_student.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=student">'.get_string('viewbystudent', 'block_gradetracker').'</a></li>';
            }
        }
        
        if ($User->hasCapability('block/gradetracker:view_unit_grids', false, $context))
        {
            if ($COURSE->id <> SITEID)
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/category.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=unit&myCourseID='.$COURSE->id.'">'.get_string('viewbyunit', 'block_gradetracker').'</a></li>';
            }
            else
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/category.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=unit">'.get_string('viewbyunit', 'block_gradetracker').'</a></li>';
            }
        }
        
        if ($User->hasCapability('block/gradetracker:view_class_grids', false, $context))
        {
            if ($COURSE->id <> SITEID)
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/users_4.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=class&myCourseID='.$COURSE->id.'">'.get_string('viewbyclass', 'block_gradetracker').'</a></li>';
            }
            else
            {
                $this->content->text .= '<li><img src="'.$this->imgdir.'icons/users_4.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'choose.php?type=class">'.get_string('viewbyclass', 'block_gradetracker').'</a></li>';
            }
        }
        
        
        
        // Edit quals on course
        if ($COURSE->id <> SITEID && $User->hasCapability('block/gradetracker:edit_course_quals', false, $context))
        {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/database.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=course&id='.$COURSE->id.'&section=quals">'.get_string('qualsoncourse:short', 'block_gradetracker').' ['.$course->countCourseQualifications(true).']</a></li>';
        }
      
        // Activity links
        if ($COURSE->id <> SITEID && $User->hasCapability('block/gradetracker:edit_course_activity_refs', false, $context))
        {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/document_prepare.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=course&id='.$COURSE->id.'&section=activities">'.get_string('manageactivityrefs', 'block_gradetracker').'</a></li>';
        }
        
        // Reporting
        if ($User->hasCapability('block/gradetracker:configure_reporting'))
        {
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/reports.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php?view=reporting">'.get_string('reporting', 'block_gradetracker').'</a></li>';
        }
        
        // Configuration
        if ($User->hasCapability('block/gradetracker:configure'))
        {
            $this->content->text .= '<li><br></li>';
            $this->content->text .= '<li><img src="'.$this->imgdir.'icons/cog_edit.png" class="gt_block_icon" alt="" /> <a href="'.$this->www.'config.php">'.get_string('config', 'block_gradetracker').'</a></li>';
        }

        $this->content->text .= "</ul>";
        
        

        return $this->content;
        
    }
    
    public function cron() 
    {
        
    }
    
} 