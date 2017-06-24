<?php
/**
 * GT\Unit
 *
 * This class handles the overall unit information.
 * 
 * This stores the general information about the unit and will be rarely instantiated itself
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

class Unit {
    
    protected $id = false;
    protected $structureID;
    protected $levelID;
    protected $gradingStructureID;
    protected $unitNumber;
    protected $name;
    protected $code;
    protected $credits;
    protected $description;
    protected $deleted = 0;
    
    protected $gradingStructure = false;
    protected $qualStructureID; // The structure ID of the qualification this unit has been loaded onto
    
    protected $criteria = false;
    protected $possibleAwards = false;
    
    protected $customFormElements; // These are the FormElement objects as loaded with a valid Qualification
    protected $errors = array();
        
    public $jsonResult = false;
    
    
    
    /**
     * Construct the object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false) {
        
        global $DB;
        
        $GTEXE = \GT\Execution::getInstance();
        
        if ($id){
            
            $record = $DB->get_record("bcgt_units", array("id" => $id));
            if ($record){
                
                $this->id = $record->id;
                $this->structureID = $record->structureid;
                $this->levelID = $record->levelid;
                $this->unitNumber = $record->unitnumber;
                $this->name = $record->name;
                $this->code = $record->code;
                $this->credits = $record->credits;
                $this->description = $record->description;
                $this->gradingStructureID = $record->gradingstructureid;
                $this->deleted = $record->deleted;
                
                // Load custom form elements
                if (!isset($GTEXE->UNIT_MIN_LOAD) || !$GTEXE->UNIT_MIN_LOAD){
                    $this->loadCustomFormElements();
                }
                
            }
            
        }
        
    }
    
    /**
     * Count the number of qualifications this unit is attached to
     * @return type
     */
    public function countQuals(){
       
        $quals= $this->getQualifications();
        return count($quals);
        
    }
    
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
    }
    
    public function getStructureID(){
        return $this->structureID;
    }
    
    public function getStructure(){
        
        if (isset($this->structure)) return $this->structure;
        
        $this->structure = new \GT\QualificationStructure($this->structureID);
        return $this->structure;
        
    }
    
    public function setStructureID($id){
        $this->structureID = $id;
        return $this;
    }
    
    public function getLevelID(){
        return $this->levelID;
    }
    
    public function getLevel(){
        
        if (isset($this->level)) return $this->level;
        
        $this->level = new \GT\Level($this->levelID);
        return $this->level;
        
    }
    
    public function setLevelID($id){
        $this->levelID = $id;
        return $this;
    }
    
    public function getUnitNumber(){
        return $this->unitNumber;
    }
    
    public function setUnitNumber($number){
        $this->unitNumber = trim($number);
        return $this;
    }
    
    public function getName(){
        return \gt_html($this->name);
    }
    
    public function getDisplayName(){
        
        if (strlen($this->unitNumber)){
            return $this->getUnitNumber() . ": " . $this->getName();
        } else {
            return $this->getName();
        }
        
    }
    
    public function getShortenedDisplayName(){
        $name = $this->getDisplayName();
        return wordwrap($name, 75, '<br>');
    }
    
    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function getCode(){
        return $this->code;
    }
    
    public function setCode($code){
        $this->code = trim($code);
        return $this;
    }
    
    public function getCredits(){
        return $this->credits;
    }
    
    public function setCredits($credits){
        $this->credits = $credits;
        return $this;
    }
    
    public function getDescription(){
        return $this->description;
    }
    
    public function setDescription($desc){
        $this->description = trim($desc);
        return $this;
    }
    
    public function getGradingStructure(){
        
        if ($this->gradingStructure === false){
            $this->gradingStructure = new \GT\UnitAwardStructure($this->getGradingStructureID());
        }
        
        return $this->gradingStructure;
        
    }
    
    public function getGradingStructureID(){
        return $this->gradingStructureID;
    }
    
    public function setGradingStructureID($id){
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
    
    public function getFullName(){
        return $this->getStructure()->getDisplayName() . " " . $this->getLevel()->getName() . " " . $this->getDisplayName();
    }
    
    /**
     * I am not sure why I have this variable twice in 2 different methods
     * @param type $id
     * @return \GT\Unit
     */
    public function getQualStructureID(){
        return $this->qualStructureID;
    }
    
    /**
     * I am not sure why I have this variable twice in 2 different methods
     * @param type $id
     * @return \GT\Unit
     */
    public function setQualStructureID($id){
        $this->qualStructureID = $id;
        return $this;
    }
    
    
    
    /**
     * Get the name of the unit to be used in select <options>
     * @return type
     */
    public function getOptionName(){
        
        $output = "";
        $output .= "({$this->getLevel()->getShortName()})";
        
        if (strlen($this->code) > 0){
            $output .= " {$this->code}";
        }
        
        $output .= " - ";
        
        if ($this->unitNumber > 0){
            $output .= $this->unitNumber . ": ";
        }
        
        $output .= $this->name;
        return $output;
        
    }    
    
    /**
     * Get the criteria on this unit
     * @return type
     */
    public function getCriteria(){
        
        if ($this->criteria === false){
            $this->loadCriteria();
        }
        
        return $this->criteria;
        
    }
    
    /**
     * Load the unit's criteria from the database
     * @global \GT\type $DB
     */
    protected function loadCriteria($parentID = null, &$obj = false){
        
        global $DB;
                
        $GTEXE = \GT\Execution::getInstance();
        
        if ($this->criteria === false){
            $this->criteria = array();
        }
        
        $criteria = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "parentcritid" => $parentID, "deleted" => 0));
        if ($criteria)
        {
            
            foreach($criteria as $criterion)
            {
                
                $critObj = \GT\Criterion::load($criterion->id);
                if ($critObj && $critObj->isValid())
                {
                                        
                    // Set the qualID if we have it
                    if (isset($this->qualID)){
                        $critObj->setQualID($this->qualID);
                    }
                    
                    // Set the qual structure id if we have it
                    if (isset($this->qualStructureID)){
                        $critObj->setQualStructureID($this->qualStructureID);
                    }
                    
                    // Check for children
                    $this->loadCriteria($critObj->getID(), $critObj);
                    if ($obj)
                    {
                        $obj->addChild($critObj);
                    }
                    else
                    {
                        $this->criteria[$criterion->id] = $critObj;
                    }
                                        
                }
                
            }
            
        }       
                
        
        // Order them
        if (is_null($parentID) && !$obj)
        {
            if (!isset($GTEXE->CRIT_NO_SORT) || !$GTEXE->CRIT_NO_SORT){
                $this->criteria = $this->sortCriteria(false, false);
            }
        }
        
        
                        
    }
    
    
    /**
     * Load the multidimensional array of criteria into a flat array
     * @param type $criteria
     * @param type $array
     * @return type
     */
    public function loadCriteriaIntoFlatArray($criteria = false, $forceLoadAll = false, &$array = false )
    {
                                           
        if ($criteria && $array)
        {
            
            if (is_null($criteria->getSubCritType()) || $forceLoadAll)
            {
                $key = ($criteria->isValid()) ? $criteria->getID() : -$criteria->getDynamicNumber();
                $array[$key] = $criteria;
                if ($criteria->getChildren())
                {
                    foreach($criteria->getChildren() as $sub)
                    {
                        $this->loadCriteriaIntoFlatArray($sub, $forceLoadAll, $array);
                    }
                }
            }
            return;
        }
        
        $return = array();
        
        // If we haven't done anything with it yet
        if ($this->criteria === false)
        {
            $this->loadCriteria();
        }
        
        
        if ($this->criteria)
        {
                        
            foreach($this->criteria as $criterion)
            {
                                
                if (is_null($criterion->getSubCritType()) || $forceLoadAll)
                {
                    $key = ($criterion->isValid()) ? $criterion->getID() : -$criterion->getDynamicNumber();
                    $return[$key] = $criterion;
                    if ($criterion->getChildren())
                    {
                        foreach($criterion->getChildren() as $sub)
                        {
                            $this->loadCriteriaIntoFlatArray($sub, $forceLoadAll, $return);
                        }
                    }
                }
                
            }
            
        }
        
        return $return;
        
    }
        
    /**
     * Count how many criteria on this unit start with a certain letter
     * @param type $letter
     * @param type $criteria
     * @return int
     */
    public function countCriteriaByLetter($letter, $criteria = false){
        
        $count = 0;
        
        if (!$criteria){
            $criteria = $this->loadCriteriaIntoFlatArray();
        }
        
        if ($criteria){
            foreach($criteria as $crit){
                if (strpos($crit->getName(), $letter) === 0){
                    $count++;
                }
            }
        }
        
        return $count;
        
    }
    
    /**
     * Given an array of values from the unit form, load them into actual criterion objects
     * @param type $criteria
     */
    public function setCriteriaPostData($criteria){
        
        $this->criteria = array();
                                
        // In case we have put a child criteria before its parent in the form, order by parents, so that
        // we can more easily add children to parent objects
        if ($criteria){
            uasort($criteria, function($a, $b){
                if ($a['parent'] == $b['parent']){
                    return strnatcasecmp($a['name'], $b['name']);
                } else {
                    return ($a['parent'] > $b['parent']);
                }
            });
        }
                                
        if ($criteria){
            
            foreach($criteria as $num => $criterion){
                
                $critObj = \GT\Criterion::load(false, $criterion['type']);
                
                if ($critObj){
                
                    if (isset($criterion['id'])){
                        $critObj->setID($criterion['id']);
                    }

                    $critObj->setQualStructureID( $this->structureID );
                    $critObj->setName($criterion['name']);
                    $critObj->setType($criterion['type']);
                    $critObj->setDescription($criterion['details']);
                    $critObj->setDynamicNumber($num);

                    if (ctype_digit($criterion['parent']) && $criterion['parent'] > 0){
                        $critObj->setParentNumber((int)$criterion['parent']);
                    }

                    $critObj->setGradingStructureID( @$criterion['grading'] );
                    $critObj->setAttribute("weighting", $criterion['weight']);
                    $critObj->setAttribute("gradingtype", $criterion['gradingtype']);
                    
                    // Additional options
                    if (isset($criterion['options']) && $criterion['options']){
                        foreach($criterion['options'] as $opt => $val){
                            $critObj->setAttribute($opt, $val);
                        }
                    }
                    
                    // Load anything extra as required by this specific type of criterion
                    $critObj->loadExtraPostData($criterion);                   
                    
                    // If it has a parent, add as child to that object
                    if (ctype_digit($criterion['parent']) && $criterion['parent'] > 0){
                        $parent = $this->findCriterionByDynamicNumber($criterion['parent']);
                        $parent->addChild($critObj, true);
                    } else {
                        $this->criteria[$num] = $critObj;
                    }
                
                }
                
            }
            
        }       
                
    }


    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Get the array of FormElement objects
     * @return type
     */
    public function getCustomFormElements(){
        return $this->customFormElements;
    }
    
    /**
     * Get the value of a specific FormElement loaded into the object
     * @param type $name
     * @return type
     */
    public function getCustomFormElementValue($name){
        
        $element = $this->getCustomFormElementByName($name);
        return ($element) ? $element->getValue() : false;
        
    }
    
    /**
     * Get a specific element from the loaded elements, by its name
     * @param type $name
     * @return boolean
     */
    public function getCustomFormElementByName($name){
        
        if ($this->customFormElements){
            
            foreach($this->customFormElements as $element){
                
                if ($element->getName() == $name){
                    return $element;
                }
                
            }
            
        }
        
        return false;
        
    }
    
   
    
    /**
     * Load the custom form elements into the qualification, with any values as well
     */
    public function loadCustomFormElements(){
                
        // Get the possible elements for the qualification form
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        $elements = $structure->getCustomFormElements('unit');
        
        // Get the saved
        if ($this->isValid())
        {
            if ($elements)
            {
                foreach($elements as $element)
                {
                    $value = $this->getAttribute("custom_{$element->getID()}");
                    $element->setValue($value);
                }
            }
        }
        
        $this->customFormElements = $elements;
                        
    }
    
    /**
     * Take the values from the Qualification form and load them into the FormElement objects
     * @param type $array
     * @return \GT\Qualification
     */
    public function setCustomElementValues($array){
        
        // Reset saved values on all elements
        if ($this->customFormElements)
        {
            foreach($this->customFormElements as $element)
            {
                $element->setValue(null);
            }
        }
        
        // Now load in the ones we have submitted
        if ($array)
        {
            
            foreach($array as $name => $value)
            {
                
                $element = $this->getCustomFormElementByName($name);
                if ($element)
                {
                    $element->setValue($value);
                }
                
            }
            
        }
                
        return $this;
        
    }
    
    /**
     * Loop through all the criteria and try to find a specific one by its dynamic number from the form
     * @param type $num
     * @param type $criteria
     * @return boolean
     */
    protected function findCriterionByDynamicNumber($num, &$criteria = false){
        
        if ($criteria)
        {
            
            foreach($criteria as $criterion)
            {
                
                // If this is the one, return it
                if ($criterion->getDynamicNumber() == $num)
                {
                    return $criterion;
                }
                
                // If it has children
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $result = $this->findCriterionByDynamicNumber($num, $children);
                    if ($result)
                    {
                        return $result;
                    }
                }
                
            }
            
        }
        
        
        elseif ($this->criteria)
        {
            
            foreach($this->criteria as $criterion)
            {
                
                // If this is the one, return it
                if ($criterion->getDynamicNumber() == $num)
                {
                    return $criterion;
                }
                
                // If it has children
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $result = $this->findCriterionByDynamicNumber($num, $children);
                    if ($result)
                    {
                        return $result;
                    }
                }
                
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Get a criterion from this unit by its id
     * @param int $critID
     * @return boolean
     */
    public function getCriterion($critID){
        
        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);        
        return ($flatArray && array_key_exists($critID, $flatArray)) ? $flatArray[$critID] : false;
        
    }
    
    /**
     * Get a criterion from this unit by its name
     * @param type $name
     * @return boolean
     */
    public function getCriterionByName($name, $forceLoadAll = true){
        
        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);
        if ($flatArray)
        {
            foreach($flatArray as $crit)
            {
                if ($crit->getName() == $name)
                {
                    return $crit;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get the possible awards for this unit grading structure
     * @return type
     */
    public function getPossibleAwards(){
                
        if (!$this->possibleAwards){
            $this->loadPossibleAwards();
        }
        
        return $this->possibleAwards;
        
    }
    
    /**
     * Load the possible awards for this unit grading structure
     */
    protected function loadPossibleAwards(){
        
        $this->possibleAwards = array();
        
        $structure = new \GT\UnitAwardStructure($this->gradingStructureID);
        if ($structure->isValid())
        {
            $awards = $structure->getAwards();
            if ($awards)
            {
                foreach($awards as $award)
                {
                    $this->possibleAwards[$award->getID()] = $award;
                }
            }
        }
        
    }
    
    
    /**
     * Gets a distinct list of all the possible criteria values for this unit to put into the key
     * @return array
     */
    public function getAllPossibleValues(){
        
        $values = array();
                    
        $criteria = $this->loadCriteriaIntoFlatArray();

        if ($criteria){

            foreach($criteria as $criterion){

                $possibleValues = $criterion->getPossibleValues();
                if ($possibleValues){

                    foreach($possibleValues as $value){

                        $values[$value->getShortName().':'.$value->getName()] = $value;

                    }

                }

            }

        }
                               
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteriaValues($values);
        
        return $values;
        
    }
    
    /**
     * Get a list of qualifications this unit is assigned to
     * @global \GT\type $DB
     * @return \GT\Qualification
     */
    public function getQualifications(){
        
        global $DB;
        
        $return = array();
        
        $results = $DB->get_records("bcgt_qual_units", array("unitid" => $this->id));
        if ($results)
        {
            foreach($results as $result)
            {
                $obj = new \GT\Qualification($result->qualid);
                if ($obj->isValid() && !$obj->isDeleted())
                {
                    $return[] = $obj;
                }
            }
        }
        
        // Order them
        $Sort = new \GT\Sorter();
        $Sort->sortQualifications($return);
        
        return $return;
        
    }
    
    /**
     * Get a unit attribute
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $userID
     * @return type
     */
    public function getAttribute($attribute, $userID = null, $qualID = null){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));
        return ($check) ? $check->value : false;
        
    }
    
    /**
     * Get all the attributes for this unit
     * @global \GT\type $DB
     * @return type
     */
    public function getUnitAttributes(){
        
        global $DB;
        
        $check = $DB->get_records("bcgt_unit_attributes", array('unitid' => $this->id));
        return $check;
        
    }
    
    /**
     * Update a unit attribute
     * @global type $DB
     * @param type $setting
     * @param type $value
     * @param type $userID
     */
    public function updateAttribute($attribute, $value, $userID = null, $qualID = null){
        
        global $DB;
        
        // If value is null, table doesn't support null values, so just delete it
        if (is_null($value))
        {
            return $this->deleteAttribute($attribute, $userID, $qualID);
        }
        
        $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));
        
        
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
            $result = $DB->update_record("bcgt_unit_attributes", $check);
        }
        else
        {
            $ins = new \stdClass();
            $ins->unitid = $this->id;
            $ins->userid = $userID;
            $ins->qualid = $qualID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $ins->lastupdate = time();
            $result = $DB->insert_record("bcgt_unit_attributes", $ins);
        }
        
        // If it was a user attribute, log it
        if (!is_null($userID)){
            
            // ----------- Log the action
            $Log->afterjson = array(
                $attribute => $value
            ); 

            $Log->attributes = array(
                    \GT\Log::GT_LOG_ATT_QUALID => $qualID,
                    \GT\Log::GT_LOG_ATT_UNITID => $this->id,
                    \GT\Log::GT_LOG_ATT_STUDID => $userID
                );

            $Log->save();
            // ----------- Log the action
            
        }
        
        return $result;
        
    }
    
    /**
     * Delete a unit attribute
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $userID
     * @param type $qualID
     * @return type
     */
    public function deleteAttribute($attribute, $userID = null, $qualID = null){
        
        global $DB;
        
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;
        
        $check = $this->getAttribute($attribute, $userID, $qualID);
        $Log->beforejson = array(
            $attribute => ($check) ? $check : null
        );
        
        $result = $DB->delete_records("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));
        
        if (!is_null($userID)){
            
            // ----------- Log the action
            $Log->attributes = array(
                    \GT\Log::GT_LOG_ATT_QUALID => $qualID,
                    \GT\Log::GT_LOG_ATT_UNITID => $this->id,
                    \GT\Log::GT_LOG_ATT_STUDID => $userID
                );
            
            $Log->afterjson = array(
                $attribute => null
            );

            $Log->save();
            // ----------- Log the action
            
            
        }
        
        return $result;
        
    }
    
    /**
     * Check if this unit has any criteria of a given type
     * The type is an id refering to the bcgt_qual_structure_levels table
     * @global \GT\type $DB
     * @param type $typeID
     * @return type
     */
    public function hasAnyCriteriaOfType($typeID)
    {
        
        global $DB;
        
        $check = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "type" => $typeID));
        return ($check);
        
    }
    
    /**
     * Check if there are any activity links on this unit, on the specified qualID
     * @param type $qualID
     * @return boolean
     */
    public function hasActivityLinks($qualID = false)
    {
        
        $records = $this->getActivityLinks($qualID);
        return ($records && count($records) > 0);
        
    }
    
    /**
     * Check if this unit has any activity links
     * @global \GT\type $DB
     * @return type
     */
    public function getActivityLinks($qualID = false){
        
        global $DB;
        
        // If we do this from UserUnit, will already have a qualID loaded in
        if ($this->qualID){
            $qualID = $this->qualID;
        }
        
        if (!$qualID) return false;
        
        $return = array();        
        $records = $DB->get_records("bcgt_activity_refs", array("qualid" => $qualID, "unitid" => $this->id, "deleted" => 0));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = \GT\ModuleLink::getModuleLinkFromCourseModule($record->cmid);
                if ($obj)
                {
                    $obj->criteria = $obj->getCriteriaOnModule($qualID, $this, false);
                    $return[$record->cmid] = $obj;
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get the criteria not linked to any activities on this unit
     * @param type $activities
     * @return type
     */
    public function getCriteriaNotLinkedToActivities($activities = false)
    {
        
        // Get the activities if they aren't passed through
        if (!$activities){
            $activities = $this->getActivityLinks();
        }
        
        $linkedCriteria = array();
        $unitCriteria = array();
        
        // Get an array of the criteria IDs linked to these activities
        if ($activities)
        {
            foreach($activities as $activity)
            {
                if ($activity->criteria)
                {
                    foreach($activity->criteria as $crit)
                    {
                        $linkedCriteria[] = $crit->getID();
                    }
                }
            }
        }
        
        // Get an array of the criteria IDs on the unit
        $criteriaNames = $this->getHeaderCriteriaNamesFlat();
        if ($criteriaNames)
        {
            foreach($criteriaNames as $crit)
            {
                $criterion = $this->getCriterionByName($crit);
                if ($criterion)
                {
                    $unitCriteria[] = $criterion->getID();
                }
            }
        }
        
        $results = array_diff( $unitCriteria, $linkedCriteria );
        $return = array();
        if ($results)
        {
            foreach($results as $critID)
            {
                $return[$critID] = $this->getCriterion($critID);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Check there are no errors with anything submitted
     * @global \GT\type $DB
     * @return type
     */
    public function hasNoErrors(){
        
        global $DB;
        
        $Structure = new \GT\QualificationStructure( $this->structureID );
        
        // Check if level is set
        if (strlen($this->levelID) == 0 || $this->levelID <= 0 || is_null($this->levelID)){
            $this->errors[] = get_string('errors:unit:level', 'block_gradetracker');
        }     
        
        // Check we can have this structure & level
        // It's an elseif because if level isn't set then this obviously won't be correct
        elseif (!\GT\QualificationBuild::exists($this->structureID, $this->levelID)){
            $this->errors[] = get_string('errors:unit:build', 'block_gradetracker');
        }
        
        // Check name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:unit:name', 'block_gradetracker');
        }
        
        // Check for duplicate build, name, number and code combination combination
        $check = $DB->get_records("bcgt_units", array("name" => $this->name, "unitnumber" => $this->unitNumber, "code" => $this->code, "structureid" => $this->structureID, "levelid" => $this->levelID, "deleted" => 0));
        if (isset($check[$this->id])){
            unset($check[$this->id]);
            $check = reset($check);
        }
        if ($check && $check->id <> $this->id){
            $this->errors[] = get_string('errors:unit:name:duplicate', 'block_gradetracker');
        }
        
        // Check for grading structure
        $gradingStructure = new \GT\UnitAwardStructure($this->gradingStructureID);
        if (!$gradingStructure->isValid() || !$gradingStructure->isEnabled() || $gradingStructure->getQualStructureID() <> $Structure->getID()){
            $this->errors[] = get_string('errors:unit:grading', 'block_gradetracker');
        }
        
        // Check custom elements
        $elements = $Structure->getCustomFormElements('unit');
        if ($elements){
            
            foreach($elements as $element){
                
                // Is it required?
                if ($element->hasValidation("REQUIRED")){
                    
                    $value = $this->getCustomFormElementValue($element->getName());
                    if (strlen($value) == 0 || $value === false){
                        $this->errors[] = sprintf( get_string('errors:unit:custom', 'block_gradetracker'), $element->getName() );
                    }
                    
                }
                
            }
            
        }
        
        $critieraNames = array();
        
        // Check criteria
        if ($this->criteria){
            
            foreach($this->criteria as $criterion){
                
                if (!array_key_exists($criterion->getName(), $critieraNames)){
                    $critieraNames[$criterion->getName()] = 0;
                }
                
                $critieraNames[$criterion->getName()]++;
                
                if (!$criterion->hasNoErrors()){
                    
                    foreach($criterion->getErrors() as $error){
                        
                        $this->errors[] = $error;
                        
                    }
                    
                }
                
            }
            
        }
        
        // Make sure we have no duplicate criteria names at top level
        foreach($critieraNames as $name => $cnt){
            if ($cnt > 1){
                $this->errors[] = sprintf( get_string('errors:crit:duplicatenames', 'block_gradetracker'), $name );
            }
        }
                
        return (!$this->errors);
        
    }
    
    /**Delete unit sets the deleted attribute to 1*/
    public function delete(){
        
        global $DB;
        
        $this->deleted = 1;
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;
        
        return $DB->update_record("bcgt_units", $obj);
    }
    
    /**Restore unit sets the deleted attribute to 0*/
    public function restore(){
        
        global $DB;
        
        $this->deleted = 0;
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;
        
        return $DB->update_record("bcgt_units", $obj);
    }
    
    public function copyUnit(){
        
        global $DB;
        
        // create new unit object
        $newunit = new \GT\Unit();
        $newunit->setStructureID($this->structureID);
        $newunit->setLevelID($this->levelID);
        $newunit->setGradingStructureID($this->gradingStructureID);
        $newunit->setUnitNumber($this->unitNumber);
        $newunit->setName($this->name." (copy)");
        $newunit->setCode($this->code);
        $newunit->setCredits($this->credits);
        $newunit->setDescription($this->description);

        
        // get criteria for unit passed through
        $this->loadCriteria();
        foreach ($this->criteria as $c){
            $name = $c->getName();
                $criteriaattributes[$name] = array();
                    $catts = $DB->get_records('bcgt_criteria_attributes', array('critid' => $c->getID()));
                        if($catts){
                            $criteriaattributes[$name] = $catts;
                        }
            // prepare criteria for new unit
            $c->setID(false);
            $newunit->addCriterion($c);
        }
        
       $newunit->save();
       
        // now that we have a new unit with an ID.
        // get and update attributes for copied unit.
        $atts = $this->getUnitAttributes();
        foreach($atts as $a){
            $newunit->updateAttribute($a->attribute, $a->value);
        }
        
        //get and update criteria attributes for copied unit
        foreach ($newunit->getCriteria() as $gc)
        {
            $name = $gc->getName();
            $atts = $criteriaattributes[$name];
            foreach ($atts as $a){
                $gc->updateAttribute($a->attribute, $a->value);
            }
        }
        
        // redirect to edit page for newly copied unit
        header('location:/blocks/gradetracker/config.php?view=units&section=edit&id='.$newunit->getID());
    }
    
    public function addCriterion($criteria){
        $this->criteria[] = $criteria;
    }
    /**
     * Save the unit to the DB
     * @global \GT\type $DB
     * @return boolean
     */
    public function save(){
       
        global $DB;
        
        $obj = new \stdClass();
        
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->structureid = $this->structureID;
        $obj->levelid = $this->levelID;
        $obj->unitnumber = $this->unitNumber;
        $obj->name = $this->name;
        $obj->code = $this->code;
        $obj->credits = $this->credits;
        $obj->description = $this->description;
        $obj->gradingstructureid = $this->gradingStructureID;
        $obj->deleted = $this->deleted;
                
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_units", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_units", $obj);
            $result = $this->id;
        }
        
        if (!$result){
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
        
                
        // Criteria
        $this->saveCriteria();
        
        
        // Order them properly again
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteria($this->criteria);
                
        
        // Delete any we removed
        $this->deleteRemovedCriteria();
                
        
        // Custom Form Elements
        // Clear any set previously
        $DB->delete_records("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => null));
        
        // Save new ones
        if ($this->customFormElements){
            
            foreach($this->customFormElements as $element){
                
                $this->updateAttribute("custom_{$element->getID()}", $element->getValue());
                
            }
            
        }
        
        
        // Unit Quals
        
        
        return true;
        
    }    
    
    /**
     * Save the criteria on the unit
     * @param type $criteria
     * @param type $parent
     */
    protected function saveCriteria($criteria = false, $parent = false)
    {
        
        if ($criteria && $parent)
        {
            
            foreach($criteria as $criterion)
            {
                
                $criterion->setUnitID($this->id);
                $criterion->setParentID($parent->getID());
                $criterion->save();
                
                // Does it have children?
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $this->saveCriteria($children, $criterion);
                }
                
            }
            
        }
        
        
        elseif ($this->criteria)
        {
            foreach($this->criteria as $criterion)
            {
                
                $criterion->setUnitID($this->id);
                $criterion->save();
                
                // Does it have children?
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $this->saveCriteria($children, $criterion);
                }
                
                // Does it have any point links?
                if (isset($criterion->pointLinks))
                {
                    $criterion->savePointLinks();
                }
                
            }
        }
        
    }
    
    /**
     * Delete any criteria we removed from the Unit creation form
     * @global \GT\type $DB
     */
    private function deleteRemovedCriteria(){
        
        global $DB;
        
        $oldIDs = array();
        $currentIDs = array();
        
        // Get the ones in the database
        $old = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "deleted" => 0));
        if ($old)
        {
            foreach($old as $o)
            {
                $oldIDs[] = $o->id;
            }
        }
        
        // Get the ones loaded into the object
        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);
                
        if ($flatArray)
        {
            foreach($flatArray as $flat)
            {
                $currentIDs[] = $flat->getID();
            }
        }
                
        // Get the ones that don't exist in both arrays
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $obj = new \stdClass();
                $obj->id = $removeID;
                $obj->deleted = 1;
                $DB->update_record("bcgt_criteria", $obj);
            }
        }
        
    }
    
    /**
     * Get flat array of criteria names for headers
     * @param type $view
     * @param type $activities
     */
    public function getHeaderCriteriaNamesFlat($view = false, $activities = false){
        
        // Get the names
        $criteria = $this->getHeaderCriteriaNames();
        
        // Get a flat array of criteria names, which may contain multiple copies if activity grid and if criteiron
        // is on more than 1 activity
        $criteriaArray = array();
        if ($view == 'activities')
        {
         
            // Criteria linked to activities
            if ($activities)
            {
                foreach($activities as $activity)
                {
                    if ($activity->criteria)
                    {
                        foreach($activity->criteria as $crit)
                        {
                            $criteriaArray[] = $crit->getName();
                        }
                    }
                }
            }
            
            // Then non-linked ones
            $nonLinkedCriteria = ($view == 'activities') ? $this->getCriteriaNotLinkedToActivities( $activities ) : false;
            if ($nonLinkedCriteria)
            {
                foreach($nonLinkedCriteria as $crit)
                {
                    $criteriaArray[] = $crit->getName();
                }
            }
            
        }
        else
        {
                        
            if ($criteria)
            {
                foreach($criteria as $crit)
                {
                    $criteriaArray[] = $crit['name'];
                    if ($crit['sub'])
                    {
                        foreach($crit['sub'] as $sub)
                        {
                            $criteriaArray[] = $sub;
                        }
                    }
                }
            }
        }
        
        return $criteriaArray;
        
    }
    
    
    /**
     * Get a distinct list of all the top-level criteria names to display in the header, based on their
     * types and their sub levels
     * @return type
     */
    public function getHeaderCriteriaNames(){
        
        $names = array();
                   
        $criteria = $this->getCriteria();

        if ($criteria){

            foreach($criteria as $criterion){

                // If this isn't a;ready in the array, add it
                if (!array_key_exists($criterion->getName(), $names)){
                    $names[$criterion->getName()] = array("name" => $criterion->getName(), "sub" => array());
                } 

                // If this has child levels, we might want some of them in the header as well
                if ($criterion->countChildLevels() > 0) {

                    switch( get_class($criterion) )
                    {

                        // Standard criterion
                        case 'GT\Criteria\StandardCriterion':
                            
                             // If only 1 level of sub criteria, add them in
                            // Though if this top level criterion has the setting "force popup" don't show the sub criteria in the grid table
                            if ($criterion->getAttribute('forcepopup') != 1)
                            {

                                // If only 1 level of sub criteria, add them in
                                foreach($criterion->getChildren() as $child){

                                    if (!in_array($child->getName(), $names[$criterion->getName()]['sub'])){
                                        $names[$criterion->getName()]['sub'][] = $child->getName();
                                    }

                                }
                            
                            }

                        break;


                        // Detail criterion - Only top level go in the header
                        case 'GT\Criteria\DetailCriterion':

                        break;


                        // Numeric criterion - Only top level go in the header
                        case 'GT\Criteria\NumericCriterion':

                        break;


                        // Ranged criterion - Only top level go in the header
                        case 'GT\Criteria\RangedCriterion':

                        break;

                    }

                }

            }

        }
        

        // Sort them
        $names = $this->sortCriteria($names);
        return $names;
               
    }
    
    /**
     * Get the content to display in the info popup for the unit
     * @return string
     */
    public function getPopUpInfo(){
                
        $output = "";
        
        $output .= "<div class='gt_criterion_popup_info'>";
        $output .= "<span class='gt-popup-studname'>{$this->getDisplayName()}</span><br><br>";
        $output .= "<p><i>{$this->getDescription()}</i></p>";
        
        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('criteria', 'block_gradetracker')."</div>";
        
        $output .= "<table class='gt_unit_popup_criteria_table'>";
            $output .= "<tr><th>".get_string('name', 'block_gradetracker')."</th><th>".get_string('description', 'block_gradetracker')."</th></tr>";
            
            if ($this->getCriteria())
            {
                foreach($this->getCriteria() as $criterion)
                {
                    
                    $output .= "<tr><td>{$criterion->getName()}</td><td>{$criterion->getDescription()}</td></tr>";
                    
                    // Check if the criterion has any sub criteria that are of type Range
                    if ($criterion->hasChildrenOfType("Range"))
                    {
                        
                        // Get ranged
                        $ranged = $criterion->getChildOfSubCritType("Range");
                        $subCriteria = $criterion->getChildOfSubCritType("Criterion");
                        
                        // Numeric Criterion will have Ranged sub criteria and Criterion sub criteria on the main parent
                        if ($ranged && $subCriteria)
                        {
                            
                            $output .= "<tr>";
                                $output .= "<td colspan='2'>";
                                
                                    $output .= "<table class='gt_unit_info_range_table'>";
                                    
                                        $output .= "<tr>";
                                            $output .= "<th></th>";
                                            foreach($ranged as $range)
                                            {
                                                $output .= "<th>{$range->getName()}</th>";
                                            }
                                        $output .= "</tr>"; 
                                        
                                        // Numeric criterion will have sub criteria of type Criterion on the main parent
                                        foreach($subCriteria as $subCriterion)
                                        {

                                            $output .= "<tr>";
                                                $output .= "<th>{$subCriterion->getName()}</th>";
                                                foreach($ranged as $range)
                                                {
                                                    $maxPoints = $criterion->getAttribute("maxpoints_{$subCriterion->getID()}_{$range->getID()}");
                                                    $output .= "<td>";
                                                        if ($maxPoints > 0)
                                                        {
                                                            for($i = 1; $i <= $maxPoints; $i++)
                                                            {
                                                                $output .= '&nbsp;&nbsp;&nbsp;'.$i.'&nbsp;&nbsp;&nbsp;';
                                                            }
                                                        }
                                                        else
                                                        {
                                                            $output .= '&nbsp;&nbsp;&nbsp;' . $maxPoints;
                                                        }
                                                    $output .= "</td>";
                                                }
                                            $output .= "</tr>"; 

                                        }
                                        
                                    $output .= "</table>";
                                
                                $output .= "</td>";
                            $output .= "</tr>";
                            
                        }
                        
                        // Ranged Criterion have the Ranged sub criteria, then the Criterion sub criteria are on each Range
                        elseif ($ranged)
                        {
                            foreach($ranged as $range)
                            {
                                $output .= "<tr>";
                                    $output .= "<td style='padding-left:10px';>{$range->getName()}</td>";
                                    $output .= "<td style='padding-left:10px';>{$range->getDescription()}</td>";
                                $output .= "</tr>"; 
                           }

                        }
                        
                    }
                    else
                    {
                        
                        // Sub Criteria level 1
                        if ($criterion->getChildren())
                        {
                            foreach($criterion->getChildren() as $child)
                            {

                                $output .= "<tr><td style='padding-left:10px';>{$child->getName()}</td><td style='padding-left:10px';>{$child->getDescription()}</td></tr>";

                                // Sub Criteria level 2
                                if ($child->getChildren())
                                {
                                    foreach($child->getChildren() as $subChild)
                                    {

                                        $output .= "<tr><td style='padding-left:20px';>{$subChild->getName()}</td><td style='padding-left:20px';>{$subChild->getDescription()}</td></tr>";

                                    }
                                }

                            }
                        }
                        
                    }
                                       
                }
            }
            
        $output .= "</table>";
        
        $output .= "</div>";

        return $output;
        
    }
    
    
    /**
     * Sort the criteria on the unit
     * @param type $criteria
     * @return type
     */
    public function sortCriteria($criteria = false, $all = false, $forceObjs = false){
        
        // If we are passing them through, they will be from the header, so just an array of names, not objects
        if ($criteria && !$forceObjs){
            $objs = false;
        } elseif ($criteria && $forceObjs){ 
            $objs = true;
        } elseif (!$criteria) {
            $criteria = ($all) ? $this->loadCriteriaIntoFlatArray() : $this->getCriteria();
            $objs = true;
        }
                
        $Sorter = new \GT\Sorter();
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        $customOrder = $structure->getCustomOrder('criteria');
        if ($customOrder){
            $Sorter->sortCriteriaCustom($criteria, $customOrder, $objs, true);
        } else {
            $Sorter->sortCriteria($criteria, $objs, true);
        }
                
        return $criteria;
        
    }
    
    
    /**
     * Search for units
     * @global \GT\type $DB
     * @param type $params
     * @return \GT\Unit
     */
    public static function search($params)
    {
    
        global $DB;
        
        $return = array();
        $sqlParams = array();
        $sql = "SELECT *
                FROM {bcgt_units}
                WHERE deleted = ? ";
        
        //is this deleted?
        $sqlParams[] = (isset($params['deleted'])) ? $params['deleted'] : 0;
        
        // Are we searching for units of a specific qual structure id?
        if (isset($params['structureID']) && $params['structureID'] > 0){
            $sql .= "AND structureid = ? ";
            $sqlParams[] = $params['structureID'];
        }
        
        // Are we searching for a particular level of unit?
        if (isset($params['levelID']) && $params['levelID'] > 0){
            $sql .= "AND levelid = ? ";
            $sqlParams[] = $params['levelID'];
        }
        
        // Are we searching for a particular unit number?
        if (isset($params['unitNumber']) && !\gt_is_empty($params['unitNumber'])){
            $sql .= "AND unitNumber LIKE ? ";
            $sqlParams[] = '%'.trim($params['unitNumber']).'%';
        }
        
        if (isset($params['nameORcode']) && !\gt_is_empty($params['nameORcode'])){
            $sql .= "AND (name LIKE ? OR unitNumber LIKE ? OR code LIKE ?) ";
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
        } else {
        
            if (isset($params['name']) && !\gt_is_empty($params['name'])){
                $sql .= "AND (name LIKE ? OR unitNumber LIKE ?) ";
                $sqlParams[] = '%'.trim($params['name']).'%';
                $sqlParams[] = '%'.trim($params['name']).'%';
            }

            if (isset($params['code']) && !\gt_is_empty($params['code'])){
                $sql .= "AND (code LIKE ?) ";
                $sqlParams[] = '%'.trim($params['code']).'%';
                $sqlParams[] = '%'.trim($params['code']).'%';
            }
        
        }
                
        $results = $DB->get_records_sql($sql, $sqlParams);
        if ($results)
        {
            foreach($results as $result)
            {
                $unit = new \GT\Unit($result->id);
                if ($unit->isValid())
                {
                    $return[] = $unit;
                }
            }
        }
        
        if (!isset($params['sort']) || $params['sort'] == true){
            $Sorter = new \GT\Sorter();
            $Sorter->sortUnitsByLevel($return);
        }
        
        return $return;
        
    }
    
    /**
     * Get the structure's display name
     * @return \GT\QualificationStructure
     */
    public function getStructureName(){
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getDisplayName() : false;
        
    }
    
    /**
     * Get the structure's real name
     * @return \GT\QualificationStructure
     */
    public function getStructureRealName(){
        
        $structure = new \GT\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getName() : false;
        
    }
    
    /**
     * Get the level name
     * @return type
     */
    public function getLevelName(){
        
        $level = new \GT\Level ($this->levelID);
        return ($level) ? $level->getName() : false;
        
    }
    
    /**
     * Get the display name without having to instantiate the object elsewhere, useful for templates
     * @param type $id
     * @return type
     */
    public static function name($id){
                
        $unit = new \GT\Unit($id);
        return $unit->getDisplayName();
        
    }
    
    
    /**
     * Count non-deleted units in system
     * @global \GT\type $DB
     * @return type
     */
    public static function countUnits(){
        
        global $DB;
        
        $count = $DB->count_records("bcgt_units", array("deleted" => 0));
        return $count;
        
    }
    
    /**
     * Get all units, sorted by number
     * @return type
     */
    public static function getAllUnits($sortInSearch = true){
        
        $units = self::search( array('sort' => $sortInSearch) );
        $Sorter = new \GT\Sorter();
        $Sorter->sortUnits($units);
        
        return $units;
        
    }
  
    
}
