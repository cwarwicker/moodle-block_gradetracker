<?php
/**
 * Module
 *
 * This class deals with Module Linking. Defining the database tables and columns so we can link up the GT
 * to activities of this Module. E.g. Assignment or Turnitin
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

class ModuleLink {
    
    public static $supportedMods = array('assign');

    private $id = false;
    private $modID;
    private $modTable;
    private $partTable;
    private $partModCol;
    private $modCourseCol;
    private $modStartCol;
    private $modDueCol;
    private $modTitleCol;
    private $partTitleCol;
    private $subTable;
    private $subPartCol;
    private $subModCol;
    private $subUserCol;
    private $subDateCol;
    private $subStatusCol;
    private $subStatusVal;
    private $auto;
    private $enabled;
    private $deleted;
    
    private $courseModID;
    private $recordID = false;
    private $record = false;
    private $recordParts = false;
    
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id){
            
            $record = $DB->get_record("bcgt_mods", array("id" => $id));
            if ($record){
                
                $this->id = $record->id;
                $this->modID = $record->modid;
                $this->modTable = $record->modtable;
                $this->partTable = $record->parttable;
                $this->partModCol = $record->partmodcol;
                $this->modCourseCol = $record->modcoursecol;
                $this->modStartCol = $record->modstartcol;
                $this->modDueCol = $record->modduecol;
                $this->modTitleCol = $record->modtitlecol;
                $this->partTitleCol = $record->parttitlecol;
                $this->subTable = $record->submissiontable;
                $this->subPartCol = $record->submissionpartcol;
                $this->subModCol = $record->submissionmodcol;
                $this->subUserCol = $record->submissionusercol;
                $this->subDateCol = $record->submissiondatecol;
                $this->subStatusCol = $record->submissionstatuscol;
                $this->subStatusVal = $record->submissionstatusval;
                $this->auto = $record->auto;
                $this->enabled = $record->enabled;
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
    
    public function isEnabled(){
        return ($this->enabled == 1);
    }
    
    public function hasAutoUpdates(){
        return ($this->auto == 1);
    }
    
    public function hasParts(){
        return (!is_null($this->partTable));
    }
        
    public function getID(){
        return $this->id;
    }
    
    public function getModID(){
        return $this->modID;
    }
    
    public function getModName(){
        
        global $DB;
        
        if (isset($this->modName)){
            return $this->modName;
        } else {
            
            $record = $DB->get_record("modules", array("id" => $this->modID));
            if ($record){
                $this->modName = $record->name;
                return $this->modName;
            } else {
                return false;
            }
            
        }
        
    }
    
    public function setModID($id){
        $this->modID = $id;
        return $this;
    }
    
    public function getModTable(){
        return addslashes($this->modTable);
    }
    
    public function setModTable($value){
        $this->modTable = trim($value);
        return $this;
    }
    
    public function getPartTable(){
        return addslashes($this->partTable);
    }
    
    public function setPartTable($value){
        $this->partTable = (is_null($value)) ? $value : trim($value);
        return $this;
    }

    public function getPartModCol(){
        return addslashes($this->partModCol);
    }
    
    public function setPartModCol($value){
        $this->partModCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }
        
    public function getModCourseCol(){
        return addslashes($this->modCourseCol);
    }
    
    public function setModCourseCol($value){
        $this->modCourseCol = trim($value);
        return $this;
    }
    
    public function getModStartCol(){
        return addslashes($this->modStartCol);
    }
    
    public function setModStartCol($value){
        $this->modStartCol = trim($value);
        return $this;
    }
    
    public function getModDueCol(){
        return addslashes($this->modDueCol);
    }
    
    public function setModDueCol($value){
        $this->modDueCol = trim($value);
        return $this;
    }
    
    public function getModTitleCol(){
        return addslashes($this->modTitleCol);
    }
    
    public function setModTitleCol($value){
        $this->modTitleCol = trim($value);
        return $this;
    }
    
    public function getPartTitleCol(){
        return addslashes($this->partTitleCol);
    }
    
    public function setPartTitleCol($value){
        $this->partTitleCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }
    
    public function getSubTable(){
        return addslashes($this->subTable);
    }
    
    public function setSubTable($value){
        $this->subTable = trim($value);
        return $this;
    }
    
    public function getSubPartCol(){
        return addslashes($this->subPartCol);
    }
    
    public function setSubPartCol($value){
        $this->subPartCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }
    
    public function getSubModCol(){
        return addslashes($this->subModCol);
    }
    
    public function setSubModCol($value){
        $this->subModCol = trim($value);
        return $this;
    }
    
    public function getSubUserCol(){
        return addslashes($this->subUserCol);
    }
    
    public function setSubUserCol($value){
        $this->subUserCol = trim($value);
        return $this;
    }
    
    public function getSubDateCol(){
        return addslashes($this->subDateCol);
    }
    
    public function setSubDateCol($value){
        $this->subDateCol = trim($value);
        return $this;
    }
    
    public function getSubStatusCol(){
        return addslashes($this->subStatusCol);
    }
    
    public function setSubStatusCol($value){
        $this->subStatusCol = $value;
        return $this;
    }
    
    public function getSubStatusVal(){
        return addslashes($this->subStatusVal);
    }
    
    public function setSubStatusVal($value){
        $this->subStatusVal = $value;
        return $this;
    }
    
    public function getAuto(){
        return $this->auto;
    }
    
    public function setAuto($value){
        $this->auto = trim($value);
        return $this;
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function setEnabled($value){
        $this->enabled = $value;
        return $this;
    }
    
    public function getDeleted(){
        return $this->deleted;
    }
    
    public function setDeleted($value){
        $this->deleted = $value;
        return $this;
    }
    
    /**
     * Get the icon of this module
     * @return boolean
     */
    public function getModIcon(){
                
        global $CFG, $DB;
        
        if (!$this->record) return false;
        
        $record = $DB->get_record("modules", array("id" => $this->modID));
        if ($record)
        {
                     
            $icon = $CFG->dirroot . '/mod/' . $record->name . '/pix/icon.png';
            if (file_exists($icon))
            {
                return str_replace($CFG->dirroot, $CFG->wwwroot, $icon);
            }
          
        }
        
        return $CFG->wwwroot . '/blocks/gradetracker/pix/no_image.jpg';
        
    }
    
    public function setCourseModID($id){
        $this->courseModID = $id;
        return $this;
    }
    
    public function getCourseModID(){
        return $this->courseModID;
    }
    
    public function getRecordID(){
        return $this->recordID;
    }
    
    public function clearRecords(){
        $this->recordID = false;
        $this->record = false;
        $this->recordParts = false;
    }
    
    public function setRecordID($id){
        
        global $DB;
                
        $this->recordID = $id;
        $this->record = $DB->get_record($this->modTable, array("id" => $this->recordID));
                        
        if ($this->partTable && $this->partModCol){
            $this->partTitleCol = addslashes($this->partTitleCol);
            $this->recordParts = $DB->get_records($this->partTable, array($this->partModCol => $this->recordID), false, "*, {$this->partTitleCol} as name");
        }
                
        return $this;
    }
    
    /**
     * Get the name of the record instance of this mod
     * @return boolean
     */
    public function getRecordName(){
        
        if (!$this->record) return false;
        
        $field = $this->modTitleCol;
        return (isset($this->record->$field)) ? $this->record->$field : false;
        
    }
    
    /**
     * Get the due date of the record instance of this mod
     * @param type $format
     * @return boolean
     */
    public function getRecordDueDate($format = false, $partID = false){
        
        if (!$this->record) return false;
        
        $field = $this->modDueCol;
                        
        // Are we getting the due date of a part of the activity?
        if ($partID){
            
            if (isset($this->recordParts[$partID])){
                $part = $this->recordParts[$partID];
                return (isset($part->$field)) ? ( ($format) ? date($format, $part->$field) : $part->$field) : false;
            }
            
        } else {
            return (isset($this->record->$field)) ? ( ($format) ? date($format, $this->record->$field) : $this->record->$field) : false;
        }
        
        return false;
        
    }
    
    /**
     * Get the parts of this record instance, if it has any
     * e.g. turnitintool has parts stored in turnitintool_parts
     * @global \GT\type $DB
     */
    public function getRecordParts(){
        return $this->recordParts;
    }
    
    public function getRecordPart($id){
        return (array_key_exists($id, $this->recordParts)) ? $this->recordParts[$id] : false;
    }
    
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function hasNoErrors(){
        
        global $DB;
        
        // Check everything is filled out
        if (empty($this->modID) || empty($this->modTable) || empty($this->modCourseCol) || empty($this->modStartCol) 
            || empty($this->modDueCol) || empty($this->modTitleCol) || empty($this->subTable) || empty($this->subUserCol)
            || empty($this->subDateCol) || empty($this->subModCol)){
            $this->errors[] = get_string('errors:missingparams', 'block_gradetracker');
        }
        
        // Check this table isn't already in use
        $check = $DB->get_record_select("bcgt_mods", "modid = ? AND deleted = 0 AND id <> ?", array($this->modID, $this->id));
        if ($check){
            $this->errors[] = get_string('modlinking:error:mod', 'block_gradetracker');
        }
        
        return (!$this->errors);
                
    }
    
    /**
     * Save the mod link
     * @global type $DB
     * @return type
     */
    public function save(){
        
        global $DB;
        
        if ($this->isValid()){
            
            $record = new \stdClass();
            $record->id = $this->id;
            $record->modid = $this->modID;
            $record->modtable = $this->modTable;
            $record->parttable = $this->partTable;
            $record->partmodcol = $this->partModCol;
            $record->modcoursecol = $this->modCourseCol;
            $record->modstartcol = $this->modStartCol;
            $record->modduecol = $this->modDueCol;
            $record->modtitlecol = $this->modTitleCol;
            $record->parttitlecol = $this->partTitleCol;
            $record->submissiontable = $this->subTable;
            $record->submissionpartcol = $this->subPartCol;
            $record->submissionusercol = $this->subUserCol;
            $record->submissiondatecol = $this->subDateCol;
            $record->submissionmodcol = $this->subModCol;
            $record->submissionstatuscol = $this->subStatusCol;
            $record->submissionstatusval = $this->subStatusVal;
            $record->auto = $this->auto;
            
            return $DB->update_record("bcgt_mods", $record);
            
        } else {
            
            $record = new \stdClass();
            $record->modid = $this->modID;
            $record->modtable = $this->modTable;
            $record->parttable = $this->partTable;
            $record->partmodcol = $this->partModCol;
            $record->modcoursecol = $this->modCourseCol;
            $record->modstartcol = $this->modStartCol;
            $record->modduecol = $this->modDueCol;
            $record->modtitlecol = $this->modTitleCol;
            $record->parttitlecol = $this->partTitleCol;
            $record->submissiontable = $this->subTable;
            $record->submissionpartcol = $this->subPartCol;
            $record->submissionusercol = $this->subUserCol;
            $record->submissiondatecol = $this->subDateCol;
            $record->submissionmodcol = $this->subModCol;
            $record->submissionstatuscol = $this->subStatusCol;
            $record->submissionstatusval = $this->subStatusVal;
            $record->auto = $this->auto;
            
            $this->id = $DB->insert_record("bcgt_mods", $record);
            return $this->id;
            
        }
        
    }
    
    /**
     * Delete the mod link and any activities linked to it
     * @global \GT\type $DB
     */
    public function delete(){
        
        global $DB;
        
        $this->setDeleted(1);
        
        // Set this mod to deleted in the DB
        $record = new \stdClass();
        $record->id = $this->id;
        $record->deleted = $this->deleted;
        $DB->update_record("bcgt_mods", $record);
        
        // Then set all activity refs of this mod to deleted as well
        $refs = $DB->get_records_sql("SELECT a.id
                                        FROM {bcgt_activity_refs} a
                                        INNER JOIN {course_modules} cm ON cm.id = a.cmid
                                        INNER JOIN mdl_modules m ON m.id = cm.module
                                        WHERE m.name = ?", array($this->getModName()));
        
        if ($refs)
        {
            foreach($refs as $ref)
            {
                $activity = new \GT\Activity($ref->id);
                $activity->remove();
            }
        }
        
        return true;
        
    }
    
    public function getQualsOnModule($partID = null){
        
        $return = array();
        $quals = \GT\Activity::getQualsLinkedToCourseModule($this->courseModID, $partID);
        if ($quals)
        {
            foreach($quals as $qualID)
            {
                $qual = new \GT\Qualification($qualID);
                if ($qual->isValid())
                {
                    $return[] = $qual;
                }
            }
        }    
        
        return $return;
        
    }
    
    public function getUnitsOnModule($qualID = false){
        
        $return = array();
        $unitIDs = \GT\Activity::getUnitsLinkedToCourseModule($this->courseModID, $qualID);
        if ($unitIDs)
        {
            foreach($unitIDs as $unitID)
            {
                $unit = new \GT\Unit($unitID);
                if ($unit->isValid())
                {
                    $return[] = $unit;
                }
            }
        }    
        
        return $return;
        
    }
    
    /**
     * Count the number of criteria linked to this module activity
     * @param type $qualID
     * @return type
     */
    public function countCriteriaOnModule($qualID = false, $unit = false, $partID = null){
        
        $critIDs = \GT\Activity::getCriteriaLinkedToCourseModule($this->courseModID, $partID, $qualID);                
        return count($critIDs);
        
    }
    
    /**
     * Get list of modules attached to qual unit
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @return \GT\ModuleLink
     */
    public static function getModulesOnUnit($qualID, $unitID, $courseID = false){
        
        global $DB;
        
        $return = array();
        $cmIDs = \GT\Activity::getCourseModulesLinkedToUnit($qualID, $unitID);
        
        if ($cmIDs)
        {
            
            foreach($cmIDs as $cmID)
            {
                
                $courseMod = $DB->get_record("course_modules", array("id" => $cmID));
                if ($courseMod)
                {
                    $modLink = $DB->get_record("bcgt_mods", array("modid" => $courseMod->module));
                    if ($modLink)
                    {
                        
                        // If we've specified a course id, the course mod has to be on this course
                        if ($courseID)
                        {
                            if ($courseMod->course <> $courseID)
                            {
                                continue;
                            }
                        }
                        
                        $mod = new \GT\ModuleLink($modLink->id);
                        $mod->setRecordID($courseMod->instance);
                        $mod->setCourseModID($courseMod->id);
                        $return[$courseMod->id] = $mod;
                    }
                }
                
            }
            
        }
                
        return $return;
        
    }
    
    /**
     * Get criteria on this module activity
     * @param type $qualID
     * @param type $unit
     * @return type
     */
     public function getCriteriaOnModule($qualID, $unit, $partID = null){
                                  
        $return = array();
        $critIDs = \GT\Activity::getCriteriaLinkedToCourseModule($this->courseModID, $partID, $qualID, $unit->getID());
        if ($critIDs)
        {
            foreach($critIDs as $critID)
            {
                $criterion = $unit->getCriterion($critID);
                if ($criterion && $criterion->isValid())
                {
                    $return[] = $criterion;
                }
            }
        }    
                
        return $return;
        
    }
    
    /**
     * Count the number of activity refs that use this module
     * @global \GT\type $DB
     * @return type
     */
    public function countActivityRefs(){
        
        global $DB;
                
        $records = $DB->get_record_sql("SELECT COUNT(a.id) as 'cnt'
                                        FROM {bcgt_activity_refs} a
                                        INNER JOIN {course_modules} cm ON cm.id = a.cmid
                                        INNER JOIN {modules} m ON m.id = cm.module
                                        WHERE m.name = ? AND a.deleted = 0", array($this->getModName()));
                
        return $records->cnt;
        
    }
    
    /**
     * Load data from the form
     */
    public function loadPostData(){
                
        if (isset($_POST['submit_mod_link']))
        {
            
            $this->setModID($_POST['modid']);
            $this->setModTable($_POST['modtable']);
            $this->setPartTable( (!empty($_POST['parttable'])) ? $_POST['parttable'] : null );
            $this->setPartModCol( (!empty($_POST['partmodcol'])) ? $_POST['partmodcol'] : null );
            $this->setModCourseCol($_POST['modcoursecol']);
            $this->setModStartCol($_POST['modstartcol']);
            $this->setModDueCol($_POST['modduecol']);
            $this->setModTitleCol($_POST['modtitlecol']);
            $this->setPartTitleCol( (!empty($_POST['parttitlecol'])) ? $_POST['parttitlecol'] : null );
            $this->setSubTable($_POST['submissiontable']);
            $this->setSubPartCol( (!empty($_POST['submissionpartcol'])) ? $_POST['submissionpartcol'] : null );
            $this->setSubUserCol($_POST['submissionusercol']);
            $this->setSubDateCol($_POST['submissiondatecol']);
            $this->setSubModCol($_POST['submissionmodinstancecol']);
            $this->setSubStatusCol($_POST['submissionstatuscol']);
            $this->setSubStatusVal($_POST['submissionstatusval']);
            $this->setAuto( ( isset($_POST['auto']) ? 1 : 0 ) );
            $this->setEnabled( ( isset($_POST['enabled']) ? 1 : 0 ) );
                        
        }
        
    }
    
    /**
     * Get mod links which are enabled
     * @global \GT\type $DB
     * @return \GT\ModuleLink
     */
    public static function getEnabledModLinks(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records("bcgt_mods", array("enabled" => 1, "deleted" => 0), "modid ASC", "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\ModuleLink($record->id);
                $return[] = $obj;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get all the mods installed in Moodle
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllInstalledMods(){
        
        global $DB;
        
        $records = $DB->get_records("modules", array("visible" => 1), "name ASC", "id, name");
        return $records;
        
    }
    
    /**
     * Get the supported mods
     * @return type
     */
    public static function getSupportedMods(){
        return self::$supportedMods;
    }
    
    /**
     * Clear the records off all the mod links in an array
     * @param type $modLinks
     */
    public static function clearModRecords(&$modLinks){
        
        if ($modLinks){
            
            foreach($modLinks as $modLink){
                
                $modLink->clearRecords();
                
            }
            
        }
        
    }
    
    /**
     * Get a bcgt_mod ModuleLink based on a courseModuleID
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getModuleLinkFromCourseModule($cmID){
        
        global $DB;
        
        $modID = \GT\Activity::getActivityModuleFromCourseModule($cmID);
        $check = $DB->get_record("bcgt_mods", array("modid" => $modID), "id");
        if ($check)
        {
            $moduleLink = new \GT\ModuleLink($check->id);
            if ($moduleLink->isValid())
            {
                $instance = \GT\Activity::getActivityInstanceFromCourseModule($cmID);
                $moduleLink->setRecordID($instance);
                $moduleLink->setCourseModID($cmID);
                return $moduleLink;
            }
        }
        
        return false;
        
    }
    
    /**
     * Get course module record from id
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getCourseModule($cmID){
        
        global $DB;
        return $DB->get_record("course_modules", array("id" => $cmID));
        
    }
    
    public static function getCourseModuleInstance($cmID){
        
        global $DB;
        
        $module = self::getCourseModule($cmID);
        
        
    }
    
    /**
     * Get the ModuleLink object by the name of the module
     * @global \GT\type $DB
     * @param type $name
     * @return boolean|\GT\ModuleLink
     */
    public static function getByModName($name){
        
        global $DB;
        
        $mod = $DB->get_record("modules", array("name" => $name));
        if ($mod)
        {
            $record = $DB->get_record("bcgt_mods", array("modid" => $mod->id), "id");
            if ($record)
            {
                return new \GT\ModuleLink($record->id);
            }
        }
        
        return false;
        
    }
    
}
