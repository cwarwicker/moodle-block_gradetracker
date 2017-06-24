<?php

/**
 * Handle Moodle events, such as course enrolments/unenrolments and what that means for the elbp data
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com>
 * 
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

class block_gradetracker_observer {


    /**
     * @param \core\event\base $data
     */
    public static function gt_auto_enrol(\core\event\base $data) {
        global $DB;
        $GT = new \GT\GradeTracker();
        
        if ($GT->getSetting('use_auto_enrol_quals') == 1 && $data->contextlevel == CONTEXT_COURSE){
            $context = $DB->get_record("context", array("contextlevel" => CONTEXT_COURSE, "instanceid" => $data->courseid));
            if (!$context) return true;
            
            $role = $DB->get_record("role_assignments", array("userid" => $data->relateduserid, "contextid" => $context->id));
            if (!$role) return true;
            
            $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));
            if (!$quals) return true;
            
            $GT_User = new \GT\User($data->relateduserid);
            
            foreach ($quals as $qual){
                $user_role = ($role->roleid < 5 ? "STAFF" : "STUDENT");
                $GT_User->addToQual($qual->qualid, $user_role);
            }
        }

        if ($GT->getSetting('use_auto_enrol_units') == 1 && $data->contextlevel == CONTEXT_COURSE){

            if (!$context) $context = $DB->get_record("context", array("contextlevel" => CONTEXT_COURSE, "instanceid" => $data->courseid));
            if (!$context) return true;

            if (!$role) $role = $DB->get_record("role_assignments", array("userid" => $data->userid, "contextid" => $context->id));
            if (!$role) return true;

            if (!$quals) $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));;
            if (!$quals) return true;

            if (!$GT_User) $GT_User = new \GT\User($data->userid);

            foreach ($quals as $qual){

                $Qual = new \GT\Qualification($qual->qualid);
                $units = $Qual->getUnits();

                $user_role = ($role->roleid < 5 ? "STAFF" : "STUDENT");

                foreach ($units as $unit){
                    $GT_User->addToQualUnit($qual->qualid, $unit->getID(), $user_role);
                }
            }
        }
                
    }
    
    
    public static function gt_auto_unenrol(\core\event\base $data) {
        global $DB;
    
        $GT = new \GT\GradeTracker();
        
        if ($GT->getSetting('use_auto_unenrol_quals') == 1){

            $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));
            if (!$quals) return true;

            $GT_User = new \GT\User($data->relateduserid);

            foreach ($quals as $qual){
                $GT_User->removeFromQual($qual->qualid);
            }
        }

        if ($GT->getSetting('use_auto_unenrol_units') == 1){

            if (!$quals) $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data->courseid));;
            if (!$quals) return true;

            if (!$GT_User) $GT_User = new \GT\User($data->relateduserid);

            foreach ($quals as $qual){

                $Qual = new \GT\Qualification($qual->qualid);
                $units = $Qual->getUnits();

                foreach ($units as $unit){
                    $GT_User->removeFromQualUnit($qual->qualid, $unit->getID());
                }
            }
        }
    }
}
