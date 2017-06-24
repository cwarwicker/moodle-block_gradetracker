<?php
/**
 * GT\Criterion
 *
 * This abstract class handles all the core Criterion stuff
 * 
 * It is extended into sub classes for the different types of criteria
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

abstract class Criterion {
   
    const DEFAULT_WEIGHT = '1.0';
    
    protected $id = false;
    protected $qualStructureID;
    protected $unitID;
    protected $parentCritID;
    protected $dynamicNumber = false; // Dynamic number of this
    protected $parentNumber; // Dynamic number of parent
    protected $name;
    protected $description = '';
    protected $type;
    protected $subCritType;
    protected $gradingStructureID = null;
    protected $gradingStructure = false;
    protected $deleted = 0;
    
    protected $children = array();
    protected $attributes = array();    
    protected $errors = array();
    
    protected $student = false;
    
    protected $userCriteriaRowID = false;
    protected $userAward = false;
    protected $userAwardDate;
    protected $userComments;
    protected $userCustomValue;
    protected $userFlag;
    protected $userTargetDate;
    protected $userLastUpdate;
    protected $userLastUpdateBy;
        
    protected $loadFrom = false;
    protected $qualID; // The qualification we are viewing this criterion on. Only used for permission checking
    public $jsonResult = false;

    
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isDeleted(){
        return ($this->deleted == 1);
    }
        
    public function getID(){
        return $this->id;
    }
    
    public function setID($id){
        $this->id = $id;
        return $this;
    }
    
    public function getQualStructureID(){
        return $this->qualStructureID;
    }
    
    public function setQualStructureID($id){
        $this->qualStructureID = $id;
        return $this;
    }
    
    public function getUnitID(){
        return $this->unitID;
    }
    
    public function setUnitID($id){
        $this->unitID = $id;
        return $this;
    }
    
    public function getUnit(){
        $unit = new \GT\Unit($this->unitID);
        return ($unit->isValid()) ? $unit : false;
    }
    
    public function getParent(){
                
        if (!$this->parentCritID) return false;
                
        $unit = new \GT\Unit\UserUnit($this->unitID);
        if ($unit->isValid())
        {
            $unit->setQualID($this->qualID);
            $unit->setQualStructureID($this->qualStructureID);
            $unit->loadStudent($this->student);
        }
        
        return $unit->getCriterion($this->parentCritID);
                
    }
    
    public function getEldestParent(){
        
        $parent = $this->getParent();
        if ($parent && $parent->hasParent()){
            
            return $parent->getEldestParent();
            
        } elseif ($parent) {
            
            return $parent;
            
        } 
        
        return false;
        
    }
    
    /**
     * Get the parents above this criterion
     * @param type $arr Passed in as recursive method
     * @return type
     */
    public function getParents(&$arr = false){
        
        
        $return = array();
        
        if ($this->getParent())
        {
            
            $parent = $this->getParent();
            
            if ($arr){
                $arr[] = $parent;
                $parent->getParents($arr);
            } else {
                $return[] = $parent;
                $parent->getParents($return);
            }
            
        
        }
        
        return $return;
        
    }
    
    public function getParentID(){
        return $this->parentCritID;
    }
    
    public function setParentID($id){
        $this->parentCritID = $id;
        return $this;
    }
    
    public function hasParent(){
        return ($this->parentCritID > 0);
    }
    
    public function getQualID(){
        return $this->qualID;
    }
    
    public function getQualification(){
        $qual = new \GT\Qualification($this->qualID);
        return ($qual->isValid()) ? $qual : false;
    }
    
    public function setQualID($id){
        $this->qualID = $id;
        return $this;
    }
    
    /**
     * Get the award date of a user's criteria in a given format
     * @param type $format
     * @return type
     */
    public function getUserAwardDate($format = false){
        return ($format) ? date($format, $this->userAwardDate) : $this->userAwardDate;
    }
    
    /**
     * Get either the actual award date specified for this, or the date it was updated
     * @param type $format
     * @return boolean
     */
    public function getUserAwardDateOrUpdateDate($format = false){
        
        if ($this->userAwardDate > 0){
            return ($format) ? date($format, $this->userAwardDate) : $this->userAwardDate;
        } elseif ($this->userLastUpdate > 0){
            return ($format) ? date($format, $this->userLastUpdate) : $this->userLastUpdate;
        } else {
            return false;
        }
        
    }
    
    
    public function hasUserComments(){
        $comments = $this->getUserComments();
        return (strlen($comments) > 0);
    }
    
    public function getUserComments(){
        return trim($this->userComments);
    }
    
    public function getUserCustomValue(){
        return $this->userCustomValue;
    }
    
    public function getUserFlag(){
        return $this->userFlag;
    }
    
    public function getUserTargetDate(){
        return $this->userTargetDate;
    }
    
    public function getUserLastUpdate($format = false){
        return ($format) ? date($format, $this->userLastUpdate) : $this->userLastUpdate;
    }
    
    public function getUserLastUpdateByUserID(){
        return $this->userLastUpdate;
    }
    
    public function getUserLastUpdateBy(){
        return new \GT\User($this->userLastUpdateBy);
    }
    
    public function setUserAward(\GT\CriteriaAward $award){
        $this->userAward = $award;
    }
    
    public function setUserAwardID($id){
        $this->userAward = new \GT\CriteriaAward($id);
    }
    
    public function setUserAwardDate($date){
        
        if ($date <= 0){
            $date = null;
        }
        
        $this->userAwardDate = $date;
        
    }
    
    public function setUserComments($comments){
        $comments = \gt_convert_to_utf8($comments);
        $this->userComments = trim($comments);
    }
    
    public function setUserCustomValue($value){
        $this->userCustomValue = trim($value);
    }
    
    public function setUserFlag($flag){
        $this->userFlag = $flag;
    }
    
    public function setUserTargetDate($date){
        $this->userTargetDate = $date;
    }
    
    
    
    /**
     * Get the dynamic number of the criterion that is the parent of this one
     * @return type
     */
    public function getParentNumber(){
        return $this->parentNumber;
    }
    
    /**
     * Set the dynamic number of the parent, to be later converted to an actual ID
     * @param type $num
     * @return \GT\Criterion
     */
    public function setParentNumber($num){
        $this->parentNumber = $num;
        return $this;
    }
    
    /**
     * Get the dynamic number of the criterion from the unit form
     * @return type
     */
    public function getDynamicNumber(){
        return $this->dynamicNumber;
    }
    
    /**
     * Set the dynamic number of the criterion from the unit form
     * @param type $num
     * @return \GT\Criterion
     */
    public function setDynamicNumber($num){
        $this->dynamicNumber = $num;
        return $this;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function getDescription(){
        return $this->description;
    }
    
    public function setDescription($desc){
        $this->description = trim($desc);
        return $this;
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function setType($type){
        $this->type = $type;
        return $this;
    }
    
    public function getSubCritType(){
        return $this->subCritType;
    }
    
    public function setSubCritType($type){
        $this->subCritType = $type;
        return $this;
    }
    
    public function getGradingStructure(){
        
        if ($this->gradingStructure === false){
            $this->gradingStructure = new \GT\CriteriaAwardStructure($this->getGradingStructureID());
        }
        
        return $this->gradingStructure;
        
    }
    
    public function getGradingStructureID(){
        return $this->gradingStructureID;
    }
    
    public function setGradingStructureID($id){
        if ($id == '' || $id == 0){
            $id = null;
        }
        $this->gradingStructureID = $id;
        return $this;
    }
    
    public function getDeleted(){
        return $this->deleted;
    }
    
    public function setDeleted($val){
        $this->deleted = $val;
        return $this;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getChildren(){
        return $this->children;
    }
    
    public function setChildren($children){
        $this->children = $children;
        return $this;
    }
    
    /**
     * Get a child of this criterion by its ID
     * @param type $id
     * @return boolean
     */
    public function getChildByID($id){
        
        $children = $this->getChildren();
        if ($children)
        {
            foreach($children as $child)
            {
                if ($child->getID() == $id)
                {
                    return $child;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get child criteria only if they have a specific subcrittype
     * @param type $type
     */
    public function getChildOfSubCritType($type){
        
        $children = $this->getChildren();
        $results = array();
        
        if ($children)
        {
            foreach($children as $child)
            {
                if ($child->getSubCritType() == $type)
                {
                    $results[$child->getID()] = $child;
                }
            }
        }
                
        return $results;
        
    }
    
    /**
     * Add a child criterion
     * @param type $criterion
     * @param bool $dynamic 
     */
    public function addChild($criterion, $dynamic = false){
        if ($dynamic){
            $this->children[] = $criterion;
        } else {
            $this->children[$criterion->getID()] = $criterion;
        }
    }
    
    public function loadChildren(){
        
        global $DB;
                
        $this->children = array();
        
        $criteria = $DB->get_records("bcgt_criteria", array("parentcritid" => $this->id, "deleted" => 0));
        if ($criteria)
        {
            foreach($criteria as $crit)
            {
                $obj = \GT\Criterion::load($crit->id);
                if ($obj)
                {
                    $obj->setQualID($this->qualID);
                    $this->addChild($obj);
                }
            }
        }
        
        // Sort the children
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteria($this->children);
        
    }
    
    public function getStudent(){
        return $this->student;
    }
    
    
    /**
     * Clear any loaded student
     */
    public function clearStudent(){
        
        $this->student = false;
        $this->userCriteriaRowID = false;
        $this->userAward = false;
        $this->userAwardDate = false;
        $this->userComments = false;
        $this->userCustomValue = false;
        $this->userFlag = false;
        $this->userTargetDate = false;
        $this->userLastUpdate = false;
        $this->userLastUpdateBy = false;
        $this->_userRow = false;

        if ($this->children)
        {
            foreach($this->children as $child)
            {
                $child->clearStudent();
            }
        }
        
    }
    
    /**
     * Load a student into the userunit object
     * @param \GT\User $student
     */
    public function loadStudent($student){
                        
        // Clear first
        $this->clearStudent();
        
        // Might be a User object we passed in
        if ($student instanceof \GT\User){
            
            if ($student->isValid()){
                $this->student = $student;
            }
            
        } else {
        
            // Or might be just an ID
            $user = new \GT\User($student);
            if ($user->isValid())
            {
                $this->student = $user;
            }
        
        }
        
        
        // Now load the info from their user_criteria record
        if ($this->student)
        {
            
            global $DB;
            
            $record = $DB->get_record("bcgt_user_criteria", array("userid" => $this->student->id, "critid" => $this->id));
            $this->_userRow = $record;
            if ($record)
            {
                
                $this->userCriteriaRowID = $record->id;
                $this->userAward = new \GT\CriteriaAward($record->awardid);
                $this->userAwardDate = $record->awarddate;
                $this->userComments = $record->comments;
                $this->userCustomValue = $record->customvalue;
                $this->userFlag = $record->flag;
                $this->userTargetDate = $record->targetdate;
                $this->userLastUpdate = $record->lastupdate;
                $this->userLastUpdateBy = $record->lastupdateby;
                
            }
            else
            {
                $this->userAward = new \GT\CriteriaAward(0);
            }
            
        }
        
        // Now we load the student into any sub criteria
        if (!$this->children)
        {
            $this->loadChildren();
        }
        
        if ($this->children)
        {
            foreach($this->children as $child)
            {
                $child->loadStudent($this->student);
            }
        }
        
    }
    
   
    
    /**
     * Get the user award for this student
     * @return boolean
     */
    public function getUserAward(){
        
        if (!$this->student){
            return false;
        }
                        
        return $this->userAward;
        
    }
    
   
    
    /**
     * Does this criterion type need a sub row in the unit creation form for extra stuff?
     * @return boolean
     */
    public function hasFormSubRow(){
        return false;
    }
    
    /**
     * Check if this criterion has any activity links
     * @global \GT\type $DB
     * @return type
     */
    public function hasActivityLink($qualID){
                
        $records = $this->getActivityLinks($qualID);
        return (count($records) > 0);
        
    }
    
    /**
     * Check if this criterion has any activity links
     * @global \GT\type $DB
     * @return type
     */
    public function getActivityLinks($qualID){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_activity_refs", array("critid" => $this->id, "qualid" => $qualID, "deleted" => 0));
        return $records;
        
    }
    
    /**
     * Count the depth of the children
     * @param type $criteria
     * @return type
     */
    public function countChildLevels(){
                
        $cnt = 0;
        
        // Does this have children?
        if ($this->getChildren())
        {
                        
            foreach($this->getChildren() as $child)
            {
                
                $depth = $child->countChildLevels();
                if ($depth > $cnt){
                    $cnt = $depth;
                }
                
            }
            
            $cnt += 1;
            
        }
        
        return $cnt;
        
    }
    
    /**
     * Check if this criterion has any sub criteria of a particular sub criteria type, e.g. Range, Criterion
     * @param type $subType
     * @return boolean
     */
    public function hasChildrenOfType($subType){
    
        if ($this->getChildren()){
            
            foreach($this->getChildren() as $child){
                
                if ($child->getSubCritType() == $subType){
                    return true;
                }
                
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Get the possible values from the grading structure of this criterion
     * @param type $metOnly
     * @return type
     */
    public function getPossibleValues($metOnly = false){
        
        $GradingStructure = new \GT\CriteriaAwardStructure($this->gradingStructureID);
        if (!$GradingStructure->isValid())
        {
            return false;
        }
        
        return $GradingStructure->getAwards($metOnly);
        
    }
    
    /**
     * Load all attributes of this criterion
     * @global \GT\type $DB
     */
    public function loadAttributes(){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_criteria_attributes", array("critid" => $this->id, "userid" => null));
        if ($records)
        {
            foreach($records as $record)
            {
                $this->attributes[$record->attribute] = $record->value;
            }
        }
        
    }
    
    /**
     * Get all attributes
     * @return type
     */
    public function getAttributes(){
        return $this->attributes;
    }
    
    /**
     * Get a local attribute for this criterion, e.g. weighting
     * @param type $attribute
     * @return type
     */
    public function getAttribute($attribute){
        
        if (!$this->attributes){
            $this->loadAttributes();
        }
        
        return (isset($this->attributes[$attribute])) ? $this->attributes[$attribute] : false;
        
    }
    
    /**
     * Set a local attribute on the object
     * @param type $attribute
     * @param type $value
     */
    public function setAttribute($attribute, $value){
        $this->attributes[$attribute] = $value;
    }
    
    /**
     * Get an attribute of this criterion for a particular user, from the database
     * @global type $DB
     * @param type $attribute
     * @param type $userID
     * @return type
     */
    public function getUserAttribute($attribute, $userID){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_criteria_attributes", array("critid" => $this->id, "userid" => $userID, "attribute" => $attribute));
        return ($check) ? $check->value : false;
        
    }
    
    /**
     * Update/Set an attribute for this criterion
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $value
     * @param type $userID
     * @return type
     */
    public function updateAttribute($attribute, $value, $userID = null){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_criteria_attributes", array("critid" => $this->id, "userid" => $userID, "attribute" => $attribute));
        
        // ------------ Logging Info
        if (!is_null($userID)){
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;
            $Log->beforejson = array(
                $attribute => ($check) ? $check->value : null
            );
        }
        // ------------ Logging Info
        
        if ($check)
        {
            $check->value = $value;
            $check->lastupdate = time();
            $result = $DB->update_record("bcgt_criteria_attributes", $check);
        }
        else
        {
            $ins = new \stdClass();
            $ins->critid = $this->id;
            $ins->userid = $userID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $ins->lastupdate = time();
            $result = $DB->insert_record("bcgt_criteria_attributes", $ins);
        }
        
        // If it was a user attribute, log it
        if (!is_null($userID)){
            
            // ----------- Log the action
            $Log->afterjson = array(
                $attribute => $value
            ); 

            $Log->attributes = array(
                    \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                    \GT\Log::GT_LOG_ATT_UNITID => $this->unitID,
                    \GT\Log::GT_LOG_ATT_CRITID => $this->id,
                    \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
                );

            $Log->save();
            // ----------- Log the action
            
        }
        
        return $result;
        
    }
    
    /**
     * Check if there are no errors in the criterion
     * @param type $parent If passed in, this is the parent of the criterion we are checking
     * @return type
     */
    public function hasNoErrors($parent = false){
                        
        $QualStructure = new \GT\QualificationStructure($this->qualStructureID);
        if (!$QualStructure->isValid()){
            $this->errors[] = sprintf( get_string('errors:crit:structure', 'block_gradetracker'), $this->name );
        }
                
        // Check name
        if (strlen($this->name) == 0){
            $this->errors[] = sprintf( get_string('errors:crit:name', 'block_gradetracker'), $this->name );
        }
        
        // Check type
        $supportedTypes = self::getSupportedTypes();
        if (!array_key_exists($this->type, $supportedTypes)){
            $this->errors[] = sprintf( get_string('errors:crit:type', 'block_gradetracker'), $this->name );
        } else {
            $type = $supportedTypes[$this->type];
            if (!$QualStructure->isLevelEnabled($type->id)){
                $this->errors[] = sprintf( get_string('errors:crit:type:disabled', 'block_gradetracker'), $this->name, $type->name );
            }
        }
        
        // Check weighting - Set to default of 1 if not correct
        if (!ctype_digit($this->getAttribute('weighting'))){
            $this->setAttribute('weighting', self::DEFAULT_WEIGHT);
        }
        
        // Check parent
        // First make sure we haven't set the parent as itself, as they will fuck things up
        if ($this->parentNumber == $this->dynamicNumber){
            $this->errors[] = sprintf( get_string('errors:crit:parent:self', 'block_gradetracker'), $this->name );
        }
        
        // Now make sure the parent is the same type as this, as we can't mix them
        if ($parent && $parent->getType() != $this->getType()){
            $this->errors[] = sprintf( get_string('errors:crit:parent:type', 'block_gradetracker'), $this->name );
        }
        
        // Now make sure we haven't gone over the maximum number of sub criteria
        $levelObj = new \GT\QualificationStructureLevel($this->type);
        if (!$levelObj->isValid()){
            $this->errors[] = sprintf( get_string('errors:crit:level', 'block_gradetracker'), $this->name );
        }
        
        
        if ($levelObj->isValid()){
        
            $maxLevels = $QualStructure->getLevelMaxSubCriteria($this->type);
            $minLevels = $levelObj->getMinSubLevels();
            $countLevels = $this->countChildLevels();
            
            if ($countLevels > $maxLevels){
                $this->errors[] = sprintf( get_string('errors:crit:levels:max', 'block_gradetracker'), $this->name, $countLevels, $maxLevels );
            } 
            
            // Only check minimum if this has no parent, as otherwise will be infinite, every level
            // requiring more levels
            if (!$parent && $countLevels < $minLevels){
                $this->errors[] = sprintf( get_string('errors:crit:levels:min', 'block_gradetracker'), $this->name, $countLevels, $minLevels );
            }
        
        }
        
                
        // Check grading type
        $GradingTypes = \GT\CriteriaAward::getSupportedGradingTypes();
        if (!in_array($this->getAttribute('gradingtype'), $GradingTypes)){
            $this->errors[] = sprintf( get_string('errors:crit:gradingtype', 'block_gradetracker'), $this->name );
        }
        
        $names = array();

        // Also check errors on any children
        if ($this->getChildren()){
            
            foreach($this->getChildren() as $child){
                
                if (!array_key_exists($child->getName(), $names)){
                    $names[$child->getName()] = 0;
                }
                
                $names[$child->getName()]++;
                
                if (!$child->hasNoErrors($this)){
                    
                    foreach($child->getErrors() as $error){
                        $this->errors[] = $error;
                    }
                    
                }
                
            }
            
        }
        
        // Make sure we have no duplicate criteria names at top level
        foreach($names as $name => $cnt){
            if ($cnt > 1){
                $this->errors[] = sprintf( get_string('errors:crit:duplicatenames', 'block_gradetracker'), $name );
            }
        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Save the criterion
     * @global \GT\type $DB
     * @return boolean
     */
    public function save(){
        
        global $DB;
                
        $obj = new \stdClass();
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->unitid = $this->unitID;
        $obj->name = $this->name;
        $obj->description = $this->description;
        $obj->type = $this->type;
        $obj->parentcritid = $this->parentCritID;
        $obj->gradingstructureid = $this->gradingStructureID;
        $obj->subcrittype = $this->subCritType;
                
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_criteria", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_criteria", $obj);
            $result = $this->id;
        }
        
        if (!$result)
        {
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
        
        // Now the attributes
        $DB->delete_records("bcgt_criteria_attributes", array("critid" => $this->id, "userid" => null));
        if ($this->attributes)
        {
            foreach($this->attributes as $att => $value)
            {
                $this->updateAttribute($att, $value);
            }
        }
        
        return true;        
        
    }
    
    /**
     * Save changes to the user's criteria
     * @global \GT\type $DB
     * @global type $USER
     * @param boolean $saveOnly Only do the saving, not the auto calculations or rules or anything like that
     * @return boolean
     */
    public function saveUser($saveOnly = false, $noEvent = false){
        
        global $DB, $USER;
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_CRIT;
        $Log->beforejson = array(
            'awardid' => ($this->_userRow) ? $this->_userRow->awardid : null,
            'awarddate' => ($this->_userRow) ? $this->_userRow->awarddate : null,
            'comments' => ($this->_userRow) ? $this->_userRow->comments : null,
            'customvalue' => ($this->_userRow) ? $this->_userRow->customvalue : null
        );
        // ------------ Logging Info
                       
        if (!$this->student){
            return false;
        }
        
        if ($this->userCriteriaRowID){
            
            $obj = new \stdClass();
            $obj->id = $this->userCriteriaRowID;
            
        } else {
        
            $obj = new \stdClass();
            $obj->userid = $this->student->id;
            $obj->critid = $this->id;
            
        }
        
        $obj->awardid = ($this->userAward && $this->getUserAward()->isValid()) ? $this->getUserAward()->getID() : null;
        $obj->awarddate = $this->userAwardDate;
        $obj->comments = (strlen($this->userComments) > 0) ? $this->userComments : null;
        $obj->customvalue = (strlen($this->userCustomValue) > 0) ? $this->userCustomValue : null;
        $obj->flag = $this->userFlag;
        $obj->targetdate = $this->userTargetDate;
        $obj->lastupdate = time();
        $obj->lastupdateby = $USER->id;
                
        // Update it
        if ($this->userCriteriaRowID){
            $DB->update_record("bcgt_user_criteria", $obj);
        } else {
            $this->userCriteriaRowID = $DB->insert_record("bcgt_user_criteria", $obj);
        }
        
        $parents = $this->getParents();
        
        // Notify Listeners of the event that just took place
        // We don't want this to call multiple times as it does parent auto calcuations, only once
        // So we do it if no parents, that way if it's just a singular criterion with no parents it does it
        // Otherwise it keeps autocalculating through until the top level, with no parents and does it then        
        if ( (!$parents && !$noEvent) || $noEvent === 'force' )
        {
            $Event = new \GT\Event( GT_EVENT_CRIT_UPDATE, array(
                'sID' => $this->student->id,
                'qID' => $this->qualID,
                'uID' => $this->unitID,
                'cID' => $this->id,
                'value' => $obj->awardid
            ) );

            $Event->notify();
        }
        
        if (!$saveOnly)
        {
        
            $result = array();
            
            // Auto calculations
            if ($parents)
            {
                foreach($parents as $parent)
                {
                    $parent->autoCalculateAward();
                    $result[] = $parent->jsonResult;
                }
            }
            
            // Set jsonResult for this criterion to be used in the ajax/update script
            $this->jsonResult = \gt_flatten_array($result);
            

            // Rules
        
        
        
        }
                
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'awardid' => $obj->awardid,
            'awarddate' => $obj->awarddate,
            'comments' => $obj->comments,
            'customvalue' => $obj->customvalue
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                \GT\Log::GT_LOG_ATT_UNITID => $this->unitID,
                \GT\Log::GT_LOG_ATT_CRITID => $this->id,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
                       
        
        
        return true;
               
    }
    
    protected function hasAutoCalculation(){
        return true;
    }
    
    protected function autoCalculateAward( $variables = false ){
                                        
        $force = (isset($variables['force']) && $variables['force'] == true) ? true : false;
        
        // If this criterion type doesn't have auto calculations, stop
        if (!$this->hasAutoCalculation() && !$force) return false;
        
        $children = (isset($variables['children'])) ? $variables['children'] : $this->getChildren();
        
        // If it doesn't have any children then nothing for it to do
        if (!$children) return false;
        
        $filter = new \GT\Filter();
        $children = $filter->filterCriteriaNotReadOnly($children);
                
        $userAward = false;
       
        // Now auto calculate this criterion        
        $currentUserAward = $this->getUserAward();
        
        // Get the grading structure of this criterion, so we can use its point ranges
        $gradingStructure = $this->getGradingStructure();
        if (!$gradingStructure->isValid()) return false;
        
        $possibleAwardArray = array();
        $possibleAwards = $gradingStructure->getAwards(true);
        if (!$possibleAwards) return false;
        
        // Check if at least one of the awards is using point ranges
        foreach($possibleAwards as $possibleAward)
        {
            if ($possibleAward->getPointsLower() > 0 || $possibleAward->getPointsUpper() > 0)
            {
                $possibleAwardArray[] = $possibleAward;
            }
        }
        
        if (!$possibleAwardArray) return false;
                
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteriaValues($possibleAwardArray, 'asc');
        
        $maxPoints = $gradingStructure->getMaxPoints();
        $minPoints = $gradingStructure->getMinPoints();
        $childMaxPointArray = array();
                
        // Check all the children to see if at least one has a grading structure with the same
        // max points, otherwise we cannot do an auto calculation
        foreach($children as $child)
        {
            $childGradingStructure = $child->getGradingStructure();
            $childMaxPointArray[$child->getID()] = $childGradingStructure->getMaxPoints();
        }
        
        // If none have a max points of the same as the parent, we cannot proceed        
        if (!in_array($maxPoints, $childMaxPointArray)) return false;
                
        
        // Now loop through children again and see if they are all met
        // And if they are, get the point score so we can work out the average
              
        $cntChildren = 0;
        
        // Use only the ones with a grading structure, as some may be readonly
        if ($children)
        {
            foreach($children as $child)
            {
                $grading = $child->getGradingStructure();
                if ($grading && $grading->isValid())
                {
                    $cntChildren++;
                }
            }
        }
        
        $cntMet = 0;
        $pointsArray = array();
        
        foreach($children as $child)
        {
            
            // Reload user award, as doesn't always update from previous loop iteration as object
            // in various places and not always a reference
            $child->loadStudent( $this->student );            
            if ($child->getUserAward() && $child->getUserAward()->isMet())
            {
                
                $cntMet++;
                
                $points = $child->getUserAward()->getPoints();
                
                // If this only has one possible award (e.g. Achieved) but the parent has multiple
                // (e.g. PMD) then don't include this in the calculations as it will throw it off
                $childPossibleAwards = $child->getGradingStructure()->getAwards(true);
                if (count($childPossibleAwards) == 1 && count($possibleAwardArray) > 1)
                {
                    continue;
                }
                
                // If this doesn't have any awards with a points score above 0, skip it as well
                if ($child->getGradingStructure()->getMaxPoints() == 0)
                {
                    continue;
                }
                
                // If the max points of this is different to that of the parent, adjust it up or down
                // to ensure calculation is accurate
                $childMaxPoints = $childMaxPointArray[$child->getID()];
                if ($childMaxPoints <> $maxPoints)
                {
                                        
                    // Get the difference between the max and min of the parent's structure
                    $diff = $maxPoints - $minPoints;
                    $steps = count($childPossibleAwards) - 1;
                    $fraction = $diff / $steps;
                    
                    // Are we adjusting from a larger scale to a smaller scale, or the other way?
                    if ( count($childPossibleAwards) > count($possibleAwardArray) )
                    {
                        $adjusted = $child->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'down');
                    } 
                    elseif ( count($childPossibleAwards) < count($possibleAwardArray) )
                    {
                        $adjusted = $child->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'up');
                    }
                    else
                    {
                        $adjusted = $child->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray);
                    }
                    
                    $points = $adjusted[$points];
                                        
                }
                
                $pointsArray[$child->getID()] = $points;
                
            }
            
        }
                                
                
        // Only auto calculate an award if they are all met
        if ($cntMet === $cntChildren)
        {
        
            $totalPoints = array_sum($pointsArray);
            $avgPoints = round( ($totalPoints / count($pointsArray)), 1 );
                        
            // Re-order from highest to lowest
            $Sorter->sortCriteriaValues($possibleAwardArray, 'desc');

            
            // Work out which award to use
            foreach($possibleAwardArray as $award)
            {
                                
                // If it has both a lower and upper range
                if ($award->getPointsLower() > 0 && $award->getPointsUpper() > 0)
                {
                    
                    if ($avgPoints >= $award->getPointsLower() && $avgPoints <= $award->getPointsUpper())
                    {
                        $userAward = $award;
                        break;
                    }
                    
                }
                // Else if it has only a lower score
                elseif ($award->getPointsLower() > 0)
                {
                    if ($avgPoints >= $award->getPointsLower())
                    {
                        $userAward = $award;
                        break;
                    }
                }
                // Else if it has only a upper score
                elseif ($award->getPointsUpper() > 0)
                {
                    if ($avgPoints <= $award->getPointsUpper())
                    {
                        $userAward = $award;
                        break;
                    }
                }
                
            }
            
            // If an award has been found to use
            if ($userAward)
            {
                $this->setUserAward($userAward);
                $this->saveUser(true);
                $this->jsonResult = array( $this->id => $userAward->getID() );
            }
        
        }
        // If they aren't all met, but the criterion award has a met value, change it to N/A
        else
        {
            
            if ($currentUserAward && $currentUserAward->isMet())
            {
                
                $this->setUserAwardID(false);
                $this->saveUser(true);
                $this->jsonResult = array( $this->id => false );
                
            }
            
        }
                
    }
    
    
    
    
    public function loadExtraPostData($criterion){
        ;
    }
    
    /**
     * Get the options tpo be displayed for this criterion type in the criteria creation form
     * @return string
     */
    public function getFormOptions(){
        ;
    }
    
    /**
     * Get the info for the range to go in the popup
     */
    public function getRangePopUpContent(){
        ;
    }
    
    public function getCell($access, $from = false){
        
        global $User;
                
        $this->loadedFrom = $from;
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( ($access == 'e' || $access == 'ae') && !$User->canEditUnit($this->qualID, $this->unitID) ){
            $access = 'v';
        }
        
        // Check the grading structure is valid
        $gradingStructure = $this->getGradingStructure();
        if (!$gradingStructure || !$gradingStructure->isValid() || $gradingStructure->isDeleted()){
            return get_string('invalidgradingstructure', 'block_gradetracker');
        }
        
        $output = "";
        
        switch($access)
        {
            
            case 'v':
                return $this->getCellView();
            break;
        
            case 'e':
                return $this->getCellEdit();
            break;
        
            case 'ae':
                return $this->getCellEdit(true);
            break;
            
        }
        

        return $output;
        
    }
    
    /**
     * Get the standard view for a criterion cell
     * @return type
     */
    protected function getCellView(){
        
        global $DB;
        
        $output = "";
        $award = $this->getUserAward();
                
        if ($award && $award->isValid() && $award->getImage() == null){
            $output .= $award->getName();
        }
        else {
            $output .= "<img class='gt_award_icon' src='{$this->getUserAward()->getImageURL()}' alt='{$this->getUserAward()->getShortName()}' />";
        }
        
        // Award date?
        if ($this->userAwardDate > 0 && $this->getAttribute('gradingtype') == 'DATE'){
            //$output .= "<br><small>".date('d/m/Y', $this->userAwardDate)."</small>";
        }
        
        return $output;
        
    }
    
    /**
     * Get the cell when exporting to an excel spreadsheet
     * @param type $objPHPExcel
     * @param type $rowNum
     * @param type $letter
     */
    public function getExcelCell(&$objPHPExcel, $rowNum, $letter)
    {
        
        // Check the grading structure is valid
        $gradingStructure = $this->getGradingStructure();
        if (!$gradingStructure || !$gradingStructure->isValid() || $gradingStructure->isDeleted()){
            return false;
        }
        
        $award = $this->getUserAward();
        $value = ($award) ? $award->getShortName() : get_string('na', 'block_gradetracker');
        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $value);
        
        
        // Select menu
        $conditionalValuesArray = array(
            'met' => array(),
            'no' => array()
        );
        $values = $this->getPossibleValues();
        $possibleValues = array();
        $possibleValues[] = get_string('na', 'block_gradetracker');
        if ($values)
        {
            foreach($values as $val)
            {
                $possibleValues[] = $val->getShortName();
                if ($val->isMet()){
                    $conditionalValuesArray['met'][] = $val->getShortName();
                } elseif ($val->getSpecialVal() == 'NO'){
                    $conditionalValuesArray['no'][] = $val->getShortName();
                }
            }
        }
                
        $possibleValuesString = '"'.implode(",", $possibleValues).'"';
        
        // Can't be more than 255 characters or Excel breaks
        if (strlen($possibleValuesString) <= 255)
        {
        
            $objValidation = $objPHPExcel->getActiveSheet()->getCell("{$letter}{$rowNum}")->getDataValidation();
            $objValidation->setType( \PHPExcel_Cell_DataValidation::TYPE_LIST );
            $objValidation->setErrorStyle( \PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
            $objValidation->setAllowBlank(false);
            $objValidation->setShowInputMessage(true);
            $objValidation->setShowErrorMessage(true);
            $objValidation->setShowDropDown(true);
            $objValidation->setErrorTitle('input error');
            $objValidation->setError( get_string('import:datasheet:process:error:value', 'block_gradetracker') );
            $objValidation->setFormula1($possibleValuesString);
        
        }
        
        // Conditional Formatting
        
        // Met
        $objConditional = new \PHPExcel_Style_Conditional();
        $objConditional->setConditionType( \PHPExcel_Style_Conditional::CONDITION_EXPRESSION )
                       ->setOperatorType( \PHPExcel_Style_Conditional::OPERATOR_EQUAL );
        if ($conditionalValuesArray['met']){
            foreach($conditionalValuesArray['met'] as $met){
                $objConditional->addCondition($letter . $rowNum . '="'.$met.'"');
            }
        }
        $objConditional->getStyle()->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()->setARGB(\PHPExcel_Style_Color::COLOR_GREEN);
        
        // NOT Met
        $objConditional2 = new \PHPExcel_Style_Conditional();
        $objConditional2->setConditionType( \PHPExcel_Style_Conditional::CONDITION_EXPRESSION )
                       ->setOperatorType( \PHPExcel_Style_Conditional::OPERATOR_EQUAL );
        if ($conditionalValuesArray['no']){
            foreach($conditionalValuesArray['no'] as $no){
                $objConditional2->addCondition($letter . $rowNum . '="'.$no.'"');
            }
        }
        $objConditional2->getStyle()->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()->setARGB(\PHPExcel_Style_Color::COLOR_RED);
        $objConditional2->getStyle()->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_WHITE);
        
        
        // Append styles to sheet
        $conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$rowNum}")->getConditionalStyles();
        array_push($conditionalStyles, $objConditional); 
        array_push($conditionalStyles, $objConditional2); 
        $objPHPExcel->getActiveSheet()->getStyle("{$letter}{$rowNum}")->setConditionalStyles($conditionalStyles);
        
    }
    
    /**
     * Get the cell for the activity overview grid, showing which criteria have links and which don't
     * @param int $qualID This is called from the Qualification and Unit classes, not UserQualification and 
     * UserUnit, so we have no qualID loaded in yet. So use this one.
     * @return string
     */
    public function getActivityOverviewCell($qualID)
    {
        
        global $CFG;
        
        $output = "";
        
        // Check if this criterion is linked to any activities
        $links = $this->getActivityLinks($qualID);
        if ($links)
        {
            if (count($links) > 1)
            {
                $output .= "<img src='".$CFG->wwwroot."/blocks/gradetracker/pix/warning_round.png' />";
            }
            else
            {
                $output .= "<img src='".$CFG->wwwroot."/blocks/gradetracker/pix/tick_round.png' />";
            }
        }
        else
        {
            $output .= "<img src='".$CFG->wwwroot."/blocks/gradetracker/pix/cross_round.png' />";
        }        
        
        return $output;
        
    }
    
    /**
     * Get any popup content if this criterion has sub criteria/ranges/etc... that want to be opened in popup
     */
    public function getPopUpContent(){
        
    }
    
    public function getPopUpInfo(){
        
    }
    
    /**
     * Get the comments popup
     * @return string
     */
    public function getPopUpComments(){
        
        $output = "";

        $qualification = $this->getQualification();
        $unit = $this->getUnit();

        $output .= "<div class='gt_criterion_popup_comments'>";

        if ($this->student){
            $output .= "<br><span class='gt-popup-studname'>{$this->student->getDisplayName()}</span><br>";
        }

        if ($qualification){
            $output .= "<span class='gt-popup-qualname'>{$qualification->getDisplayName()}</span><br>";
        }

        if ($unit){
            $output .= "<span class='gt-popup-unitname'>{$unit->getDisplayName()}</span><br>";
        }

        $output .= "<span class='gt-popup-critname'>{$this->getName()}</span><br><br>";

        $output .= "<textarea class='gt_criterion_comments_textbox' qID='{$this->qualID}' uID='{$this->unitID}' cID='{$this->id}' sID='{$this->student->id}'>".\gt_html($this->userComments)."</textarea>";
            
        $output .= "<br><br>";
        
        $output .= "</div>";        
        
        return $output;
        
    }
    
    
    /**
     * Get an array of the types of criteria we support
     * @return type
     */
    public static function getSupportedTypes(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records_sql("SELECT *
                                         FROM {bcgt_qual_structure_levels}
                                         WHERE name LIKE '%Criteria'");
        
        if ($records)
        {
            foreach($records as $record)
            {
                $record->type = str_replace(" Criteria", "", $record->name);
                $return[$record->id] = $record;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Load the correct Criterion object for this criterion, based on its type
     * @global \GT\type $DB
     * @param type $id
     * @return boolean|\GT\Criteria\StandardCriterion
     */
    public static function load($id = false, $type = false, $forceTypeChange = false){
        
        global $DB;
        
        // Are we loading up a criterion from the database?
        if ($id)
        {
        
            // Check for it's type
            if (!$forceTypeChange && !$type){
                $check = $DB->get_record_sql("select c.id, c.type, sl.name
                                        from {bcgt_criteria} c
                                        inner join {bcgt_qual_structure_levels} sl on sl.id = c.type
                                        where c.id = ?", array($id));
                if ($check)
                {
                    $type = $check->type;
                }
            }
        
        }
        
        // Have we passed in a level id as the type?
        if (ctype_digit($type))
        {
            
            $check = $DB->get_record_sql("SELECT id, name FROM {bcgt_qual_structure_levels} WHERE id = ?", array($type));
            if ($check)
            {
                $type = str_replace(" Criteria", "", $check->name);
            }
            
        }
                
        
        // Switch the type to work out which object to use
        switch($type)
        {

            case 'Standard':
                return new \GT\Criteria\StandardCriterion($id);
            break;
        
            case 'Detail':
                return new \GT\Criteria\DetailCriterion($id);
            break;
        
            case 'Numeric':
                return new \GT\Criteria\NumericCriterion($id);
            break;
        
            case 'Ranged':
                return new \GT\Criteria\RangedCriterion($id);
            break;

        }
        
        return false;
        
    }
    
    
    /**
     * Count non-deleted criteria in system
     * @global \GT\type $DB
     * @return type
     */
    public static function countCriteria(){
        
        global $DB;
        
        $count = $DB->count_records("bcgt_criteria", array("deleted" => 0));
        return $count;
        
    }
    
}
