<?php
/**
 * Update AJAX
 *
 * AJAX script to update grids
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

require_once '../../../config.php';
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

$PAGE->set_context( context_system::instance() );
require_login();

$action = optional_param('action', false, PARAM_TEXT);
$params = (isset($_POST['params'])) ? $_POST['params'] : false;

$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);

\gt_debug("Called update.php: " . print_r($_POST, true));

// If action not defined exit. Don't use reqired_param as the error message will mess up our ajax call
if (!$action) exit;






switch($action)
{
    
    case 'update_student_criterion':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $met = (isset($params['met'])) ? $params['met'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;
                
        // OPtional range id
        $rangeID = (isset($params['rID']) && $params['rID'] > 0) ? $params['rID'] : false;
        // Optional observation number
        $obNum = (isset($params['obNum']) && $params['obNum'] > 0) ? $params['obNum'] : false;
                
        // If the date was set, convert that to a unix timestamp
        if ($date){
            $date = strtotime($date);
        }
                
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || ($met === false && $value === false && $date === false)){
            \gt_debug("missing params");
            exit;
        }
        
        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            \gt_debug("Either qualification or student is invalid");
            exit;
        }
                        
        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            \gt_debug("Could not find unit ID ({$unitID}) on qualification ({$Qualification->getID()} - {$Qualification->getName()})");
            exit;
        }
                
        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion){
            \gt_debug("Could not find criterion on unit");
            exit;
        }
                                
        // We can only do a tickbox or DATE if it has ONE met value
        if ($met !== false || $date !== false)
        {
            $awards = $criterion->getPossibleValues(true);
            if (count($awards) <> 1){
                \gt_debug("More or Less than 1 'met' criteria award, so cannot do tickbox");
                exit;
            }
            
            // If we ticked it
            if ($met || $date){
                $award = reset($awards);
            } else {
                // If we unticked it
                $award = new \GT\CriteriaAward(0);
            }
            
        }
        
        // Using a select menu to send a value id instead
        elseif ($value !== false)
        {
            $award = new \GT\CriteriaAward($value);
        }
                
        
        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            \gt_debug("User has no editing access on this qual unit");
            exit;
        }
                        
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            \gt_debug("Student is not assigned to this qual unit");
            exit;
        }
                        
        // If we have passed through an Observation Number, this is a Ranged criterion we are storing a
        // value against a Observation with
        if ($obNum > 0){
            
            $criterion->setCriterionObservationValue($obNum, $rangeID, $award->getID(), $date);
            
        } else {
        
            // Otherwise just a normal criterion update
            $criterion->setUserAward($award);
            
            if ($date !== false){
                $criterion->setUserAwardDate($date);
            } elseif (!$award->isMet()) {

                // If the value is not MET, reset the award date as that's only for MET values
                $criterion->setUserAwardDate(0);

            }

            $criterion->saveUser();
                        
        }
        
        // Auto calculations - Don't notify events if the unit award changes
        $unit->autoCalculateAwards( false );
        
        $result = array(
            'awards' => $criterion->jsonResult,
            'unitawards' => $unit->jsonResult,
            'progress' => $unit->unitCal()
        );
                
        // Merge in the results from the rules
        if (isset($GLOBALS['rule_response']['awards'])){
            if (!is_array($result['awards'])){
                $result['awards'] = array();
            }
            $result['awards'] = $result['awards'] + $GLOBALS['rule_response']['awards'];
        }
        
        if (isset($GLOBALS['rule_response']['unitawards'])){
            if (!is_array($result['unitawards'])){
                $result['unitawards'] = array();
            }
            $result['unitawards'] = $result['unitawards'] + $GLOBALS['rule_response']['unitawards'];
        }
        
        
        echo json_encode($result);
        exit;
        
        
    break;
    
    case 'update_student_assessment':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assessmentID = (isset($params['assessmentID'])) ? $params['assessmentID'] : false;
        $type = (isset($params['type'])) ? $params['type'] : false;
        $value = (isset($params['value']) && ctype_digit($params['value'])) ? $params['value'] : null;
        $gradingMethod = (isset($params['gradingMethod'])) ? $params['gradingMethod'] : false;
                        
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assessmentID || !in_array($type, array('grade', 'ceta'))){
            exit;
        }
        
        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
                               
        // Do we have the permissions to edit this qual?
        if (!$User->canEditQual($qualID)){
            exit;
        }
        
        // Make sure the Assessment is valid
        $Assessment = new \GT\Assessment($assessmentID);
        if (!$Assessment->isValid()){
            exit;
        }
                
        $award = false;
        
        // If we're using a grading structure, check the award is valid
        if ($gradingMethod != 'numeric')
        {
            if ($type == 'grade'){
                $award = new \GT\CriteriaAward($value);
                // If we specified an actual value, then check if that value is valid
                // Otherwise we passed through an invalid value in the first place, so must be setting to null
                if ($value > 0 && !$award->isValid()){
                    exit;
                }
            } elseif ($type == 'ceta'){
                $award = new \GT\QualificationAward($value);
                // Same as above
                if ($value > 0 && !$award->isValid()){
                    exit;
                }
            }
        }
        elseif ($gradingMethod == 'numeric')
        {
            
            // Check is within min and max of assessment
            $min = $Assessment->getSetting('numeric_grading_min');
            $max = $Assessment->getSetting('numeric_grading_max');
            
            if (!is_numeric($value) || $value < $min || $value > $max){
                exit;
            }
            
        }
        
        // Do the assessment stuff
        $Assessment->setQualification( $Qualification );
        $Assessment->loadStudent( $studentID );
                              
        if ($type == 'grade'){
            if ($gradingMethod == 'numeric'){
                $Assessment->setUserScore($value);
            } else {
                $Assessment->setUserGrade($award);
            }
        } elseif ($type == 'ceta'){
            $Assessment->setUserCeta($award);
        }
                
        $Assessment->saveUser();
        
        $result = array();
        
        echo json_encode($result);
        exit;
        
    break;
    
    // Update the custom field of an assessment
    case 'update_student_assessment_custom_field':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assessmentID = (isset($params['assessmentID'])) ? $params['assessmentID'] : false;
        $fieldID = (isset($params['fieldID'])) ? $params['fieldID'] : false;
        $value = (isset($params['value'])) ? trim($params['value']) : null;
                
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assessmentID || !$fieldID){
            exit;
        }
        
        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
                               
        // Do we have the permissions to edit this qual?
        if (!$User->canEditQual($qualID)){
            exit;
        }
        
        // Make sure the Assessment is valid
        $Assessment = new \GT\Assessment($assessmentID);
        if (!$Assessment->isValid()){
            exit;
        }
        
        $Field = new \GT\FormElement($fieldID);
        if (!$Field->isValid()){
            exit;
        }
        
        // Do the assessment stuff
        $Assessment->setQualification( $Qualification );
        $Assessment->loadStudent( $studentID );
        
        $Assessment->setUserCustomFieldValue($fieldID, $value);           
        
        // Save user to update last updated time
        $Assessment->saveUser();
        
        $result = array();
        echo json_encode($result);
        exit;
                        
    break;
    
    
    
    case 'update_student_unit':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
                        
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || $value === false){
            exit;
        }
        
        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
                
        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            exit;
        }
                        
        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            exit;
        }
                
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            exit;
        }
                                
        $award = new \GT\UnitAward($value);   
        $unit->setUserAward($award);
        $unit->saveUser();     
        
        // Reload the student's criteria to get any updated values
        $unit->loadStudent( $unit->getStudent() );
        
        $result = array(
            'awards' => array(),
            'unitawards' => array(),
            'progress' => $unit->unitCal()
        );
        
        // Merge in the results from the rules
        if (isset($GLOBALS['rule_response']['awards'])){
            if (!is_array($result['awards'])){
                $result['awards'] = array();
            }
            $result['awards'] = $result['awards'] + $GLOBALS['rule_response']['awards'];
        }
        
        if (isset($GLOBALS['rule_response']['unitawards'])){
            if (!is_array($result['unitawards'])){
                $result['unitawards'] = array();
            }
            $result['unitawards'] = $result['unitawards'] + $GLOBALS['rule_response']['unitawards'];
        }
                
        echo json_encode($result);
        exit;
        
        
    break;
    
    
    // Just the award date, not the value
    case 'update_student_criterion_award_date':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;
        
        // If the date was set, convert that to a unix timestamp
        if ($date){
            $date = strtotime($date);
        }
                
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $date === false){
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
        
        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            exit;
        }
        
        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion){
            exit;
        }
                
        
        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            exit;
        }
        
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            exit;
        }
                
        $criterion->setUserAwardDate($date);                
        $criterion->saveUser();
        
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    // Update the award date of an observation number on a Range
    case 'update_student_range_observation_award_date':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $rangeID = (isset($params['rangeID'])) ? $params['rangeID'] : false;
        $obNum = (isset($params['obNum'])) ? $params['obNum'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;
        
        // If the date was set, convert that to a unix timestamp
        if ($date){
            $date = strtotime($date);
        }
                
        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$rangeID || !$obNum || $date === false){
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
        
        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            exit;
        }
        
        // Make sure the criterion is valid
        $range = $unit->getCriterion($rangeID);
        if (!$range){
            exit;
        }
                
        
        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            exit;
        }
        
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            exit;
        }
           
        // Uses a setting
        $range->setUserObservationAwardDate($obNum, $date);
        
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    
    case 'update_student_criterion_comments':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
        
         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $value === false){
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
        
        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            exit;
        }
        
        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion){
            exit;
        }
                
        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            exit;
        }
        
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            exit;
        }
                
        $criterion->setUserComments($value);      
        $criterion->saveUser();
        
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
  
    // This one is for when we have a popup for the Criterion because we have levels of sub crit
    case 'update_student_sub_criterion_comments':
        
        if (isset($params))
        {
            
            foreach($params as $param)
            {
                
                $studentID = (isset($param['studentID'])) ? $param['studentID'] : false;
                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $value = (isset($param['value'])) ? $param['value'] : false;
                
                 // If any of these are not set, stop
                if (!$studentID || !$qualID || !$unitID || !$critID || $value === false){
                    exit;
                }

                // Load the UserQualification object and load the specified user into it
                $Qualification = new \GT\Qualification\UserQualification($qualID);
                $Qualification->loadStudent($studentID);

                // Make sure Qual & Student are valid
                if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit){
                    exit;
                }

                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);
                if (!$criterion){
                    exit;
                }

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($qualID, $unitID)){
                    exit;
                }

                // Is the student actually on this qualification and this unit?
                if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
                    exit;
                }

                $criterion->setUserComments($value);
                $criterion->saveUser();
                
            }
            
        }
                
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    
    
    
    case 'update_student_assessment_comments':
        
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assID = (isset($params['assID'])) ? $params['assID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
                
         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assID || $value === false){
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);
        
        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }
        
        // Make sure the assessment is valid
        $assessment = $Qualification->getAssessment($assID);
        if (!$assessment){
            exit;
        }
                
        // Do we have the permissions to edit this unit?
        if (!$User->canEditQual($qualID)){
            exit;
        }
        
        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQual($qualID, "STUDENT")){
            exit;
        }
                
                
        $assessment->setUserComments($value);      
        $assessment->saveUser();
        
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    
    
    case 'update_student_detail_criterion':
                
        if (isset($params))
        {
            
            foreach($params as $param)
            {
                
                $studentID = (isset($param['studentID'])) ? $param['studentID'] : false;
                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $type = (isset($param['type'])) ? $param['type'] : false;
                $value = (isset($param['value'])) ? $param['value'] : false;
                
                 // If any of these are not set, stop
                if (!$studentID || !$qualID || !$unitID || !$critID || !$type || $value === false){
                    exit;
                }
                
                // Must be valid type
                if ($type != 'custom_value' && $type != 'comments'){
                    exit;
                }

                // Load the UserQualification object and load the specified user into it
                $Qualification = new \GT\Qualification\UserQualification($qualID);
                $Qualification->loadStudent($studentID);

                // Make sure Qual & Student are valid
                if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit){
                    exit;
                }

                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);
                if (!$criterion){
                    exit;
                }

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($qualID, $unitID)){
                    exit;
                }

                // Is the student actually on this qualification and this unit?
                if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
                    exit;
                }

                if ($type == 'custom_value'){
                    $criterion->setUserCustomValue($value);      
                } elseif ($type == 'comments'){
                    $criterion->setUserComments($value);
                }
                
                $criterion->saveUser();
                
            }
            
        }
                
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    
    case 'mass_update_student_detail_criterion':
                
        if (isset($params))
        {
            
            foreach($params as $param)
            {
                
                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $groupID = (isset($param['groupID'])) ? $param['groupID'] : false;
                $courseID = (isset($param['courseID'])) ? $param['courseID'] : false;
                $valueID = (isset($param['valueID'])) ? $param['valueID'] : false;
                
                // If any of these are not set, stop
                if ( $qualID === false || $unitID === false || $critID === false || $courseID === false || $valueID === false){
                    exit;
                }
                
                // Load the UserQualification object and load the specified user into it
                $Qualification = new \GT\Qualification\UserQualification($qualID);
                // $Qualification->loadStudent($studentID);
                // 
                // Make sure Qual
                if (!$Qualification->isValid()){
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit){
                    exit;
                }
                
                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);                
                if (!$criterion){
                    exit;
                }
                
                $students = $unit->getUsers("STUDENT", false, $courseID, $groupID);
                
                // Do we have the permissions to edit this unit?                
                if (!$User->canEditUnit($qualID, $unitID)){
                    exit;
                }
                        
                
                $award = new \GT\CriteriaAward($valueID);
            
                
                foreach ($students as $student) {
                    $Qualification->loadStudent($student);
                    
                    $unit = $Qualification->getUnit($unitID);
                    if (!$unit){
                        exit;
                    }
                
                    $criterion = $unit->getCriterion($critID);
                    if (!$criterion){
                        exit;
                    }
                    
                    if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
                        exit;
                    }
                    
                    $criterion->setUserAward($award);
                    $criterion->saveUser();
                }

                
                
            }
            
        }
                
        $result = array(
            
        );
        
        echo json_encode($result);
        exit;
        
    break;
    
    
    case 'update_student_numeric_point':
                
        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $rangeID = (isset($params['rangeID']) && $params['rangeID'] > 0) ? $params['rangeID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
        
         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $value === false){
            exit;
        }
        
        // Load the UserQualification object and load the specified user into it
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()){
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit){
            exit;
        }

        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion){
            exit;
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)){
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")){
            exit;
        }

        if ($rangeID){
            $range = $unit->getCriterion($rangeID);
            if (!$range){
                exit;
            }
        }
        
        $totalPoints = 0;
        $awardID = 0;
        $parentAwardID = 0;
                
        // If we are on a Range, we store in the user_range table
        if ($rangeID){
            
            $parent = $unit->getCriterion( $criterion->getParentID() );
            
            $criterion->setRangeCriterionValue($range->getID(), $criterion->getID(), $value);
            $totalPoints = $range->getTotalPoints();
            
            // Calculate range award
            $awardID = $range->autoCalculateAwardFromConversionChart();
            
            // Calculate award for overall criteria - checking if all Ranges are completed
            $parentAwardID = $parent->autoCalculateAwardFromRanges();
            
        } else {
        
            // Otherwise it's just against the 1 criterion, so we  use the Custom Value to store the points
            $criterion->setUserCustomValue($value);
            $criterion->saveUser();
            
            // Get the new total points
            $parent = $unit->getCriterion( $criterion->getParentID() );
            if ($parent){
                $totalPoints = $parent->getTotalPoints();
            }

            // Now calculate the overall criteria
            $awardID = $parent->autoCalculateAwardFromConversionChart();
        
        }
                
        $result = array(
            'points' => $totalPoints,
            'awardID' => $awardID,
            'awardCriterion' => (($rangeID) ? $rangeID : $parent->getID()),
            'parentAwardID' => $parentAwardID,
            'parentCriterion' => $parent->getID()
        );
        
        echo json_encode($result);
        exit;
                
    break;
    
    case 'update_user_grade':
        
        $student = new \GT\User($params['sID']);
        if (!$student->isValid()) exit;
        
        // Should be valid type of grade
        if ($params['type'] != 'target' && $params['type'] != 'aspirational' && $params['type'] != 'ceta') exit;
        
        // Do we have the permission to do this?
        if ($params['type'] == 'target' && !$User->hasUserCapability('block/gradetracker:edit_target_grades', $student->id, $params['qID'])) exit;
        elseif ($params['type'] == 'aspirational' && !$User->hasUserCapability('block/gradetracker:edit_aspirational_grades', $student->id, $params['qID'])) exit;
        elseif ($params['type'] == 'ceta' && !$User->hasUserCapability('block/gradetracker:edit_ceta_grades', $student->id, $params['qID'])) exit;
        
        $award = new \GT\QualificationAward($params['awardID']);
        
        // Set target grade
        $student->setUserGrade($params['type'], $params['awardID'], array('qualID' => $params['qID']));
        
        $grade = (!is_null($award->getName())) ? $award->getName() : '';
        
        $result = array(
            'gradeID' => $award->getID(),
            'grade' => $grade
        );
        
        echo json_encode($result);        
        exit;
        
    break;
    
    case 'update_user_attribute':
                
        if (!isset($params['attribute']) || !isset($params['type']) || !isset($params['value']) || !isset($params['studentID'])) exit;

        $student = new \GT\User($params['studentID']);
        if (!$student->isValid()) exit;
                
        switch($params['type'])
        {
            // User unit attribute
            case 'unit':

                // need unitID and qualID to make sure they are actually on the unit somewhere
                if (!isset($params['unitID']) || !isset($params['qualID'])) exit;
                
                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($params['qualID'], $params['unitID'])){
                    exit;
                }

                // Get qual and load student into it
                $qual = new \GT\Qualification\UserQualification($params['qualID']);
                if (!$qual->isValid() || !$qual->loadStudent($params['studentID'])) exit;

                // Get unit from qual
                $unit = $qual->getUnit($params['unitID']);
                if (!$unit) exit;

                // Check user is on unit
                if (!$student->isOnQualUnit($qual->getID(), $unit->getID(), "STUDENT")) exit;
                
                $value = trim($params['value']);
                if ($value == '') $value = null;
                
                // Update the attribute, don't use the qualID though, units are qual independant
                $unit->updateAttribute($params['attribute'], $value, $student->id);
                
                $result = array(
                    
                );
        
                echo json_encode($result);        
                exit;
                
            break;
        }
        
        exit;
        
    break;
    
    case 'set_debugging':
        
        if (!\is_siteadmin()) exit;
        
        $value = (isset($params['value'])) ? $params['value'] : false;
        if ($value){
            $_SESSION['gt_debug'] = true;
        } else {
            unset($_SESSION['gt_debug']);
        }
                
        exit;
        
    break;
    
    case 'clear_debugging':
        
        if (!\is_siteadmin()) exit;
                
        $file = \GT\GradeTracker::dataroot() . "/debug/{$USER->id}.txt";
        if (file_exists($file)){
            file_put_contents($file, '');
        }
        
        exit;
        
    break;
    
    case 'register_site':
    
        if (!\is_siteadmin()) exit;

        $Site = new \GT\Site();
        $Site->load($params);       
        $result = $Site->submit();
                
        echo $result;
        exit;
        
    break;
    
    
    
}