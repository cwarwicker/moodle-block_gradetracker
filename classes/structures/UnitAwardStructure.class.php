<?php
/**
 * UnitAwardStructure
 *
 * Class for dealing with Unit Grading Structures
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

class UnitAwardStructure {
    
    private $id = false;
    private $qualStructureID;
    private $name;
    private $enabled = 0;
    private $deleted = 0;
    
    private $awards = array();
    private $unitPoints = array();
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_unit_award_structures", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->qualStructureID = $record->qualstructureid;
                $this->name = $record->name;
                $this->enabled = $record->enabled;
                $this->deleted = $record->deleted;
                
                // Load the awards
                $this->loadAwards();
                
            }
            
        }
        
    }
    
    /**
     * Is it a valid record from the DB?
     * @return type
     */
    public function isValid(){
        
        // Check the qual structure hasn't been deleted
        $qualStructure = new \GT\QualificationStructure($this->qualStructureID);
        return ($this->id !== false && !$this->isDeleted() && $qualStructure->isValid() && !$qualStructure->isDeleted());
    }
    
    /**
     * Is it enabled?
     * @return type
     */
    public function isEnabled(){
        return ($this->enabled == 1);
    }
    
    /**
     * Is it deleted?
     * @return type
     */
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
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function getQualStructureID(){
        return $this->qualStructureID;
    }
    
    public function setQualStructureID($id){
        $this->qualStructureID = $id;
        return $this;
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function setEnabled($val){
        $this->enabled = $val;
        return $this;
    }
    
    public function getDeleted(){
        return $this->deleted;
    }
    
    public function setDeleted($val){
        $this->deleted = $val;
        return $this;
    }
    
    public function loadAwards(){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_unit_awards", array("gradingstructureid" => $this->id), "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $this->awards[$record->id] = new \GT\UnitAward($record->id);
            }
        }
        
    }
    
    /**
     * Get the awards for this grading structure, ordered by points
     * @return type
     */
    public function getAwards(){
        
        usort($this->awards, function($a, $b){
            return ($a->getPoints() > $b->getPoints());
        });
        
        return $this->awards;
        
    }
    
    /**
     * Add an award to the structure
     * @param \GT\UnitAward $award
     */
    public function addAward(\GT\UnitAward $award){
        
        if ($award->isValid())
        {
            $this->awards[$award->getID()] = $award;
        }
        else
        {
            $this->awards[] = $award;
        }
        
    }
    
    /**
     * Get an award by its name
     * @param type $name
     * @return boolean
     */
    public function getAwardByName($name){
        
        if ($this->awards){
            
            foreach($this->awards as $award){
                
                if (strcasecmp($award->getName(), $name) == 0){
                    return $award;
                }
                
            }
            
        }
        
        return null;
        
    }
    
    
    
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Count the number of units with an award defined by this structure
     */
    public function countUnits(){
        
        global $DB;
        return $DB->count_records("bcgt_units", array("gradingstructureid" => $this->id, "deleted" => 0));
        
    }
    
    /**
     * Get the maximum number of points of this award structure
     * @return type
     */
    public function getMaxPoints(){
        
        $max = 0;
        $awards = $this->getAwards();
        if ($awards){
            foreach($awards as $award){
                if ($award->getPoints() > $max){
                    $max = $award->getPoints();
                }
            }
        }
        
        return $max;
        
    }
    
     /**
     * Get the maximum number of points of this award structure
     * @return type
     */
    public function getMinPoints(){
        
        $min = false;
        $awards = $this->getAwards();
        if ($awards){
            foreach($awards as $award){
                if ($award->getPoints() < $min || ($min === false)){
                    $min = $award->getPoints();
                }
            }
        }
        
        return ($min !== false) ? $min : 0;
        
    }
    
    /**
     * Check no errors
     * @return type
     */
    public function hasNoErrors(){
        
        // Name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:gradestructures:name', 'block_gradetracker');
        }
        
        // Qual Structure
        if ($this->qualStructureID <= 0){
            $this->errors[] = get_string('errors:gradestructures:qualstructure', 'block_gradetracker');
        }
        
        // Awards
        if (!$this->awards){
            $this->errors[] = get_string('errors:gradestructures:awards', 'block_gradetracker');
        }
        
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                if (!$award->hasNoErrors())
                {
                    foreach($award->getErrors() as $err)
                    {
                        $this->errors[] = $err;
                    }
                }
            }
        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Save the unit award structure
     * @global type $DB
     * @return boolean
     */
    public function save(){
        
        global $DB;
                
        $obj = new \stdClass();
        
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->qualstructureid = $this->qualStructureID;
        $obj->name = $this->name;
        $obj->enabled = $this->enabled;
        
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_unit_award_structures", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_unit_award_structures", $obj);
            $result = $this->id;
        }
        
        if (!$result){
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
        
        
        // Now the awards
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                
                $award->setGradingStructureID($this->id);
                $award->save();
                
            }
        }
        
        // Remove old awards
        $this->deleteRemovedAwards();
                
        // Now any unit points we've set
        // 
        // Clear existing - These can be deleted, their IDs are irrelevant
        $this->wipeUnitPoints();
        
        // Save any passed in the form
        $this->saveUnitPoints($this->unitPoints);
        
        return true;
        
    }
    
    /**
     * Save unit points records
     * @global \GT\type $DB
     * @param type $unitPoints
     */
    public function saveUnitPoints($unitPoints){
        
        global $DB;
        
        if ($unitPoints)
        {
            foreach($unitPoints as $type => $array)
            {
                foreach($array as $relevantID => $points)
                {
                    if (is_array($points) && $points)
                    {
                        foreach($points as $awardID => $point)
                        {
                            if (is_numeric($point))
                            {
                                $ins = new \stdClass();
                                $ins->qualstructureid = $this->getQualStructureID();
                                $ins->unitawardstructureid = $this->id;
                                if ($type == 'levels'){
                                    $typefield = 'levelid';
                                    $ins->levelid = $relevantID;
                                } elseif ($type == 'builds'){
                                    $typefield = 'qualbuildid';
                                    $ins->qualbuildid = $relevantID;
                                }
                                $ins->awardid = $awardID;
                                $ins->points = $point;
                                
                                // Check if already exists
                                $check = $DB->get_record("bcgt_unit_award_points", array("qualstructureid" => $ins->qualstructureid, "unitawardstructureid" => $ins->unitawardstructureid, $typefield => $relevantID, 'awardid' => $ins->awardid));
                                if ($check)
                                {
                                    $check->points = $ins->points;
                                    $DB->update_record("bcgt_unit_award_points", $check);
                                }
                                else
                                {                                
                                    $DB->insert_record("bcgt_unit_award_points", $ins);
                                }
                            }
                        }
                    }
                }
            }
        }
        
    }
    
    /**
     * Wipe them all for this qual structure
     * @global \GT\type $DB
     */
    private function wipeUnitPoints(){
        
        global $DB;
        $DB->delete_records("bcgt_unit_award_points", array("qualstructureid" => $this->getQualStructureID(), "unitawardstructureid" => $this->id));
        
    }
    
    /**
     * Get the defined points for a given level and award on this structure
     * @global \GT\type $DB
     * @param type $levelID
     * @param type $awardID
     * @return type
     */
    public function getUnitPoint($levelID, $awardID, $buildID = null){
        
        global $DB;
        
        if ($buildID){
            $record = $DB->get_record("bcgt_unit_award_points", array("qualstructureid" => $this->getQualStructureID(), "unitawardstructureid" => $this->id, "qualbuildid" => $buildID, "awardid" => $awardID));
        } else {        
            $record = $DB->get_record("bcgt_unit_award_points", array("qualstructureid" => $this->getQualStructureID(), "unitawardstructureid" => $this->id, "levelid" => $levelID, "awardid" => $awardID));
        }
        return ($record) ? $record->points : false;
        
    }
    
    /**
     * Get all the unit points for this unit grading structure
     * @global \GT\type $DB
     * @return type
     */
    public function getAllUnitPoints(){
        
        global $DB;
        return $DB->get_records("bcgt_unit_award_points", array("qualstructureid" => $this->getQualStructureID(), "unitawardstructureid" => $this->id));
        
    }
    
    
    /**
     * Delete any awards that were on the grading structure before but not submitted this time
     * @global \GT\type $DB
     */
    public function deleteRemovedAwards(){
        
        global $DB;
        
        $oldIDs = array();
        $currentIDs = array();
        
        // Old ones
        $old = $DB->get_records("bcgt_unit_awards", array("gradingstructureid" => $this->id));
        if ($old)
        {
            foreach($old as $o)
            {
                $oldIDs[] = $o->id;
            }
        }
        
        // Current ones
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                $currentIDs[] = $award->getID();
            }
        }
                
        // Remove
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $DB->delete_records("bcgt_unit_awards", array("id" => $removeID));
            }
        }
        
    }
    
    /**
     * Delete the unit grading structure
     * @global \GT\type $DB
     * @return boolean
     */
    public function delete(){
    
        global $DB;
        
        // Mark the structure as deleted
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = 1;
        $DB->update_record("bcgt_unit_award_structures", $obj);
        
        // BCTODO - Also need to then reset award of any units that were using this structure
        // Don't actually remove the grading structure id from the unit, in case we want to undelete it
        // Can be manually changed on the unit to a different one otherwise
        $units = $DB->get_records("bcgt_units", array("gradingstructureid" => $this->id));
        foreach ($units as $unit) {
            $unit->gradingstructureid = 0;
            $DB->update_record("bcgt_units", $unit);
        }
        
        return true;
        
    }
    
    
    /**
     * Adjust the awards to fit into the parent grading structure.
     * FOr more info on this, see the comment for this method on the CriteriaAwardStructure class
     * @param type $fraction
     * @param type $steps
     * @return array
     */
    public function adjustPointsByFraction($fraction, $possibleAwardArray, $direction = false){
        
        $return = array();
        $awards = $this->getAwards();
                        
        $i = 1;
        
        foreach($awards as $award)
        {
        
            $points = '-';
            
            if ($direction == 'down')
            {

                // STEP - ((STEP - 1) * FRACTION)
                $points = $i - ( ($i - 1) * $fraction );

            }
            elseif ($direction == 'up')
            {

                // ( (STEP - 1) * FRACTION ) + 1
                $points = ( ($i - 1) * $fraction ) + 1;

            }
            elseif (count($awards) == count($possibleAwardArray))
            {

                $key = $i - 1;
                $points = $possibleAwardArray[$key]->getPoints();

            }
            
            $return[$award->getPoints()] = $points;
                        
            $i++;
        
        }
        
        return $return;
       
        
    }
    
    
      /**
     * Enable or Disable the grading structure, based on whichever it currently is
     * @global \GT\type $DB
     */
    public function toggleEnabled(){
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->enabled = !$this->enabled;
        $DB->update_record("bcgt_unit_award_structures", $obj);
        
    }
    
    /**
     * Load data from form into object
     */
    public function loadPostData(){
        
        if (isset($_POST['grading_id'])){
            $this->setID( $_POST['grading_id'] );
        }
        
        $this->setName( $_POST['grading_name'] );
        $this->setEnabled( (isset($_POST['grading_enabled']) && $_POST['grading_enabled'] == 1 ) ? 1 : 0);
        $this->setQualStructureID( $_POST['grading_qual_structure_id'] );
        
        // Grades
        $gradeIDs = (isset($_POST['grade_ids'])) ? $_POST['grade_ids'] : false;
        
        if ($gradeIDs)
        {
            foreach($gradeIDs as $key => $id)
            {
                
                $award = new \GT\UnitAward($id);
                $award->setGradingStructureID( $this->id );
                $award->setName( $_POST['grade_names'][$key] );
                $award->setShortName( $_POST['grade_shortnames'][$key] );
                $award->setPoints( $_POST['grade_points'][$key] );
                $award->setPointsLower( $_POST['grade_points_lower'][$key] );
                $award->setPointsUpper( $_POST['grade_points_upper'][$key] );
                
                if ($award->isValid())
                {
                    $this->awards[$award->getID()] = $award;
                }
                else
                {
                    $this->awards[] = $award;
                }
                
            }
        }
        
        
        // Unit points
        $this->unitPoints = array();
        if (isset($_POST['unit_points']))
        {
            $this->unitPoints = $_POST['unit_points'];
        }       
        
        
    }
    
    
}
