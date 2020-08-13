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
 * This class handles the overall unit information.
 *
 * This stores the general information about the unit and will be rarely instantiated itself
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

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

        $GTEXE = \block_gradetracker\Execution::getInstance();

        if ($id) {

            $record = $DB->get_record("bcgt_units", array("id" => $id));
            if ($record) {

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
                if (!isset($GTEXE->UNIT_MIN_LOAD) || !$GTEXE->UNIT_MIN_LOAD) {
                    $this->loadCustomFormElements();
                }

            }

        }

    }

    /**
     * Count the number of qualifications this unit is attached to
     * @return type
     */
    public function countQuals() {

        $quals = $this->getQualifications();
        return count($quals);

    }

    public function isValid() {
        return ($this->id !== false);
    }

    public function isDeleted() {
        return ($this->deleted == 1);
    }

    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
    }

    public function getStructureID() {
        return $this->structureID;
    }

    public function getStructure() {

        if (isset($this->structure)) {
            return $this->structure;
        }

        $this->structure = new \block_gradetracker\QualificationStructure($this->structureID);
        return $this->structure;

    }

    public function setStructureID($id) {
        $this->structureID = $id;
        return $this;
    }

    public function getLevelID() {
        return $this->levelID;
    }

    public function getLevel() {

        if (isset($this->level)) {
            return $this->level;
        }

        $this->level = new \block_gradetracker\Level($this->levelID);
        return $this->level;

    }

    public function setLevelID($id) {
        $this->levelID = $id;
        return $this;
    }

    public function getUnitNumber() {
        return $this->unitNumber;
    }

    public function setUnitNumber($number) {
        $this->unitNumber = trim($number);
        return $this;
    }

    public function getName() {
        return \gt_html($this->name);
    }

    public function getDisplayName() {

        if (strlen($this->unitNumber)) {
            return $this->getUnitNumber() . ": " . $this->getName();
        } else {
            return $this->getName();
        }

    }

    public function getShortenedDisplayName() {
        $name = $this->getDisplayName();
        return wordwrap($name, 75, '<br>');
    }

    public function setName($name) {
        $this->name = trim($name);
        return $this;
    }

    public function getCode() {
        return $this->code;
    }

    public function setCode($code) {
        $this->code = trim($code);
        return $this;
    }

    public function getCredits() {
        return $this->credits;
    }

    public function setCredits($credits) {
        $this->credits = $credits;
        return $this;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($desc) {
        $this->description = trim($desc);
        return $this;
    }

    public function getGradingStructure() {

        if ($this->gradingStructure === false) {
            $this->gradingStructure = new \block_gradetracker\UnitAwardStructure($this->getGradingStructureID());
        }

        return $this->gradingStructure;

    }

    public function getGradingStructureID() {
        return $this->gradingStructureID;
    }

    public function getGradingStructureName() {
        $structure = $this->getGradingStructure();
        return $structure->getName();
    }

    public function setGradingStructureID($id) {
        $this->gradingStructureID = $id;
        return $this;
    }

    public function getDeleted() {
        return $this->deleted;
    }

    public function setDeleted($val) {
        $this->deleted = $val;
        return $this;
    }

    public function getFullName() {
        return $this->getStructure()->getDisplayName() . " " . $this->getLevel()->getName() . " " . $this->getDisplayName();
    }

    /**
     * I am not sure why I have this variable twice in 2 different methods
     * @param type $id
     * @return \block_gradetracker\Unit
     */
    public function getQualStructureID() {
        return $this->qualStructureID;
    }

    /**
     * I am not sure why I have this variable twice in 2 different methods
     * @param type $id
     * @return \block_gradetracker\Unit
     */
    public function setQualStructureID($id) {
        $this->qualStructureID = $id;
        return $this;
    }



    /**
     * Get the name of the unit to be used in select <options>
     * @return type
     */
    public function getOptionName() {

        $output = "";
        $output .= "({$this->getLevel()->getShortName()})";

        if (strlen($this->code) > 0) {
            $output .= " {$this->code}";
        }

        $output .= " - ";

        if ($this->unitNumber > 0) {
            $output .= $this->unitNumber . ": ";
        }

        $output .= $this->name;
        return $output;

    }

    /**
     * Get the criteria on this unit
     * @return type
     */
    public function getCriteria() {

        if ($this->criteria === false) {
            $this->loadCriteria();
        }

        return $this->criteria;

    }

    /**
     * Load the unit's criteria from the database
     * @global \block_gradetracker\type $DB
     */
    protected function loadCriteria($parentID = null, &$obj = false) {

        global $DB;

        $GTEXE = \block_gradetracker\Execution::getInstance();

        if ($this->criteria === false) {
            $this->criteria = array();
        }

        $criteria = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "parentcritid" => $parentID, "deleted" => 0));
        if ($criteria) {

            foreach ($criteria as $criterion) {

                $critObj = \block_gradetracker\Criterion::load($criterion->id);
                if ($critObj && $critObj->isValid()) {

                    // Set the qualID if we have it
                    if (isset($this->qualID)) {
                        $critObj->setQualID($this->qualID);
                    }

                    // Set the qual structure id if we have it
                    if (isset($this->qualStructureID)) {
                        $critObj->setQualStructureID($this->qualStructureID);
                    }

                    // Check for children
                    $this->loadCriteria($critObj->getID(), $critObj);
                    if ($obj) {
                        $obj->addChild($critObj);
                    } else {
                        $this->criteria[$criterion->id] = $critObj;
                    }

                }

            }

        }

        // Order them
        if (is_null($parentID) && !$obj) {
            if (!isset($GTEXE->CRIT_NO_SORT) || !$GTEXE->CRIT_NO_SORT) {
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
    public function loadCriteriaIntoFlatArray($criteria = false, $forceLoadAll = false, &$array = false ) {

        if ($criteria && $array) {

            if (is_null($criteria->getSubCritType()) || $forceLoadAll) {
                $key = ($criteria->isValid()) ? $criteria->getID() : -$criteria->getDynamicNumber();
                $array[$key] = $criteria;
                if ($criteria->getChildren()) {
                    foreach ($criteria->getChildren() as $sub) {
                        $this->loadCriteriaIntoFlatArray($sub, $forceLoadAll, $array);
                    }
                }
            }
            return;
        }

        $return = array();

        // If we haven't done anything with it yet
        if ($this->criteria === false) {
            $this->loadCriteria();
        }

        if ($this->criteria) {

            foreach ($this->criteria as $criterion) {

                if (is_null($criterion->getSubCritType()) || $forceLoadAll) {
                    $key = ($criterion->isValid()) ? $criterion->getID() : -$criterion->getDynamicNumber();
                    $return[$key] = $criterion;
                    if ($criterion->getChildren()) {
                        foreach ($criterion->getChildren() as $sub) {
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
    public function countCriteriaByLetter($letter, $criteria = false) {

        $count = 0;

        if (!$criteria) {
            $criteria = $this->loadCriteriaIntoFlatArray();
        }

        if ($criteria) {
            foreach ($criteria as $crit) {
                if (strpos($crit->getName(), $letter) === 0) {
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
    public function setCriteriaPostData($criteria) {

        $this->criteria = array();

        // In case we have put a child criteria before its parent in the form, order by parents, so that
        // we can more easily add children to parent objects
        if ($criteria) {
            uasort($criteria, function($a, $b) {
                if ($a['parent'] == $b['parent']) {
                    return strnatcasecmp($a['name'], $b['name']);
                } else {
                    return ($a['parent'] > $b['parent']);
                }
            });
        }

        if ($criteria) {

            foreach ($criteria as $num => $criterion) {

                $critObj = \block_gradetracker\Criterion::load(false, $criterion['type']);

                if ($critObj) {

                    if (isset($criterion['id'])) {
                        $critObj->setID($criterion['id']);
                    }

                    $critObj->setQualStructureID( $this->structureID );
                    $critObj->setName($criterion['name']);
                    $critObj->setType($criterion['type']);
                    $critObj->setDescription($criterion['details']);
                    $critObj->setDynamicNumber($num);

                    if (ctype_digit($criterion['parent']) && $criterion['parent'] > 0) {
                        $critObj->setParentNumber((int)$criterion['parent']);
                    }

                    $critObj->setGradingStructureID( @$criterion['grading'] );
                    $critObj->setAttribute("weighting", $criterion['weight']);
                    $critObj->setAttribute("gradingtype", $criterion['gradingtype']);

                    // Additional options
                    if (isset($criterion['options']) && $criterion['options']) {
                        foreach ($criterion['options'] as $opt => $val) {
                            $critObj->setAttribute($opt, $val);
                        }
                    }

                    // Load anything extra as required by this specific type of criterion
                    $critObj->loadExtraPostData($criterion);

                    // If it has a parent, add as child to that object
                    if (ctype_digit($criterion['parent']) && $criterion['parent'] > 0) {
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
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get the array of FormElement objects
     * @return type
     */
    public function getCustomFormElements() {
        return $this->customFormElements;
    }

    /**
     * Get the value of a specific FormElement loaded into the object
     * @param type $name
     * @return type
     */
    public function getCustomFormElementValue($name) {

        $element = $this->getCustomFormElementByName($name);
        return ($element) ? $element->getValue() : false;

    }

    /**
     * Get a specific element from the loaded elements, by its name
     * @param type $name
     * @return boolean
     */
    public function getCustomFormElementByName($name) {

        if ($this->customFormElements) {

            foreach ($this->customFormElements as $element) {

                if ($element->getName() == $name) {
                    return $element;
                }

            }

        }

        return false;

    }



    /**
     * Load the custom form elements into the qualification, with any values as well
     */
    public function loadCustomFormElements() {

        // Get the possible elements for the qualification form
        $structure = new \block_gradetracker\QualificationStructure( $this->getStructureID() );
        $elements = $structure->getCustomFormElements('unit');

        // Get the saved
        if ($this->isValid()) {
            if ($elements) {
                foreach ($elements as $element) {
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
     * @return \block_gradetracker\Qualification
     */
    public function setCustomElementValues($array) {

        // Reset saved values on all elements
        if ($this->customFormElements) {
            foreach ($this->customFormElements as $element) {
                $element->setValue(null);
            }
        }

        // Now load in the ones we have submitted
        if ($array) {

            foreach ($array as $name => $value) {

                $element = $this->getCustomFormElementByName($name);
                if ($element) {
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
    protected function findCriterionByDynamicNumber($num, &$criteria = false) {

        if ($criteria) {

            foreach ($criteria as $criterion) {

                // If this is the one, return it
                if ($criterion->getDynamicNumber() == $num) {
                    return $criterion;
                }

                // If it has children
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $result = $this->findCriterionByDynamicNumber($num, $children);
                    if ($result) {
                        return $result;
                    }
                }

            }

        } else if ($this->criteria) {

            foreach ($this->criteria as $criterion) {

                // If this is the one, return it
                if ($criterion->getDynamicNumber() == $num) {
                    return $criterion;
                }

                // If it has children
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $result = $this->findCriterionByDynamicNumber($num, $children);
                    if ($result) {
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
    public function getCriterion($critID) {

        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);
        return ($flatArray && array_key_exists($critID, $flatArray)) ? $flatArray[$critID] : false;

    }

    /**
     * Get a criterion from this unit by its name
     * @param type $name
     * @return boolean
     */
    public function getCriterionByName($name, $forceLoadAll = true) {

        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);
        if ($flatArray) {
            foreach ($flatArray as $crit) {
                if ($crit->getName() == $name) {
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
    public function getPossibleAwards() {

        if (!$this->possibleAwards) {
            $this->loadPossibleAwards();
        }

        return $this->possibleAwards;

    }

    /**
     * Load the possible awards for this unit grading structure
     */
    protected function loadPossibleAwards() {

        $this->possibleAwards = array();

        $structure = new \block_gradetracker\UnitAwardStructure($this->gradingStructureID);
        if ($structure->isValid()) {
            $awards = $structure->getAwards();
            if ($awards) {
                foreach ($awards as $award) {
                    $this->possibleAwards[$award->getID()] = $award;
                }
            }
        }

    }


    /**
     * Gets a distinct list of all the possible criteria values for this unit to put into the key
     * @return array
     */
    public function getAllPossibleValues() {

        $values = array();

        $criteria = $this->loadCriteriaIntoFlatArray();

        if ($criteria) {

            foreach ($criteria as $criterion) {

                $possibleValues = $criterion->getPossibleValues();
                if ($possibleValues) {

                    foreach ($possibleValues as $value) {

                        $values[$value->getShortName().':'.$value->getName()] = $value;

                    }

                }

            }

        }

        $Sorter = new \block_gradetracker\Sorter();
        $Sorter->sortCriteriaValues($values);

        return $values;

    }

    /**
     * Get a list of qualifications this unit is assigned to
     * @global \block_gradetracker\type $DB
     * @return \block_gradetracker\Qualification
     */
    public function getQualifications() {

        global $DB;

        $return = array();

        $results = $DB->get_records("bcgt_qual_units", array("unitid" => $this->id));
        if ($results) {
            foreach ($results as $result) {
                $obj = new \block_gradetracker\Qualification($result->qualid);
                if ($obj->isValid() && !$obj->isDeleted()) {
                    $return[] = $obj;
                }
            }
        }

        // Order them
        $Sort = new \block_gradetracker\Sorter();
        $Sort->sortQualifications($return);

        return $return;

    }

    /**
     * Get a unit attribute
     * @global \block_gradetracker\type $DB
     * @param type $attribute
     * @param type $userID
     * @return type
     */
    public function getAttribute($attribute, $userID = null, $qualID = null) {

        global $DB;

        $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));
        return ($check) ? $check->value : false;

    }

    /**
     * Get all the attributes for this unit
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function getUnitAttributes() {

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
    public function updateAttribute($attribute, $value, $userID = null, $qualID = null) {

        global $DB;

        // If value is null, table doesn't support null values, so just delete it
        if (is_null($value)) {
            return $this->deleteAttribute($attribute, $userID, $qualID);
        }

        $check = $DB->get_record("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));

        // ------------ Logging Info
        if (!is_null($userID)) {
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_GRID;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;
            $Log->beforejson = array(
                $attribute => ($check) ? $check->value : null
            );
        }
        // ------------ Logging Info

        if ($check) {
            $check->value = $value;
            $check->lastupdate = time();
            $result = $DB->update_record("bcgt_unit_attributes", $check);
        } else {
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
        if (!is_null($userID)) {

            // ----------- Log the action
            $Log->afterjson = array(
                $attribute => $value
            );

            $Log->attributes = array(
                    \block_gradetracker\Log::GT_LOG_ATT_QUALID => $qualID,
                    \block_gradetracker\Log::GT_LOG_ATT_UNITID => $this->id,
                    \block_gradetracker\Log::GT_LOG_ATT_STUDID => $userID
                );

            $Log->save();
            // ----------- Log the action

        }

        return $result;

    }

    /**
     * Delete a unit attribute
     * @global \block_gradetracker\type $DB
     * @param type $attribute
     * @param type $userID
     * @param type $qualID
     * @return type
     */
    public function deleteAttribute($attribute, $userID = null, $qualID = null) {

        global $DB;

        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;

        $check = $this->getAttribute($attribute, $userID, $qualID);
        $Log->beforejson = array(
            $attribute => ($check) ? $check : null
        );

        $result = $DB->delete_records("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => $userID, "qualid" => $qualID, "attribute" => $attribute));

        if (!is_null($userID)) {

            // ----------- Log the action
            $Log->attributes = array(
                    \block_gradetracker\Log::GT_LOG_ATT_QUALID => $qualID,
                    \block_gradetracker\Log::GT_LOG_ATT_UNITID => $this->id,
                    \block_gradetracker\Log::GT_LOG_ATT_STUDID => $userID
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
     * @global \block_gradetracker\type $DB
     * @param type $typeID
     * @return type
     */
    public function hasAnyCriteriaOfType($typeID) {

        global $DB;

        $check = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "type" => $typeID));
        return ($check);

    }

    /**
     * Check if there are any activity links on this unit, on the specified qualID
     * @param type $qualID
     * @return boolean
     */
    public function hasActivityLinks($qualID = false) {

        $records = $this->getActivityLinks($qualID);
        return ($records && count($records) > 0);

    }

    /**
     * Check if this unit has any activity links
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function getActivityLinks($qualID = false) {

        global $DB;

        // If we do this from UserUnit, will already have a qualID loaded in
        if ($this->qualID) {
            $qualID = $this->qualID;
        }

        if (!$qualID) {
            return false;
        }

        $return = array();
        $records = $DB->get_records("bcgt_activity_refs", array("qualid" => $qualID, "unitid" => $this->id, "deleted" => 0));
        if ($records) {
            foreach ($records as $record) {
                $obj = \block_gradetracker\ModuleLink::getModuleLinkFromCourseModule($record->cmid);
                if ($obj) {
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
    public function getCriteriaNotLinkedToActivities($activities = false) {

        // Get the activities if they aren't passed through
        if (!$activities) {
            $activities = $this->getActivityLinks();
        }

        $linkedCriteria = array();
        $unitCriteria = array();

        // Get an array of the criteria IDs linked to these activities
        if ($activities) {
            foreach ($activities as $activity) {
                if ($activity->criteria) {
                    foreach ($activity->criteria as $crit) {
                        $linkedCriteria[] = $crit->getID();
                    }
                }
            }
        }

        // Get an array of the criteria IDs on the unit
        $criteriaNames = $this->getHeaderCriteriaNamesFlat();
        if ($criteriaNames) {
            foreach ($criteriaNames as $crit) {
                $criterion = $this->getCriterionByName($crit);
                if ($criterion) {
                    $unitCriteria[] = $criterion->getID();
                }
            }
        }

        $results = array_diff( $unitCriteria, $linkedCriteria );
        $return = array();
        if ($results) {
            foreach ($results as $critID) {
                $return[$critID] = $this->getCriterion($critID);
            }
        }

        return $return;

    }

    /**
     * Check there are no errors with anything submitted
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function hasNoErrors() {

        global $DB;

        $Structure = new \block_gradetracker\QualificationStructure( $this->structureID );

        // Check if level is set
        if (strlen($this->levelID) == 0 || $this->levelID <= 0 || is_null($this->levelID)) {
            $this->errors[] = get_string('errors:unit:level', 'block_gradetracker');
        } else if (!\block_gradetracker\QualificationBuild::exists($this->structureID, $this->levelID)) {
            // Check we can have this structure & level
            // It's an elseif because if level isn't set then this obviously won't be correct
            $this->errors[] = get_string('errors:unit:build', 'block_gradetracker');
        }

        // Check name
        if (strlen($this->name) == 0) {
            $this->errors[] = get_string('errors:unit:name', 'block_gradetracker');
        }

        // Check for duplicate build, name, number and code combination combination
        $check = $DB->get_records("bcgt_units", array("name" => $this->name, "unitnumber" => $this->unitNumber, "code" => $this->code, "structureid" => $this->structureID, "levelid" => $this->levelID, "deleted" => 0));
        if (isset($check[$this->id])) {
            unset($check[$this->id]);
        }

        // Just get first element of the array, as there should only be 1 anyway max.
        if ($check) {
            $check = reset($check);
        }

        if ($check && $check->id <> $this->id) {
            $this->errors[] = get_string('errors:unit:name:duplicate', 'block_gradetracker');
        }

        // Check for grading structure
        $gradingStructure = new \block_gradetracker\UnitAwardStructure($this->gradingStructureID);
        if (!$gradingStructure->isValid() || !$gradingStructure->isEnabled() || $gradingStructure->getQualStructureID() <> $Structure->getID()) {
            $this->errors[] = get_string('errors:unit:grading', 'block_gradetracker');
        }

        // Check custom elements
        $elements = $Structure->getCustomFormElements('unit');
        if ($elements) {

            foreach ($elements as $element) {

                // Is it required?
                if ($element->hasValidation("REQUIRED")) {

                    $value = $this->getCustomFormElementValue($element->getName());
                    if (strlen($value) == 0 || $value === false) {
                        $this->errors[] = sprintf( get_string('errors:unit:custom', 'block_gradetracker'), $element->getName() );
                    }

                }

            }

        }

        $critieraNames = array();

        // Check criteria
        if ($this->criteria) {

            foreach ($this->criteria as $criterion) {

                if (!array_key_exists($criterion->getName(), $critieraNames)) {
                    $critieraNames[$criterion->getName()] = 0;
                }

                $critieraNames[$criterion->getName()]++;

                if (!$criterion->hasNoErrors()) {

                    foreach ($criterion->getErrors() as $error) {

                        $this->errors[] = $error;

                    }

                }

            }

        }

        // Make sure we have no duplicate criteria names at top level
        foreach ($critieraNames as $name => $cnt) {
            if ($cnt > 1) {
                $this->errors[] = sprintf( get_string('errors:crit:duplicatenames', 'block_gradetracker'), $name );
            }
        }

        return (!$this->errors);

    }

    /**Delete unit sets the deleted attribute to 1*/
    public function delete() {

        global $DB;

        $this->deleted = 1;
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;

        return $DB->update_record("bcgt_units", $obj);
    }

    /**Restore unit sets the deleted attribute to 0*/
    public function restore() {

        global $DB;

        $this->deleted = 0;
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = $this->deleted;

        return $DB->update_record("bcgt_units", $obj);
    }

    public function copyUnit() {

        global $DB, $CFG;

        // create new unit object
        $newunit = new \block_gradetracker\Unit();
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
        foreach ($this->criteria as $c) {
            $name = $c->getName();
            $criteriaattributes[$name] = array();
            $catts = $DB->get_records('bcgt_criteria_attributes', array('critid' => $c->getID()));
            if ($catts) {
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
        foreach ($atts as $a) {
            $newunit->updateAttribute($a->attribute, $a->value);
        }

        //get and update criteria attributes for copied unit
        foreach ($newunit->getCriteria() as $gc) {
            $name = $gc->getName();
            $atts = $criteriaattributes[$name];
            foreach ($atts as $a) {
                $gc->updateAttribute($a->attribute, $a->value);
            }
        }

        // redirect to edit page for newly copied unit
        header('location:'.$CFG->wwwroot.'/blocks/gradetracker/config.php?view=units&section=edit&id='.$newunit->getID());
    }

    public function addCriterion($criteria) {
        $this->criteria[] = $criteria;
    }
    /**
     * Save the unit to the DB
     * @global \block_gradetracker\type $DB
     * @return boolean
     */
    public function save() {

        global $DB;

        $obj = new \stdClass();

        if ($this->isValid()) {
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

        if ($this->isValid()) {
            $result = $DB->update_record("bcgt_units", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_units", $obj);
            $result = $this->id;
        }

        if (!$result) {
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }

        // Criteria
        $this->saveCriteria();

        // Order them properly again
        $Sorter = new \block_gradetracker\Sorter();
        $Sorter->sortCriteria($this->criteria);

        // Delete any we removed
        $this->deleteRemovedCriteria();

        // Custom Form Elements
        // Clear any set previously
        $DB->delete_records("bcgt_unit_attributes", array("unitid" => $this->id, "userid" => null));

        // Save new ones
        if ($this->customFormElements) {

            foreach ($this->customFormElements as $element) {

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
    protected function saveCriteria($criteria = false, $parent = false) {

        if ($criteria && $parent) {

            foreach ($criteria as $criterion) {

                $criterion->setUnitID($this->id);
                $criterion->setParentID($parent->getID());
                $criterion->save();

                // Does it have children?
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $this->saveCriteria($children, $criterion);
                }

            }

        } else if ($this->criteria) {
            foreach ($this->criteria as $criterion) {

                $criterion->setUnitID($this->id);
                $criterion->save();

                // Does it have children?
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $this->saveCriteria($children, $criterion);
                }

                // Does it have any point links?
                if (isset($criterion->pointLinks)) {
                    $criterion->savePointLinks();
                }

            }
        }

    }

    /**
     * Delete any criteria we removed from the Unit creation form
     * @global \block_gradetracker\type $DB
     */
    private function deleteRemovedCriteria() {

        global $DB;

        $oldIDs = array();
        $currentIDs = array();

        // Get the ones in the database
        $old = $DB->get_records("bcgt_criteria", array("unitid" => $this->id, "deleted" => 0));
        if ($old) {
            foreach ($old as $o) {
                $oldIDs[] = $o->id;
            }
        }

        // Get the ones loaded into the object
        $flatArray = $this->loadCriteriaIntoFlatArray(false, true);

        if ($flatArray) {
            foreach ($flatArray as $flat) {
                $currentIDs[] = $flat->getID();
            }
        }

        // Get the ones that don't exist in both arrays
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs) {
            foreach ($removeIDs as $removeID) {
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
    public function getHeaderCriteriaNamesFlat($view = false, $activities = false) {

        // Get the names
        $criteria = $this->getHeaderCriteriaNames();

        // Get a flat array of criteria names, which may contain multiple copies if activity grid and if criteiron
        // is on more than 1 activity
        $criteriaArray = array();
        if ($view == 'activities') {

            // Criteria linked to activities
            if ($activities) {
                foreach ($activities as $activity) {
                    if ($activity->criteria) {
                        foreach ($activity->criteria as $crit) {
                            $criteriaArray[] = $crit->getName();
                        }
                    }
                }
            }

            // Then non-linked ones
            $nonLinkedCriteria = ($view == 'activities') ? $this->getCriteriaNotLinkedToActivities( $activities ) : false;
            if ($nonLinkedCriteria) {
                foreach ($nonLinkedCriteria as $crit) {
                    $criteriaArray[] = $crit->getName();
                }
            }

        } else {

            if ($criteria) {
                foreach ($criteria as $crit) {
                    $criteriaArray[] = $crit['name'];
                    if ($crit['sub']) {
                        foreach ($crit['sub'] as $sub) {
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
    public function getHeaderCriteriaNames() {

        $names = array();

        $criteria = $this->getCriteria();

        if ($criteria) {

            foreach ($criteria as $criterion) {

                // If this isn't a;ready in the array, add it
                if (!array_key_exists($criterion->getName(), $names)) {
                    $names[$criterion->getName()] = array("name" => $criterion->getName(), "sub" => array());
                }

                // If this has child levels, we might want some of them in the header as well
                if ($criterion->countChildLevels() > 0) {

                    switch ( get_class($criterion) ) {

                        // Standard criterion
                        case 'block_gradetracker\Criteria\StandardCriterion':

                             // If only 1 level of sub criteria, add them in
                            // Though if this top level criterion has the setting "force popup" don't show the sub criteria in the grid table
                            if ($criterion->getAttribute('forcepopup') != 1) {

                                // If only 1 level of sub criteria, add them in
                                foreach ($criterion->getChildren() as $child) {

                                    if (!in_array($child->getName(), $names[$criterion->getName()]['sub'])) {
                                        $names[$criterion->getName()]['sub'][] = $child->getName();
                                    }

                                }

                            }

                        break;

                        // Numeric criterion - Only top level go in the header
                        case 'block_gradetracker\Criteria\NumericCriterion':

                        break;

                        // Ranged criterion - Only top level go in the header
                        case 'block_gradetracker\Criteria\RangedCriterion':

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
    public function getPopUpInfo() {

        $output = "";

        $output .= "<div class='gt_criterion_popup_info'>";
        $output .= "<span class='gt-popup-studname'>{$this->getDisplayName()}</span><br><br>";
        $output .= "<p><i>{$this->getDescription()}</i></p>";

        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('criteria', 'block_gradetracker')."</div>";

        $output .= "<table class='gt_unit_popup_criteria_table'>";
            $output .= "<tr><th>".get_string('name', 'block_gradetracker')."</th><th>".get_string('description', 'block_gradetracker')."</th></tr>";

        if ($this->getCriteria()) {
            foreach ($this->getCriteria() as $criterion) {

                $output .= "<tr><td>{$criterion->getName()}</td><td>{$criterion->getDescription()}</td></tr>";

                // Check if the criterion has any sub criteria that are of type Range
                if ($criterion->hasChildrenOfType("Range")) {

                    // Get ranged
                    $ranged = $criterion->getChildOfSubCritType("Range");
                    $subCriteria = $criterion->getChildOfSubCritType("Criterion");

                    // Numeric Criterion will have Ranged sub criteria and Criterion sub criteria on the main parent
                    if ($ranged && $subCriteria) {

                        $output .= "<tr>";
                            $output .= "<td colspan='2'>";

                                $output .= "<table class='gt_unit_info_range_table'>";

                                    $output .= "<tr>";
                                        $output .= "<th></th>";
                                        foreach ($ranged as $range) {
                                            $output .= "<th>{$range->getName()}</th>";
                                        }
                                    $output .= "</tr>";

                                    // Numeric criterion will have sub criteria of type Criterion on the main parent
                                    foreach ($subCriteria as $subCriterion) {

                                        $output .= "<tr>";
                                        $output .= "<th>{$subCriterion->getName()}</th>";
                                        foreach ($ranged as $range) {
                                            $maxPoints = $criterion->getAttribute("maxpoints_{$subCriterion->getID()}_{$range->getID()}");
                                            $output .= "<td>";
                                            if ($maxPoints > 0) {
                                                for ($i = 1; $i <= $maxPoints; $i++) {
                                                    $output .= '&nbsp;&nbsp;&nbsp;'.$i.'&nbsp;&nbsp;&nbsp;';
                                                }
                                            } else {
                                                $output .= '&nbsp;&nbsp;&nbsp;' . $maxPoints;
                                            }
                                            $output .= "</td>";
                                        }
                                        $output .= "</tr>";

                                    }

                                $output .= "</table>";

                            $output .= "</td>";
                        $output .= "</tr>";

                    } else if ($ranged) {
                        // Ranged Criterion have the Ranged sub criteria, then the Criterion sub criteria are on each Range
                        foreach ($ranged as $range) {
                            $output .= "<tr>";
                                $output .= "<td style='padding-left:10px';>{$range->getName()}</td>";
                                $output .= "<td style='padding-left:10px';>{$range->getDescription()}</td>";
                            $output .= "</tr>";
                        }

                    }

                } else {

                    // Sub Criteria level 1
                    if ($criterion->getChildren()) {
                        foreach ($criterion->getChildren() as $child) {

                            $output .= "<tr><td style='padding-left:10px';>{$child->getName()}</td><td style='padding-left:10px';>{$child->getDescription()}</td></tr>";

                            // Sub Criteria level 2
                            if ($child->getChildren()) {
                                foreach ($child->getChildren() as $subChild) {

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
    public function sortCriteria($criteria = false, $all = false, $forceObjs = false) {

        // If we are passing them through, they will be from the header, so just an array of names, not objects
        if ($criteria && !$forceObjs) {
            $objs = false;
        } else if ($criteria && $forceObjs) {
            $objs = true;
        } else if (!$criteria) {
            $criteria = ($all) ? $this->loadCriteriaIntoFlatArray() : $this->getCriteria();
            $objs = true;
        }

        $Sorter = new \block_gradetracker\Sorter();
        $structure = new \block_gradetracker\QualificationStructure( $this->getStructureID() );
        $customOrder = $structure->getCustomOrder('criteria');
        if ($customOrder) {
            $Sorter->sortCriteriaCustom($criteria, $customOrder, $objs, true);
        } else {
            $Sorter->sortCriteria($criteria, $objs, true);
        }

        return $criteria;

    }

    /**
     * Export a unit structure to XML and download file.
     * @return void
     */
    public function export() {

        $XML = $this->exportXML();

        $name = preg_replace("/[^a-z0-9]/i", "", $this->getName());
        $name = str_replace(" ", "_", $name);

        header('Content-disposition: attachment; filename=gt_unit_'.$name.'.xml');
        header('Content-type: text/xml');

        echo $XML->asXML();
        exit;

    }

    /**
     * Export a unit and its criteria to XML
     * @return \SimpleXMLElement
     */
    protected function exportXML() {

        $doc = new \SimpleXMLElement('<xml/>');

        $xml = $doc->addChild('Unit');
        $xml->addChild('qualificationStructure', \gt_html($this->getStructureRealName()));
        $xml->addChild('level', \gt_html($this->getLevelName()));
        $xml->addChild('number', \gt_html($this->getUnitNumber()));
        $xml->addChild('name', \gt_html($this->getName()));
        $xml->addChild('uniqueCode', \gt_html($this->getCode()));
        $xml->addChild('description', \gt_html($this->getDescription()));
        $xml->addChild('credits', \gt_html($this->getCredits()));
        $xml->addChild('gradingStructure', \gt_html($this->getGradingStructureName()));

        // Criteria
        $criteriaxml = $xml->addChild('criteria');
        $criteria = $this->loadCriteriaIntoFlatArray(false, true);
        if ($criteria) {

            foreach ($criteria as $criterion) {

                $critxml = $criteriaxml->addChild('Criterion');
                $critxml->addChild('exportID', $criterion->getID());
                $critxml->addChild('name', $criterion->getName());
                $critxml->addChild('description', $criterion->getDescription());
                $critxml->addChild('type', $criterion->getTypeName());
                $critxml->addChild('gradingStructure', $criterion->getGradingStructure()->getName());

                // Attributes - e.g. Weighting, Grading Type, etc...
                $attributesxml = $critxml->addChild('attributes');

                $attributes = $criterion->getAttributes();
                if ($attributes) {

                    foreach ($attributes as $attribute => $value) {

                        $att = $attributesxml->addChild($attribute, $value);

                        // If it's a Conversion Chart attribute, the award ID will mean nothing to us, so we need to
                        // put in a reference to the award itself.
                        if (strpos($attribute, 'conversion_chart') === 0) {

                            $awardID = str_replace('conversion_chart_', '', $attribute);
                            $award = new \block_gradetracker\CriteriaAward($awardID);
                            $att->addAttribute('award', $award->getName());

                        }

                    }

                }

                // If it has a parent, add that node and any other sub-criteria nodes.
                if ($criterion->hasParent()) {

                    $parent = $criterion->getParent();
                    $critxml->addChild('parent', $parent->getName());
                    $critxml->addChild('subCritType', $criterion->getSubCritType());

                }

            }

        }

        return $doc;

    }

    public static function importXML($file) {

        // We are importing, so there are certain error checks we want to skip.
        define('GT_IMPORTING', true);

        $result = array();
        $result['result'] = false;
        $result['errors'] = array();
        $result['output'] = '';

        // Required XML nodes
        $requiredNodes = array('qualificationStructure', 'level', 'number', 'name', 'uniqueCode', 'description', 'credits', 'gradingStructure', 'criteria');

        // Check file exists
        if (!file_exists($file)) {
            $result['errors'][] = get_string('errors:import:file', 'block_gradetracker') . ' - (' . $file . ')';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Check mime type of file to make sure it is XML
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fInfo, $file);
        finfo_close($fInfo);

        // Has to be XML file or a zip file, otherwise error and return
        if ($mime != 'application/xml' && $mime != 'text/plain' && $mime != 'application/zip' && $mime != 'text/xml') {
            $result['errors'][] = sprintf(get_string('errors:import:mimetype', 'block_gradetracker'), 'application/xml, text/xml, text/plain or application/zip', $mime);
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // If it's a zip file, we need to unzip it and run on each of the XML files inside
        if ($mime == 'application/zip') {
            return self::importXMLZip($file);
        }

        // Open file
        $doc = \simplexml_load_file($file);
        if (!$doc) {
            $result['errors'][] = get_string('errors:import:xml:load', 'block_gradetracker') . ' - (' . $file . ')';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Make sure it is wrapped in Unit tag
        if (!isset($doc->Unit)) {
            $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - Unit';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Get the nodes inside that tag
        $xml = $doc->Unit;

        // Check for required nodes
        $missingNodes = array();
        foreach ($requiredNodes as $node) {
            if (!property_exists($xml, $node)) {
                $missingNodes[] = $node;
            }
        }

        // If there are missing nodes, error.
        if ($missingNodes) {
            foreach ($missingNodes as $node) {
                $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - ' . $node;
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                return $result;
            }
        }

        // Get each node into a variable.
        $nodes = array();
        foreach ($requiredNodes as $node) {
            // Decode htmlspecialchars as if there was an & in the QualStructure name, it becomes &amp; in the XML and needs converting back.
            $nodes[$node] = htmlspecialchars_decode((string)$xml->{$node});
        }

        // Check Qual structure exists
        $QualStructure = \block_gradetracker\QualificationStructure::findByName($nodes['qualificationStructure']);
        if (!$QualStructure) {
            $result['errors'][] = get_string('errors:qualbuild:type', 'block_gradetracker') . ' - ' . $nodes['qualificationStructure'];
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Check Level exists
        $Level = \block_gradetracker\Level::findByName($nodes['level']);
        if (!$Level) {
            $result['errors'][] = get_string('errors:qualbuild:level', 'block_gradetracker') . ' - ' . $nodes['level'];
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Check QualBuild for this Structure and level exists.
        $Build = \block_gradetracker\QualificationBuild::find($QualStructure->getID(), $Level->getID());
        if (!$Build || (is_array($Build) && count($Build) <> 1)) {
            $result['errors'][] = get_string('errors:qualawards:buildid', 'block_gradetracker') . ' - ' . $nodes['qualificationStructure'] . '/' . $nodes['level'];
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Should only be one element in this array.
        $Build = reset($Build);

        // Now actually get the QualificationBuild object instead of stdClass;
        $Build = new \block_gradetracker\QualificationBuild($Build->id);

        // Check grading structure exists.
        $GradingStructure = \block_gradetracker\UnitAwardStructure::findByName($nodes['gradingStructure'], $QualStructure->getID());
        if (!$GradingStructure) {
            $result['errors'][] = get_string('errors:unit:grading', 'block_gradetracker') . ' - ' . $nodes['gradingStructure'];
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        $newUnit = new Unit();
        $newUnit->setStructureID($QualStructure->getID());
        $newUnit->setLevelID($Level->getID());
        $newUnit->setUnitNumber($nodes['number']);
        $newUnit->setName($nodes['name']);
        $newUnit->setCode($nodes['uniqueCode']);
        $newUnit->setDescription($nodes['description']);
        $newUnit->setCredits($nodes['credits']);
        $newUnit->setGradingStructureID($GradingStructure->getID());

        $criteriaArray = array();
        $parentArray = array();
        $dynamicArray = array();

        // Criteria
        if ($xml->criteria) {

            foreach ($xml->criteria->children() as $critNode) {

                // This is just a numeric index of the exported criteria (technically the ID from the system it was exported to).
                $exportID = (int)$critNode->exportID;

                $type = (string)$critNode->type;
                switch($type) {
                    case 'Ranged Criteria':
                        $criterion = new \block_gradetracker\Criteria\RangedCriterion();
                    break;
                    case 'Numeric Criteria':
                        $criterion = new \block_gradetracker\Criteria\NumericCriterion();
                    break;
                    default:
                        $criterion = new \block_gradetracker\Criteria\StandardCriterion();
                    break;
                }

                $StructureLevel = \block_gradetracker\QualificationStructureLevel::getByName($type);

                $criterion->setDynamicNumber($exportID);
                $criterion->setQualStructureID($QualStructure->getID());
                $criterion->setType($StructureLevel->getID());
                $criterion->setName( (string)$critNode->name );
                $criterion->setDescription( (string)$critNode->description );
                if (!empty($critNode->subCritType)) {
                    $criterion->setSubCritType( (string)$critNode->subCritType );
                }

                // Check crit grading structure exists as well.
                $critGradingStructure = \block_gradetracker\CriteriaAwardStructure::findByName((string)$critNode->gradingStructure, $QualStructure->getID(), $Build->getID());
                if (!$critGradingStructure) {
                    $result['errors'][] = get_string('invalidgradingstructure', 'block_gradetracker') . ' - ' . (string)$critNode->gradingStructure;
                    $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                    return $result;
                }

                $criterion->setGradingStructureID($critGradingStructure->getID());

                // Add the name of the criterion against its dynamic id.
                $dynamicArray[$exportID] = $criterion->getName();

                // Set all the criterion attributes.
                if ($critNode->attributes) {

                    foreach ($critNode->attributes->children() as $attNode) {

                        $attName = $attNode->getName();

                        // Conversion chart needs the award name attribute converted to an id.
                        if (strpos($attName, 'conversion_chart') === 0) {

                            $award = $critGradingStructure->getAwardByName($attNode['award']);
                            if (!$award) {
                                $result['errors'][] = get_string('invalidaward', 'block_gradetracker') . ' - ' . $attNode['award'];
                                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                                return $result;
                            }

                            // Change the name of the attribute, to use the real id from our system, instead of the exported system.
                            $attName = 'conversion_chart_' . $award->getID();

                        }

                        // Maxpoints will need to be adjusted after the criteria have been saved so we can get their IDs.

                        $criterion->setAttribute( $attName, (string)$attNode );

                    }

                }

                $newUnit->addCriterion($criterion);

                // We can't set the parent IDs yet, as the criteria won't be saved until the unit is saved.
                // So we will have to store numeric indexes against the name of their parent and then re-save them afterwards.
                if (!empty($critNode->parent)) {
                    $parentArray[$exportID] = (string)$critNode->parent;
                }

            }

        }

        // Now do the general error checks done when we save a unit.
        if (!$newUnit->hasNoErrors()) {
            $result['errors'][] = $newUnit->getErrors();
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Try to save the unit.
        $result['result'] = $newUnit->save();
        if (!$result['result']) {
            $result['errors'][] = $newUnit->getErrors();
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }

        // Now go through the parent array and re-save the criteria parents.
        // Also adjust any attributes which need adjusting with real ID values.
        if ($newUnit->getCriteria()) {

            foreach ($newUnit->getCriteria() as $newCrit) {

                // Is this dynamic number in the parent array?
                if (array_key_exists($newCrit->getDynamicNumber(), $parentArray)) {

                    // Get the criterion off the unit, by its name.
                    $parentCrit = $newUnit->getCriterionByName($parentArray[$newCrit->getDynamicNumber()]);

                    // Now set the parent id.
                    $newCrit->setParentID($parentCrit->getID());

                }

                // Check attributes.
                foreach ($newCrit->getAttributes() as $newCritAttName => $newCritAttValue) {

                    if (strpos($newCritAttName, 'maxpoints_') === 0) {

                        // Get the ids from the attribute as it is at the moment.
                        preg_match_all('/\d+/', $newCritAttName, $matches);
                        $oldIDs = $matches[0];

                        $newAttName = 'maxpoints';

                        // Loop through the old IDs, as found in the attribute name, get the criterion name associated with them from the
                        // dynamic array, then get the ID of that new criterion record and replace in the attribute.
                        foreach ($oldIDs as $oldID) {

                            $crit = $newUnit->getCriterionByName( $dynamicArray[$oldID] );
                            $newAttName .= '_' . $crit->getID();

                        }

                        // Delete the old attribute.
                        $newCrit->unsetAttribute($newCritAttName);

                        // Set the new attribute.
                        $newCrit->setAttribute($newAttName, $newCritAttValue);

                    }

                }

                // Save any changes we made.
                $newCrit->save();

            }

        }

        $result['result'] = true;
        return $result;

    }

    /**
     * Import a zip file containing XML files of Units
     * @param $file
     * @return array
     * @throws \coding_exception
     */
    public static function importXMLZip($file) {

        global $USER;

        $result = array();
        $result['result'] = true;
        $result['errors'] = array();
        $result['output'] = '';

        // Unzip the file
        $fp = \get_file_packer();
        $tmpFileName = 'import-unit-' . time() . '-' . $USER->id . '.zip';
        $extracted = $fp->extract_to_pathname($file, \block_gradetracker\GradeTracker::dataroot() . '/tmp/' . $tmpFileName);

        if ($extracted) {
            foreach ($extracted as $extractedFile => $bool) {

                $result['output'] .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $extractedFile ) . '<br>';

                $load = \block_gradetracker\GradeTracker::dataroot() . '/tmp/' . $tmpFileName . '/' . $extractedFile;
                $import = self::importXML($load);

                // Append to result
                $result['result'] = $result['result'] && $import['result'];
                $result['errors'][] = $import['errors'];
                $result['output'] .= $import['output'];

            }
        } else {
            $result['result'] = false;
            $result['errors'][] = get_string('errors:import:zipfile', 'block_gradetracker');
        }

        return $result;

    }

    /**
     * Search for units
     * @global \block_gradetracker\type $DB
     * @param type $params
     * @return \block_gradetracker\Unit
     */
    public static function search($params) {

        global $DB;

        $return = array();
        $sqlParams = array();
        $sql = "SELECT *
                FROM {bcgt_units}
                WHERE deleted = ? ";

        //is this deleted?
        $sqlParams[] = (isset($params['deleted'])) ? $params['deleted'] : 0;

        // Are we searching for units of a specific qual structure id?
        if (isset($params['structureID']) && $params['structureID'] > 0) {
            $sql .= "AND structureid = ? ";
            $sqlParams[] = $params['structureID'];
        }

        // Are we searching for a particular level of unit?
        if (isset($params['levelID']) && $params['levelID'] > 0) {
            $sql .= "AND levelid = ? ";
            $sqlParams[] = $params['levelID'];
        }

        // Are we searching for a particular unit number?
        if (isset($params['unitNumber']) && !\gt_is_empty($params['unitNumber'])) {
            $sql .= "AND unitNumber LIKE ? ";
            $sqlParams[] = '%'.trim($params['unitNumber']).'%';
        }

        if (isset($params['nameORcode']) && !\gt_is_empty($params['nameORcode'])) {
            $sql .= "AND (name LIKE ? OR unitNumber LIKE ? OR code LIKE ?) ";
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
            $sqlParams[] = '%'.trim($params['nameORcode']).'%';
        } else {

            if (isset($params['name']) && !\gt_is_empty($params['name'])) {
                $sql .= "AND (name LIKE ? OR unitNumber LIKE ?) ";
                $sqlParams[] = '%'.trim($params['name']).'%';
                $sqlParams[] = '%'.trim($params['name']).'%';
            }

            if (isset($params['code']) && !\gt_is_empty($params['code'])) {
                $sql .= "AND (code LIKE ?) ";
                $sqlParams[] = '%'.trim($params['code']).'%';
                $sqlParams[] = '%'.trim($params['code']).'%';
            }

        }

        $results = $DB->get_records_sql($sql, $sqlParams);
        if ($results) {
            foreach ($results as $result) {
                $unit = new \block_gradetracker\Unit($result->id);
                if ($unit->isValid()) {
                    $return[] = $unit;
                }
            }
        }

        if (!isset($params['sort']) || $params['sort'] == true) {
            $Sorter = new \block_gradetracker\Sorter();
            $Sorter->sortUnitsByLevel($return);
        }

        return $return;

    }

    /**
     * Get the structure's display name
     * @return \block_gradetracker\QualificationStructure
     */
    public function getStructureName() {

        $structure = new \block_gradetracker\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getDisplayName() : false;

    }

    /**
     * Get the structure's real name
     * @return \block_gradetracker\QualificationStructure
     */
    public function getStructureRealName() {

        $structure = new \block_gradetracker\QualificationStructure( $this->getStructureID() );
        return ($structure->isValid()) ? $structure->getName() : false;

    }

    /**
     * Get the level name
     * @return type
     */
    public function getLevelName() {

        $level = new \block_gradetracker\Level ($this->levelID);
        return ($level) ? $level->getName() : false;

    }

    /**
     * Get the display name without having to instantiate the object elsewhere, useful for templates
     * @param type $id
     * @return type
     */
    public static function name($id) {

        $unit = new \block_gradetracker\Unit($id);
        return $unit->getDisplayName();

    }


    /**
     * Count non-deleted units in system
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public static function countUnits() {

        global $DB;

        $count = $DB->count_records("bcgt_units", array("deleted" => 0));
        return $count;

    }

    /**
     * Get all units, sorted by number
     * @return type
     */
    public static function getAllUnits($sortInSearch = true) {

        $units = self::search( array('sort' => $sortInSearch) );
        $Sorter = new \block_gradetracker\Sorter();
        $Sorter->sortUnits($units);

        return $units;

    }

}
