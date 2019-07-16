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
   * Automatically enrol users onto quals and units when enrolled onto course
   * @param  coreeventbase $data [description]
   * @return [type]              [description]
   */
    public static function gt_auto_enrol(\core\event\base $data){

      global $DB;

      $GT = new \GT\GradeTracker();

      // If both settings are false, don't waste our time doing anything
      if ($GT->getSetting('use_auto_enrol_quals') != 1 && $GT->getSetting('use_auto_enrol_units') != 1) return true;

      // If this enrolment was on a course
      if ($data->contextlevel == CONTEXT_COURSE){

        $array = array();
        $array['courseID'] = $data->courseid;
        $array['contextLevel'] = $data->contextlevel;
        $array['userID'] = $data->relateduserid;

        return \GT\Automation::enrol($array);

      }

    }

    /**
     *Automatically unenrol users from quals and units, when unenrolled from course
     * @param  coreeventbase $data [description]
     * @return [type]              [description]
     */
    public static function gt_auto_unenrol(\core\event\base $data) {

        global $DB;

        $GT = new \GT\GradeTracker();

        // If both settings are false, don't waste our time doing anything
        if ($GT->getSetting('use_auto_unenrol_quals') != 1 && $GT->getSetting('use_auto_unenrol_units') != 1) return true;

        // If this enrolment was on a course
        if ($data->contextlevel == CONTEXT_COURSE){

          $array = array();
          $array['courseID'] = $data->courseid;
          $array['contextLevel'] = $data->contextlevel;
          $array['userID'] = $data->relateduserid;

          return \GT\Automation::unenrol($array);

        }

    }


    public static function gt_auto_modifyenrolment(\core\event\base $data){

      global $DB;

      $GT = new \GT\GradeTracker();

      // If both settings are false, don't waste our time doing anything
      if ($GT->getSetting('use_auto_unenrol_quals') != 1 && $GT->getSetting('use_auto_unenrol_units') != 1) return true;

      // If this enrolment was on a course
      if ($data->contextlevel == CONTEXT_COURSE){

        // Make sure we can find the user_enrolment in order to get the status
        $record = $DB->get_record('user_enrolments', array('id' => $data->objectid));
        if (!$record) return false;

        $array = array();
        $array['courseID'] = $data->courseid;
        $array['contextLevel'] = $data->contextlevel;
        $array['userID'] = $data->relateduserid;

        // Enrolment activated
        if($record->status == 0){
          return \GT\Automation::enrol($array);
        }

        // Suspended
        elseif($record->status == 1){
          return \GT\Automation::unenrol($array);
        }

      }

    }

}
