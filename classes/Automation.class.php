<?php
/**
 * Automation
 *
 * This class defines the automated actions which happen on moodle events
 * e.g. Formal and Homework ones
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

namespace GT;

defined('MOODLE_INTERNAL') || die();

class Automation
{

  public static function enrol($data){

    global $DB;

    $GT = new \GT\GradeTracker();

    // Get the role assignment from this enrolment
    $roleAssignment = \gt_get_user_role_from_context($data['courseID'], $data['contextLevel'], $data['userID']);
    if (!$roleAssignment) return true;

    // Get the role shortname
    $role = \gt_get_role($roleAssignment->roleid);
    if (!$role) return true;

    // Get the qualifications attached to this course
    $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data['courseID']));
    if (!$quals) return true;

    // Load user into User object
    $user = new \GT\User($data['userID']);

    // Get STUDENT role names and STAFF role names
    $shortnames = array(
      'STUDENT' => explode(",", $GT->getSetting("student_role_shortnames")),
      'STAFF' => explode(",", $GT->getSetting("staff_role_shortnames"))
    );

    $userRole = false;

    // Check which qual role they should have, based on the enrolment role
    foreach($shortnames as $type => $values){
      if (in_array($role->shortname, $values)) $userRole = $type;
    }

    // If the role for this enrolment is not defined as either STUDENT or STAFF, then we can't do anything
    if ($userRole === false) return true;

    // Add user to each of the quals on this course
    foreach ($quals as $qual){

      // If we are using the auto enrol to qual setting
      if ($GT->getSetting('use_auto_enrol_quals') == 1){
        $user->addToQual($qual->qualid, $userRole);
      }

      // If we are using the auto enrol to units setting
      if ($GT->getSetting('use_auto_enrol_units') == 1){

        $Qual = new \GT\Qualification($qual->qualid);

        $units = $Qual->getUnits();
        foreach ($units as $unit){
            $user->addToQualUnit($qual->qualid, $unit->getID(), $userRole);
        }

      }

    }

    return true;

  }


  public static function unenrol($data){

    global $DB;

    $GT = new \GT\GradeTracker();

    $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data['courseID']));
    if (!$quals) return true;

    $user = new \GT\User($data['userID']);

    foreach($quals as $qual){

      // If we have the auto unenrol from quals setting
      if ($GT->getSetting('use_auto_unenrol_quals') == 1){
        $user->removeFromQual($qual->qualid);
      }

      // if we have the auto unenrol from units setting
      if ($GT->getSetting('use_auto_unenrol_units') == 1){

        $Qual = new \GT\Qualification($qual->qualid);

        $units = $Qual->getUnits();
        foreach ($units as $unit){
            $user->removeFromQualUnit($qual->qualid, $unit->getID());
        }

      }

    }

    return true;

  }



}