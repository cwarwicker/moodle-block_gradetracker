<?php
/**
 * CriteriaAwardStructure
 *
 * Class for dealing with Criteria Grading Structures
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

class CriteriaAwardStructure {
    
    private $id = false;
    private $qualStructureID = null;
    private $qualBuildID = null;
    private $name;
    private $enabled = 0;
    private $assessments = 0;
    private $deleted = 0;
    
    private $awards = array();
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_crit_award_structures", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->qualStructureID = $record->qualstructureid;
                $this->qualBuildID = $record->buildid;
                $this->name = $record->name;
                $this->enabled = $record->enabled;
                $this->assessments = $record->assessments;
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
        
        $valid = true;
        
        if ($this->id === false){
            $valid = false;
        }
        
        if ($this->isDeleted()){
            $valid = false;
        }
        
        if (!is_null($this->qualStructureID)){
            $qualStructure = new \GT\QualificationStructure($this->qualStructureID);        
            if (!$qualStructure->isValid() || $qualStructure->isDeleted()){
                $valid = false;
            }
        } elseif (!is_null($this->qualBuildID)){
            $qualBuild = new \GT\QualificationBuild($this->qualBuildID);        
            if (!$qualBuild->isValid() || $qualBuild->isDeleted()){
                $valid = false;
            }
        }

        return $valid;
        
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
    
    /**
     * is this structure used for assessments?
     * @return type
     */
    public function isUsedInAssessments(){
        return ($this->assessments == 1);
    }
    
    /**
     * Can this currently be used?
     * Is it: valid, enabled and not deleted?
     * @return type
     */
    public function isUsable(){
        return ($this->isValid() && $this->isEnabled() && !$this->isDeleted());
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
    
    public function getQualBuildID(){
        return $this->qualBuildID;
    }
    
    public function setQualBuildID($id){
        $this->qualBuildID = $id;
        return $this;
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function setEnabled($val){
        $this->enabled = $val;
        return $this;
    }
    
    public function getIsUsedForAssessments(){
        return $this->assessments;
    }
    
    public function setIsUsedForAssessments($val){
        $this->assessments = $val;
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
        
        $records = $DB->get_records("bcgt_criteria_awards", array("gradingstructureid" => $this->id), "id");
        if ($records)
        {
            foreach($records as $record)
            {
                $this->awards[$record->id] = new \GT\CriteriaAward($record->id);
            }
        }
        
    }
    
    /**
     * Get award by id
     * @param type $id
     * @return type
     */
    public function getAward($id){
        return (array_key_exists($id, $this->awards)) ? $this->awards[$id] : false;
    }
    
    /**
     * Get the awards for this grading structure, ordered by points
     * @return type
     */
    public function getAwards($metOnly = false, $sortOrder = 'asc'){
        
        $Sorter = new \GT\Sorter();
        $Sorter->sortCriteriaValues($this->awards, $sortOrder);
        
        // Do we only want the ones that are "MET"?
        if ($metOnly){
            return array_filter($this->awards, function($a){
                return ($a->isMet());
            });
        } else {       
            return $this->awards;
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
    
    /**
     * Get award by name, but from the DB not the object
     * @global \GT\type $DB
     * @param type $name
     * @return type
     */
    public function getAwardByNameDB($name){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_criteria_awards", array("gradingstructureid" => $this->id, "name" => $name), "id");
        return ($record) ? new \GT\CriteriaAward($record->id) : false;
        
    }
    
    /**
     * Get an award by its shortname
     * @param type $name
     * @return type
     */
    public function getAwardByShortName($name){
        
        if ($this->awards){
            
            foreach($this->awards as $award){
                
                if (strcasecmp($award->getShortName(), $name) == 0){
                    return $award;
                }
                
            }
            
        }
        
        return null;
        
    }
    
    /**
     * Add an award to the structure
     * @param \GT\CriteriaAward $award
     */
    public function addAward(\GT\CriteriaAward $award){
        
        // If it already exists, don't append another one, update the existing object
        if ($award->isValid()){
            if ($this->awards){
                foreach($this->awards as $key => $awrd){
                    if ($awrd->getID() == $award->getID()){
                        $this->awards[$key] = $award;
                        return;
                    }
                }
            }
        }
        
        // Otherwise just append
        $this->awards[] = $award;
    }
    
    public function setAwards(array $awards){
        $this->awards = $awards;
    }
    
    /**
     * Get the maximum number of points of this award structure
     * @return type
     */
    public function getMaxPoints(){
        
        $max = 0;
        $awards = $this->getAwards(true);
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
        
        $min = 0;
        $awards = $this->getAwards(true);
        if ($awards){
            foreach($awards as $award){
                if ($award->getPoints() < $min || $min == 0){
                    $min = $award->getPoints();
                }
            }
        }
        
        return $min;
        
    }
        
    public function getErrors(){
        return $this->errors;
    }
    
    //todo
    public function countCriteria(){
        
        global $DB;
        return $DB->count_records("bcgt_criteria", array("gradingstructureid" => $this->id, "deleted" => 0));
        
    }
    
    
    /**
     * Adjust the awards to fit into the parent grading structure.
     * This is done differently, depdnding on if the parent has more or less possible awards.
     * 
     * 
     * -------------------------------------------------------------------------
     * Less:
     * 
     * Say the unit has the grading scale of PMD (P = 1, M = 2, D = 3)
     * 
     * And there are two criteria:
     * 
     * C1 has the PMD scale (P = 1, M = 2, D = 3)
     * C2 has the AE scale (E = 1, D = 2, C = 3, B = 4, A = 5)
     * 
     * ANd let's say the awards are C1 = D (3) and C2 = A (5)
     * 
     * We cannot do a simple avergage, as that would give us (3 + 5 = 8) / 2 = 4
     * And 4 is not in our unit's PMD scale 
     * 
     * So we need to adjust the AE scale points to fit into the unit award's scale
     * 
     * So firstly we need to work out what fraction we need to adjust to.
     * 
     * We take the difference between the unit scale's highest and lowest possible values:
     * 3 - 1 = 2 (We'll call this x)
     * 
     * Then we take the number of steps between the lowest and highest values in the criterion's scale:
     * 4 (We'll call this y)
     * 
     * We then do x/y to get our fraction: 2/4 = 0.5
     * 
     * This is correct, as if we look at the two scales and how we expect them to be converted, it's right:
     * 
     *          P 1             M 2             D 3
     *          E 1     D 1.5   C 2     B 2.5   A 3
     * 
     * Now with the fraction we can convert a point on the AE scale to that of the PMD scale.
     * 
     * We also need to use the step number (e.g. E is the first step, D is 2nd, C is 3, etc...)
     * 
     * The calculation for this is:
     * 
     * STEP - ((STEP - 1) * FRACTION)
     * 
     *  A: 1 - ( (1-1) * 0.5 ) = 1
     *  B: 2 - ( (2-1) * 0.5 ) = 1.5
     *  C: 3 - ( (3-1) * 0.5 ) = 2
     *  D: 4 - ( (4-1) * 0.5 ) = 2.5
     *  E: 5 - ( (5-1) * 0.5 ) = 3
     * 
     * -------------------------------------------------------------------------
     * More:
     * 
     * Say the unit has a grading structure of AE (A = 5, B = 4, C = 3, D = 2, E = 1)
     *      
     * C1 and C2 have the grading structure of PMD
     * 
     * So if we had:
     * 
     * C1 = D
     * C2 = D
     * 
     * Again we can't do a normal average of that, as it would give us 3, being a C, when we want an A
     * 
     * Firstly we work out the fraction again:
     * 
     * A - E = 4
     * Steps from P to D = 2 (It's important to note you don't do D-P here as the points could be anything, e.g. 1, 7, 15, it has to be the number of steps)
     * 
     * 4/2 = 2
     * 
     *  This is correct, as if we look at the two scales and how we expect them to be converted, it's right:
     * 
     *          E1    D2   C3    B4   A5
     *          P1         M3         D5
     * 
     * So the calculation for this one is:
     * 
     * ( (STEP - 1) * FRACTION ) + 1
     * 
     * P: ( (1-1) * 2 ) + 1 = 1
     * M: ( (2-1) * 2 ) + 1 = 3
     * D: ( (3-1) * 2 ) + 1 = 5
     * 
     * 
     * @param type $fraction
     * @param type $steps
     * @return array
     */
    public function adjustPointsByFraction($fraction, $possibleAwardArray, $direction = false){
        
        $return = array();
        $awards = $this->getAwards(true);
                        
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
     * Find the award of a given special value, eg. LATE, WS, etc...
     * @param type $specialVal
     * @return boolean
     */
    public function findAwardBySpecialValue($specialVal)
    {
    
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                if ($award->getSpecialVal() == $specialVal)
                {
                    return $award;
                }
            }
        }
        
        return false;
        
    }
        
    /**
     * Check it has no errors
     * @global \GT\type $DB
     * @return type
     */
    public function hasNoErrors(){
        
        global $DB;
     
        // Name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:gradestructures:name', 'block_gradetracker');
        }
        
        $params = array("name" => $this->name, "deleted" => 0);
        if (!is_null($this->qualStructureID)){
            $params['qualstructureid'] = $this->qualStructureID;
        } elseif (!is_null($this->qualBuildID)){
            $params['buildid'] = $this->qualBuildID;
        }
        
        $check = $DB->get_record("bcgt_crit_award_structures", $params);
        if ($check && $check->id <> $this->id)
        {
            $this->errors[] = get_string('errors:gradestructures:name:duplicate', 'block_gradetracker') . ' - ' . $this->name;
        }
        
        // Qual Structure or Qual Build
        if ($this->qualStructureID <= 0 && ($this->qualBuildID <= 0 || is_null($this->qualBuildID))){
            $this->errors[] = get_string('errors:gradestructures:qualstructureorbuild', 'block_gradetracker') . ' - ' . $this->name;
        }
        
        // Awards
        if (!$this->awards){
            $this->errors[] = get_string('errors:gradestructures:awards', 'block_gradetracker') . ' - ' . $this->name;
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
     * Save the criteria grading structure
     * @global type $DB
     * @return boolean
     */
    public function save($deleteRemoved = true){
        
        global $DB;
                
        $obj = new \stdClass();
        
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->qualstructureid = $this->qualStructureID;
        $obj->buildid = $this->qualBuildID;
        $obj->name = $this->name;
        $obj->enabled = $this->enabled;
        $obj->assessments = $this->assessments;
        
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_crit_award_structures", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_crit_award_structures", $obj);
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
        if ($deleteRemoved)
        {
            $this->deleteRemovedAwards();
        }
        
        return true;
        
    }
    
     /**
     * Delete the grading structure
     * @global \GT\type $DB
     * @return boolean
     */
    public function delete(){
    
        global $DB;
        
        // Mark the structure as deleted
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = 1;
        $obj->assessments = 0;
        $DB->update_record("bcgt_crit_award_structures", $obj);
        
        // BCTODO - Also need to then reset award of any criteria that were using this structure
        $criterias = $DB->get_records("bcgt_criteria", array("gradingstructureid" => $this->id));
        foreach ($criterias as $criteria) {
            $criteria->gradingstructureid = 0;
            $DB->update_record("bcgt_criteria", $criteria);
        }
        
        return true;
        
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
        $old = $DB->get_records("bcgt_criteria_awards", array("gradingstructureid" => $this->id));
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
                $DB->delete_records("bcgt_criteria_awards", array("id" => $removeID));
            }
        }
        
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
        $DB->update_record("bcgt_crit_award_structures", $obj);
        
        // If this is a Build grading structure, disable all the others first
        // Removed this, as now GCSE have different grading structures for different assessments
//        if ($this->qualBuildID > 0)
//        {
//            
//            $DB->execute("UPDATE {bcgt_crit_award_structures} 
//                          SET enabled = 0
//                          WHERE buildid = ?
//                          AND id <> ?
//                          AND deleted = 0", array($this->qualBuildID, $this->id));
//            
//        }
        
    }
    
    
    /**
     * Load the submitted post data into the object
     */
    public function loadPostData(){
        
        // ID - if we're editing existing one
        if (isset($_POST['grading_id'])){
            $this->setID($_POST['grading_id']);
        }
                
        $this->setName($_POST['grading_name']);
        $this->setEnabled( (isset($_POST['grading_enabled']) && $_POST['grading_enabled'] == 1 ) ? 1 : 0);
        $this->setIsUsedForAssessments( (isset($_POST['grading_assessments']) && $_POST['grading_assessments'] == 1 ) ? 1 : 0);
        
        // If Build ID use that, otherwise use QualStructureID
        $buildID = optional_param('build', false, PARAM_INT);
        if ($buildID){
            $this->setQualBuildID($buildID);
            $this->setIsUsedForAssessments(1);
        } else {
            $this->setQualStructureID( $_POST['grading_qual_structure_id'] );
        }
        
        $gradeIDs = (isset($_POST['grade_ids'])) ? $_POST['grade_ids'] : false;
        if ($gradeIDs)
        {
            
            foreach($gradeIDs as $key => $id)
            {
                
                $award = new \GT\CriteriaAward($id);
                $award->setName($_POST['grade_names'][$key]);
                $award->setShortName($_POST['grade_shortnames'][$key]);
                $award->setSpecialVal($_POST['grade_specialvals'][$key]);
                $award->setPoints($_POST['grade_points'][$key]);
                $award->setPointsLower($_POST['grade_points_lower'][$key]);
                $award->setPointsUpper($_POST['grade_points_upper'][$key]);
                $award->setMet( (isset($_POST['grade_met'][$key])) ? 1 : 0 );
                $award->setImageFile( \gt_get_multidimensional_file($_FILES['grade_files'], $key) );
                
                // If we have a tmp icon set load that back in
                if ( isset($_POST['grade_icon_names'][$key]) && strpos($_POST['grade_icon_names'][$key], "tmp//") === 0 ){
                    $award->iconTmp = str_replace("tmp//", "", $_POST['grade_icon_names'][$key]);
                }
                
                // If are editing something which already has a valid image saved
                elseif (isset($_POST['grade_icon_names'][$key]) && strlen($_POST['grade_icon_names'][$key]) > 0)
                {
                    $award->setImage($_POST['grade_icon_names'][$key]);
                }
                
                $this->addAward($award);
                
            }
            
        }
        
        
    }
    
    
    
}

