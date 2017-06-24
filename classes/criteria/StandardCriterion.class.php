<?php
/**
 * GT\Criteria\StandardCriterion
 *
 * This is the class for Standard Criteria
 * 
 * These are the basic Criteria, which allow you to set a value either from a drop-down
 * or, if there is only 1 met value, from a tickbox
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

class StandardCriterion extends \GT\Criterion {
    
    /**
     * Construct the object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
        
            $check = $DB->get_record_sql("SELECT * FROM {bcgt_criteria} WHERE id = ?", array($id));
            if ($check)
            {

                $this->id = $check->id;
                $this->unitID = $check->unitid;
                $this->gradingStructureID = $check->gradingstructureid;
                $this->parentCritID = $check->parentcritid;
                $this->name = $check->name;
                $this->description = $check->description;
                $this->type = $check->type;
                $this->deleted = $check->deleted;

            }
        
        }
        
    }
    
    
    
    /**
     * Get the info for the info popup
     * @return string
     */
    public function getPopUpInfo(){
        
        $output = "";

        $qualification = $this->getQualification();
        $unit = $this->getUnit();
        $values = $this->getPossibleValues();

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
        
        if ($values)
        {
        
            $output .= "<img class='gt_award_icon' src='{$this->getUserAward()->getImageURL()}' alt='{$this->getUserAward()->getShortName()}' /><br>";
            $output .= "<span class='gt-popup-unitname'>{$this->getUserAward()->getName()}</span>";
            $output .= "<br><br>";
            $output .= get_string('lastupdatedby', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdateByUserID() > 0) ? $this->getUserLastUpdateBy()->getName() : '-') . '</b>';
            $output .= "&nbsp;&nbsp;&nbsp;";
            $output .= get_string('updatetime', 'block_gradetracker') . ' <b>'.( ($this->getUserAwardDateOrUpdateDate()) ? $this->getUserAwardDateOrUpdateDate('D jS M Y, H:i') : '-' ) . '</b>';
            
        }
        else
        {
            $output .= "<b>".get_string('readonly', 'block_gradetracker')."</b>";
        }
            
        $output .= "</div>";
        
        if ($this->hasUserComments())
        {
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('comments', 'block_gradetracker')."</div>";
            $output .= "<div class='gt_criterion_info_comments'>";
                $output .= gt_html($this->userComments, true);
            $output .= "</div>";
        }   
        
        // Sub Criteria
        if ($this->getChildren())
        {
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('subcriteria', 'block_gradetracker')."</div>";
            $output .= "<div class=''>";
                $output .= "<table class='gt_unit_popup_criteria_table'>";
                $output .= "<tr><th>".get_string('name')."</th><th>".get_string('details', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th><th>".get_string('comments', 'block_gradetracker')."</th></tr>";
                foreach($this->getChildren() as $child)
                {
                    $output .= "<tr>";
                        $output .= "<td>{$child->getName()}</td>";
                        $output .= "<td>{$child->getDescription()}</td>";
                        $output .= "<td>";
                            $output .= $child->getUserAward()->getShortName();
                            // If we are using DATE grading type, display that date awarded
                            if ($child->getAttribute('gradingtype') == 'DATE' && $child->getUserAwardDate() > 0)
                            {
                                $output .= " <small>({$child->getUserAwardDate('d-m-Y')})</small>";
                            }
                        $output .= "</td>";
                        $output .= "<td>".\gt_html($child->getUserComments())."</td>";
                    $output .= "</tr>";
                }
            $output .= "</div>";
        }
            
        $output .= "</div>";
                
        return $output;
        
    }
    
    /**
     * Get sub criteria popup content
     */
    public function getPopUpContent($access = false){
        
        global $OUTPUT;
        
        $adv = ($access == 'ae') ? true : false;
        
        $output = "";
                
        $this->loadChildren();
        
        if ( $this->countChildLevels() > 0 )
        {
            
            $qualification = $this->getQualification();
            $unit = $this->getUnit();
            
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
            
            $output .= "<span class='gt-popup-critname'>{$this->getName()}</span><br>";
            
            $output .= "<p><i>{$this->getDescription()}</i></p>";
            $output .= "<br>";
            
            $output .= "<p id='gt_popup_loader' class='gt_c gt_hidden'><img src='".$OUTPUT->pix_url('i/loading_small')."' alt='".get_string('loading', 'block_gradetracker')."' /></p>";
            $output .= "<div id='gt_popup_error' class='gt_alert_bad gt_left gt_hidden'>".get_string('errors:save', 'block_gradetracker')."</div>";
            $output .= "<div id='gt_popup_success' class='gt_alert_good gt_left gt_hidden'>".get_string('saved', 'block_gradetracker')."</div>";
            
            
            $output .= "<table class='gt_popup_table'>";
            
                $output .= "<tr class='gt_lightpink'><th>".get_string('name')."</th><th>".get_string('details', 'block_gradetracker')."</th><th>".get_string('comments', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th></tr>";
                
                if ($this->getChildren())
                {
                    
                    foreach($this->getChildren() as $child)
                    {
                        
                        $child->loadStudent( $this->student );
                        $gradingStructure = $child->getGradingStructure();
                        
                        $output .= "<tr class='gt_criterion_wrapper' sID='{$child->getStudent()->id}' qID='{$qualification->getID()}' uID='{$child->getUnitID()}' cID='{$child->getID()}'>";
                            $output .= "<td>{$child->getName()}</td>";
                            $output .= "<td>{$child->getDescription()}</td>";
                            if ($gradingStructure->isValid()){
                                $output .= "<td><textarea class='gt_update_comments gt_comments_sub_large'>{$child->getUserComments()}</textarea></td>";
                            } else {
                                $output .= "<td>-</td>";
                            }
                            $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}' cName='".\gt_html($child->getName())."' subPopUp='1'>{$child->getCellEdit($adv, true)}</td>";
                        $output .= "</tr>";
                        
                    }
                    
                }
                
                // Overall for this Criterion
                $output .= "<tr class='gt_pink gt_double_top gt_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>";
                    $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                    $output .= "<td colspan='3' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea class='gt_update_comments gt_comments_large'>".\gt_html($this->userComments, true)."</textarea></td>";
                $output .= "</tr>";
                
                
                $output .= "<tr class='gt_pink'>";
                    $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                    $output .= "<td colspan='3' class='gt_grid_cell gt_popup_overall_value' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>{$this->getCellEdit(true, true)}</td>";
                $output .= "</tr>";
                
                $output .= "<tr class='gt_pink'>";
                    $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                    $output .= "<th>".get_string('date')."</th>";
                    $output .= "<td colspan='3' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                $output .= "</tr>";
                
            $output .= "</table>";
            
            $output .= "</div>";
            
        }
                        
        return $output;
        
    }
    
    /**
     * Get the cell content if we are in an editing mode
     * @global type $CFG
     * @param type $advanced
     * @return type
     */
    protected function getCellEdit($advanced = false, $fromSub = false){
        
        global $CFG;
        
        $output = "";
        
        $count = $this->countChildLevels();
        
        $access = ($advanced) ? 'ae' : 'e';
                
        $elID = "S{$this->student->id}_Q{$this->qualID}_U{$this->unitID}_C{$this->id}";
        
        // If we have more than 1 child level, we will be using a popup box
        if ( (($this->parentCritID > 0 && $count > 0 && !$fromSub) || ($this->getAttribute('forcepopup') == 1 && $count && !$fromSub)) && $this->loadedFrom != 'external' )
        {
            
            $img = ($this->getUserAward()->isMet()) ? 'openA.png' : 'open.png';
            $output .= "<a href='#' class='gt_open_criterion_window'>";
                $output .= "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/symbols/default/{$img}' alt='".get_string('open', 'block_gradetracker')."' />";
            $output .= "</a>";
            
        }
        else
        {
        

            // In advanced we always use a select menu as we have numerous other values as well
            if ($advanced)
            {

                $values = $this->getPossibleValues();
                
                if ($values)
                {
                    
                    $output .= "<select id='{$elID}' name='gt_criteria[{$this->qualID}][{$this->unitID}][{$this->id}]' class='gt_criterion_select gt_criterion_select_{$access} gt_criterion_select_{$this->id}'>";

                        $output .= "<option value=''></option>";

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
                    
                    if (!$fromSub && $this->loadedFrom != 'external')
                    {
                        // Comment icon
                        $icon = (!$this->hasUserComments()) ? 'comment_add.png' : 'comment_edit.png';
                        $output .= "<br><img class='gt_comment_icon' src='{$CFG->wwwroot}/blocks/gradetracker/pix/{$icon}' alt='".get_string('comments', 'block_gradetracker')."' />";
                    }
                    
                }
                else
                {
                    $output .= "-";
                }

                
            }
            else
            {

                $values = $this->getPossibleValues(true);

                // If the grading type is DATE then we want to set a date rather than using a choice
                if ($values && $this->getAttribute('gradingtype') == 'DATE')
                {

                    $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                    $output .= "<input id='{$elID}' type='text' class='gt_criterion_date gt_datepicker' value='{$date}' />";

                }
                else
                {

                    // If we have multiple MET values, do a select menu
                    if ($values && count($values) > 1)
                    {

                        $output .= "<select id='{$elID}' class='gt_criterion_select'>";

                            $output .= "<option value=''></option>";

                            foreach($values as $award)
                            {
                                $sel = ($this->getUserAward() && $this->getUserAward()->getID() == $award->getID()) ? 'selected' : '';
                                $output .= "<option value='{$award->getID()}' {$sel} >{$award->getShortName()} - {$award->getName()}</option>";
                            }

                        $output .= "</select>";

                    }
                    elseif ($values && count($values) == 1)
                    {

                        // Otherwise if it's one do a checkbox
                        $value = reset($values);
                        $chk = ($this->getUserAward() && $this->getUserAward()->getID() == $value->getID()) ? 'checked' : '';
                        $output .= "<input id='{$elID}' class='gt_criterion_checkbox' type='checkbox' value='{$value->getID()}' {$chk} />";

                    }
                    else
                    {
                        $output .= "-";
                    }

                }

            }

        }
        
        return $output;
        
    }
    
    
     /**
     * Get the options to be displayed for this criterion type in the criteria creation form
=     * @return string
     */
    public function getFormOptions(){
        
        $return = array();        
        
        $obj = new \stdClass();
        $obj->type = 'CHECKBOX';
        $obj->name = 'forcepopup';
        $obj->label = get_string('forcepopup:q', 'block_gradetracker');
        $obj->value = ( $this->isValid() ) ? $this->getAttribute('forcepopup') : '';
        
        $return[] = $obj;
        
        return $return;
        
    }
    
    /**
     * Check to make sure the criterion has no errors
     * @param type $parent
     * @return type
     */
    public function hasNoErrors($parent = false) {
        
        parent::hasNoErrors($parent);
        
        // For the standard criteria
        
        // Check grading structure - Can be blank - This will mean a readonly criterion
        $QualStructure = new \GT\QualificationStructure($this->qualStructureID);
        $GradingStructures = $QualStructure->getCriteriaGradingStructures();
        
        if (!array_key_exists($this->gradingStructureID, $GradingStructures) && ctype_digit($this->gradingStructureID) && $this->gradingStructureID > 0){
            $this->errors[] = sprintf( get_string('errors:crit:gradingstructure', 'block_gradetracker'), $this->name );
        }
        
        return (!$this->errors);
        
    }
    
    public function save() {
        $type = \GT\QualificationStructureLevel::getByName("Standard Criteria");
        $this->type = $type->getID();
        parent::save();
    }
    
}
