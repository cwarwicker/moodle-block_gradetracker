<?php
/**
 * GT\Criteria\NumericCriterion
 *
 * This is the class for Numeric Criteria
 * 
 * These allow you to enter points scores which can be calculated into grades using conversion charts
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

class NumericCriterion extends \GT\Criterion {
    
    const DEFAULT_MAX_POINTS = 3;  
    
    public $pointLinks = array();
    
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
        return 'numeric';
    }
    
    /**
     * This doesn't have auto calculation, as the criterion award is based on the points
     * @return boolean
     */
    protected function hasAutoCalculation(){
        return false;
    }
    
    protected function autoCalculateAward( $variables = false ) {
        
        $variables = array(
            'force' => true,
            'children' => $this->getChildOfSubCritType("Range")
        );
        
        parent::autoCalculateAward( $variables );
                
    }
    
    
    /**
     * Check for errors
     * @param type $parent
     */
    public function hasNoErrors($parent = false) {
        
        parent::hasNoErrors($parent);

        // If it's a top level Numeric Criterion, it cannot have a parent
        if (is_null($this->subCritType) && $this->parentNumber > 0){
            $this->errors[] = sprintf( get_string('errors:crit:numeric:parent', 'block_gradetracker'), $this->name );
        }
        
        // Must have at least 1 sub criterion. Cannot just have Observations with no sub criteria
        if (!$this->hasChildrenOfType('Criterion') && !$parent)
        {
            $this->errors[] = sprintf( get_string('errors:crit:numeric:sub', 'block_gradetracker'), $this->name );
        }

        // Check grading structure - Can be blank - This will mean a readonly criterion
        $QualStructure = new \GT\QualificationStructure($this->qualStructureID);
        $GradingStructures = $QualStructure->getCriteriaGradingStructures();
        
        // Numeric criteria cannot be readonly
        // The top level needs to have a grading structure, then the Range and the Criteria don't have any, though in the DB they are stored with the same as the parent
        if (!array_key_exists($this->gradingStructureID, $GradingStructures)){
            $this->errors[] = sprintf( get_string('errors:crit:gradingstructure', 'block_gradetracker'), $this->name );
        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Load in extra data for NumericCriteria
     * @param type $criterion
     */
    public function loadExtraPostData($criterion){
                
        $subCriteria = array();
        $observations = array();
        
        // Criteria-only conversion chart
        if (isset($criterion['chart']))
        {
            foreach($criterion['chart'] as $awardID => $points)
            {
                $this->setAttribute("conversion_chart_{$awardID}", (int)$points);
            }
        }
        
        
        // Now the sub criteria
        if (isset($criterion['subcriteria']))
        {
            foreach($criterion['subcriteria'] as $subDNum => $sub)
            {
                
                $id = (isset($sub['id'])) ? $sub['id'] : false;
                $subObj = new \GT\Criteria\NumericCriterion($id);
                $subObj->setQualStructureID($this->qualStructureID);
                $subObj->setType($this->type);
                $subObj->setName($sub['name']);
                $subObj->setSubCritType("Criterion");
                $subObj->setDynamicNumber( floatval($this->dynamicNumber . '.' . $subDNum) );
                $subObj->setGradingStructureID($this->gradingStructureID);
                if (isset($sub['points'])){
                    $subObj->setAttribute("maxpoints", $sub['points']);
                }
                $subObj->setAttribute("gradingtype", "NORMAL");
                $this->addChild($subObj, true);
                
                $subCriteria[$subDNum] = $subObj;
                
            }
        }
        
        // Now the observations
        if (isset($criterion['observation']))
        {
            foreach($criterion['observation'] as $obNum => $observation)
            {
                
                $id = (isset($observation['id'])) ? $observation['id'] : false;
                $subObj = new \GT\Criteria\NumericCriterion($id);
                $subObj->setQualStructureID($this->qualStructureID);
                $subObj->setType($this->type);
                $subObj->setName($observation['name']);
                $subObj->setSubCritType("Range");
                $subObj->setDynamicNumber( floatval($this->dynamicNumber . '.' . $obNum) );
                $subObj->setGradingStructureID($this->gradingStructureID);
                $subObj->setAttribute("gradingtype", "NORMAL");
                
                // Chart
                if (isset($criterion['charts'][$obNum])){
                    foreach($criterion['charts'][$obNum] as $awardID => $points)
                    {
                        $subObj->setAttribute("conversion_chart_{$awardID}", (int)$points);
                    }
                }
                
                $this->addChild($subObj, true);
                
                $observations[$obNum] = $subObj;
                                
            }
        }
        
        if (isset($criterion['points']))
        {
            foreach($criterion['points'] as $link => $points)
            {
                
                $link = explode("|", $link);
                $subCritNum = $link[0];
                $obNum = $link[1];
                
                $this->pointLinks[] = array(
                    'Criterion' => $subCriteria[$subCritNum],
                    'Range' => $observations[$obNum],
                    'Points' => $points
                );
                
            }
        }
        
                
    }
    
    /**
     * Add a points link
     * @param type $criterion
     * @param type $observation
     * @param type $points
     */
    public function addPointsLink($subCriterion, $observation, $points){
        
        $this->pointLinks[] = array(
            'Criterion' => $subCriterion,
            'Range' => $observation,
            'Points' => $points
        );
        
    }
    
    /**
     * Save the links between criteria and observations and points
     */
    public function savePointLinks(){
        
        if (isset($this->pointLinks)){
                                     
            foreach($this->pointLinks as $link){
                                
                if (is_numeric($link['Criterion'])){
                    $cID = $link['Criterion'];
                } else {
                    $cID = $link['Criterion']->getID();
                }
                
                if (is_numeric($link['Range'])){
                    $rID = $link['Range'];
                } else {
                    $rID = $link['Range']->getID();
                }

                $this->updateAttribute("maxpoints_{$cID}_{$rID}", $link['Points']);
                $this->setAttribute("maxpoints_{$cID}_{$rID}", $link['Points']);
            
            }
                            
        }
        
    }
    
    
    /**
     * 
     * @global type $CFG
     * @param type $advanced
     * @param type $fromSub
     * @return string
     */
    protected function getCellEdit($advanced = false, $fromSub = false){
        
        global $CFG;
        
        $output = "";

        $elID = "S{$this->student->id}_Q{$this->qualID}_U{$this->unitID}_C{$this->id}";

        if (!$fromSub)
        {
            // Show the icon to pop it up into a...popup
            $img = ($this->getUserAward()->isMet()) ? 'openA.png' : 'open.png';
            $output .= "<a href='#' class='gt_open_numeric_criterion_window'>";
                $output .= "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/symbols/default/{$img}' alt='".get_string('open', 'block_gradetracker')."' />";
            $output .= "</a>";
        }
        else
        {
            
            $values = $this->getPossibleValues();

            $output .= "<select id='{$elID}' name='gt_criteria[{$this->qualID}][{$this->unitID}][{$this->id}]' class='gt_criterion_select'>";

                $output .= "<option value='0'></option>";

                $lastMet = true;

                foreach($values as $award)
                {
                    if ($award->isMet() !== $lastMet)
                    {
                        $output .= "<option value='' disabled>----------</option>";
                    }
                    $sel = ($this->getUserAward() && $this->getUserAward()->getID() == $award->getID()) ? 'selected' : '';
                    $output .= "<option value='{$award->getID()}' {$sel} >{$award->getShortName()} - {$award->getName()}</option>";
                    $lastMet = $award->isMet();
                }

            $output .= "</select>";
                            
        }
        
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
                
        $output .= "<div class='gt_numeric_criterion_popup'>";        
        
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

                // Do we have any ranges?
                $ranges = $this->getChildOfSubCritType("Range");
                
                $totalPoints = 0;
                $totalMaxPoints = 0;
                
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
                else
                {
                    
                    // No ranges, only criteria
                    $children = $this->getChildOfSubCritType("Criterion");
                    if ($children)
                    {
                        
                        $output .= "<table class='gt_popup_table'>";
                        $output .= "<tr class='gt_lightblue'><th>".get_string('name')."</th><th>".get_string('comments', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th></tr>";
                        
                        foreach($children as $child)
                        {
                            
                            $child->loadStudent( $this->student );
                            
                            $maxPoints = $child->getAttribute('maxpoints');

                            $output .= "<tr class='gt_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'>";
                                $output .= "<td>{$child->getName()}</td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'>";
                                
                                if ($maxPoints > 0)
                                {
                                    $output .= "<textarea type='comments' class='gt_update_comments gt_comments_sub_large'>".\gt_html($child->getUserComments(), true)."</textarea>";
                                }
                                
                                $output .= "</td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}' cName='".\gt_html($child->getName())."'>";
                                                                        
                                    if ($maxPoints > 0)
                                    {
                                        $points = $child->getUserCustomValue();
                                        $totalPoints += $points;
                                        $totalMaxPoints += $maxPoints;
                                        
                                        $output .= "<table class='gt_numeric_values_table'>";
                                        $output .= "<tr>";
                                        
                                        for ($p = 0; $p <= $maxPoints; $p++)
                                        {
                                            $output .= "<th>{$p}</th>";
                                        }
                                        
                                        $output .= "</tr>";
                                        $output .= "<tr>";
                                        
                                        for ($p = 0; $p <= $maxPoints; $p++)
                                        {
                                            $chk = ($points == $p) ? 'checked' : '';
                                            $output .= "<td>";
                                                $output .= "<input type='radio' name='C{$child->getID()}' class='gt_update_numeric_point' value='{$p}' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}' {$chk} />";
                                            $output .= "</td>";
                                        }
                                        
                                        $output .= "</tr>";
                                        $output .= "</table>";
                                        
                                    }
                                
                                $output .= "</td>";
                            $output .= "</tr>";
                            
                        }
                        
                        $output .= "</table>";
                        
                    }
                    
                                
                    $output .= "<table class='gt_detail_criterion_overall_table gt_criterion_wrapper' style='background-color:#fff;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>";
                
                        // Overall for this Criterion
                        $output .= "<tr class=''>";
                            $output .= "<th>".get_string('totalpoints', 'block_gradetracker')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' ><span id='gt_total_points'>{$totalPoints}</span> / {$totalMaxPoints}</td>";
                        $output .= "</tr>";
                        
                        
                        $output .= "<tr class=''>";
                            $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($this->userComments, true)."</textarea></td>";
                        $output .= "</tr>";


                        $output .= "<tr class=''>";
                            $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                            $output .= "<td colspan='2' id='gt_criterion_value_{$this->id}' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>{$this->getCellEdit(true, true)}</td>";
                        $output .= "</tr>";

                        $output .= "<tr class=''>";
                            $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                            $output .= "<th>".get_string('date')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                        $output .= "</tr>";

                    $output .= "</table>";

                }
                
            $output .= "</div>";
            
        $output .= "</div>";    

        return $output;
        
    }
    
    /**
     * Get the content for the range part of the popup, so we can switch between range tabs
     * @return string
     */
    public function getRangePopUpContent(){
        
        $qualification = $this->getQualification();
        $unit = $this->getUnit();
        $parent = $unit->getCriterion( $this->getParentID() );
        $parent->loadStudent($this->student);
        $parent->setQualID($this->qualID);
        
        $totalMaxPoints = 0;
        $totalPoints = 0;
        
        $output = "";
        
        $children = $parent->getChildOfSubCritType("Criterion");
        if ($children)
        {

            $output .= "<table class='gt_popup_table'>";
            $output .= "<tr class='gt_lightblue'><th>".get_string('name')."</th><th>".get_string('comments', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th></tr>";

            foreach($children as $child)
            {

                $child->loadStudent( $this->student );

                $output .= "<tr class='gt_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$child->getID()}'>";
                    $output .= "<td>{$child->getName()}</td>";
                    $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$child->getID()}'><textarea type='comments' class='gt_update_comments gt_comments_sub_large'>".\gt_html($child->getUserComments(), true)."</textarea></td>";
                    $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$child->getID()}' cName='".\gt_html($child->getName())."'>";

                        $maxPoints = $parent->getAttribute("maxpoints_{$child->getID()}_{$this->getID()}");
                        $points = $this->getRangeCriterionValue($this->getID(), $child->getID());
                        $totalPoints += $points;
                        
                        if ($maxPoints > 0)
                        {
                            $totalMaxPoints += $maxPoints;
                            
                            
                            $output .= "<table class='gt_numeric_values_table'>";
                            $output .= "<tr>";

                            for ($p = 0; $p <= $maxPoints; $p++)
                            {
                                $output .= "<th>{$p}</th>";
                            }

                            $output .= "</tr>";
                            $output .= "<tr>";

                            for ($p = 0; $p <= $maxPoints; $p++)
                            {
                                $chk = ($points == $p) ? 'checked' : '';
                                $output .= "<td>";
                                    $output .= "<input type='radio' name='R{$this->getID()}C{$child->getID()}' class='gt_update_numeric_point' value='{$p}' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$child->getID()}' rID='{$this->getID()}' {$chk} />";
                                $output .= "</td>";
                            }

                            $output .= "</tr>";
                            $output .= "</table>";
                            
                        }

                    $output .= "</td>";
                $output .= "</tr>";

            }

            $output .= "</table>";

        }


        // This is the Range overall
        $output .= "<table class='gt_detail_criterion_overall_table gt_criterion_wrapper' style='background-color:#fff;margin-bottom:1px;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>";

            $output .= "<tr><th colspan='2'>{$this->getName()}</th></tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('totalpoints', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' ><span id='gt_total_points'>{$this->getTotalPoints()}</span> / {$totalMaxPoints}</td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($this->getUserComments(), true)."</textarea></td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' id='gt_criterion_value_{$this->getID()}' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'>{$this->getCellEdit(true, true)}</td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $date = ($this->getUserAwardDate() > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                $output .= "<th>".get_string('date')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
            $output .= "</tr>";

        $output .= "</table>";



        // This is the overall criterion
        $output .= "<table class='gt_detail_criterion_overall_table gt_detail_criterion_overall_table_2 gt_criterion_wrapper' style='background-color:#fff;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$parent->getID()}'>";

            $output .= "<tr><th colspan='2'>{$parent->getName()}</th></tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$parent->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($parent->getUserComments(), true)."</textarea></td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                $output .= "<td colspan='2' id='gt_criterion_value_{$parent->getID()}' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$parent->getID()}'>{$parent->getCellEdit(true, true)}</td>";
            $output .= "</tr>";

            $output .= "<tr class=''>";
                $date = ($parent->getUserAwardDate() > 0) ? $parent->getUserAwardDate('d-m-Y') : '';
                $output .= "<th>".get_string('date')."</th>";
                $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$unit->getID()}' cID='{$parent->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
            $output .= "</tr>";

        $output .= "</table>";

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
            $output .= "<span class='gt-popup-unitname'>".$this->getUserAward()->getName()."</span>";
            $output .= "<br><br>";
            $output .= get_string('lastupdatedby', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdateByUserID() > 0) ? $this->getUserLastUpdateBy()->getName() : '-') . '</b>';
            $output .= "&nbsp;&nbsp;&nbsp;";
            $output .= get_string('updatetime', 'block_gradetracker') . ' <b>'.( ($this->getUserAwardDateOrUpdateDate()) ? $this->getUserAwardDateOrUpdateDate('D jS M Y, H:i') : '-' ) . '</b>';

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
        $criteria = $this->getChildOfSubCritType("Criterion");
        
        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('criteria', 'block_gradetracker')."</div>";
        $output .= "<div class='gt_left'>";

        $output .= "<table class='gt_criterion_info_popup_sub_table'>";

            $output .= "<tr>";
            
                $output .= "<th style='border-top:none;'>".get_string('criterion', 'block_gradetracker')."</th>";
                $output .= "<th style='border-top:none;'>".get_string('comments', 'block_gradetracker')."</th>";
                
                if ($ranges)
                {
                    foreach($ranges as $range)
                    {
                        $output .= "<th>{$range->getName()}</th>";
                    }
                }
                else
                {
                    $output .= "<th>".get_string('points', 'block_gradetracker')."</th>";
                }
                
            $output .= "</tr>";
        
            
            // If there are sub criteria
            if ($criteria)
            {
                foreach($criteria as $crit)
                {
                    $output .= "<tr>";
                        $output .= "<td>{$crit->getName()}</td>";
                        $output .= "<td><em>{$crit->getUserComments()}</em></td>";
                        if ($ranges)
                        {
                            foreach($ranges as $range)
                            {
                                $output .= "<td class='gt_c'>{$crit->getCriterionPoints($range)}</td>";
                            }
                        }
                        else
                        {
                            $output .= "<td class='gt_c'>{$crit->getCriterionPoints()}</td>";
                        }
                    $output .= "</tr>";
                }
            }
            
            // Range Comments
            if ($ranges)
            {
                $output .= "<tr>";
                $output .= "<th colspan='2' class='gt_right'>".get_string('comments', 'block_gradetracker')."</th>";
                foreach($ranges as $range)
                {
                    $output .= "<td><em>{$range->getUserComments()}</em></td>";
                }
                $output .= "</tr>";
            }
            
            // Range awards
            if ($ranges)
            {
                $output .= "<tr>";
                $output .= "<th colspan='2'>".get_string('awards', 'block_gradetracker')."</th>";
                foreach($ranges as $range)
                {
                    $output .= "<td class='gt_c gt_bold'>{$range->getUserAward()->getName()}</td>";
                }
                $output .= "</tr>";
            }

        $output .= "</table>";

        $output .= "</div>";
            
        $output .= "</div>";
                
        return $output;
        
    }
    
    
    
    
    /**
     * Get the total number of points awarded in this criterion
     * @return string
     */
    public function getTotalPoints(){
                
        if (!$this->student) return false;
        
        $total = 0;
        
        // Is this a range we are looking at?
        if ($this->subCritType == "Range")
        {
            
            global $DB;
            $record = $DB->get_record_sql("SELECT SUM(value) as ttl
                                           FROM {bcgt_user_ranges}
                                           WHERE userid = ? AND rangeid = ?", array($this->student->id, $this->id));
            
            return (!is_null($record->ttl)) ? $record->ttl : 0;
            
        }
        
        // Only do it this way if we don't have ranges
        $ranges = $this->getChildOfSubCritType("Range");
        if (!$ranges){
            
            $children = $this->getChildOfSubCritType("Criterion");
            if ($children)
            {
                foreach($children as $child)
                {
                    $maxPoints = $child->getAttribute('maxpoints');
                    if ($maxPoints > 0)
                    {
                        $points = $child->getUserCustomValue();
                        if ($points > 0)
                        {
                            $total += $points;
                        }
                    }
                }
            }
            
        }
        
        return $total;
        
    }
    
    /**
     * Get the conversion chart for this criterion
     * @return type
     */
    private function getConversionChart(){
        
        $return = array();
        
        // Met values
        $GradingStructure = new \GT\CriteriaAwardStructure($this->gradingStructureID);
        $awards = $GradingStructure->getAwards(true);
        
        if ($awards)
        {
            foreach($awards as $award)
            {
                $points = $this->getAttribute('conversion_chart_' . $award->getID());
                // Don't bother to include it if we didn;t give it a value, it's of no use to us
                if ($points && $points > 0)
                {
                    $return[$award->getID()] = $points;
                }
            }
        }
        
        // Sort by highest first
        arsort($return);
 
        return $return;
        
    }
    
    
    public function autoCalculateAwardFromRanges(){
                
        $ranges = $this->getChildOfSubCritType("Range");
        $awards = array();
        $userAward = false;
        
        $cntRanges = count($ranges);
                
        if ($ranges)
        {
            foreach($ranges as $range)
            {
                $award = $range->getUserAward();
                if ($award->isMet())
                {
                    $awards[] = $award;
                }
            }
        }
                        
        // If all of the ranges are met, work out the award to give the overall criterion
        if ($awards && count($awards) == $cntRanges)
        {
            
            $points = 0;
            foreach($awards as $award)
            {
                $points += $award->getPoints();
            }
            
            $avg = round( ($points / $cntRanges), 1 );
                                    
            // Grading structure of the overall criterion
            $grading = new \GT\CriteriaAwardStructure($this->getGradingStructureID());
            $possibleAwards = $grading->getAwards(true);
            
                        
            // Loop through them and see if we have one with point ranges
            if ($possibleAwards)
            {
                foreach($possibleAwards as $possibleAward)
                {
                    if ($avg >= $possibleAward->getPointsLower() && $avg <= $possibleAward->getPointsUpper())
                    {
                        $userAward = $possibleAward;
                    }
                }
            }
                        
            if ($userAward)
            {
                $this->setUserAward($userAward);
                $this->saveUser();
                return $userAward->getID();
            }
            
        }
        
        return false;
        
    }
    
    /**
     * From the conversion chart work out what award the criterion or the range should have from the points
     */
    public function autoCalculateAwardFromConversionChart(){
        
        $userAwardID = false;
        $allScored = true;
                
        // Must all the criteria be scored in order to calculate an award?
        if ($this->parentCritID){
            $parent = $this->getUnit()->getCriterion($this->parentCritID);
            $mustBeScored = ($parent->getAttribute('reqallscored') == 1);
            $children = $parent->getChildOfSubCritType("Criterion");
        } else {
            $mustBeScored = ($this->getAttribute('reqallscored') == 1);
            $children = $this->getChildOfSubCritType("Criterion");
        }
                
        // First check if we actually have a conversion chart with valid scores, if not, no point continuing
        $conversionChart = $this->getConversionChart();
        if (!$conversionChart) return null;
                

        if ($children)
        {

            // If this is a range
            if ($this->subCritType == "Range")
            {

                // First check if they are all scored (if they need to be)
                if ($mustBeScored)
                {

                    foreach($children as $child)
                    {

                        $child->loadStudent($this->student);
                        $maxPoints = $parent->getAttribute("maxpoints_{$child->getID()}_{$this->getID()}");

                        // If this one has not been given a points score, stop as we can go no further
                        if ($maxPoints > 0 && $child->getCriterionPoints($this) <= 0)
                        {
                            $allScored = false;
                        }

                    }

                }

            }
            else
            {

                // First check if they are all scored (if they need to be)
                if ($mustBeScored)
                {

                    foreach($children as $child)
                    {

                        $maxPoints = $child->getAttribute('maxpoints');

                        // If this one has not been given a points score, stop as we can go no further
                        if ($maxPoints > 0 && $child->getCriterionPoints() <= 0)
                        {
                            $allScored = false;
                        }

                    }

                }

            }

            // All is well so far, we let's get our total points and see where we sit in the chart
            if ($allScored)
            {
                $totalPoints = $this->getTotalPoints();

                foreach($conversionChart as $awardID => $chartPoints)
                {
                    if ($totalPoints >= $chartPoints)
                    {
                        $userAwardID = $awardID;
                        break;
                    }
                }
            }

        }
                                                    
       
        // If we have a new award ID to set, do it now
        if ($allScored && $userAwardID){
            $this->setUserAwardID($userAwardID);
            $this->saveUser();
            return $userAwardID;
        } elseif (!$allScored || ($allScored && !$userAwardID)){
            $this->setUserAwardID(null);
            $this->saveUser();
            return false;
        }
        
        return null;
        
    }
    
    /**
     * Get the value assigned to a range criterion
     * @global \GT\Criteria\type $DB
     * @param type $rangeID
     * @param type $critID
     * @return type
     */
    private function getRangeCriterionValue($rangeID, $critID){
        
        global $DB;

        if (!$this->student) return false;
        
        $record = $DB->get_record("bcgt_user_ranges", array("userid" => $this->student->id, "rangeid" => $rangeID, "critid" => $critID));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Get the points score awarded to a particular criterion
     * @param type $range
     * @return type
     */
    private function getCriterionPoints($range = false){
        
        if (!$range){
            return $this->getUserCustomValue();
        } else {
            return $this->getRangeCriterionValue($range->getID(), $this->getID());
        }
        
    }
    
    /**
     * Set the value for a range criterion
     * @global \GT\Criteria\type $DB
     * @param type $rangeID
     * @param type $critID
     * @param type $value
     * @return boolean
     */
    public function setRangeCriterionValue($rangeID, $critID, $value){
        
        global $DB;
                       
        if (!$this->student) return false;
        
        $record = $DB->get_record("bcgt_user_ranges", array("userid" => $this->student->id, "rangeid" => $rangeID, "critid" => $critID));
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_RANGE;
        $Log->beforejson = array(
            'value' => ($record) ? $record->value : null
        );
        // ------------ Logging Info
        
        
        if ($record)
        {
            $record->value = $value;
            $result = $DB->update_record("bcgt_user_ranges", $record);
        }
        else
        {
            $ins = new \stdClass();
            $ins->userid = $this->student->id;
            $ins->rangeid = $rangeID;
            $ins->critid = $critID;
            $ins->value = $value;
            $result = $DB->insert_record("bcgt_user_ranges", $ins);
        }
        
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'value' => $value
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                \GT\Log::GT_LOG_ATT_UNITID => $this->unitID,
                \GT\Log::GT_LOG_ATT_CRITID => $critID,
                \GT\Log::GT_LOG_ATT_RANGEID => $rangeID,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
        
        
        
        return $result;
        
    }
    
    
    
    /**
     * Get the options to be displayed for this criterion type in the criteria creation form
=     * @return string
     */
    public function getFormOptions(){
        
        $return = array();
        
        $obj = new \stdClass();
        $obj->type = 'CHECKBOX';
        $obj->name = 'reqallscored';
        $obj->label = get_string('reqallscored', 'block_gradetracker');
        $obj->value = ( $this->isValid() ) ? $this->getAttribute($obj->name) : '';
        
        $return[] = $obj;
        
        return $return;
        
    }
    
    
    /**
     * Get the maximum possible points we can assign to numeric criteria
     * @global type $GT
     * @return type
     */
    public static function getMaxPoints(){
        
        global $GT;
        
        $value = $GT->getSetting('numeric_criteria_max_points');
        return ($value) ? (int)$value : self::DEFAULT_MAX_POINTS;
        
    }
    
    public function save() {
        $type = \GT\QualificationStructureLevel::getByName("Numeric Criteria");
        $this->type = $type->getID();
        parent::save();
    }
    
}
