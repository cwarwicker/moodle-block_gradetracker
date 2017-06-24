<?php
/**
 * This class defines the scheduled task to update grids that are linked to activities
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

namespace block_gradetracker\task;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

class update_grids_from_activities extends \core\task\scheduled_task
{
    
    protected $traceText = '';
    
    /**
     * Get the name of the task
     * @return type
     */
    public function get_name(){
        return get_string('task:updategridsfromactivities', 'block_gradetracker');
    }
    
    /**
     * Print a trace out to the cron script
     * @param type $str
     */
    private function trace($str)
    {
        $this->traceText .= $str . "\n";
        mtrace( get_string('pluginname', 'block_gradetracker') . ": " . $str, "\n" );
    }
    
    /**
     * Process the submissions made to find out if they were on time or late and update grids accordingly
     * @param type $modLinks
     */
    private function process_submissions($modLinks)
    {
                
        $now = time();
        $unitArray = array();
        
        $this->trace( "Checking for submissions between " . date('d-m-Y, H:i', $this->get_last_run_time()) . " and " . date('d-m-Y, H:i', $now) );
        
        if ($modLinks)
        {
            
            foreach($modLinks as $modLink)
            {
                 
                $submissions = $this->find_submissions($modLink, $this->get_last_run_time(), $now);
                $this->trace("Found ".count($submissions)." ".$modLink->getModName()." submissions");
                if ($submissions)
                {
                    
                    $instanceField = trim($modLink->getSubModCol());
                    $activityTitleField = trim($modLink->getModTitleCol());
                    $submissionModField = trim($modLink->getSubModCol());
                    $submissionPartCol = trim($modLink->getSubPartCol());
                    $partTitleField = trim($modLink->getPartTitleCol());
                                        
                    if ($instanceField == '' || $activityTitleField == '' || $submissionModField == ''){
                        $this->trace("Error: Module configuration missing required fields");
                        continue;
                    }
                    
                            
                    foreach($submissions as $submission)
                    {
                        
                        // Check if the user has access to this activity
                        if (!\gt_can_user_access_activity($submission->cmid, $submission->userid)){
                            $this->trace("User {$submission->firstname} {$submission->lastname} ({$submission->username}) does not have access to specified activity (probably does not meet Restrict Access conditions)");
                            continue;
                        }
                        
                        // Get the information about the actual activity - such as its name, duedate, etc..
                        $activity = $this->find_activity($modLink->getModTable(), $submission->$instanceField);
                        if (!$activity){
                            $this->trace("Error: Cannot load activity from database table {{$modLink->getModTable()}} using saved Module Linking configuration details");
                            continue;
                        }
                                                
                        $user = new \GT\User($submission->userid);
                        
                        // Set the id of the actual activity into the ModuleLink object
                        $modLink->setRecordID($submission->$submissionModField);
                        
                        $partID = null;
                        $partName = '';
                        if ($modLink->hasParts())
                        {
                            if ($submissionPartCol != '' && $partTitleField != '')
                            {
                                $partID = $submission->$submissionPartCol;
                                $part = $modLink->getRecordPart($partID);
                                $partName = " ({$part->$partTitleField})";
                            } 
                            else
                            {
                                $this->trace("Error: Module configuration missing required fields (parts)");
                            }
                        }
                                                                                                
                        $this->trace("Found submission for {$submission->firstname} {$submission->lastname} ({$submission->username}) on activity {$activity->$activityTitleField}{$partName}");
                        
                        // Was it on time?
                        if ($this->is_submission_on_time($submission, $activity, $modLink))
                        {
                            $this->trace("Submission was on time");
                            $submissionOnTime = true;
                        }
                        // Or was it late?
                        else
                        {
                            $this->trace("Submission was late");
                            $submissionOnTime = false;
                        }
                                                
                        // Get the links from this activity to criteria
                        $activityLinks = \GT\Activity::findLinks($submission->cmid, $partID);
                        if ($activityLinks)
                        {
                            
                            foreach($activityLinks as $activityLink)
                            {
                                
                                // Is the user actually attached to this?
                                if ($user->isOnQualUnit($activityLink->getQualID(), $activityLink->getUnitID(), "STUDENT"))
                                {
                                
                                    $unit = (array_key_exists($activityLink->getUnitID(), $unitArray)) ? $unitArray[$activityLink->getUnitID()] : new \GT\Unit\UserUnit($activityLink->getUnitID());
                                    $unitArray[$activityLink->getUnitID()] = $unit;
                                    
                                    if ($unit)
                                    {
                                        $unit->loadStudent($user);
                                        $criterion = $unit->getCriterion($activityLink->getCritID());
                                        if ($criterion)
                                        {

                                            $award = $criterion->getUserAward();
                                                                                    
                                            // We do not want to update if there is already a value set, unless it is Work Not Submitted, which can be overriden
                                            if (!$award || !$award->isValid() || $award->getSpecialVal() == 'WNS')
                                            {
                                                
                                                $value = ($submissionOnTime) ? 'WS' : 'LATE';
                                                if ($this->update_criterion_value($criterion, $value))
                                                {
                                                    $this->trace("Updated unit ({$unit->getName()}), criterion ({$criterion->getName()}) to {$value}, for {$submission->firstname} {$submission->lastname} ({$submission->username})");
                                                }
                                                else
                                                {
                                                    $this->trace("Error: Failed to update unit ({$unit->getName()}), criterion ({$criterion->getName()}), for {$submission->firstname} {$submission->lastname} ({$submission->username})");
                                                }
                                                
                                            }
                                            else
                                            {
                                                $this->trace("Unit ({$unit->getName()}) - Criterion ({$criterion->getName()}) already has a value ({$award->getShortName()}) for {$user->getDisplayName()}");
                                            }

                                        }
                                        
                                    }
                                
                                }
                                                                
                            }
                            
                        }                        
                        
                    }
                    
                }
                
            }
            
        }
                
    }
    
    /**
     * Process to find submissions which have not been made and update grids to WNS accordingly
     * @param type $modLinks
     */
    private function process_missing_submissions($modLinks)
    {
        
        global $GT;
        
        $GT = new \GT\GradeTracker();
        
        $now = time();
        $unitArray = array();
        
        $this->trace( "Checking for missing submissions between " . date('d-m-Y, H:i', $this->get_last_run_time()) . " and " . date('d-m-Y, H:i', $now) );
        
        if ($modLinks)
        {
            
            foreach($modLinks as $modLink)
            {
                
                // Fields
                $modCourseField = trim($modLink->getModCourseCol());
                
                if ($modCourseField == ''){
                    $this->trace("Error: Module configuration missing required fields");
                    continue;
                }
                
                // Does this mod use parts? If so we need to check the parts that are beyond the due date
                // Rather than the whole thing
                if ($modLink->hasParts())
                {
                   
                    $parts = $this->find_activities($modLink, $this->get_last_run_time(), true);
                    
                    // If there are activities go through them and find out which students are on the course
                    if ($parts)
                    {
                        
                        foreach($parts as $part)
                        {
                                                        
                            // Get the activity from the part
                            $activity = $this->find_activity_from_part($modLink, $part);
                            if ($activity)
                            {

                                // Get the course and the coursemodule record from this activity
                                $courseID = $activity->$modCourseField;
                                $course = new \GT\Course($courseID);
                                $courseModule = $course->getCourseModule($modLink->getModID(), $activity->id);

                                // Get the activities linked to this coursemodule
                                $activityLinks = \GT\Activity::findLinks($courseModule->id, $part->id);

                                $this->process_missing_activity_links($activityLinks, $modLink, $course, $courseModule, $activity, $unitArray);

                            }
                            
                        }
                        
                    }
                    
                    
                }
                else
                {
                                                            
                    // Find activities of this type that are passed due date
                    $activities = $this->find_activities($modLink, $this->get_last_run_time());
                    
                    // If there are activities go through them and find out which students are on the course
                    if ($activities)
                    {
                        
                        foreach($activities as $activity)
                        {
                                                        
                            // Get the course and the coursemodule record from this activity
                            $courseID = $activity->$modCourseField;
                            $course = new \GT\Course($courseID);
                            $courseModule = $course->getCourseModule($modLink->getModID(), $activity->id);
                            
                            if ($courseModule)
                            {
                                // Get the activities linked to this coursemodule
                                $activityLinks = \GT\Activity::findLinks($courseModule->id);
                                $this->process_missing_activity_links($activityLinks, $modLink, $course, $courseModule, $activity, $unitArray);
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
    }
    
    /**
     * Process an activity to find the users who should be on it and then find who has not submitted and update grid accordingly
     * @param type $activityLinks
     * @param type $modLink
     * @param type $course
     * @param type $courseModule
     * @param type $activity
     * @param type $unitArray
     */
    private function process_missing_activity_links($activityLinks, $modLink, $course, $courseModule, $activity, &$unitArray)
    {
        
        // Only carry on if this is actually linked to something
        if ($activityLinks)
        {

            // Fields
            $activityTitleField = $modLink->getModTitleCol();
                            
            // Filter the users on the course down to the ones actually on this activity
            $modinfo = \get_fast_modinfo($course);
            $cm = $modinfo->get_cm($courseModule->id);
            $info = new \core_availability\info_module($cm);
            $users = $info->filter_user_list( $course->getStudents() );

            // Loop through the users and see if any of them have not submitted
            if ($users)
            {
                foreach($users as $user)
                {

                    // Convert the user back to a \GT\User object after the filter
                    $user = new \GT\User($user->id);

                    $submission = $this->find_user_submission($modLink, $activity->id, $user->id);

                    // If they have not submitted, then we need to mark their grid as WNS
                    if (!$submission)
                    {

                        $this->trace("Found missing submission for {$user->getDisplayName()} on activity {$activity->$activityTitleField} on course {$course->getName()}");

                        foreach($activityLinks as $activityLink)
                        {

                            // Is the user actually attached to this?
                            if ($user->isOnQualUnit($activityLink->getQualID(), $activityLink->getUnitID(), "STUDENT"))
                            {

                                $unit = (array_key_exists($activityLink->getUnitID(), $unitArray)) ? $unitArray[$activityLink->getUnitID()] : new \GT\Unit\UserUnit($activityLink->getUnitID());
                                $unitArray[$activityLink->getUnitID()] = $unit;

                                if ($unit)
                                {
                                    $unit->loadStudent($user);
                                    $criterion = $unit->getCriterion($activityLink->getCritID());
                                    if ($criterion)
                                    {

                                        $award = $criterion->getUserAward();
                                        
                                        // Only update the award to WNS if it hasn't had one set already
                                        if (!$award || !$award->isValid())
                                        {
                                            
                                            // Work Not Submitted
                                            $value = 'WNS';

                                            if ($this->update_criterion_value($criterion, $value))
                                            {
                                                $this->trace("Updated unit ({$unit->getName()}), criterion ({$criterion->getName()}) to {$value}, for {$user->getDisplayName()}");
                                            }
                                            else
                                            {
                                                $this->trace("Error: Failed to update unit ({$unit->getName()}), criterion ({$criterion->getName()}), for {$user->getDisplayName()}");
                                            }

                                        }
                                        else
                                        {
                                            $this->trace("Unit ({$unit->getName()}) - Criterion ({$criterion->getName()}) already has a value ({$award->getShortName()}) for {$user->getDisplayName()}");
                                        }

                                    }

                                }

                            }

                        }

                    }

                }
                
            }

        }
        
    }
    
    /**
     * Find a user's submission on a specific activity
     * @global \block_gradetracker\task\type $DB
     * @param type $modLink
     * @param type $activityID
     * @param type $userID
     */
    private function find_user_submission($modLink, $activityID, $userID)
    {
        
        global $DB;
        
        $sql = "SELECT s.*
                FROM {{$modLink->getSubTable()}} s 
                WHERE s.{$modLink->getSubModCol()} = ? 
                AND s.{$modLink->getSubUserCol()} = ? ";
                
        $params = array($activityID, $userID);
                
        // If the mod uses statuses
        if (!is_null($modLink->getSubStatusCol()) && $modLink->getSubStatusCol() !== '' && !is_null($modLink->getSubStatusVal()) && $modLink->getSubStatusVal() !== ''){
            $sql .= "AND s.{$modLink->getSubStatusCol()} = ?";
            $params[] = $modLink->getSubStatusVal();
        }
                
        return $DB->get_record_sql($sql, $params);
        
    }
    
    
    /**
     * Check if a submission is on time
     * @param type $submission
     * @param type $activity
     * @param type $modLink
     * @return type
     */
    private function is_submission_on_time($submission, $activity, $modLink)
    {
                     
        // Fields
        $activityDueDateField = $modLink->getModDueCol();
        $submissionDateField = $modLink->getSubDateCol();
        
        // Does this activity have parts?
        if ($modLink->hasParts())
        {
        
            // Fields
            $submissionPartCol = $modLink->getSubPartCol(); 
                                     
            // Get the part this submission was for
            $partID = $submission->$submissionPartCol;
            $part = $modLink->getRecordPart($partID);
            
            // Was it submitted before the due date of this part?
            return ($submission->$submissionDateField <= $part->$activityDueDateField);            
            
        }
        else
        {
            return ($submission->$submissionDateField <= $activity->$activityDueDateField);
        }
        
        
    }
    
    
    
    /**
     * Update the value of the student's criterion
     * @global \block_gradetracker\task\type $DB
     * @param type $criterion
     * @param type $value
     * @return boolean
     */
    private function update_criterion_value($criterion, $value)
    {
                
        // Find the relevant value from the grading structure
        $gradingStructure = new \GT\CriteriaAwardStructure($criterion->getGradingStructureID());
        $award = $gradingStructure->findAwardBySpecialValue($value);
        if (!$award){
            $this->trace("Could not find a criteria award on this grading structure with specialval {$value}");
            return false;
        }
        
        // If it already has this award, don't update it again
        if (!$criterion->getUserAward() || $criterion->getUserAward()->getID() <> $award->getID()){        
            $criterion->setUserAward($award);
            return $criterion->saveUser();
        }
        
        // We didn't update, as it was already on that value
        return true;
        
    }
    
    /**
     * Find an activity based on its id and table to look in
     * @global \block_gradetracker\task\type $DB
     * @param type $table
     * @param type $id
     * @return type
     */
    private function find_activity($table, $id)
    {
        
        global $DB;
        return $DB->get_record($table, array("id" => $id));        
        
    }
    
    private function find_activity_from_part($modLink, $part)
    {
        
        global $DB;
        $modField = $modLink->getPartModCol();
        return $DB->get_record($modLink->getModTable(), array("id" => $part->$modField));       
        
    }
    
    /**
     * Find any activities of this type where the end date has passed since we last ran the script
     * @param type $modLink
     * @param type $lastRun
     */
    private function find_activities($modLink, $lastRun, $useParts = false)
    {
        
        global $DB;
                
        $now = time();
        
        $table = ($useParts) ? $modLink->getPartTable() : $modLink->getModTable();
        
        $sql = "SELECT a.*  
                FROM {{$table}} a
                WHERE a.{$modLink->getModDueCol()} <= ? 
                AND a.{$modLink->getModDueCol()} > ? 
                ORDER BY a.{$modLink->getModDueCol()}";
                
        $params = array($now, $lastRun);

        return $DB->get_records_sql($sql, $params);
        
    }
    
    /**
     * Find submissions between 2 dates
     * @global type $DB
     * @param type $table
     * @param type $dateField
     * @param type $startTime
     * @param type $endTime
     * @return type
     */
    private function find_submissions($modLink, $startTime, $endTime)
    {
        
        global $DB;
                
        $sql = "SELECT DISTINCT s.*, u.id as userid, u.firstname, u.lastname, u.username, cm.id as cmid
                FROM {{$modLink->getSubTable()}} s
                INNER JOIN {user} u ON u.id = s.{$modLink->getSubUserCol()}
                INNER JOIN {course_modules} cm ON cm.instance = s.{$modLink->getSubModCol()}
                INNER JOIN {bcgt_activity_refs} ref ON ref.cmid = cm.id
                WHERE s.{$modLink->getSubDateCol()} >= ? 
                AND s.{$modLink->getSubDateCol()} <= ?
                AND cm.module = ? ";
                
        $params = array($startTime, $endTime, $modLink->getModID());
                
        // If the mod uses statuses
        if (!is_null($modLink->getSubStatusCol()) && $modLink->getSubStatusCol() !== '' && !is_null($modLink->getSubStatusVal()) && $modLink->getSubStatusVal() !== ''){
            $sql .= "AND s.{$modLink->getSubStatusCol()} = ?";
            $params[] = $modLink->getSubStatusVal();
        }
        
        return $DB->get_records_sql($sql, $params);                
                
    }
    
    /**
     * Run the script
     * @return boolean
     */
    public function execute() {
                
        // Debugging log
        \gt_create_data_directory('log/task');
        $file = \GT\GradeTracker::dataroot() . '/log/task/update_grids_from_activities_' . date('Hi') . '.log';
        \gt_trace($file, "Beginning process...");


        $this->trace("Process beginning");
        
        $modLinks = \GT\ModuleLink::getEnabledModLinks();
        if (!$modLinks){
            $this->trace("Process ended prematurely - No module links configured");
            return false;
        }
        
        // Find all submissions since it was last run, to check if they were on time or not
        $this->process_submissions($modLinks);
        
        // Clear any records
        \GT\ModuleLink::clearModRecords($modLinks);
        
        // Find all missing submissions since it was last run, to update to WNS
        $this->process_missing_submissions($modLinks);
        
        
        // Debugging logs
        \gt_trace($file, $this->traceText);
        \gt_trace($file, "Process ended");
                
        return true;
        
    }
    
}