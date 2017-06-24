<?php
/**
 * Assessment
 *
 * This class defines Assessments which can be created to be stored against the GT, 
 * e.g. Formal and Homework ones
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

class Assessment {
    
    private $id = false;
    private $type;
    private $name;
    private $details;
    private $date;
    private $deleted;
    
    private $qualification = false; // The qualification loaded into this assessment
    private $student = false; // The student loaded in
    private $userAssessmentRowID = false;
    private $userGrade = false;
    private $userCeta = false;
    private $userComments = false;
    private $userLastUpdate = false;
    private $userLastUpdateBy = false;
    private $userScore = null;
    
    private $qualIDs = array(); // The ids of the quals linked to this assessment
    private $errors = array();
        
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id){
            
            $record = $DB->get_record("bcgt_assessments", array("id" => $id));
            if ($record){
                
                $this->id = $record->id;
                $this->type = $record->type;
                $this->name = $record->name;
                $this->details = $record->details;
                $this->date = $record->assessmentdate;
                $this->deleted = $record->deleted;
                
                // Load all settings
                
                
            }
            
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false && !$this->isDeleted());
    }
    
    public function isDeleted(){
        return ($this->deleted == 1);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getDetails(){
        return $this->details;
    }
    
    public function getDate($format = false){
        if ($this->date == 0) return '';
        return ($format) ? date($format, $this->date) : $this->date;
    }
    
    public function getQualification(){
        return $this->qualification;
    }
    
    public function getStudent(){
        return $this->student;
    }
    
    public function getUserAssessmentRowID(){
        return $this->userAssessmentRowID;
    }
    
    public function getUserGrade(){
        return ($this->userGrade instanceof \GT\CriteriaAward) ? $this->userGrade : new \GT\CriteriaAward();
    }
    
    public function getUserCeta(){
        return ($this->userCeta instanceof \GT\QualificationAward) ? $this->userCeta : new \GT\QualificationAward();
    }
    
    public function getUserComments(){
        return trim($this->userComments);
    }
    
    public function getUserLastUpdate($format = false){
        return ($format) ? date($format, $this->userLastUpdate) : $this->userLastUpdate;
    }
    
    public function getUserLastUpdateByUserID(){
        return $this->userLastUpdateBy;
    }
    
    public function getUserLastUpdateBy(){
        return new \GT\User($this->userLastUpdateBy);
    }
    
    public function getUserScore(){
        return $this->userScore;
    }
    
    /**
     * Does this assessment have user comments?
     * @return type
     */
    public function hasUserComments(){
        $comments = $this->getUserComments();
        return (strlen($comments) > 0);
    }
        
    
    public function setID($id){
        $this->id = $id;
        return $this;
    }
    
    public function setType($type){
        $this->type = trim($type);
        return $this;
    }
    
    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function setDetails($details){
        $this->details = trim($details);
        return $this;
    }
    
    public function setDate($date){
        $this->date = $date;
        return $this;
    }
    
    /**
     * Clear the loaded student
     */
    private function clearStudent(){
        
        $this->student = false;
        $this->userAssessmentRowID = false;
        $this->userGrade = false;
        $this->userCeta = false;
        $this->userComments = false;
        $this->userLastUpdate = false;
        $this->userLastUpdateBy = false;
        $this->userScore = null;
        $this->_userRow = false;
        
    }
    
    /**
     * Reload the currently loaded student, incase we have loaded in a new Qualification to the assessment
     * and we need to get updated user comments and grades and such
     */
    public function reloadStudent(){
        
        // If we already have loaded the student, reload it
        if ($this->student){
            $this->loadStudent($this->student->id);
        }
        
    }
    
    /**
     * Load a student into the assessment
     * The qualification must be loaded first, otherwise we don't know which qualificationid to use
     * @global \GT\type $DB
     * @param type $studentID
     * @return boolean
     */
    public function loadStudent($studentID){
        
        global $DB;
        
        $this->clearStudent();
                
        // If we've already loaded this student, don't do it again
        if ($this->student && ( (is_numeric($studentID) && $this->student->id == $studentID) || $studentID instanceof \GT\User && $this->student->id == $studentID->id )){
            return true;
        }
                
        if (!$this->qualification){
            return false;
        }
        
        if ($studentID instanceof \GT\User){
            $student = $studentID;
        } else {
            $student = new \GT\User($studentID);
        }
        
        if ($student->isValid()){
            
            $this->student = $student;
            
            // Get grade for this assessment
            $record = $DB->get_record("bcgt_user_assessments", array("userid" => $this->student->id, "assessmentid" => $this->id, "qualid" => $this->qualification->getID()));
            $this->_userRow = $record;
            if ($record){
                
                $this->userAssessmentRowID = $record->id;
                $this->userGrade = new \GT\CriteriaAward($record->grade);
                $this->userCeta = new \GT\QualificationAward($record->ceta);
                $this->userComments = $record->comments;
                $this->userLastUpdate = $record->lastupdate;
                $this->userLastUpdateBy = $record->lastupdateby;
                $this->userScore = $record->score;
                                
            }
                        
        }
        
        return true;
        
    }
    
    /**
     * Load the qualification into the assessment
     * @param \GT\Qualification $qual
     */
    public function setQualification(\GT\Qualification $qual){
        $this->qualification = $qual;
    }
    
    public function setUserGrade(\GT\CriteriaAward $award){
        $this->userGrade = $award;
    }
    
    public function setUserCeta(\GT\QualificationAward $award){
        $this->userCeta = $award;
    }
    
    public function setUserComments($comments){
        // Strip any invalid characters (e.g. quotes from Word)
        $comments = \gt_convert_to_utf8($comments);
        $this->userComments = trim($comments);
    }
    
    public function setUserScore($score){
        $this->userScore = $score;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    public function getQuals(){
        
        if (!$this->qualIDs){
            $this->loadQuals();
        }
        
        return $this->qualIDs;
        
    }
    
    public function loadQuals(){
        
        global $DB;
        
        $this->qualIDs = array();
        $records = $DB->get_records_sql("SELECT aq.* 
                                        FROM {bcgt_assessment_quals} aq
                                        INNER JOIN {bcgt_qualifications} q ON q.id = aq.qualid
                                        WHERE q.deleted = 0
                                        AND aq.assessmentid = ?", array($this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                $this->qualIDs[$record->qualid] = $record->qualid;
            }
        }
                
    }
        
    public function setQualID($id){
        $this->qualIDs[$id] = $id;
    }
      
    /**
     * Count the number of quals linked to this assessment
     * @return type
     */
    public function countQuals(){
        
        $quals = $this->getQuals();
        return count($quals);
        
    }
    
    /**
     * Check which grading structure should be used for this Assessment on a particular Qualification
     * @return boolean
     */
    public function getQualificationAssessmentGradingStructure(){
        
        if (!$this->qualification){
            return false;
        }
        
        $GradingStructure = false;
        $gradingStructureIDBuild = $this->getSetting('grading_structure_qual_build_' . $this->qualification->getBuildID());
        $gradingStructureIDStructure = $this->getSetting('grading_structure_qual_structure_' . $this->qualification->getStructureID());

        // First check for one from this build
        if ($gradingStructureIDBuild && (int)$gradingStructureIDBuild > 0)
        {
            $GradingStructure = new \GT\CriteriaAwardStructure($gradingStructureIDBuild);
            if (!$GradingStructure->isValid() || !$GradingStructure->isEnabled() || $GradingStructure->isDeleted())
            {
                $GradingStructure = false;
            }
        }

        // Didn't find one for this build, so check one for this structure
        if (!$GradingStructure && $gradingStructureIDStructure && (int)$gradingStructureIDStructure > 0)
        {
            $GradingStructure = new \GT\CriteriaAwardStructure($gradingStructureIDStructure);
            if (!$GradingStructure->isValid() || !$GradingStructure->isEnabled() || $GradingStructure->isDeleted())
            {
                $GradingStructure = false;
            }
        }
        
        return $GradingStructure;
        
    }
    
    
    public function getGradeByID($id){
        
        $structure = $this->getQualificationAssessmentGradingStructure();
        $award = $structure->getAward($id);
        return ($award);
        
    }
    
    /**
     * Get the cell for the assessment's grade column
     * @global type $User
     * @param string $access
     * @param int $studentID
     * @return string
     */
    public function getGradeCell($access, $studentID = false){
        
        global $User;
        
        if (!$this->qualification){
            return false;
        }
            
        if ($studentID){
            $this->loadStudent($studentID);
        }
        
        if (!$this->student){
            return false;
        }
        
        $gradingMethod = $this->getSetting('grading_method');
        $grade = $this->getUserGrade();
        $grade->setDefaultName('-');
                
        // No advanced editing
        if ($access == 'ae'){
            $access = 'e';
        }
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( $access == 'e' && !$User->canEditQual($this->qualification->getID()) ){
            $access = 'v';
        }
        
        $output = "";
        
        if ($access == 'e'){
            
            // Numeric grading
            if ($gradingMethod == 'numeric')
            {
                
                $score = $this->getUserScore();
                $min = (int)$this->getSetting('numeric_grading_min');
                $max = (int)$this->getSetting('numeric_grading_max');
                
                $output .= "<select class='gt_assessment_select' gradingMethod='numeric'>";
                
                    $output .= "<option value=''></option>";
                    
                    for ($i = $min; $i <= $max; $i++)
                    {
                        $sel = ($score == $i) ? 'selected' : '';
                        $output .= "<option value='{$i}' {$sel}>{$i}</option>";
                    }
                       
                $output .= "</select>";
                
            }
            else
            {
            
                // Check to see which grading structure we are using - one set for the build or structure
                $GradingStructure = $this->getQualificationAssessmentGradingStructure();
                if ($GradingStructure && $GradingStructure->isValid() && !$GradingStructure->isDeleted() && $GradingStructure->isEnabled())
                {

                    $awards = $GradingStructure->getAwards(false, 'desc');

                    $output .= "<select class='gt_assessment_select'>";
                        $output .= "<option value=''></option>";
                        if ($awards)
                        {
                            foreach($awards as $award)
                            {
                                $sel = ($award->getID() == $grade->getID()) ? 'selected' : '';
                                $output .= "<option value='{$award->getID()}' {$sel}>{$award->getShortName()}</option>";
                            }
                        }
                    $output .= "</select>";

                }
                else
                {
                    $output .= get_string('invalidgradingstructure', 'block_gradetracker');
                }
            
            }
            
        } else {
            
            if ($gradingMethod == 'numeric')
            {
                
                $value = $this->getUserScore();
                
                // Set default of '-' if it's not a valid score
                if ($value === false || is_null($value)){
                    $value = '-';
                }
                
                $output .= $value;
                
            }
            else
            {
            
                // If we are using CETAs as well, it would look stupid having one image and one text, so display as text
                if ($this->qualification->isFeatureEnabledByName('cetagrades')){
                    $output .= $grade->getShortName();
                } else {            
                    $output .= "<img class='gt_award_icon' src='{$grade->getImageURL()}' alt='{$grade->getShortName()}' title='{$grade->getShortName()}' />";
                }
            
            }
            
        }
                        
        return $output;
        
    }
    
    /**
     * Get the cell for the assessment's CETA column
     * @global \GT\type $User
     * @param string $access
     * @return boolean
     */
    public function getCetaCell($access, $studentID = false){
        
        global $User;
        
        if (!$this->qualification){
            return false;
        }
        
        if ($studentID){
            $this->loadStudent($studentID);
        }
        
        if (!$this->student){
            return false;
        }
        
        $ceta = $this->getUserCeta();
        $ceta->setDefaultName('-');
                
        // No advanced editing
        if ($access == 'ae'){
            $access = 'e';
        }
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( ($access == 'e' || $access == 'ae') && !$User->canEditQual($this->qualification->getID()) ){
            $access = 'v';
        }
        
        $output = "";
        
        // Edit
        if ($access == 'e'){
            
            // CETA Grades use the Qualification Build awards, as these are what they can actually get at the
            // end, so this is what we need to pick from for currently expected to achieve
            $QualBuild = $this->qualification->getBuild();            
            if ($QualBuild && $QualBuild->isValid() && !$QualBuild->isDeleted())
            {
            
                $awards = $QualBuild->getAwards('desc');
                
                $output .= "<select class='gt_assessment_select'>";
                    $output .= "<option value=''></option>";
                    if ($awards)
                    {
                        foreach($awards as $award)
                        {
                            $sel = ($award->getID() == $ceta->getID()) ? 'selected' : '';
                            $output .= "<option value='{$award->getID()}' {$sel}>{$award->getName()}</option>";
                        }
                    }
                $output .= "</select>";
            
            }
            else
            {
                $output .= get_string('invalidgradingstructure', 'block_gradetracker');
            }
            
        } else {
            // View
            $output .= $ceta->getName();
        }
        
        
        
        return $output;
        
    }
    
    /**
     * Get the cell when exporting to an excel spreadsheet
     * @param type $objPHPExcel
     * @param type $rowNum
     * @param type $letter
     */
    public function getExcelGradeCell(&$objPHPExcel, $rowNum, $letter)
    {
                
        if (!$this->qualification){
            return false;
        }
                
        if (!$this->student){
            return false;
        }
        
        $grade = $this->getUserGrade();
        $grade->setDefaultName('');
        
        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $grade->getShortName());
        
        $gradingMethod = $this->getSetting('grading_method');
        if ($gradingMethod == 'numeric')
        {
            
            // Set the score
            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $this->getUserScore());
            
            $min = (int)$this->getSetting('numeric_grading_min');
            $max = (int)$this->getSetting('numeric_grading_max');
            $possibleValues = range($min, $max);
            $possibleValuesString = '"'.implode(",", $possibleValues).'"';

            // Imploded list of values can't be more than 255 characters long, if it is, don't use data validation
            // Just have a normal cell
            if (strlen($possibleValuesString) <= 255)
            {
            
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("{$letter}{$rowNum}")->getDataValidation();
                $objValidation->setType( \PHPExcel_Cell_DataValidation::TYPE_LIST );
                $objValidation->setErrorStyle( \PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
                $objValidation->setAllowBlank(true);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('input error');
                $objValidation->setError( get_string('import:datasheet:process:error:value', 'block_gradetracker') );
                $objValidation->setFormula1($possibleValuesString);
            
            }
            
            
        }
        else
        {
        
            $GradingStructure = $this->getQualificationAssessmentGradingStructure();
            if ($GradingStructure && $GradingStructure->isValid() && !$GradingStructure->isDeleted() && $GradingStructure->isEnabled())
            {

                // Select menu
                $values = $GradingStructure->getAwards(false, 'desc');
                $possibleValues = array();
                $possibleValues[] = '';
                if ($values)
                {
                    foreach($values as $val)
                    {
                        $possibleValues[] = $val->getShortName();
                    }
                }
                
                $possibleValuesString = '"'.implode(",", $possibleValues).'"';

                // Imploded list of values can't be more than 255 characters long, if it is, don't use data validation
                // Just have a normal cell
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

            }
        
        }
        
        
    }
    
    
    /**
     * Get the cell when exporting to an excel spreadsheet
     * @param type $objPHPExcel
     * @param type $rowNum
     * @param type $letter
     */
    public function getExcelCetaCell(&$objPHPExcel, $rowNum, $letter)
    {
                
        if (!$this->qualification){
            return false;
        }
                
        if (!$this->student){
            return false;
        }
        
        $grade = $this->getUserCeta();
        $grade->setDefaultName('');
        
        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $grade->getName());
        
        
        $QualBuild = $this->qualification->getBuild();            
        if ($QualBuild && $QualBuild->isValid() && !$QualBuild->isDeleted())
        {

            // Select menu
            $values = $QualBuild->getAwards('desc');
            $possibleValues = array();
            $possibleValues[] = '';
            if ($values)
            {
                foreach($values as $val)
                {
                    $possibleValues[] = $val->getName();
                }
            }
            
            $possibleValuesString = '"'.implode(",", $possibleValues).'"';

            // Can't have more than 255 characters or Excel breaks
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

        }
     
        
        
    }
    
    
    /**
     * Get the cell of a custom form field when exporting to an excel spreadsheet
     * @param type $objPHPExcel
     * @param type $rowNum
     * @param type $letter
     */
    public function getExcelCustomFormFieldCell(&$objPHPExcel, $rowNum, $letter, $field)
    {
                
        if (!$this->qualification){
            return false;
        }
                
        if (!$this->student){
            return false;
        }
        
        // Get the value of this attribute
        $value = $this->getCustomFieldValue($field, 'v', '');
        $field->setValue($value);
        
        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $field->getValue());

        // SELECT MENU
        if ($field->getType() == "SELECT"){
            
            $options = $field->getOptions();
            $possibleValuesString = '"'.implode(",", $options).'"';
            
            // Can't have more than 255 characters or Excel breaks
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
            
        }
                
    }
    
    
    
    /**
     * Get the cell for the assessment comments
     * @global type $CFG
     * @global \GT\type $User
     * @param type $access
     * @param type $studentID
     * @return boolean
     */
    public function getCommentsCell($access, $studentID = false){
        
        global $CFG, $User;
        
        if (!$this->qualification){
            return false;
        }
        
        if ($studentID){
            $this->loadStudent($studentID);
        }
        
        if (!$this->student){
            return false;
        }
        
        // No advanced editing
        if ($access == 'ae'){
            $access = 'e';
        }
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( ($access == 'e' || $access == 'ae') && !$User->canEditQual($this->qualification->getID()) ){
            $access = 'v';
        }
        
        $output = "";
        
        $icon = "comment_add.png";
        if ($this->hasUserComments()){
            $icon = "comment_edit.png";
        }
        
        // Edit
        if ($access == 'e'){
            
            $output .= "<img class='gt_assessment_comment_icon gt_assessment_comment_edit' src='{$CFG->wwwroot}/blocks/gradetracker/pix/{$icon}' alt='".get_string('comments', 'block_gradetracker')."'>";
            
        } else  {
            
            // View
            $output .= "<img class='gt_assessment_comment_icon gt_assessment_comment_v' src='{$CFG->wwwroot}/blocks/gradetracker/pix/{$icon}' alt='".get_string('comments', 'block_gradetracker')."'>";
            
        }
        
        return $output;
        
    }
    
    
    /**
     * Get the grid cell for a custom assessment field
     * @global \GT\type $CFG
     * @global \GT\type $User
     * @param type $field
     * @param type $access
     * @param type $studentID
     * @return boolean
     */
    public function getCustomFieldCell($field, $access, $studentID = false){
        
        global $CFG, $User;
        
        if (!$this->qualification){
            return false;
        }
            
        if ($studentID){
            $this->loadStudent($studentID);
        }
        
        if (!$this->student){
            return false;
        }
                
        // No advanced editing
        if ($access == 'ae'){
            $access = 'e';
        }
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( $access == 'e' && !$User->canEditQual($this->qualification->getID()) ){
            $access = 'v';
        }
        
        // Get the value of this attribute
        $value = $this->getCustomFieldValue($field, $access);
        $field->setValue($value);
                
        $output = "";
        
        if ($access == 'e'){
            
            $output .= $field->display( array('class' => 'gt_assessment_custom_field') );
            
        } else {
            
            // Checkbox needs to display tick if it's been ticked, instead of just "1", everything can display actual value
            if ($field->getType() == "CHECKBOX" && $field->getValue() == 1){
                $output .= "<img class='gt_award_icon' src='{$CFG->wwwroot}/blocks/gradetracker/pix/symbols/default/tick.png' alt='{$field->getValue()}' />";
            } else {            
                $output .= \gt_html($field->getValue());
            }
            
        }
                        
        return $output;
        
    }
    
    /**
     * Get the value for a student's qual on this custom form field on the assessment
     * Student and Qual should be loaded before using this
     * @param type $field
     * @param type $access
     * @return string
     */
    public function getCustomFieldValue($field, $access = 'v', $defaultValue = '-'){
        
        $value = $this->getAttribute("custom_{$field->getID()}");
        if ($value === false && $access == 'v'){
            $value = $defaultValue;
        }
        
        // If checkbox and value is not 1, set it to "-" again, instead of displaying "0"
        if ($field->getType() == "CHECKBOX" && $value != 1){
            $value = $defaultValue;
        }
        
        return $value;
        
    }
    
    /**
     * Set the custom form field value for a student's assessment field
     * @param type $fieldID
     * @param type $value
     * @return type
     */
    public function setUserCustomFieldValue($fieldID, $value){
        
        $attribute = "custom_{$fieldID}";
        
        // Save the custom field value
        if ($value == ''){
            return $this->deleteAttribute($attribute);
        } else {
            return $this->updateAttribute($attribute, $value);
        }
        
    }
    
    
    /**
     * Get the info to go in the popup information
     * @return string
     */
    public function getPopUpInfo(){
        
        $output = "";
        
        $qualification = $this->getQualification();
        $grade = $this->getUserGrade();
        $gradingMethod = $this->getSetting('grading_method');

        $output .= "<div class='gt_criterion_popup_info'>";

        if ($this->student){
            $output .= "<br><span class='gt-popup-studname'>{$this->student->getDisplayName()}</span><br>";
        }  

        if ($qualification){
            $output .= "<span class='gt-popup-qualname'>{$qualification->getDisplayName()}</span><br>";
        }

        $output .= "<span class='gt-popup-critname'>{$this->getName()}</span><br>";

        $output .= "<p><i>{$this->getDetails()}</i></p>";
        
        $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('value', 'block_gradetracker')."</div>";

        $output .= "<div class='gt_c'>";
        
            $output .= "<table>";
            
                $output .= "<tr>";
                    $output .= "<th>".get_string('grade', 'block_gradetracker')."</th>";
                    if ($qualification->isFeatureEnabledByName('cetagrades') && $this->isCetaEnabled())
                    {
                        $output .= "<th>".get_string('ceta', 'block_gradetracker')."</th>";
                    }
                $output .= "</tr>";
                
                
                $output .= "<tr>";
                
                    // If numeric show that number instead
                    if ($gradingMethod == 'numeric')
                    {
                        $max = $this->getSetting('numeric_grading_max');
                        // If we have a valid max, which we should do, show it score out of max
                        $score = ($max) ? ($this->getUserScore() . "/" . $max) : $this->getUserScore();
                        $output .= "<td>".$score."</td>";
                    }
                    else
                    {
                
                        // If we're using CETA grades, show the name of the grade
                        if ($qualification->isFeatureEnabledByName('cetagrades'))
                        {
                            $output .= "<td>".$grade->getShortName()."</td>";
                        }
                        else
                        {
                            // If we are not using CETA, then use an image
                            $output .= "<td>";
                            $output .= "<img class='gt_award_icon' src='{$grade->getImageURL()}' alt='{$grade->getShortName()}' title='{$grade->getShortName()}' /><br>";
                            $output .= $grade->getShortName();
                            $output .= "</td>";
                        }
                    
                    }
                
                    if ($qualification->isFeatureEnabledByName('cetagrades') && $this->isCetaEnabled())
                    {
                        $output .= "<td>".$this->getCetaCell('v')."</td>";
                    }
                    
                $output .= "</tr>";
                
            $output .= "</table>";
            $output .= "<br>";
            
            
            // Custom Fields
            $numCols = 3;
            $customFields = $this->getEnabledCustomFormFields();
            $customFields = \gt_split_array($customFields, $numCols);
            
            // Work out the colspan to use for each row, to make the table look nice and evenly formatted
            $numRows = count($customFields);
            if ($numRows > 1)
            {
                $lastRow = $customFields[$numRows-1];
                $numElements = count($lastRow);
                $leastCommonMultiple = \gt_lcm($numCols, $numElements);
            }
            
            $rowNum = 0;
                        
            if ($customFields)
            {
                
                $output .= "<table class='gt_assessment_popup_custom_fields_table'>";
                
                foreach($customFields as $customFieldsArray)
                {
                
                    // Work out width percentage for columns and whether to use the calculated colspan or not
                    $cnt = count($customFieldsArray);
                    $width = ($cnt > 0) ? 100 / $cnt : 100;
                    $colspan = (isset($leastCommonMultiple)) ? ($leastCommonMultiple / $cnt) : 1;
                    
                    $output .= "<tr>";
                    foreach($customFieldsArray as $field)
                    {
                        $output .= "<th colspan='{$colspan}' style='width:{$width}%;'>{$field->getName()}</th>";
                    }
                    $output .= "</tr>";
                    
                    $output .= "<tr>";
                    foreach($customFieldsArray as $field)
                    {
                        $output .= "<td colspan='{$colspan}' style='width:{$width}%;'>".\gt_html($this->getCustomFieldValue($field), true)."</td>";
                    }
                    $output .= "</tr>";
                    
                    $rowNum++;
                
                }
                
                $output .= "</table>";
                
            }
            
            
            $output .= "<br>";
            $output .= get_string('lastupdatedby', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdateByUserID() > 0) ? $this->getUserLastUpdateBy()->getName() : '-') . '</b>';
            $output .= "&nbsp;&nbsp;&nbsp;";
            $output .= get_string('updatetime', 'block_gradetracker') . ' <b>'. ( ($this->getUserLastUpdate() > 0) ? $this->getUserLastUpdate('D jS M Y, H:i') : '-') . '</b>';
                    
            $output .= "<br>";
            $output .= "<div class='gt_criterion_info_popup_heading'>".get_string('comments', 'block_gradetracker')."</div>";
            $output .= "<div class='gt_criterion_info_comments'>";
                $output .= gt_html($this->userComments, true);
            $output .= "</div>";

        $output .= "</div>";
        
        
        $output .= "</div>";
        
        return $output;
        
    }
    
     /**
     * Get the comments popup
     * @return string
     */
    public function getPopUpComments(){
        
        global $OUTPUT;
        
        $output = "";

        $qualification = $this->getQualification();

        $output .= "<div class='gt_criterion_popup_comments'>";

        if ($this->student){
            $output .= "<br><span class='gt-popup-studname'>{$this->student->getDisplayName()}</span><br>";
        }

        if ($qualification){
            $output .= "<span class='gt-popup-qualname'>{$qualification->getDisplayName()}</span><br>";
        }
        
        $output .= "<span class='gt-popup-critname'>{$this->getName()}</span><br><br>";

        $output .= "<textarea class='gt_assessment_comments_textbox' qID='{$qualification->getID()}' aID='{$this->id}' sID='{$this->student->id}'>".\gt_html($this->userComments)."</textarea>";
            
        $output .= "<br>";
        $output .= "<p><img id='gt_comment_loading' class='gt_hidden' src='".$OUTPUT->pix_url('i/loading_small')."' alt='".get_string('loading', 'block_gradetracker')."' /></p>";
        $output .= "<br>";
        
        $output .= "</div>";        
        
        return $output;
        
    }
    
    /**
     * Get an assessment setting
     * @global \GT\type $DB
     * @param type $setting
     * @return type
     */
    public function getSetting($setting){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_assessment_settings", array("assessmentid" => $this->id, "setting" => $setting));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Get a user's attribute for this assessment on a particular qualification
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $qualID
     * @param type $studentID
     * @return boolean
     */
    public function getAttribute($attribute, $qualID = false, $studentID = false){
        
        global $DB;
        
        // If we didn't specify the qualID use the qualification loaded in
        if (!$qualID && $this->qualification){
            $qualID = $this->qualification->getID();
        }
        
        // If we didn't specify the studentID use the student loaded in
        if (!$studentID && $this->student){
            $studentID = $this->student->id;
        }
        
        // If either of them aren't set, stop, as we need them
        if (!$qualID || !$studentID){
            return false;
        }
        
        $record = $DB->get_record("bcgt_assessment_attributes", array("assessmentid" => $this->id, "qualid" => $qualID, "userid" => $studentID, "attribute" => $attribute));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Update a user's attribute for this assessment
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $value
     * @param type $qualID
     * @param type $studentID
     * @return boolean
     */
    public function updateAttribute($attribute, $value, $qualID = false, $studentID = false){
        
        global $DB;
        
        // ------------ Logging Info
        
        $current = $this->getAttribute($attribute, $qualID, $studentID);
        
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;
        $Log->beforejson = array(
            $attribute => ($current) ? $current : null
        );
        
        // ------------ Logging Info
        
        
        
        // If we didn't specify the qualID use the qualification loaded in
        if (!$qualID && $this->qualification){
            $qualID = $this->qualification->getID();
        }
        
        // If we didn't specify the studentID use the student loaded in
        if (!$studentID && $this->student){
            $studentID = $this->student->id;
        }
        
        // If either of them aren't set, stop, as we need them
        if (!$qualID || !$studentID){
            return false;
        }
        
        $record = $DB->get_record("bcgt_assessment_attributes", array("assessmentid" => $this->id, "qualid" => $qualID, "userid" => $studentID, "attribute" => $attribute));
        if ($record)
        {
            $record->value = $value;
            $record->lastupdate = time();
            $result = $DB->update_record("bcgt_assessment_attributes", $record);
        }
        else
        {
            $ins = new \stdClass();
            $ins->assessmentid = $this->id;
            $ins->qualid = $qualID;
            $ins->userid = $studentID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $ins->lastupdate = time();
            $result = $DB->insert_record("bcgt_assessment_attributes", $ins);
        }
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            $attribute => $value
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualification->getID(),
                \GT\Log::GT_LOG_ATT_ASSID => $this->id,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
        
        
       
        return $result;
        
    }
    
    /**
     * Delete a user's attribute from the assessment
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $qualID
     * @param type $studentID
     * @return boolean
     */
    public function deleteAttribute($attribute, $qualID = false, $studentID = false){
        
        global $DB;
        
        // If we didn't specify the qualID use the qualification loaded in
        if (!$qualID && $this->qualification){
            $qualID = $this->qualification->getID();
        }
        
        // If we didn't specify the studentID use the student loaded in
        if (!$studentID && $this->student){
            $studentID = $this->student->id;
        }
        
        // If either of them aren't set, stop, as we need them
        if (!$qualID || !$studentID){
            return false;
        }
        
        
        // --------- Log Info
        if (!is_null($studentID)){
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_ATT;

            $check = $this->getAttribute($attribute, $qualID, $studentID);
            $Log->beforejson = array(
                $attribute => ($check) ? $check : null
            );
        }
        // --------- Log Info
        
        
        $result = $DB->delete_records("bcgt_assessment_attributes", array("assessmentid" => $this->id, "qualid" => $qualID, "userid" => $studentID, "attribute" => $attribute));
        
        
        if (!is_null($studentID)){
            
            // ----------- Log the action
            $Log->attributes = array(
                    \GT\Log::GT_LOG_ATT_QUALID => $qualID,
                    \GT\Log::GT_LOG_ATT_ASSID => $this->id,
                    \GT\Log::GT_LOG_ATT_STUDID => $studentID
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
     * Update an assessment setting
     * @global \GT\type $DB
     * @param type $setting
     * @param type $value
     * @return type
     */
    public function updateSetting($setting, $value){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_assessment_settings", array("assessmentid" => $this->id, "setting" => $setting));
        if ($record)
        {
            $record->value = $value;
            return $DB->update_record("bcgt_assessment_settings", $record);
        }
        else
        {
            $obj = new \stdClass();
            $obj->assessmentid = $this->id;
            $obj->setting = $setting;
            $obj->value = $value;
            return $DB->insert_record("bcgt_assessment_settings", $obj);
        }
        
    }
    
    /**
     * Check if CETA is enabled for this assessment
     * @return type
     */
    public function isCetaEnabled(){
        
        $result = $this->getSetting('ceta');
        return ($result == 1);
        
    }
    
    /**
     * Check if summary enabled for this assessment
     * @return type
     */
    public function isSummaryEnabled(){
        
        $result = $this->getSetting('summary');
        return ($result == 1);
        
    }
    
    /**
     * Save the user
     * @global \GT\type $DB
     * @return boolean
     */
    public function saveUser(){
        
        global $DB, $USER;
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_ASS;
        $Log->beforejson = array(
            'grade' => ($this->_userRow) ? $this->_userRow->grade : null,
            'ceta' => ($this->_userRow) ? $this->_userRow->ceta : null,
            'comments' => ($this->_userRow) ? $this->_userRow->comments : null,
            'score' => ($this->_userRow) ? $this->_userRow->score : null
        );
        // ------------ Logging Info
        
        
        
        if (!$this->student || !$this->qualification){
            return false;
        }
                        
        // Update
        if ($this->userAssessmentRowID)
        {
        
            $obj = new \stdClass();
            $obj->id = $this->userAssessmentRowID;
            $obj->grade = ($this->userGrade) ? $this->userGrade->getID() : 0;
            $obj->ceta = ($this->userCeta) ? $this->userCeta->getID() : 0;
            $obj->comments = ($this->userComments) ? $this->userComments : null;
            $obj->lastupdate = time();
            $obj->lastupdateby = $USER->id;
            $obj->score = $this->userScore;
            $result =  $DB->update_record("bcgt_user_assessments", $obj);
            
        }
        else
        {
            
            $obj = new \stdClass();
            $obj->userid = $this->student->id;
            $obj->assessmentid = $this->id;
            $obj->qualid = $this->qualification->getID();
            $obj->grade = ($this->userGrade) ? $this->userGrade->getID() : 0;
            $obj->ceta = ($this->userCeta) ? $this->userCeta->getID() : 0;
            $obj->comments = ($this->userComments) ? $this->userComments : null;
            $obj->lastupdate = time();
            $obj->lastupdateby = $USER->id;
            $obj->score = $this->userScore;
            $result = $DB->insert_record("bcgt_user_assessments", $obj);
            
        }
        
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'grade' => $obj->grade,
            'ceta' => $obj->ceta,
            'comments' => $obj->comments,
            'score' => $obj->score
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualification->getID(),
                \GT\Log::GT_LOG_ATT_ASSID => $this->id,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
        
        
        
        return $result;
        
    }
    
    
    /**
     * Check if it has no errors
     * @return type
     */
    public function hasNoErrors(){
        
        // Make sure everything is filled out
        if (empty($this->name)){
            $this->errors[] = get_string('errors:filloutfield', 'block_gradetracker') . ' - ' . get_string('name');
        }
        
        if (empty($this->type)){
            $this->errors[] = get_string('errors:filloutfield', 'block_gradetracker') . ' - ' . get_string('type', 'block_gradetracker');
        }
        
        if (empty($this->details)){
            $this->errors[] = get_string('errors:filloutfield', 'block_gradetracker') . ' - ' . get_string('description', 'block_gradetracker');
        }
        
//        if (empty($this->date)){
//            $this->errors[] = get_string('errors:filloutfield', 'block_gradetracker') . ' - ' . get_string('date');
//        }
        
        
        
        $structureErrors = array();
        
        // Check quals are ok to have formal assessments
        if ($this->qualIDs){
            
            foreach($this->qualIDs as $qualID){
                
                $qual = new \GT\Qualification($qualID);
                $structure = $qual->getStructure();
                $build = $qual->getBuild();
                
                // Check qual has a valid structure
                if (!$structure->isValid()){
                    $this->errors[] = get_string('invalidqual', 'block_gradetracker') . ' - ' . $qual->getName();
                    continue;
                }
                
                // Check it has an assessment grading structure
                $gradingStructures = $structure->getAssessmentGradingStructures();
                $buildGradingStructures = $build->getAssessmentGradingStructures();
                if ( 
                        ( 
                            (!$gradingStructures) 
                            &&
                            (!$buildGradingStructures) 
                        )
                        && 
                        !in_array($structure->getID(), $structureErrors) 
                    )
                {
                    $this->errors[] = sprintf( get_string('qualstructurehasnoassessmentgradingstructure', 'block_gradetracker'), $structure->getName() );
                    $structureErrors[] = $structure->getID();
                    continue;
                }
                
            }
            
        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Save the assessment
     * @global \GT\type $DB
     * @return type
     */
    public function save(){
        
         global $DB;
         
         $record = new \stdClass();
         
         if ($this->isValid()){
             
             $record->id = $this->id;
             $record->type = $this->type;
             $record->name = $this->name;
             $record->details = $this->details;
             $record->assessmentdate = $this->date;
             $result = $DB->update_record("bcgt_assessments", $record);
             
         } else {
             
             $record->type = $this->type;
             $record->name = $this->name;
             $record->details = $this->details;
             $record->assessmentdate = $this->date;
             $this->id = $DB->insert_record("bcgt_assessments", $record);
             $result = $this->id;
             
         }
         
         // If that failed somehow, don't continue
         if (!$result) return false;
         
         // Settings
         
         // Wipe custom form field ones and grading structure ID ones
         $DB->execute("DELETE FROM {bcgt_assessment_settings} WHERE assessmentid = ? AND setting LIKE 'custom_form_field_enabled_%'", array($this->id));
         $DB->execute("DELETE FROM {bcgt_assessment_settings} WHERE assessmentid = ? AND setting LIKE 'grading_structure_qual_%'", array($this->id));
         
         // Save settings
         if ($this->useSettings){
             
             foreach($this->useSettings as $setting => $value){
                 
                 $this->updateSetting($setting, $value);
                 
             }
             
         }
         
                  
         // Quals
         if ($this->qualIDs){
             
             foreach($this->qualIDs as $qualID){
                 
                 $check = $DB->get_record("bcgt_assessment_quals", array("assessmentid" => $this->id, "qualid" => $qualID));
                 if (!$check){
                     
                     $ins = new \stdClass();
                     $ins->assessmentid = $this->id;
                     $ins->qualid = $qualID;
                     $DB->insert_record("bcgt_assessment_quals", $ins);
                     
                 }
                 
             }
             
         }
         
         // Remove any that weren't submitted
         $records = $DB->get_records("bcgt_assessment_quals", array("assessmentid" => $this->id));
         if ($records){
             foreach($records as $record){
                 if (!in_array($record->qualid, $this->qualIDs)){
                     $DB->delete_records("bcgt_assessment_quals", array("id" => $record->id));
                 }
             }
         }

         
         return $result;
        
    }
    
    /**
     * 
     * @global \GT\type $DB
     * @return type
     */
    public function delete(){
        
        global $DB;
        
        $record = new \stdClass();
        $record->id = (int)$this->id;
        $record->deleted = 1;
        return $DB->update_record("bcgt_assessments", $record);
        
    }
    
    /**
     * Load data from the form into the object
     */
    public function loadPostData(){
        
        if (isset($_POST['save_assessment'])){
            
            if (isset($_POST['assessmentid'])){
                $this->setID($_POST['assessmentid']);
            }
                        
            $this->setName($_POST['name']);
            $this->setType( ( $_POST['type'] == 'other' ) ? $_POST['type_other'] : $_POST['type'] );
            $this->setDetails($_POST['description']);
            $this->setDate( strtotime($_POST['date']) );
            
            // Settings
            $this->useSettings['ceta'] = $_POST['ceta'];
            $this->useSettings['summary'] = $_POST['summary'];
            $this->useSettings['grading_method'] = $_POST['grading_method'];
            $this->useSettings['numeric_grading_min'] = $_POST['numeric_grading_min'];
            $this->useSettings['numeric_grading_max'] = $_POST['numeric_grading_max'];
            
            // QualStructure grading structure to use with this assessment
            if (isset($_POST['structure_grading_structure']))
            {
                foreach($_POST['structure_grading_structure'] as $structureID => $gradingStructureID)
                {
                    if ((int)$gradingStructureID > 0)
                    {
                        $this->useSettings['grading_structure_qual_structure_' . $structureID] = $gradingStructureID;
                    }
                }
            }
            
            // QualBuild grading structure to use with this assessment
            if (isset($_POST['structure_grading_build']))
            {
                foreach($_POST['structure_grading_build'] as $buildID => $gradingStructureID)
                {
                    if ((int)$gradingStructureID > 0)
                    {
                        $this->useSettings['grading_structure_qual_build_' . $buildID] = $gradingStructureID;
                    }
                }
            }
            
            
            
            $customFormFields = array();
            if (isset($_POST['custom_form_fields_enabled']))
            {
                foreach($_POST['custom_form_fields_enabled'] as $key => $enabled)
                {
                    if ($enabled == 1)
                    {
                        $this->useSettings['custom_form_field_enabled_' . $key] = $enabled;
                    }
                }
            }
            
            $this->qualIDs = array();
            
            if (isset($_POST['quals'])){
                foreach($_POST['quals'] as $qualID){
                    $this->setQualID($qualID);
                }
            }
                                    
        }
        
    }
    
    /**
     * Get the custom form fields which are enabled on this Assessment
     * @return type
     */
    public function getEnabledCustomFormFields(){
        
        $return = array();
        $fields = self::getCustomFormFields();
        
        if ($fields)
        {
            foreach($fields as $field)
            {
                if ($this->getSetting('custom_form_field_enabled_' . $field->getID()) == 1)
                {
                    $return[$field->getID()] = $field;
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get all assessments, or all assessments of a particular type
     * @global \GT\type $DB
     * @param type $type
     * @return \GT\Assessment
     */
    public static function getAllAssessments($type = false){
        
        global $DB;
        
        if ($type){
            $records = $DB->get_records("bcgt_assessments", array("type" => $type, "deleted" => 0), "assessmentdate", "id");
        } else {
            $records = $DB->get_records("bcgt_assessments", array("deleted" => 0), "assessmentdate", "id");
        }
        
        $return = array();
        if ($records){
            foreach($records as $record){
                $return[] = new \GT\Assessment($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get a distinct list of all the types
     * @global type $DB
     * @return type
     */
    public static function getAllTypes(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records("bcgt_assessments", array("deleted" => 0), "type ASC", "DISTINCT type");
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = $record->type;
            }
        }
        
        // Defaults
        $defaults = array("Formal");
        
        return $return + $defaults;
        
    }
    
    /**
     * Get all the assessments on an array of qualifications, for the assessment grid header
     * @param array $quals
     * @return type
     */
    public static function getAllAssessmentsOnQuals(array $quals){
        
        $return = array();
        
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $assessments = $qual->getAssessments();
                if ($assessments)
                {
                    foreach($assessments as $assessment)
                    {
                        $return[$assessment->getID()] = $assessment;
                    }
                }
            }
        }
        
        // Sort them by date
        $Sorter = new \GT\Sorter();
        $Sorter->sortAssessmentsByDate($return);
        
        return $return;
        
    }
    
    /**
     * Get the custom form fields defined for the assessment grid
     * @return \GT\FormElement
     */
    public static function getCustomFormFields(){
        
        $fields = array();
        $fieldIDs = \GT\Setting::getSetting('assessment_grid_custom_form_elements');
        
        if (!empty($fieldIDs))
        {
            $fieldIDs = explode(",", $fieldIDs);
            if ($fieldIDs)
            {
                foreach($fieldIDs as $fieldID)
                {
                    $element = new \GT\FormElement($fieldID);
                    $fields[$fieldID] = $element;
                }
            }
        }
        
        return $fields;
        
    }
    
}
