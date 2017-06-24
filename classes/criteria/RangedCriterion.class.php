<?php
/**
 * GT\Criteria\Ranged
 *
 * This is the class for Ranged Criteria
 * 
 * These allow you to have grids of criteria across different ranges/observations
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

namespace GT\Criteria;

class RangedCriterion extends \GT\Criterion {
    
    /**
     * Construct the object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
        
            $check = $DB->get_record("bcgt_criteria", array("id" => $id));
            if ($check)
            {

                $this->id = $check->id;
                $this->unitID = $check->unitid;
                $this->gradingStructureID = $check->gradingstructureid;
                $this->parentCritID = $check->parentcritid;
                $this->name = $check->name;
                $this->description = $check->description;
                $this->type = $check->type;
                $this->subCritType = $check->subcrittype;
                $this->deleted = $check->deleted;

            }
        
        }
        
    }
    
    /**
     * Does this criterion type need a sub row in the unit creation form for extra stuff?
     * @return mixed
     */
    public function hasFormSubRow(){
        return 'ranged';
    }
    
    /**
     * This doesn't have auto calculation, as the criterion award is based on the points
     * @return boolean
     */
    protected function hasAutoCalculation(){
        return false;
    }
    
    public function hasNoErrors($parent = false) {
        
        parent::hasNoErrors($parent);
        
        // Check grading structure - Can be blank - This will mean a readonly criterion
        $QualStructure = new \GT\QualificationStructure($this->qualStructureID);
        $GradingStructures = $QualStructure->getCriteriaGradingStructures();
                        
        // Ranged criteria cannot be readonly
        // The top level and the Ranges need to have a grading structure, but the Criterion sub criteria can be readonly
        if (!array_key_exists($this->gradingStructureID, $GradingStructures) && $this->subCritType != 'Criterion'){
            $this->errors[] = sprintf( get_string('errors:crit:gradingstructure', 'block_gradetracker'), $this->name );
        }
                
        return (!$this->errors);
        
    }
    
    /**
     * Load in extra data for RangedCriterion
     * @param type $criterion
     */
    public function loadExtraPostData($criterion){
                        
        // Ranges
        if (isset($criterion['ranges']))
        {
            
            foreach($criterion['ranges'] as $rNum => $range)
            {
                
                $id = (isset($range['id'])) ? $range['id'] : false;
                $subObj = new \GT\Criteria\RangedCriterion($id);
                $subObj->setQualStructureID($this->qualStructureID);
                $subObj->setType($this->type);
                $subObj->setName($range['name']);
                $subObj->setDescription($range['details']);
                $subObj->setSubCritType("Range");
                $subObj->setDynamicNumber( floatval($this->dynamicNumber . '.' . $rNum) );
                $subObj->setGradingStructureID( $range['gradingstructure'] );
                if (isset($range['numobservations'])){
                    $subObj->setAttribute("numobservations", $range['numobservations']);
                }
                $subObj->setAttribute("gradingtype", "NORMAL");
                
                // Range Criteria
                if (isset($range['criteria']))
                {
                    
                    foreach($range['criteria'] as $sRNum => $rangeCriterion)
                    {
                        
                        $id = (isset($rangeCriterion['id'])) ? $rangeCriterion['id'] : false;
                        $subSubObj = new \GT\Criteria\RangedCriterion($id);
                        $subSubObj->setQualStructureID($this->qualStructureID);
                        $subSubObj->setType($this->type);
                        $subSubObj->setName($rangeCriterion['name']);
                        $subSubObj->setDescription($rangeCriterion['details']);
                        $subSubObj->setSubCritType("Criterion");
                        $subSubObj->setDynamicNumber( $this->dynamicNumber . '.' . $rNum . '.' . $sRNum );
                        $subSubObj->setGradingStructureID( $rangeCriterion['gradingstructure'] );
                        $subSubObj->setAttribute("gradingtype", "NORMAL");
                        if (isset($rangeCriterion['readonly']) && $rangeCriterion['readonly'] == 1){
                            $subSubObj->setAttribute("readonly", 1);
                        }
                        
                        $subObj->addChild($subSubObj, true);
                        
                    }
                    
                }
                
                $this->addChild($subObj, true);
                
            }
            
        }
                
    }
    
    protected function getCellEdit($advanced = false)
    {
        return $this->getCellContent(true, $advanced);
    }
    
    
    /**
     * 
     * @global type $CFG
     * @param type $advanced
     * @param type $fromSub
     * @return string
     */
    public function getCellContent($editing = false, $advanced = false, $fromSub = false, $observationNum = false, $parent = false){
        
        global $CFG;
                
        $output = "";
        
        $elID = "S{$this->student->id}_Q{$this->qualID}_U{$this->unitID}_C{$this->id}";
        
        if (!$fromSub && $editing)
        {
            // Show the icon to pop it up into a...popup
            $img = ($this->getUserAward()->isMet()) ? 'openA.png' : 'open.png';
            $output .= "<a href='#' class='gt_open_ranged_criterion_window'>";
                $output .= "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/symbols/default/{$img}' alt='".get_string('open', 'block_gradetracker')."' />";
            $output .= "</a>";
        }
        else
        {
                        
            $userValue = $this->getCriterionObservationValue($observationNum);
            $userValueDate = $this->getCriterionObservationDate($observationNum);

            // If we are using a grading structure with only 1 met value, use a tick box
            $metValues = $this->getPossibleValues(true);
            if ( !$advanced && $metValues && count($metValues) == 1 )
            {
                
                // Are we using a DATE grading type?
                if ($parent && $parent->getAttribute('gradingtype') == 'DATE')
                {

                    if ($editing)
                    {
                        $date = ($userValueDate > 0) ? date('d-m-Y', $userValueDate) : '';
                        $output .= "<input type='text' class='gt_criterion_date gt_datepicker' value='{$date}' observationNum='{$observationNum}' />";
                    }
                    else
                    {
                        $output .= ($userValueDate > 0) ? date('d-m-Y', $userValueDate) : '-';
                    }
                    
                }
                else
                {
                    
                    // Otherwise just normal tickbox
                    $value = reset($metValues);
                    if ($editing)
                    {
                        $chk = ($userValue == $value->getID()) ? 'checked' : '';
                        $output .= "<input class='gt_criterion_checkbox' observationNum='{$observationNum}' type='checkbox' value='{$value->getID()}' {$chk} />";
                    }
                    else
                    {
                        $userValueObj = new \GT\CriteriaAward($userValue);
                        $valueName = ($observationNum) ? $userValueObj->getShortName() : $userValueObj->getName();
                        $output .= ($userValueObj->isValid()) ? $valueName : '-';
                    }
                    
                }
                
            }
            else
            {
            
                if ($editing)
                {
                    
                    $values = $this->getPossibleValues();
                    
                    // If it has values, give it a select menu, otherwise it's readonly
                    if ($values)
                    {

                        // If it has a parent, it's the edit cell of a range/observation
                        if ($parent){

                            $userValue = $this->getCriterionObservationValue($observationNum);
                            $output .= "<select id='{$elID}' name='gt_criteria[{$this->qualID}][{$this->unitID}][{$this->id}]' class='gt_criterion_select' observationNum='{$observationNum}'>";

                        } else {

                            $userValue = $this->getUserAward();
                            if ($userValue){
                                $userValue = $userValue->getID();
                            }

                            $output .= "<select id='{$elID}' name='gt_criteria[{$this->qualID}][{$this->unitID}][{$this->id}]' class='gt_criterion_select'>";

                        }

                        $output .= "<option value='0'></option>";

                        $lastMet = true;

                        foreach($values as $award)
                        {
                            if ($award->isMet() !== $lastMet)
                            {
                                $output .= "<option value='' disabled>----------</option>";
                            }
                            $sel = ($userValue == $award->getID()) ? 'selected' : '';
                            $output .= "<option value='{$award->getID()}' {$sel} >{$award->getShortName()} - {$award->getName()}</option>";
                            $lastMet = $award->isMet();
                        }

                        $output .= "</select>";
                    
                    }
                
                }
                else
                {
                    
                    if ($parent)
                    {
                        $userValueID = $this->getCriterionObservationValue($observationNum);
                        $userValue = new \GT\CriteriaAward($userValueID);
                        $valueName = ($observationNum) ? $userValue->getShortName() : $userValue->getName();
                        $output .= ($userValue->isValid()) ? $valueName : '-';
                    }
                    else
                    {
                        $userValue = $this->getUserAward();
                        $valueName = ($observationNum) ? $userValue->getShortName() : $userValue->getName();
                        $output .= ($userValue->isValid()) ? $valueName : '-';
                    }
                    
                                       
                }
            
            }
                            
        }
        
        return $output;
        
    }
    
    /**
     * Get the info for the info popup
     * @return string
     */
    public function getPopUpInfo(){
        
        $output = "";

        $qualification = $this->getQualification();
        $unit = $this->getUnit();

        $output .= "<div class='gt_criterion_popup_info'>";

        if ($this->student){
            $output .= "<br><span class='gt-popup-studname'>{$this->student->getDisplayName()}</span><br>";
        }  

        if ($qualification){
            $output .= "<span class='gt-popup-qualname'>{$qualification->getDisplayName()}</span><br>";
        }

        if ($unit){
            $output .= "<span class='gt-popup-unitname'>{$unit->getDisplayName()}</span><br>";
        }

        $output .= "<span class='gt-popup-critname'>{$this->getName()}</span><br>";

        $output .= "<p><i>{$this->getDescription()}</i></p>";
        

        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('value', 'block_gradetracker')."</div>";
        $output .= "<div class='gt_c'>";

            $output .= "<img class='gt_award_icon' src='{$this->getUserAward()->getImageURL()}' alt='{$this->getUserAward()->getShortName()}' /><br>";
            $output .= "<span class='gt-popup-unitname'>".$this->getUserAward()->getName()."<br></span>";
            if ($this->getUserAwardDate() > 0)
            {
                $output .= "<span class='gt-popup-awarddate'>{$this->getUserAwardDate('D jS M Y')}</span>";
            }
            $output .= "<br><br>";
            $output .= get_string('lastupdatedby', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdateByUserID() > 0) ? $this->getUserLastUpdateBy()->getName() : '-') . '</b>';
            $output .= "&nbsp;&nbsp;&nbsp;";
            $output .= get_string('updatetime', 'block_gradetracker') . ' <b>'.( ($this->getUserLastUpdate() > 0) ? $this->getUserLastUpdate('D jS M Y, H:i') : '-' ) . '</b>';

        $output .= "</div>";

        if ($this->hasUserComments())
        {
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('comments', 'block_gradetracker')."</div>";
            $output .= "<div class='gt_criterion_info_comments'>";
                $output .= gt_html($this->userComments, true);
            $output .= "</div>";
        }   
        
        
        // Does it have ranges?
        $ranges = $this->getChildOfSubCritType("Range");
        
        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('ranges', 'block_gradetracker')."</div>";
        $output .= "<div class='gt_left'>";

        if ($ranges)
        {

            $Range = reset($ranges);
            $Range->loadStudent($this->student);
            $Range->setQualID($this->qualID);

            $output .= "<ul class='gt_tabbed_list'>";

            foreach($ranges as $range)
            {
                $class = ($Range->getID() == $range->getID()) ? 'active' : '';
                $output .= "<li class='{$class}'><a href='#' class='gt_load_range' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$range->getID()}'>{$range->getName()}</a></li>";
            }

            $output .= "</ul>";


            // Get the first range and do that one
            $output .= "<div id='gt_popup_range_info'>";
                $output .= $Range->getRangePopUpContent();                                                            
            $output .= "</div>";

        }


        $output .= "</div>";      
            
        $output .= "</div>";
                
        return $output;
        
    }
    
    
    
     /**
     * 
     * @return string
     */
    public function getPopUpContent() {
        
        global $OUTPUT;
        
        $this->loadChildren();
        
        $qualification = $this->getQualification();
        $unit = $this->getUnit();
        
        $output = "";
                
        $output .= "<div class='gt_ranged_criterion_popup'>";        
        
            $output .= "<div class='gt_c'>";

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

                $output .= "<p id='gt_popup_loader' class='gt_c gt_hidden'><img src='".$OUTPUT->pix_url('i/loading_small')."' alt='".get_string('loading', 'block_gradetracker')."' /></p>";
                $output .= "<div id='gt_popup_error' class='gt_alert_bad gt_left gt_hidden'>".get_string('errors:save', 'block_gradetracker')."</div>";
                $output .= "<div id='gt_popup_success' class='gt_alert_good gt_left gt_hidden'>".get_string('saved', 'block_gradetracker')."</div>";

                
                $ranges = $this->getChildOfSubCritType("Range");
                if ($ranges)
                {
                    
                    $Range = reset($ranges);
                    $Range->loadStudent($this->student);
                    $Range->setQualID($this->qualID);

                    $output .= "<ul class='gt_tabbed_list'>";
                    
                    foreach($ranges as $range)
                    {
                        $class = ($Range->getID() == $range->getID()) ? 'active' : '';
                        $output .= "<li class='{$class}'><a href='#' class='gt_load_range' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$range->getID()}' editing='1'>{$range->getName()}</a></li>";
                    }
                    
                    $output .= "</ul>";
                    
                    
                    // Get the first range and do that one
                    $output .= "<div id='gt_popup_range_info'>";
                        $output .= $Range->getRangePopUpContent(true);                                                            
                    $output .= "</div>";
                    
                }
                
                $output .= "<table class='gt_detail_criterion_overall_table gt_criterion_wrapper' style='background-color:#fff;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>";
                
                // Overall for this Criterion
                $output .= "<tr><th colspan='2'>{$this->getName()}</th></tr>";

                $output .= "<tr class=''>";
                    $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                    $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($this->userComments, true)."</textarea></td>";
                $output .= "</tr>";


                $output .= "<tr class=''>";
                    $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                    $output .= "<td colspan='2' id='gt_criterion_value_{$this->id}' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>{$this->getCellContent(true, true, true)}</td>";
                $output .= "</tr>";

                $output .= "<tr class=''>";
                    $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                    $output .= "<th>".get_string('date')."</th>";
                    $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                $output .= "</tr>";

            $output .= "</table>";
                
                
            $output .= "</div>";
            
        $output .= "</div>";            
     
        return $output;
                
    }
    
    /**
     * Get the maximum number of observations used by this range for this user
     * @global \GT\Criteria\type $DB
     * @return type
     */
    private function getMaxObservations(){
        
        global $DB;
        
//        $sql = "SELECT MAX(obnum) as ttl
//                FROM {bcgt_user_ranges}
//                WHERE userid = ? AND rangeid = ?";
        
        $sql = "SELECT MAX(ttl) as ttl
                FROM
                (

                    SELECT 
                    MAX(REPLACE(attribute, 'observation_award_date_', '')) as ttl
                    FROM {bcgt_criteria_attributes}
                    WHERE critid = ? AND userid = ? and attribute LIKE 'observation_award_date_%'

                    UNION

                    SELECT MAX(obnum) as ttl
                    FROM {bcgt_user_ranges}
                    WHERE userid = ? AND rangeid = ?

                ) as t";
        
        $record = $DB->get_record_sql($sql, array($this->id, $this->student->id, $this->student->id, $this->id));
        return ($record) ? (int)$record->ttl : 0;
        
    }
    
    public function getRangePopUpContent($editing = false) {
        
        global $OUTPUT;
        
        $GT = new \GT\GradeTracker();
        
        $qualification = $this->getQualification();
        $unit = $this->getUnit();
        $parent = $unit->getCriterion( $this->parentCritID );

        // Work out max observation number
        $numObs = $this->getAttribute('numobservations');
        if (!$numObs){
            $numObs = 0;
        }

        $max = $this->getMaxObservations();
        
        if ($max > $numObs){
            $numObs = $max;
        }
        
        
        $children = $this->getChildOfSubCritType("Criterion");
        
        $output = "";
        
        if ($children)
        {

            $output .= "<table id='gt_ranged_observations_table' class='gt_popup_table'>";
            
            $output .= "<tr class='gt_lightblue'>";
            
                $output .= "<th>".get_string('name')."</th>";
                
                for ($i = 1; $i <= $numObs; $i++)
                {
                    $output .= "<th class='gt_obnum'>{$i}</th>";
                }
                
                if ($editing)
                {
                    $output .= "<th class='gt_obnum'><a href='#' class='gt_add_ranged_observation'><img src='".$GT->icon('plus_circle_frame')."' class='gt_icon' alt='".get_string('add', 'block_gradetracker')."' /></a></th>";
                }
                
            $output .= "</tr>";
            
            // Award dates of observations
            $output .= "<tr class='gt_lightblue'>";
            
                $output .= "<th>".get_string('dateachieved', 'block_gradetracker')."</th>";
                
                for ($i = 1; $i <= $numObs; $i++)
                {
                    $unix = $this->getUserObservationAwardDate($i);
                    $date = ($unix && $unix > 0) ? date('d-m-Y', $unix) : '';
                    $output .= "<td class='gt_obcell'>";
                        if ($editing)
                        {
                            $output .= "<input type='text' class='gt_datepicker gt_range_observation_award_date' value='{$date}' sID='{$this->student->id}' qID='{$this->qualID}' uID='{$this->unitID}' rID='{$this->id}' observationNum='{$i}' />";
                        }
                        else
                        {
                            $output .= $date;
                        }
                    $output .= "</td>";
                }
                
            $output .= "</tr>";
            

            foreach($children as $child)
            {
                
                $output .= "<tr>";
                    
                    $output .= "<td>{$child->getName()} - {$child->getDescription()}</td>";
                    
                    for ($i = 1; $i <= $numObs; $i++)
                    {
                        $output .= "<td class='gt_obcell' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$child->getID()}' rID='{$this->id}' sID='{$this->student->id}'>";
                            if (!$child->getAttribute('readonly'))
                            {
                                $output .= $child->getCellContent($editing, false, true, $i, $parent);
                            }
                        $output .= "</td>";                    
                    }
                    
                $output .= "</tr>";
                
            }

            $output .= "</table>";

        }
        
        
         // This is the Range overall
        $output .= "<table class='gt_detail_criterion_overall_table gt_criterion_wrapper' style='background-color:#fff;margin-bottom:1px;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>";

            $output .= "<tr><th colspan='2'>{$this->getName()}</th></tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>";
                    if ($editing)
                    {
                        $output .= "<textarea type='comments' class='gt_update_comments'>".\gt_html($this->getUserComments(), true)."</textarea>";
                    }
                    else
                    {
                        $output .= \gt_html($this->getUserComments());
                    }
                $output .= "</td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' id='gt_criterion_value_{$this->getID()}' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>{$this->getCellContent($editing, true, true)}</td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $date = ($this->getUserAwardDate() > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                $output .= "<th>".get_string('date')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>";
                    if ($editing)
                    {
                        $output .= "<input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' />";
                    }
                    else
                    {
                        $output .= $date;
                    }
                $output .= "</td>";
            $output .= "</tr>";

        $output .= "</table>";
        
                
        return $output;
        
    }
    
    /**
     * Get the crit observation value
     * @global \GT\Criteria\type $DB
     * @param type $obNum
     * @return boolean
     */
    public function getCriterionObservationValue($obNum){
        
        global $DB;
        
        if (!$this->student) return false;
        
        $record = $DB->get_record("bcgt_user_ranges", array("userid" => $this->student->id, "critid" => $this->id, "obnum" => $obNum));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Get the crit observation value
     * @global \GT\Criteria\type $DB
     * @param type $obNum
     * @return boolean
     */
    public function getCriterionObservationDate($obNum){
        
        global $DB;
        
        if (!$this->student) return false;
        
        $record = $DB->get_record("bcgt_user_ranges", array("userid" => $this->student->id, "critid" => $this->id, "obnum" => $obNum));
        return ($record) ? $record->awarddate : false;
        
    }
    
    /**
     * Set value for observation criterion
     * @global \GT\Criteria\type $DB
     * @param type $obNum
     * @param type $value
     * @param type $date
     * @return boolean
     */
    public function setCriterionObservationValue($obNum, $rangeID, $value, $date = null){
        
        global $DB;
                        
        if (!$this->student) return false;
        
        $record = $DB->get_record("bcgt_user_ranges", array("userid" => $this->student->id, "critid" => $this->id, "obnum" => $obNum));
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_RANGE;
        $Log->beforejson = array(
            'observationnumber' => $obNum,
            'value' => ($record) ? $record->value : null,
            'awarddate' => ($record) ? $record->awarddate : null
        );
        // ------------ Logging Info
        
        
        if ($record)
        {
            $record->value = $value;
            $record->awarddate = ($date > 0 ) ? $date : null;
            $result = $DB->update_record("bcgt_user_ranges", $record);
        }
        else
        {
            $ins = new \stdClass();
            $ins->userid = $this->student->id;
            $ins->critid = $this->id;
            $ins->rangeid = $rangeID;
            $ins->obnum = $obNum;
            $ins->value = $value;
            $ins->awarddate = ($date > 0 ) ? $date : null;
            $result = $DB->insert_record("bcgt_user_ranges", $ins);
        }
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'observationnumber' => $obNum,
            'value' => $value,
            'awarddate' => ($date > 0) ? $date : null
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                \GT\Log::GT_LOG_ATT_UNITID => $this->unitID,
                \GT\Log::GT_LOG_ATT_CRITID => $this->id,
                \GT\Log::GT_LOG_ATT_RANGEID => $rangeID,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
        
        
        return $result;
        
    }
    
    /**
     * Get the observation award date for a range
     * @param type $obNum
     * @return boolean
     */
    public function getUserObservationAwardDate($obNum)
    {
        
        if (!$this->student) return false;
        return $this->getUserAttribute('observation_award_date_' . $obNum, $this->student->id);
                
    }
    
    /**
     * Set the award date of an observation on the range
     * @param type $obNum
     * @param type $date
     * @return boolean
     */
    public function setUserObservationAwardDate($obNum, $date)
    {
        
        if (!$this->student) return false;
        return $this->updateAttribute('observation_award_date_' . $obNum, $date, $this->student->id);
        
    }
    
    public function save() {
        $type = \GT\QualificationStructureLevel::getByName("Ranged Criteria");
        $this->type = $type->getID();
        parent::save();
    }
    
}
