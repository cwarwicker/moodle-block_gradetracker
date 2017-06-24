<?php
/**
 * User
 *
 * This class deals with Moodle Users, and any methods relating them to the Grade Tracker
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

class User {
    
    private $contextArray = array();
    
    public function __construct($id) {
                
        global $DB;
        
        $user = $DB->get_record("user", array("id" => $id, "deleted" => 0));
                
        if ($user)
        {
            $props = (array)$user;
            foreach($props as $prop => $val)
            {
                $this->$prop = $val;
            }
        }
        
        
    }
    
    public function isValid(){
        return (isset($this->id) && $this->id > 0 && $this->deleted == 0);
    }
    
    /**
     * Get the user's full name
     * @return type
     */
    public function getName(){
        $name = trim(fullname($this));
        return (strlen($name) > 0) ? $name : '?';
    }
    
    /**
     * Get the user's name and username
     * @return type
     */
    public function getDisplayName(){
        return $this->getName() . " ({$this->username})";
    }
    
    /**
     * Get the user's picture to be printed out
     * @global type $OUTPUT
     * @param type $courseID
     * @param type $size
     */
    public function getPicture($courseID = null, $size = 30){
        
        global $OUTPUT;
        
        $stdClass = new \stdClass();
        $props = get_object_vars($this);
        foreach($props as $prop => $val)
        {
            $stdClass->$prop = $val;
        }
        
        return $OUTPUT->user_picture($stdClass, array("courseid" => $courseID, "size" => $size));
        
    }
    
    /**
     * Get a specific property of the user
     * @param type $prop
     * @return type
     */
    public function getProp($prop){
        
        if ($prop == 'pic'){
            return $this->getPicture();
        } elseif ($prop == 'name'){
            return $this->getName();
        } else {
            return $this->$prop;
        }
        
    }
    
    public function getContextArray(){
        return $this->contextArray;
    }
    
    public function setContextArray($arr){
        $this->contextArray = $arr;
    }
    
    /**
     * Do we have access to the qualification in the given role?
     * Or do we have the view_all_quals capability?
     * @param type $qualID
     */
    public function canAccessQual($qualID, $role){
        
        return ($this->isOnQual($qualID, $role) || $this->hasCapability('block/gradetracker:view_all_quals'));
        
    }
    
    /**
     * Do we have the permission to edit this qualification?
     * Or do we have the edit_all_quals capability?
     * @param type $qualID
     * @param type $role
     * @return type
     */
    public function canEditQual($qualID){
        
        return (($this->isOnQual($qualID, "STAFF") && $this->hasCapability('block/gradetracker:edit_grids')) || $this->hasCapability('block/gradetracker:edit_all_quals'));
        
    }
    
    /**
     * Do we have the permission to edit this unit on this qual?
     * Or do we have the edit_all_quals capability?
     * @param type $qualID
     * @param type $unitID
     */
    public function canEditUnit($qualID, $unitID){
        
        // We need to either be on the qual as a STAFF and have the edit_grids capability, OR just have the edit_all_quals capability
        return ( ($this->isOnQualUnit($qualID, $unitID, "STAFF") && $this->hasCapability('block/gradetracker:edit_grids')) || $this->hasCapability('block/gradetracker:edit_all_quals'));
        
    }
    
    /**
     * Check capability
     * @param type $cap
     * @return type
     */
    public function hasCapability($cap){
        return \gt_has_capability($cap, false, false, $this);
    }
    
    /**
     * Check access to given user and qual, and capability
     * @param type $cap
     * @param type $userID
     * @param type $qualID
     */
    public function hasUserCapability($cap, $userID, $qualID = false){
                
        if (!$this->isValid()){
            return false;
        }
        
        // First check that the user is valid
        $theUser = new \GT\User($userID);
        if (!$theUser->isValid()){
            return false;
        }
        
        // Then check if we have the capability on any of the user's contexts
        $result = \gt_has_user_capability($cap, $userID);
        if (!$result) return false;
        
        // Is the qualification ID set?
        if ($qualID){
            
            // Are we on the qualification?
            if (!$this->canAccessQual($qualID, "STAFF")){
                return false;
            }
            
            // Is the user on the qual?
            if (!$theUser->canAccessQual($qualID, "STUDENT")){
                return false;
            }
            
        }
                
        return true;
        
    }
    
    /**
     * Get the user's qualifications
     * @param type $role
     * @return type
     */
    public function getQualifications($role){
        
        if (!isset($this->quals[$role])){
            $this->loadQualifications($role);
        }
        
        return $this->quals[$role];
        
    }
    
    /**
     * Load the user's qualifications
     * @global \GT\type $DB
     * @param type $role
     */
    private function loadQualifications($role){
        
        global $DB;
 
        $this->quals[$role] = array();
                
        $records = $DB->get_records("bcgt_user_quals", array("userid" => $this->id, "role" => $role));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Qualification\UserQualification($record->qualid);
                $structure = $obj->getStructure();
                if ($obj->isValid() && !$obj->isDeleted() && $structure->isEnabled())
                {
                    $obj->loadStudent($this->id);
                    $this->quals[$role][$obj->getID()] = $obj;
                }
            }
        }
        
        // Order by type, level, subtype, name
        $Sort = new \GT\Sorter();
        
        foreach($this->quals as $role => $quals)
        {
            $Sort->sortQualifications($this->quals[$role]);
        }
                
    }
    
    
    /**
     * Is a user on the given course as one of the roles defined for either STUDENT or STAFF
     * @global \GT\type $DB
     * @global type $GT
     * @param type $courseID
     * @param type $role
     * @return boolean
     */
    public function isOnCourse($courseID, $role){
        
        global $DB, $GT;
        
        if ($role ==  "STUDENT"){
            
            $shortnames = $GT->getStudentRoles();
            $in = \gt_create_sql_placeholders($shortnames);
            
        } elseif ($role == "STAFF"){
            
            $shortnames = $GT->getStaffRoles();
            $in = \gt_create_sql_placeholders($shortnames);
            
        } else {
            
            return false;
            
        }
        
        $params = $shortnames;
        $params[] = CONTEXT_COURSE;
        $params[] = $this->id;
        $params[] = $courseID;
        
        $check = $DB->get_record_sql("SELECT c.id
                                      FROM {course} c
                                      INNER JOIN {context} x ON x.instanceid = c.id
                                      INNER JOIN {role_assignments} ra ON ra.contextid = x.id
                                      INNER JOIN {role} r ON r.id = ra.roleid
                                      WHERE r.shortname IN ({$in}) AND x.contextlevel = ? AND ra.userid = ? AND c.id = ?", $params);
                                    
        return ($check) ? true : false;
        
    }
    
    /**
     * Get the user's courses for either STUDENT or STAFF
     * @param type $role
     * @return type
     */
    public function getCourses($role){
        
        if (!isset($this->courses[$role])){
            $this->loadCourses($role);
        }
        
        return $this->courses[$role];
        
    }
    
    /**
     * Load the user's courses where they are either STUDENT or STAFF
     * @global \GT\type $DB
     * @global \GT\type $GT
     * @param type $role
     * @return boolean
     */
    private function loadCourses($role){
        
        global $DB, $GT;
        
        if (!$GT){
            $GT = new \GT\GradeTracker();
        }
        
        $this->courses[$role] = array();
        
        $return = array();
        
        if ($role ==  "STUDENT"){
           
            $shortnames = $GT->getStudentRoles();
            $in = \gt_create_sql_placeholders($shortnames);
            
        } elseif ($role == "STAFF"){
            
            $shortnames = $GT->getStaffRoles();
            $in = \gt_create_sql_placeholders($shortnames);
            
        } else {
            
            return false;
            
        }
        
        $params = $shortnames;
        $params[] = CONTEXT_COURSE;
        $params[] = $this->id;
        
        $records = $DB->get_records_sql("SELECT DISTINCT c.id
                                        FROM {course} c
                                        INNER JOIN {context} x ON x.instanceid = c.id
                                        INNER JOIN {role_assignments} ra ON ra.contextid = x.id
                                        INNER JOIN {role} r ON r.id = ra.roleid
                                        WHERE r.shortname IN ({$in}) 
                                        AND x.contextlevel = ? 
                                        AND ra.userid = ?
                                        ORDER BY c.shortname, c.fullname", $params);
                                                                                
        if ($records){
            
            foreach($records as $record){
                
                $obj = new \GT\Course($record->id);
                
                if ($obj->isValid()){
                    
                    $return[$obj->id] = $obj;
                    
                }
                
            }
            
        }
                
        $this->courses[$role] = $return;
        
    }
    
    
    /**
     * Check if this user is linked to a given qualification
     * @global \GT\type $DB
     * @param type $qualID
     * @return type
     */
    public function isOnQual($qualID, $role = false){
        
        global $DB;
        
        if ($role){
            $check = $DB->get_record("bcgt_user_quals", array("userid" => $this->id, "qualid" => $qualID, "role" => $role));
        } else {
            $check = $DB->get_record("bcgt_user_quals", array("userid" => $this->id, "qualid" => $qualID));
        }
        
        return ($check) ? true : false;
        
    }
        
    /**
     * Check if this user is taking a specific unit on a given qualification
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @param type $role
     * @return type
     */
    public function isOnQualUnit($qualID, $unitID, $role){
        
        global $DB;
        
        $check = $DB->get_record_sql("SELECT uqu.id
                                        FROM {bcgt_user_qual_units} uqu
                                        INNER JOIN {bcgt_user_quals} uq ON (uq.userid = uqu.userid AND uq.qualid = uqu.qualid AND uq.role = uqu.role)
                                        WHERE uqu.qualid = ?
                                        AND uqu.unitid = ?
                                        AND uqu.userid = ?
                                        AND uqu.role = ?", array($qualID, $unitID, $this->id, $role));
        
        return ($check) ? true : false;
        
    }
    
    /**
     * Link this user to a given qualification
     * @global type $DB
     * @param type $qualID
     * @return boolean
     */
    public function addToQual($qualID, $role){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_user_quals", array("userid" => $this->id, "qualid" => $qualID, "role" => $role));
        if ($check) return true;
        
        $ins = new \stdClass();
        $ins->userid = $this->id;
        $ins->qualid = $qualID;
        $ins->role = $role;
        return $DB->insert_record("bcgt_user_quals", $ins);
        
    }
    
    /**
     * Remove the user from the qualification
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $role
     */
    public function removeFromQual($qualID, $role = false){
        
        global $DB;
        
        if ($role){
            return $DB->delete_records("bcgt_user_quals", array("qualid" => $qualID, "userid" => $this->id, "role" => $role));
        } else {
            return $DB->delete_records("bcgt_user_quals", array("qualid" => $qualID, "userid" => $this->id));
        }
        
    }
    
    /**
     * Link this user to a given qualification's unit
     * @global type $DB
     * @param type $qualID
     * @param type $unitID
     * @return boolean
     */
    public function addToQualUnit($qualID, $unitID, $role){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_user_qual_units", array("userid" => $this->id, "qualid" => $qualID, "unitid" => $unitID, "role" => $role));
        if ($check) return true;
        
        $ins = new \stdClass();
        $ins->userid = $this->id;
        $ins->qualid = $qualID;
        $ins->unitid = $unitID;
        $ins->role = $role;
        return $DB->insert_record("bcgt_user_qual_units", $ins);
        
    }
    
    
    /**
     * Remove the user from the unit on a qualification
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @param type $role
     */
    public function removeFromQualUnit($qualID, $unitID, $role = false){
        
        global $DB;
        
        if ($role){
            return $DB->delete_records("bcgt_user_qual_units", array("qualid" => $qualID, "unitid" => $unitID, "userid" => $this->id, "role" => $role));
        } else {
            return $DB->delete_records("bcgt_user_qual_units", array("qualid" => $qualID, "unitid" => $unitID, "userid" => $this->id));
        }
        
    }
    
    
    /**
     * Get a user grade, e.g. Target, Aspirational, Ceta, etc...
     * @global \GT\type $DB
     * @param type $type
     * @param type $params
     * @return boolean
     */
    public function getUserGrade($type, $params, $return = false, $returnGradeObject = false, $defaultReturn = false){
        
        global $DB;       
        
        $sqlParams = array($this->id, $type);
        
        $sql = "SELECT * FROM {bcgt_user_grades}
                WHERE userid = ? AND type = ? ";
        
        if (isset($params['courseID'])){
            $sql .= "AND courseid = ?";
            $sqlParams[] = $params['courseID'];
        }
        
        if (isset($params['qualID'])){
            $sql .= "AND qualid = ?";
            $sqlParams[] = $params['qualID'];
        }
        
        $record = $DB->get_record_sql($sql, $sqlParams);
        if ($record)
        {
            
            // If it's an id of a grade, go get that, otherwise just a text grade
            if (!is_null($record->qualid) && is_numeric($record->grade))
            {
                $award = $DB->get_record("bcgt_qual_build_awards", array("id" => $record->grade));
                if ($award)
                {
                    if ($returnGradeObject){
                        return new \GT\QualificationAward($award->id);
                    } else {
                        return ($return && isset($award->$return)) ? $award->$return : $award->name;
                    }
                }
            }
            
            return $record->grade;
            
        }
        
        return $defaultReturn;        
        
    }
    
    /**
     * Get all the user's qual awards of a certain type, e.g. average, min, max, final
     * @global \GT\type $DB
     * @param type $type
     * @return \GT\QualificationAward
     */
    public function getAllUserAwards($type){
        
        global $DB;       
        
        $sqlParams = array($this->id, $type);
        
        $sql = "SELECT * FROM {bcgt_user_qual_awards}
                WHERE userid = ? AND type = ? ";
                        
        $records = $DB->get_records_sql($sql, $sqlParams);
        $return = array();
        
        if ($records)
        {
            foreach($records as $record)
            {
                
                // Check that the student is still on this qualificaiton
                if ($this->isOnQual($record->qualid))
                {
                
                    $arr = array();
                    $arr['record'] = $record;

                    if (!is_null($record->qualid) && is_numeric($record->awardid))
                    {
                        $award = $DB->get_record("bcgt_qual_build_awards", array("id" => $record->awardid));
                        if ($award)
                        {
                            $obj = new \GT\QualificationAward($award->id);
                            if ($obj->isValid())
                            {
                                $arr['grade'] = $obj;
                                $return[] = $arr;
                            }
                        }
                    }
                
                }
                
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get multiple grade records for a student and type
     * e.g. get all their target grades for any qual they are on, in an array
     * @global \GT\type $DB
     * @param type $type
     * @param type $params
     * @return type
     */
    public function getAllUserGrades($type, $params = array()){
        
        global $DB;       
        
        $sqlParams = array($this->id, $type);
        
        $sql = "SELECT * FROM {bcgt_user_grades}
                WHERE userid = ? AND type = ? ";
        
        if (isset($params['courseID']) && $params['courseID'] > 0){
            $sql .= "AND courseid = ?";
            $sqlParams[] = $params['courseID'];
        }
        
        if (isset($params['qualID']) && $params['qualID'] > 0){
            $sql .= "AND qualid = ?";
            $sqlParams[] = $params['qualID'];
        }
                
        $records = $DB->get_records_sql($sql, $sqlParams);
        $return = array();
        
        if ($records)
        {
            foreach($records as $record)
            {
                
                // Check that the student is still on this qualificaiton
                if ($this->isOnQual($record->qualid))
                {
                
                    $arr = array();
                    $arr['record'] = $record;

                    if (!is_null($record->qualid) && is_numeric($record->grade))
                    {
                        $award = $DB->get_record("bcgt_qual_build_awards", array("id" => $record->grade));
                        if ($award)
                        {
                            $obj = new \GT\QualificationAward($award->id);
                            if ($obj->isValid())
                            {
                                $arr['grade'] = $obj;
                                $return[] = $arr;
                            }
                        }
                    }
                
                }
                
            }
        }
        
        return $return;
        
    }
    
    /**
     * Clear user grade from DB (done before recalculating)
     * @global \GT\type $DB
     * @param type $type
     * @param type $params
     * @return type
     */
    public function clearUserGrade($type, $params){
        
        global $DB;
        
        $params = array_merge( array('userid' => $this->id, 'type' => $type), $params );
        return $DB->delete_records("bcgt_user_grades", $params);
        
    }
    
    /**
     * Set a user's grade, e.g. target, ceta, aspirational, etc..
     * @global \GT\type $DB
     * @param type $type
     * @param type $grade
     * @param type $params
     * @return type
     */
    public function setUserGrade($type, $grade, $params){
        
        global $DB, $AUTOUPDATE;
                
        $sqlParams = array($this->id, $type);
        
        $sql = "SELECT * FROM {bcgt_user_grades}
                WHERE userid = ? AND type = ? ";
        
        if (isset($params['courseID'])){
            $sql .= "AND courseid = ?";
            $sqlParams[] = $params['courseID'];
        }
        
        if (isset($params['qualID'])){
            $sql .= "AND qualid = ?";
            $sqlParams[] = $params['qualID'];
        }
        
        $record = $DB->get_record_sql($sql, $sqlParams);
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = (isset($AUTOUPDATE) && $AUTOUPDATE === true) ? \GT\Log::GT_LOG_DETAILS_AUTO_UPDATED_USER_GRADE : \GT\Log::GT_LOG_DETAILS_UPDATED_USER_GRADE;
        $Log->beforejson = array(
            'type' => $type,
            'grade' => ($record) ? $record->grade : null
        );
        // ------------ Logging Info
        
        
        
        if ($record)
        {
            $record->grade = $grade;
            $result = $DB->update_record("bcgt_user_grades", $record);
        }
        else
        {
            
            $obj = new \stdClass();
            $obj->userid = $this->id;
            $obj->qualid = (isset($params['qualID'])) ? $params['qualID'] : null;
            $obj->courseid = (isset($params['courseID'])) ? $params['courseID'] : null;
            $obj->type = $type;
            $obj->grade = $grade;
            $result = $DB->insert_record("bcgt_user_grades", $obj);
            
        }  
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'type' => $type,
            'grade' => $grade
        ); 

        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => (isset($params['qualID'])) ? $params['qualID'] : null,
                \GT\Log::GT_LOG_ATT_COURSEID => (isset($params['courseID'])) ? $params['courseID'] : null,
                \GT\Log::GT_LOG_ATT_STUDID => $this->id
            );

        $Log->save();
        // ----------- Log the action
        
        
        return $result;
        
    }
    
    /**
     * Get user's qoe
     * @global \GT\type $DB
     * @return \GT\QualOnEntry
     */
    public function getQualsOnEntry(){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("bcgt_user_qoe", array("userid" => $this->id), "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\QualOnEntry($record->id);
                if ($obj->isValid())
                {
                    $return[] = $obj;
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * Calculate the average GCSE score from the student's quals on entry
     * @return type
     */
    public function calculateAverageGCSEScore(){
        
        $qoe = $this->getQualsOnEntry();
        
        $avg = false;
        $numEntries = 0;
        $totalPoints = 0;
                
        if ($qoe)
        {
            foreach($qoe as $entry)
            {
                
                // Only use GCSEs
                if (!in_array($entry->getType()->name, array(\GT\QualOnEntry::GCSENORMAL, \GT\QualOnEntry::GCSEDOUBLE, \GT\QualOnEntry::GCSESHORT))) continue;
                
                // If not valid grade for it, or the grade has 0 points (and is not a GCSE U) then skip
                if (!$entry->getGradeObject() || ($entry->getGradeObject()->points <= 0 && $entry->getGradeObject()->grade != 'U')) continue;
                
                // Set weighting to 1 if it's not set or is 0
                if (!$entry->getGradeObject()->weighting || $entry->getGradeObject()->weighting == 0) $entry->getGradeObject()->weighting = 1;
                if (!$entry->getType()->weighting || $entry->getType()->weighting == 0) $entry->getType()->weighting = 1;
                
                $numEntries += ( $entry->getGradeObject()->weighting * $entry->getType()->weighting );
                $totalPoints += ( $entry->getGradeObject()->points * $entry->getType()->weighting );
                
            }
        }
        
        // Work out average
        if ($numEntries > 0){
            
            $avg = round( $totalPoints / $numEntries, 2 );
            $this->setAverageGCSEScore($avg);
            
        }
        
        return $avg;
        
    }
    
    /**
     * Get the user's avg gcse score
     * @global \GT\type $DB
     * @return type
     */
    public function getAverageGCSEScore(){
        
        global $DB;
        $record = $DB->get_record("bcgt_user_qoe_scores", array("userid" => $this->id));
        return ($record) ? $record->score : false;
        
    }
    
    /**
     * Set the user's avg gcse score
     * @global \GT\type $DB
     * @param type $score
     * @return type
     */
    public function setAverageGCSEScore($score){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_user_qoe_scores", array("userid" => $this->id));
        if ($record)
        {
            $record->score = $score;
            return $DB->update_record("bcgt_user_qoe_scores", $record);
        }
        else
        {
            
            $obj = new \stdClass();
            $obj->userid = $this->id;
            $obj->score = $score;
            return $DB->insert_record("bcgt_user_qoe_scores", $obj);
            
        }
        
    }
    
    /**
     * Calculate the user's target grade for a given qualification
     * @param type $qualID
     * @return boolean
     */
    public function calculateTargetGrade($qualID){
        
        global $AUTOUPDATE;
        
        $AUTOUPDATE = true;
        
        if ($this->isOnQual($qualID, "STUDENT")){
            
            $qual = new \GT\Qualification\UserQualification($qualID);
            
            \gt_debug("Calculating target grade for {$this->getName()} on {$qual->getDisplayName()}");
            
            // Does it have targets enabled?
            if (!$qual->isFeatureEnabledByName('targetgrades')){
                \gt_debug("targetgrades feature not enabled on qualification");
                return false;
            }
            
            // Clear existing
//            $this->clearUserGrade('target', array('qualid' => $qual->getID()));
            
            $avgGcse = $this->getAverageGCSEScore();
            if (!$avgGcse || $avgGcse < 1){
                $this->TargetCalculationError = get_string('errors:calcgrade:noavggcse', 'block_gradetracker');
            }
            
            \gt_debug("Avg GCSE Score: {$avgGcse}");
            
            $targetGrade = $qual->getBuild()->getAwardByAvgGCSEScore($avgGcse);
            if ($targetGrade){
                \gt_debug("Avg GCSE Score resolved to target grade: {$targetGrade->getName()}");
                if ($this->setUserGrade('target', $targetGrade->getID(), array('qualID' => $qual->getID()))){
                    \gt_debug("Target Grade successfully set to ({$targetGrade->getName()})");
                    return $targetGrade;
                }
            } else {
                if (!isset($this->TargetCalculationError)){
                    $this->TargetCalculationError = get_string('errors:calcgrade:nograde', 'block_gradetracker');
                }
                \gt_debug("Could not find a target grade with Avg Score {$avgGcse} between QOE boundaries");
                return false;
            }
                    
        } else {
            \gt_debug("Student is not on qualification");
            return false;
        }
        
        \gt_debug("Unknown error");
        return false;
        
    }
    
    /**
     * Calculate the user's weighted target grade for a given qualification
     * @param type $qualID
     * @return boolean
     */
    public function calculateWeightedTargetGrade($qualID){
        
        global $AUTOUPDATE;
        
        $AUTOUPDATE = true;
        
        if ($this->isOnQual($qualID, "STUDENT")){
            
            $qual = new \GT\Qualification\UserQualification($qualID);
            
            \gt_debug("Calculating weighted target grade for {$this->getName()} on {$qual->getDisplayName()}");
            
            // Does it have weighted targets enabled?
            if (!$qual->isFeatureEnabledByName('weightedtargetgrades')){
                \gt_debug("weightedtargetgrades feature not enabled on qualification");
                return false;
            }
            
            $coefficient = $qual->getWeightingCoefficient();
            if (!$coefficient){
                \gt_debug("Could not get qualification coefficient");
                return false;
            }
            
            // Clear existing
            $this->clearUserGrade('weighted_target', array('qualid' => $qual->getID()));
            
            // Is there a Target Grade? If not, we have nothing to weight
            $targetGrade = $this->getUserGrade('target', array('qualID' => $qualID), false, true);
            if (!$targetGrade){
                \gt_debug("No Target Grade defined for this user on this qualification, so we have nothing to weight");
                return false;
            }
            
            // Which method of calculation are we using?
            $method = \GT\Setting::getSetting('weighted_target_method');
            
            \gt_debug("Using weighting calculation method: {$method}");
                        
            // Calculating by multiplying the avg GCSE score by the qualification coefficient
            if ($method == 'gcse'){
                
                $avgGCSE = $this->getAverageGCSEScore();
                $newGCSE = $avgGCSE * $coefficient;
                
                \gt_debug("Multiplied Avg GCSE ({$avgGCSE}) by coefficient ({$coefficient}) to get new score: {$newGCSE}");
                
                $weightedGrade = $qual->getBuild()->getAwardByAvgGCSEScore($newGCSE);
                if ($weightedGrade){
                    \gt_debug("New GCSE Score resolved to weighted target grade: {$weightedGrade->getName()}");
                    if ($this->setUserGrade('weighted_target', $weightedGrade->getID(), array('qualID' => $qual->getID()))){
                        return $weightedGrade;
                    }
                }  else {
                    \gt_debug("Could not find a target grade with Avg Score {$newGCSE} between QOE boundaries");
                }     
                
            }
            
            // Calculating by multiplying the UCAS points of the Target Grade by the qualification coefficient
            elseif ($method == 'ucas'){
                
                // Are we using a Constant to artificially inflate the grade?
                $constantsEnabled = \GT\Setting::getSetting('weighting_constants_enabled');
                
                // Are we weighting the grade UP or DOWN?
                $direction = \GT\Setting::getSetting('weighted_target_direction');
                if ($direction != 'UP' && $direction != 'DOWN'){
                    \gt_debug("Invalid direction ({$direction}). Should be either UP or DOWN");
                    return false;
                }
                
                \gt_debug("Constants are enabled? ({$constantsEnabled})");

                // Get the constant for this qualification build
                $qualBuildConstant = $qual->getBuild()->getAttribute('build_default_weighting_constant');
                \gt_debug("Constant for this qualification build: {$qualBuildConstant}");

                // Get the UCAS points of the existing Target Grade
                $ucasPoints = $targetGrade->getUcas();

                // Multiply the target grade's UCAS points by the coefficient
                $newUCAS = $ucasPoints * $coefficient;

                \gt_debug("Target Grade's UCAS Points ({$ucasPoints}) multiplied by the coefficient ({$coefficient}) to get new UCAS points: {$newUCAS}");

                // If we are using constants, apply them now
                if ($constantsEnabled)
                {
                    $newUCASWithConstant = $newUCAS + $qualBuildConstant; // New variable name for ease of log
                    \gt_debug("Added the constant ({$qualBuildConstant}) to the UCAS Points ({$newUCAS}) to get a new UCAS points: {$newUCASWithConstant}");
                    $newUCAS = $newUCASWithConstant;
                }               
                
                    
                // Get the new grade by the UCAS points
                $weightedGrade = $qual->getBuild()->getAwardByUCASPoints($newUCAS, $direction);
                if ($weightedGrade){
                    \gt_debug("New UCAS Points resolved to weighted target grade: {$weightedGrade->getName()}");
                    if ($this->setUserGrade('weighted_target', $weightedGrade->getID(), array('qualID' => $qual->getID()))){
                        return $weightedGrade;
                    }
                } else {
                    \gt_debug("Could not find a target grade with UCAS points {$newUCAS}");
                }
                
            }
            
            else
            {
                \gt_debug("Invalid default calculation method: {$method}");
                return false;
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Calculate the user's aspirational grade for a given qualification
     * @param type $qualID
     * @return boolean
     */
    public function calculateAspirationalGrade($qualID){
        
        global $AUTOUPDATE;
        
        $AUTOUPDATE = true;
        
        if ($this->isOnQual($qualID, "STUDENT")){
            
            $qual = new \GT\Qualification\UserQualification($qualID);
            
            \gt_debug("Calculating aspirational grade for {$this->getName()} on {$qual->getDisplayName()}");
            
            // Does it have targets enabled?
            if (!$qual->isFeatureEnabledByName('aspirationalgrades')){
                \gt_debug("aspirationalgrades feature not enabled on qualification");
                return false;
            }
            
            // Clear existing
//            $this->clearUserGrade('aspirational', array('qualid' => $qual->getID()));
            
            // What setting are we using to calculate aspirational?
            $diff = $qual->getSystemSetting('asp_grade_diff');
            if ($diff){

                $diff = floatval($diff);
                
                \gt_debug("Using 'diff' configuration setting: {$diff}");
                
                $targetGrade = new \GT\QualificationAward( $this->getUserGrade('target', array('qualID' => $qual->getID()), 'id') );
                if ($targetGrade && $targetGrade->isValid()){

                    \gt_debug("Found Target grade ({$targetGrade->getName()}), incrementing rank by +({$diff})");
                    
                    $aspRank = $targetGrade->getRank() + $diff;
                    $aspirationalGrade = $qual->getBuild()->getAwardByPoints($aspRank, true);
                    if ($aspirationalGrade){
                        \gt_debug("Found valid Aspirational Grade with rank ({$aspRank})");
                        if ($this->setUserGrade('aspirational', $aspirationalGrade->getID(), array('qualID' => $qual->getID()))){
                            \gt_debug("Aspirational Grade successfully set to ({$aspirationalGrade->getName()})");
                            return $aspirationalGrade;
                        }
                    } else {
                        $this->AspTargetCalculationError = get_string('errors:calcgrade:nograde', 'block_gradetracker');
                        \gt_debug("Could not find a valid Qualification Build Award with the adjusted points ({$aspRank})");
                    }
                    
                } else {
                    $this->AspTargetCalculationError = get_string('errors:calcgrade:notg', 'block_gradetracker');
                    \gt_debug("Could not find a valid Target Grade for this user to use as a base grade");
                } 
                
            } else {
                $this->AspTargetCalculationError = get_string('errors:configerror', 'block_gradetracker');
                \gt_debug("Could not find a valid 'diff' configuration setting");
            }
            
        } else {
            \gt_debug("Student is not on qualification");
        }
        
        \gt_debug("Unknown error");
        return false;
        
    }
    
    
    
    /**
     * Get qual award
     * @param type $qualID
     * @return type
     */
    public function getQualAward($qualID){
        
        $qual = new \GT\Qualification\UserQualification($qualID);
        $qual->loadStudent($this->id);
        return $qual->getUserPredictedOrFinalAward();
        
    }
    
    public static function byUsername($username){
        
        global $DB;
        
        $record = $DB->get_record("user", array("username" => $username), "id");
        return ($record) ? new \GT\User($record->id) : false;
        
    }
    
    public function getActiveUnitCredits($qualID){
                
        $qual = new \GT\Qualification\UserQualification($qualID);
        $qual->loadStudent($this->id);
        $units = $qual->getUnits();
        
        $totalActiveCredits = 0;
        
        foreach ($units as $unit){
            if ($this->isOnQualUnit($qualID, $unit->getID(), "STUDENT")){
                $totalActiveCredits += $unit->getCredits();
            }
        }
        return $totalActiveCredits;
    }
    
    /**
     * Check if a user is on any of the given qualifications
     * @param type $quals
     * @return boolean
     */
    public function onAnyOfTheseQuals($quals, $role = false){
        
        if ($quals){
            foreach($quals as $qual){
                if ($this->isOnQual($qual->getID(), $role)){
                    return true;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Count all non-deleted & active (confirmed) users
     * @global \GT\type $DB
     * @return type
     */
    public static function countUsers(){
        
        global $DB;
        
        $count = $DB->count_records("user", array("deleted" => 0, "confirmed" => 1));
        return $count;
        
    }
    
}
