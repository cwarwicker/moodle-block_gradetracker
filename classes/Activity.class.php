<?php
/**
 * ActivityRef
 *
 * This class deals with the links between Moodle activities, such as assignments, and the GradeTracker
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

class Activity {
    
    private $id = false;
    private $cmID;
    private $partID = null;
    private $qualID;
    private $unitID;
    private $critID;
    private $deleted;
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id){
            
            $record = $DB->get_record("bcgt_activity_refs", array("id" => $id));
            if ($record){
                
                $this->id = $record->id;
                $this->cmID = $record->cmid;
                $this->partID = $record->partid;
                $this->qualID = $record->qualid;
                $this->unitID = $record->unitid;
                $this->critID = $record->critid;
                $this->deleted = $record->deleted;
                
            }
            
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false && !$this->isDeleted());
    }
    
    public function isDeleted(){
        return ($this->deleted == 1);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getCourseModuleID(){
        return $this->cmID;
    }
    
    public function setCourseModuleID($id){
        $this->cmID = $id;
        return $this;
    }
    
    public function getPartID(){
        return $this->partID;
    }
    
    public function setPartID($id){
        $this->partID = $id;
        return $this;
    }
    
    public function getQualID(){
        return $this->qualID;
    }
    
    public function setQualID($id){
        $this->qualID = $id;
        return $this;
    }
    
    public function getUnitID(){
        return $this->unitID;
    }
    
    public function setUnitID($id){
        $this->unitID = $id;
        return $this;
    }
    
    public function getCritID(){
        return $this->critID;
    }
    
    public function setCritID($id){
        $this->critID = $id;
        return $this;
    }
    
    public function getDeleted(){
        return $this->deleted;
    }
    
    public function setDeleted($val){
        $this->deleted = $val;
        return $this;
    }
    
    public function create(){
        
        global $DB;
                
        // Only insert if this doesn't already exist
        if (!$existingRecord = self::checkExists($this->cmID, $this->qualID, $this->unitID, $this->critID, false)){
        
            $record = new \stdClass();
            $record->cmid = $this->cmID;
            $record->partid = $this->partID;
            $record->qualid = $this->qualID;
            $record->unitid = $this->unitID;
            $record->critid = $this->critID;
            return $DB->insert_record("bcgt_activity_refs", $record);

        } else {
            
            // Update it
            $existingRecord->partid = $this->partID;
            return $DB->update_record("bcgt_activity_refs", $existingRecord);
            
        }
        
    }
    
    /**
     * Remove an activity ref
     * @global \GT\type $DB
     * @return boolean
     */
    public function remove(){
        
        global $DB;
        
        if (!$this->isValid()) return true;
        
        $this->setDeleted(1);
        
        $record = new \stdClass();
        $record->id = $this->id;
        $record->deleted = $this->getDeleted();
        return $DB->update_record("bcgt_activity_refs", $record);
        
    }
    
    /**
     * Check if a given link exists
     * @global type $DB
     * @param type $cmID
     * @param type $qualID
     * @param type $unitID
     * @param type $critID
     * @return type
     */
    public static function checkExists($cmID, $qualID, $unitID, $critID, $partID = null){
        
        global $DB;
        
        if (!$cmID) return false;
        
        $params = array("cmid" => $cmID, "qualid" => $qualID, "unitid" => $unitID, "critid" => $critID, "deleted" => 0);
        
        // Only search for that if we don't specify it as false
        if ($partID !== false){
            $params['partid'] = $partID;
        }
        
        $record = $DB->get_record("bcgt_activity_refs", $params);
        return $record;
        
    }
    
    
    
    /**
     * Get the qual ids linked to a course module
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getQualsLinkedToCourseModule($cmID, $partID = null){
        
        global $DB;
        
        if (!$cmID) return array();
        
        $return = array();
        
        $records = $DB->get_records("bcgt_activity_refs", array("cmid" => $cmID, "partid" => $partID, "deleted" => 0), null, "DISTINCT qualid");
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = $record->qualid;
            }
        }
        
        return $return;
        
    }
    
    
    
    /**
     * Get the unit ids linked to a course module and qual
     * @global \GT\type $DB
     * @param type $cmID
     * @param type $qualID
     * @return type
     */
    public static function getUnitsLinkedToCourseModule($cmID, $qualID = false, $objects = false){
        
        global $DB;
        
        if (!$cmID) return array();
        
        $return = array();
        
        if ($qualID){
            $records = $DB->get_records("bcgt_activity_refs", array("cmid" => $cmID, "qualid" => $qualID, "deleted" => 0), null, "DISTINCT unitid");
        } else {
            $records = $DB->get_records("bcgt_activity_refs", array("cmid" => $cmID, "deleted" => 0), null, "DISTINCT unitid");
        }
        
        
        // Using objects
        if ($objects)
        {
            $qual = new \GT\Qualification($qualID);
        }
                
        
        if ($records)
        {
            foreach($records as $record)
            {
                if ($objects)
                {
                    $return[$record->unitid] = $qual->getUnit($record->unitid);
                }
                else
                {
                    $return[] = $record->unitid;
                }
            }
        }
        
        return $return;
        
    }
    
    
    /**
     * Get the crit ids linked to a course module and qual and unit
     * @global \GT\type $DB
     * @param type $cmID
     * @param type $qualID
     * @return type
     */
    public static function getCriteriaLinkedToCourseModule($cmID, $partID = null, $qualID = false, $unitID = false, $objects = false){
        
        global $DB;
        
        if (!$cmID) return array();
        
        if ($objects){
            // if we passed through a \GT\Unit object instead of an ID, put that into the $unit variable
            // then set the $unitID variable to the id of the object
            if ($unitID instanceof \GT\Unit){
                $unit = $unitID;
                $unitID = $unit->getID();
            } else {
                $unit = new \GT\Unit($unitID);
            }
        }
        
        $return = array();
                
        if ($qualID){
            if ($unitID){
                $params = array("cmid" => $cmID, "partid" => $partID, "qualid" => $qualID, "unitid" => $unitID, "deleted" => 0);
            } else {
                $params = array("cmid" => $cmID, "partid" => $partID, "qualid" => $qualID, "deleted" => 0);
            }
        } else {
            if ($unitID){
                $params = array("cmid" => $cmID, "partid" => $partID, "unitid" => $unitID, "deleted" => 0);
            } else {
                $params = array("cmid" => $cmID, "partid" => $partID, "deleted" => 0);
            }
        }
        
        // if we passed through FALSE for the partID, that means we don't want to check the part at all,
        // so the results could have a part and they could not
        if ($partID === false){
            unset($params['partid']);
        }
        
        $records = $DB->get_records("bcgt_activity_refs", $params, null, "DISTINCT critid");
        
        if ($records)
        {
            foreach($records as $record)
            {
                if ($objects){
                    $return[$record->critid] = $unit->getCriterion($record->critid);
                } else {
                    $return[] = $record->critid;
                }
            }
        }
        
        // If using objects, sort them
        if ($objects){
            $sort = new \GT\Sorter();
            $sort->sortCriteria($return, true);
        }
                
        return $return;
        
    }
    
    /**
     * Get distinct list of course modules attached to qual unit
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @return type
     */
    public static function getCourseModulesLinkedToUnit($qualID, $unitID, $critID = false){
        
        global $DB;
        
        $return = array();
        
        $params = array("qualid" => $qualID, "unitid" => $unitID, "deleted" => 0);
        if ($critID !== false){
            $params['critid'] = $critID;
        }
        
        $records = $DB->get_records("bcgt_activity_refs", $params, null, "DISTINCT cmid");
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = $record->cmid;
            }
        }
        
        return $return;
        
    }
    
    
    
    /**
     * Find all links on a course module
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function findLinks($cmID, $partID = null, $qualID = false, $unitID = false){
        
        global $DB;
        
        $return = array();
        $params = array("cmid" => $cmID, "partid" => $partID, "deleted" => 0);
        
        // If partID is TRUE that means it may have a partID but we don't want to limit it to a specific one
        // So we will just take the partID out of the query, so it will get ones with and without
        if ($partID === true)
        {
            unset($params['partid']);
        }
        
        // If qualID passed through, look for that as well
        if ($qualID)
        {
            $params['qualid'] = $qualID;
        }
        
        // If unitID passed through, look for that as well
        if ($unitID)
        {
            $params['unitid'] = $unitID;
        }
           
        $records = $DB->get_records("bcgt_activity_refs", $params);
        
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Activity($record->id);
                $return[] = $obj;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Find all links on a qual unit
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function findLinksByUnit($qualID, $unitID){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records("bcgt_activity_refs", array("qualid" => $qualID, "unitid" => $unitID, "deleted" => 0));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Activity($record->id);
                $return[] = $obj;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Remove any links to this course module that were not submitted this time
     * @param type $cmID
     * @param type $submitted
     */
    public static function removeNonSubmittedLinks($cmID, $submitted){
                
        $existingLinks = \GT\Activity::findLinks($cmID, true);
        if ($existingLinks)
        {
            foreach($existingLinks as $link)
            {
                if (!array_key_exists($link->getQualID(), $submitted) || !in_array($link->getCritID(), $submitted[$link->getQualID()]))
                {
                    $link->remove();
                }
            }
        }
        
    }
    
    /**
     * Remove any activity links to this qual unit that were not submitted this time
     * Though only ones that are linked to course modules on this course, as otherwise we'll delete stuff
     * linked off other courses
     * @param type $qualID
     * @param type $unitID
     * @param type $courseID
     * @param type $submitted
     */
    public static function removeNonSubmittedLinksOnUnit($qualID, $unitID, $courseID, $submitted){
        
        global $DB;
                        
        $records = $DB->get_records("bcgt_activity_refs", array("qualid" => $qualID, "unitid" => $unitID, "deleted" => 0));
        if ($records)
        {
            foreach($records as $record)
            {
                $link = new \GT\Activity($record->id);
                if (!in_array($link->getCritID(), $submitted[$link->getCourseModuleID()]))
                {
                    $cm = $DB->get_record("course_modules", array("id" => $link->getCourseModuleID()));
                    if ($cm && $cm->course == $courseID)
                    {
                        $link->remove();
                    }
                }
            }
        }
        
    }
    
    /**
     * Given a course module id, get the id of the actual activity instance
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getActivityFromCourseModule($cmID){
        
        global $DB;
        
        $record = $DB->get_record("course_modules", array("id" => $cmID));
        return $record;
        
    }
    
    /**
     * Given a course module id, get the id of the actual activity instance
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getActivityModuleFromCourseModule($cmID){
        
        $record = self::getActivityFromCourseModule($cmID);
        return ($record) ? $record->module : false;
        
    }
    
    /**
     * Given a course module id, get the id of the actual activity instance
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getActivityInstanceFromCourseModule($cmID){
        
        $record = self::getActivityFromCourseModule($cmID);
        return ($record) ? $record->instance : false;
        
    }
    
    /**
     * Get the Course object from a course module ID
     * @global \GT\type $DB
     * @param type $cmID
     * @return boolean|\GT\Course
     */
    public static function getCourseFromCourseModule($cmID){
        
        global $DB;
        $cm = self::getActivityFromCourseModule($cmID);
        if ($cm){
            return new \GT\Course($cm->course);
        }
        
        return false;
        
    }
        
    
}
