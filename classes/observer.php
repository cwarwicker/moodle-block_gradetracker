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
 * Handle Moodle events, such as course enrolments/unenrolments and what that means for the elbp data
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

class block_gradetracker_observer {

    /**
     * Automatically enrol users onto quals and units when enrolled onto course
     * @param  coreeventbase $data [description]
     * @return [type]              [description]
     */
    public static function gt_auto_enrol(\core\event\base $data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        // If both settings are false, don't waste our time doing anything
        if ($GT->getSetting('use_auto_enrol_quals') != 1 && $GT->getSetting('use_auto_enrol_units') != 1) {
            return true;
        }

        // If this enrolment was on a course
        if ($data->contextlevel == CONTEXT_COURSE) {

            $array = array();
            $array['courseID'] = $data->courseid;
            $array['contextLevel'] = $data->contextlevel;
            $array['userID'] = $data->relateduserid;

            return \GT\Automation::enrol($array);

        }

    }

    /**
     * Automatically unenrol users from quals and units, when unenrolled from course
     * @param  coreeventbase $data [description]
     * @return [type]              [description]
     */
    public static function gt_auto_unenrol(\core\event\base $data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        // If both settings are false, don't waste our time doing anything
        if ($GT->getSetting('use_auto_unenrol_quals') != 1 && $GT->getSetting('use_auto_unenrol_units') != 1) {
            return true;
        }

        // If this enrolment was on a course
        if ($data->contextlevel == CONTEXT_COURSE) {

            $array = array();
            $array['courseID'] = $data->courseid;
            $array['contextLevel'] = $data->contextlevel;
            $array['userID'] = $data->relateduserid;

            return \GT\Automation::unenrol($array);

        }

    }

    public static function gt_auto_modifyenrolment(\core\event\base $data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        // If both settings are false, don't waste our time doing anything
        if ($GT->getSetting('use_auto_unenrol_quals') != 1 && $GT->getSetting('use_auto_unenrol_units') != 1) {
            return true;
        }

        // If this enrolment was on a course
        if ($data->contextlevel == CONTEXT_COURSE) {

            // Make sure we can find the user_enrolment in order to get the status
            $record = $DB->get_record('user_enrolments', array('id' => $data->objectid));
            if (!$record) {
                return false;
            }

            $array = array();
            $array['courseID'] = $data->courseid;
            $array['contextLevel'] = $data->contextlevel;
            $array['userID'] = $data->relateduserid;

            // Enrolment activated
            if ($record->status == 0) {
                return \GT\Automation::enrol($array);
            } else if ($record->status == 1) {
                // Suspended
                return \GT\Automation::unenrol($array);
            }

        }

    }

}
