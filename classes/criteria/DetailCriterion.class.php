<?php
/**
 * GT\Criteria\DetailCriterion
 *
 * This is the class for Detail Criteria
 * 
 * These allow you to enter free text against the criterion, to record what was done to achieve it
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

class DetailCriterion extends \GT\Criterion {
    
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
                $this->deleted = $check->deleted;

            }
        
        }
        
    }
    
    /**
     * Get the options to be displayed for this criterion type in the criteria creation form
=     * @return string
     */
    public function getFormOptions(){
        
        $return = array();
        
        $obj = new \stdClass();
        $obj->type = 'CHECKBOX';
        $obj->name = 'readonly';
        $obj->label = get_string('readonly:q', 'block_gradetracker');
        $obj->value = ( $this->isValid() ) ? $this->getAttribute('readonly') : '';
        
        $return[] = $obj;
        
        return $return;
        
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
                
        $output .= "<div class='gt_detail_criterion_popup'>";        
        
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

            $output .= "<p id='gt_detail_popup_loader' class='gt_c gt_hidden'><img src='".$OUTPUT->pix_url('i/loading_small')."' alt='".get_string('loading', 'block_gradetracker')."' /></p>";
            $output .= "<div id='gt_detail_popup_error' class='gt_alert_bad gt_left gt_hidden'>".get_string('errors:save', 'block_gradetracker')."</div>";
            $output .= "<div id='gt_detail_popup_success' class='gt_alert_good gt_left gt_hidden'>".get_string('saved', 'block_gradetracker')."</div>";
            
            if ( $this->countChildLevels() > 0 )
            {

                $output .= "<p><i>{$this->getDescription()}</i></p>";
                $output .= "<br>";

                $output .= "<table class='gt_popup_table'>";

                    $output .= "<tr class='gt_lightblue'><th>".get_string('name')."</th><th>".get_string('details', 'block_gradetracker')."</th><th>".get_string('customvalue', 'block_gradetracker')."</th><th>".get_string('comments', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th><th>".get_string('date')."</th></tr>";

                    if ($this->getChildren())
                    {

                        foreach($this->getChildren() as $child)
                        {

                            $child->loadStudent( $this->student );
                            $date = ($child->getUserAwardDate() > 0) ? $child->getUserAwardDate('d-m-Y') : '';

                            $output .= "<tr class='gt_detail_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'>";
                                $output .= "<td>{$child->getName()}</td>";
                                $output .= "<td>{$child->getDescription()}</td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'><textarea type='custom_value' class='gt_update_custom_value'>".\gt_html($child->getUserCustomValue())."</textarea></td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($child->getUserComments(), true)."</textarea></td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}' cName='".\gt_html($child->getName())."'>{$child->getCellEdit(true, true)}</td>";
                                $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$child->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                            $output .= "</tr>";
                            
                            // Does this have children itself?
                            if ($child->getChildren())
                            {
                                
                                foreach($child->getChildren() as $grandChild)
                                {
                                    
                                    $grandChild->loadStudent( $this->student );
                                    $date = ($grandChild->getUserAwardDate() > 0) ? $grandChild->getUserAwardDate('d-m-Y') : '';
                                    $readonly = $grandChild->getAttribute('readonly');
                                    
                                    $output .= "<tr class='gt_detail_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$grandChild->getID()}'>";
                                        $output .= "<td>{$grandChild->getName()}</td>";
                                        $output .= "<td>{$grandChild->getDescription()}</td>";
                                        
                                        if ( $readonly != 1){
                                            $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$grandChild->getID()}'><textarea type='custom_value' class='gt_update_custom_value'>".\gt_html($grandChild->getUserCustomValue())."</textarea></td>";
                                            $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$grandChild->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($grandChild->getUserComments(), true)."</textarea></td>";
                                            $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$grandChild->getID()}' cName='".\gt_html($grandChild->getName())."'>{$grandChild->getCellEdit(true, true)}</td>";
                                            $output .= "<td class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$grandChild->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                                        } else {
                                            $output .= "<td></td><td></td><td></td><td></td>";
                                        }
                                        
                                    $output .= "</tr>";
                                    
                                }
                                
                            }

                        }

                    }
                    
                $output .= "</table>";
                               
                $output .= "<table class='gt_detail_criterion_overall_table gt_detail_criterion_wrapper' style='background-color:#fff;' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>";
                
                    // Overall for this Criterion
                    $output .= "<tr class='gt_double_top'>";
                        $output .= "<th>".get_string('customvalue', 'block_gradetracker')."</th>";
                        $output .= "<td colspan='3' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea type='custom_value' class='gt_update_custom_value'>".\gt_html($this->userCustomValue)."</textarea></td>";
                    $output .= "</tr>";
                    
                    $output .= "<tr class=''>";
                        $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                        $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($this->userComments, true)."</textarea></td>";
                    $output .= "</tr>";


                    $output .= "<tr class=''>";
                        $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                        $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>{$this->getCellEdit(true, true)}</td>";
                    $output .= "</tr>";

                    $output .= "<tr class=''>";
                        $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                        $output .= "<th>".get_string('date')."</th>";
                        $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                    $output .= "</tr>";

                
                $output .= "</table>";
                
               

            }
            else
            {

                $output .= "<div class='gt_detail_criterion_wrapper' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>";
                
                    $output .= "<div class='gt_form_panel'>";
                        $output .= "<div class='gt_form_panel_heading'>".get_string('customvalue', 'block_gradetracker')."</div>";
                        $output .= "<div class='gt_form_panel_body'>";
                            $output .= "<textarea class='gt_criterion_detail_textbox gt_update_custom_value' type='custom_value' qID='{$this->qualID}' uID='{$this->unitID}' cID='{$this->id}' sID='{$this->student->id}'>".\gt_html($this->userCustomValue)."</textarea>";
                        $output .= "</div>";
                    $output .= "</div>";



                    $output .= "<table class='gt_detail_criterion_overall_table'>";

                        // Overall for this Criterion
                        $output .= "<tr class='gt_double_top'>";
                            $output .= "<th>".get_string('comments', 'block_gradetracker')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><textarea type='comments' class='gt_update_comments'>".\gt_html($this->userComments, true)."</textarea></td>";
                        $output .= "</tr>";


                        $output .= "<tr class=''>";
                            $output .= "<th>".get_string('value', 'block_gradetracker')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'>{$this->getCellEdit(true, true)}</td>";
                        $output .= "</tr>";

                        $output .= "<tr class=''>";
                            $date = ($this->userAwardDate > 0) ? $this->getUserAwardDate('d-m-Y') : '';
                            $output .= "<th>".get_string('date')."</th>";
                            $output .= "<td colspan='2' class='gt_grid_cell' sID='{$this->student->id}' qID='{$qualification->getID()}' uID='{$this->unitID}' cID='{$this->getID()}'><input type='text' class='gt_datepicker gt_criterion_award_date' value='{$date}' /></td>";
                        $output .= "</tr>";


                    $output .= "</table>";
                $output .= "</div>";

            }

            $output .= "</div>";
            
        $output .= "</div>";    

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
        
        if ($this->getAttribute('readonly') != 1){
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('value', 'block_gradetracker')."</div>";
            $output .= "<div class='gt_c'>";

                $output .= "<img class='gt_award_icon' src='{$this->getUserAward()->getImageURL()}' alt='{$this->getUserAward()->getShortName()}' /><br>";
                $output .= "<span class='gt-popup-unitname'>".$this->getUserAward()->getName()."</span>";
                $output .= "<br><br>";
                $output .= get_string('lastupdatedby', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdateByUserID() > 0) ? $this->getUserLastUpdateBy()->getName() : '-') . '</b>';
                $output .= "&nbsp;&nbsp;&nbsp;";
                $output .= get_string('updatetime', 'block_gradetracker') . ' <b>'.( ($this->getUserAwardDateOrUpdateDate()) ? $this->getUserAwardDateOrUpdateDate('D jS M Y, H:i') : '-' ) . '</b>';

            $output .= "</div>";
        
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('customvalue', 'block_gradetracker')."</div>";
            $output .= "<div class='gt_left'>";
                $output .= gt_html($this->userCustomValue, true);
            $output .= "</div>";
        }
        
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
                $output .= "<table class='gt_criterion_info_popup_sub_table'>";
                $output .= "<tr><th>".get_string('name')."</th><th>".get_string('details', 'block_gradetracker')."</th><th>".get_string('comments', 'block_gradetracker')."</th><th>".get_string('customvalue', 'block_gradetracker')."</th><th>".get_string('value', 'block_gradetracker')."</th></tr>";
                foreach($this->getChildren() as $child)
                {
                    $output .= "<tr>";
                        $output .= "<td>{$child->getName()}</td>";
                        $output .= "<td>{$child->getDescription()}</td>";
                        
                        // Make sure we can mark it
                        if ($child->getAttribute('readonly') != 1){
                            $output .= "<td>".\gt_html($child->getUserComments())."</td>";
                            $output .= "<td>".\gt_html($child->getUserCustomValue())."</td>";
                            $output .= "<td>{$child->getUserAward()->getName()}</td>";
                        } else {
                            $output .= "<td></td><td></td><td></td>";
                        }
                        
                    $output .= "</tr>";
                    
                    if ($child->getChildren())
                    {
                        
                        foreach($child->getChildren() as $grandChild)
                        {
                            $output .= "<tr>";
                                $output .= "<td>{$grandChild->getName()}</td>";
                                $output .= "<td>{$grandChild->getDescription()}</td>";
                                
                                // Make sure we can mark it
                                if ($grandChild->getAttribute('readonly') != 1){
                                    $output .= "<td>".\gt_html($grandChild->getUserComments())."</td>";
                                    $output .= "<td>".\gt_html($grandChild->getUserCustomValue())."</td>";
                                    $output .= "<td>{$grandChild->getUserAward()->getName()}</td>";
                                } else {
                                    $output .= "<td></td><td></td><td></td>";
                                }
                                
                            $output .= "</tr>";
                        }
                        
                    }
                    
                }
            $output .= "</div>";
        }
            
        $output .= "</div>";
                
        return $output;
        
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
        
        // If it's readonly, don't do anything
        if ($this->getAttribute('readonly') == 1){
            return $output;
        }
        
        if (!$fromSub)
        {
            // Show the icon to pop it up into a...popup
            $img = ($this->getUserAward()->isMet()) ? 'openA.png' : 'open.png';
            $output .= "<a href='#' class='gt_open_detail_criterion_window'>";
                $output .= "<img src='{$CFG->wwwroot}/blocks/gradetracker/pix/symbols/default/{$img}' alt='".get_string('open', 'block_gradetracker')."' />";
            $output .= "</a>";
        }
        else
        {
            
            $values = $this->getPossibleValues();

            $output .= "<select id='{$elID}' name='gt_criteria[{$this->qualID}][{$this->unitID}][{$this->id}]' class='gt_criterion_select'>";

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
                            
        }
        
        return $output;
        
    }
    
    
    /**
     * Check to make sure the criterion has no errors
     * @param type $parent
     * @return type
     */
    public function hasNoErrors($parent = false) {
        
        parent::hasNoErrors($parent);
        
        // For the detail criteria
        
        // Check grading structure - Can be blank - This will mean a readonly criterion
        $QualStructure = new \GT\QualificationStructure($this->qualStructureID);
        $GradingStructures = $QualStructure->getCriteriaGradingStructures();
        
        if (!array_key_exists($this->gradingStructureID, $GradingStructures) && ctype_digit($this->gradingStructureID) && $this->gradingStructureID > 0){
            $this->errors[] = sprintf( get_string('errors:crit:gradingstructure', 'block_gradetracker'), $this->name );
        }
        
        return (!$this->errors);
        
    }
    
    public function save() {
        $type = \GT\QualificationStructureLevel::getByName("Detail Criteria");
        $this->type = $type->getID();
        parent::save();
    }
    
    
}
