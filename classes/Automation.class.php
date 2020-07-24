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
 * This class defines the automated actions which happen on moodle events
 * e.g. Formal and Homework ones
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') || die();

class Automation {

    public static function enrol($data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        // Get the role assignment from this enrolment
        $roleAssignment = \gt_get_user_role_from_context($data['courseID'], $data['contextLevel'], $data['userID']);
        if (!$roleAssignment) {
            return true;
        }

        // Get the role shortname
        $role = \gt_get_role($roleAssignment->roleid);
        if (!$role) {
            return true;
        }

        // Get the qualifications attached to this course
        $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data['courseID']));
        if (!$quals) {
            return true;
        }

        // Load user into User object
        $user = new \GT\User($data['userID']);

        // Get STUDENT role names and STAFF role names
        $shortnames = array(
        'STUDENT' => explode(",", $GT->getSetting("student_role_shortnames")),
        'STAFF' => explode(",", $GT->getSetting("staff_role_shortnames"))
        );

        $userRole = false;

        // Check which qual role they should have, based on the enrolment role
        foreach ($shortnames as $type => $values) {
            if (in_array($role->shortname, $values)) {
                $userRole = $type;
            }
        }

        // If the role for this enrolment is not defined as either STUDENT or STAFF, then we can't do anything
        if ($userRole === false) {
            return true;
        }

        // Add user to each of the quals on this course
        foreach ($quals as $qual) {

            // If we are using the auto enrol to qual setting
            if ($GT->getSetting('use_auto_enrol_quals') == 1) {
                $user->addToQual($qual->qualid, $userRole);
            }

            // If we are using the auto enrol to units setting
            if ($GT->getSetting('use_auto_enrol_units') == 1) {

                $Qual = new \GT\Qualification($qual->qualid);

                $units = $Qual->getUnits();
                foreach ($units as $unit) {
                    $user->addToQualUnit($qual->qualid, $unit->getID(), $userRole);
                }

            }

        }

        return true;

    }


    public static function unenrol($data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        $quals = $DB->get_records("bcgt_course_quals", array("courseid" => $data['courseID']));
        if (!$quals) {
            return true;
        }

        $user = new \GT\User($data['userID']);

        foreach ($quals as $qual) {

            // If we have the auto unenrol from quals setting
            if ($GT->getSetting('use_auto_unenrol_quals') == 1) {
                $user->removeFromQual($qual->qualid);
            }

            // if we have the auto unenrol from units setting
            if ($GT->getSetting('use_auto_unenrol_units') == 1) {

                $Qual = new \GT\Qualification($qual->qualid);

                $units = $Qual->getUnits();
                foreach ($units as $unit) {
                    $user->removeFromQualUnit($qual->qualid, $unit->getID());
                }

            }

        }

        return true;

    }

}