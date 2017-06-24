<?php
/**
 * Get AJAX
 *
 * AJAX script to get various pieces of info, mostly for loading into javascript functions
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
$params = (isset($_POST['params'])) ? $_POST['params'] : false; // optional_param_array doesn't support multi arrays

$skipLogin = false;
// Need to validate the session as well - TODO
if (isset($params['external']) && $params['external'] == true && isset($params['extSsn'])){
    $skipLogin = true;
}

// If we aren't skipping the Moodle login, make sure we're logged in
if (!$skipLogin) {
    require_login();
}

// TODO - Need to put permissions around all the stuff in here which might return student data, in case they
// do skipLogin, which should only be relevant for the bits I want it to be (student grid)

$action = optional_param('action', false, PARAM_TEXT);

$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);

// If action not defined exit. Don't use reuired_param as the error message will mess up our ajax call
if (!$action) exit;

// Check which action we're doing
switch ($action)
{
    
    case 'get_student_grid':
        
        $qualID = $params['qualID'];
        
        // If we are trying to skip the login, check the external session is valid
        if ($skipLogin){
            if (!\gt_validate_external_session($params['extSsn'], $params['studentID'])){
                echo json_encode( \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') ) );
                exit;
            }
        }
                
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            echo json_encode( \gt_error_alert_box( get_string('invalidqual', 'block_gradetracker') ) );
            exit;
        }
		
		echo json_encode( $Qualification->getStudentGridData($params) );
        
        
    break;
    
    case 'get_unit_grid':
        
        $qualID = $params['qualID'];
        $courseID = $params['courseID'];
        $groupID = $params['groupID'];
        $unitID = $params['unitID'];
        $access = $params['access'];
        $view = $params['view'];
        $page = (isset($params['page'])) ? $params['page'] : 1;
        
        
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            echo json_encode( \gt_error_alert_box( get_string('invalidqual', 'block_gradetracker') ) );
            exit;
        }
        
        $Unit = $Qualification->getUnit($unitID);
        if (!$Unit){
            echo json_encode( \gt_error_alert_box( get_string('invalidunit', 'block_gradetracker') ) );
            exit;
        }
                
        // Do we have the permission to view the unit grids?
        if (!\gt_has_capability('block/gradetracker:view_unit_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
            echo json_encode( \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') ) );
            exit;
        }
        
        // Are we a staff member on this unit and this qual?
        if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
            echo json_encode( \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') ) );
            exit;
        }
        
                
        $settings = array();
        $settings['cnt'] = 0;
        $settings['activitycnt'] = 0;
        $settings['percentage'] = false;
        $settings['iv'] = false;
        
        $students = $Unit->getUsers("STUDENT", $page, $courseID, $groupID);
        $studentCols = array_filter( explode( ",", $GT->getSetting('student_columns') ) );
        
        // Count columns required
        $settings['cnt'] += count($studentCols) + 2; // 2 for the qual award and unit award columns
        $settings['activitycnt'] += count($studentCols) + 2;
        
        if ($Qualification->isFeatureEnabledByName('percentagecomp')){
            $settings['cnt']++;
            $settings['activitycnt']++;
            $settings['percentage'] = true;
        }
                
        // Get a flat array of criteria names, which may contain multiple copies if activity grid and if criteiron
        // is on more than 1 activity
        $activities = ($view == 'activities') ? $Unit->getActivityLinks() : false;
        $criteriaArray = $Unit->getHeaderCriteriaNamesFlat($view, $activities);
        
        //Get an array of Criterion of the criterias
//        $criterion = [];
//        foreach ($Qualification->getUnits() as $unit){
//            $criterion[$unit->getID] = $unit->getCriteria();
//        }
        
        $settings['criteriacnt'] = count($criteriaArray);
        $settings['cnt'] += $settings['criteriacnt'];
        
        // IV Column
        if ($Qualification->getStructure() && $Qualification->getStructure()->getSetting('iv_column') == 1)
        {
            $settings['cnt']++;
            $settings['iv'] = true;
        }
        
        // Column for the hack at the end of the grid
        $settings['cnt']++;

        $TPL->set("Unit", $Unit);
        $TPL->set("Qualification", $Qualification);
        $TPL->set("criteria", $criteriaArray);
        $TPL->set("User", $User);
        $TPL->set("GT", $GT);
        $TPL->set("params", $params);
        $TPL->set("studentCols", $studentCols);
        $TPL->set("students", $students);
        $TPL->set("view", $view);
        $TPL->set("activities", $activities);
        $TPL->set("settings", $settings);
               
        try {
            $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/unit/grid.html' );
            echo json_encode( $TPL->getOutput() );
        } catch (\GT\GTException $e){
            echo json_encode( $e->getException() );
        }
        
    break;
    
    
    case 'get_class_grid':
        
        $qualID = $params['qualID'];
        $courseID = $params['courseID'];
        $groupID = $params['groupID'];
        $access = $params['access'];
        $assessmentView = $params['assessmentView'];
        $page = (isset($params['page'])) ? $params['page'] : 1;
        
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            echo json_encode( \gt_error_alert_box( get_string('invalidqual', 'block_gradetracker') ) );
            exit;
        }
        
        if ($courseID > 0){
            $Qualification->loadCourse($courseID);
        }
             
        // Do we have the permission to view the class grids?
        if (!\gt_has_capability('block/gradetracker:view_class_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
            echo json_encode( \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') ) );
            exit;      
        }
        
        // Are we a staff member on this qual? Or can we view all things?
        if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
            echo json_encode( \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') ) );
            exit;
        }
        
        
        $QualStructure = new \GT\QualificationStructure($Qualification->getStructureID());
        if (!$QualStructure->isValid()){
            echo json_encode( \gt_error_alert_box( get_string('invalidqual', 'block_gradetracker') ) );
            exit;
        }
        
        // The student list
        $students = $Qualification->getUsers("STUDENT", $courseID, $groupID, $page);
            
        // Columns for the student
        $studentCols = explode(",", $GT->getSetting('student_columns'));
        
        // Which file to load
        $file = 'grid';
                
        // Assessment view
        if (!$QualStructure->isLevelEnabled("Units") || ($assessmentView == 1 && $Qualification->getAssessments()) ){
            
            $file = 'assessment_grid';
                        
            $canSeeWeightings = false;
            $hasWeightings = false;
            if ($Qualification->getBuild()->hasQualWeightings()){

                $hasWeightings = true;                
                $canSeeWeightings = \gt_has_capability('block/gradetracker:see_weighting_percentiles');
                
                $TPL->set("weightingPercentiles", \GT\Setting::getSetting('qual_weighting_percentiles'));

            }
            
            $TPL->set("hasWeightings", $hasWeightings);
            $TPL->set("canSeeWeightings", $canSeeWeightings);
            
            
            // Assessments may have different colspans, e.g. if they have CETA enabled or have any custom fields
            $allAssessments = $Qualification->getAssessments();
            
            $defaultColspan = 0;
            if ($GT->getSetting('use_assessments_comments') == 1){
                $defaultColspan++;
            }
            
            $customFieldsArray = array();
            $colspanArray = array();
            if ($allAssessments)
            {
                
                foreach($allAssessments as $ass)
                {
                    
                    $colspan = $defaultColspan;
                    
                    // Does the assessment have CETA enabled?
                    if ($Qualification->isFeatureEnabledByName('cetagrades') && $ass->isCetaEnabled())
                    {
                        $colspan++;
                    }
                    
                    // Does this assessment have any custom fields on it?
                    $fields = $ass->getEnabledCustomFormFields();
                    $customFieldsArray[$ass->getID()] = $fields;
                    
                    $colspan += count($fields);
                    
                    // Does it have a grading method?
                    if ($ass->getSetting('grading_method') != 'none'){
                        $colspan++;
                    }
                    
                    $colspanArray[$ass->getID()] = $colspan;
                    
                }
                
            }
            
            $TPL->set("colspanArray", $colspanArray);
            $TPL->set("defaultColspan", $defaultColspan);
            $TPL->set("customFieldsArray", $customFieldsArray);

            
            $weightingColspan = 0;
            $weightingColspan += count($studentCols);
            
            // ALPS weighting
            if ($canSeeWeightings){
                $weightingColspan++;
            }
            
            if ($Qualification->isFeatureEnabledByName('targetgrades')) $weightingColspan++;
            if ($Qualification->isFeatureEnabledByName('weightedtargetgrades')) $weightingColspan++;
            if ($Qualification->isFeatureEnabledByName('cetagrades')) $weightingColspan++;
            
            $TPL->set("weightingColspan", $weightingColspan);
            
        }
                        
        $TPL->set("Qualification", $Qualification);
        $TPL->set("User", $User);
        $TPL->set("GT", $GT);
        $TPL->set("params", $params);
        $TPL->set("studentCols", $studentCols );
        $TPL->set("students", $students);
               
        try {
            $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/class/'.$file.'.html' );
            echo json_encode( $TPL->getOutput() );
        } catch (\GT\GTException $e){
            echo json_encode( $e->getException() );
        }
        
    break;
    
    // Get the popup content for a criterion with sub criteria or ranges or somesuch
    case 'get_criterion_popup':
        
        $criterion = \GT\Criterion::load($params['critID']);
        if ($criterion->isValid())
        {
            $criterion->setQualID($params['qualID']);
            $criterion->loadStudent($params['studentID']);
            echo $criterion->getPopUpContent($params['access']);
        }
        
    break;
    
    case 'get_unit_info_popup':
        
        $unit = new \GT\Unit($params['unitID']);
        if ($unit->isValid())
        {
            echo $unit->getPopUpInfo();
        }
        
    break;
    
    // Get the info for the criterion info popup
    case 'get_criterion_info_popup':
        
        // If we are trying to skip the login, check the external session is valid
        if ($skipLogin){
            if (!\gt_validate_external_session($params['extSsn'], $params['studentID'])){
                echo \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') );
                exit;
            }
        }
        
        $criterion = \GT\Criterion::load($params['critID']);
        if ($criterion->isValid())
        {
            $criterion->setQualID($params['qualID']);
            $criterion->loadStudent($params['studentID']);
            echo $criterion->getPopUpInfo();
        }
        
        
    break;
    
    // Get the popup box for adding a comment to a criterion
    case 'get_criterion_comment_popup':
        
        $criterion = \GT\Criterion::load($params['critID']);
        if ($criterion->isValid())
        {
            $criterion->setQualID($params['qualID']);
            $criterion->loadStudent($params['studentID']);
            if ($criterion->getStudent()){
                echo $criterion->getPopUpComments();
            }
        }
        
    break;
    
    // Get the info for the criterion info popup
    case 'get_assessment_info_popup':
        
        $assessment = new \GT\Assessment($params['assID']);
        if ($assessment)
        {
            $assessment->setQualification( new \GT\Qualification($params['qualID']));
            $assessment->loadStudent($params['studentID']);
            if ($assessment->getStudent()){
                echo $assessment->getPopUpInfo();
            }
        }
        
        
    break;
    
    // Get the popup box for adding a comment to a formal assessment
    case 'get_assessment_comment_popup':
        
        $assessment = new \GT\Assessment($params['assID']);
        if ($assessment)
        {
            $assessment->setQualification( new \GT\Qualification($params['qualID']));
            $assessment->loadStudent($params['studentID']);
            if ($assessment->getStudent()){
                echo $assessment->getPopUpComments();
            }
        }
        
    break;
    
    case 'get_range_info':
        
        $range = \GT\Criterion::load($params['critID']);
        if ($range->isValid())
        {
            $range->setQualID($params['qualID']);
            $range->loadStudent($params['studentID']);
            $editing = (isset($params['editing']) && $params['editing'] == 1);
            echo $range->getRangePopUpContent($editing);
        }
        
    break;
    
    case 'get_refreshed_predicted_grades':
        
        $result = array();
        
        $qualID = $params['qualID'];
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            exit;
        }
        
        // If no student, then do them all
        if (!isset($params['studentID'])){
                        
            // FInd all students on the qual and loop through them
            $students = $Qualification->getUsers("STUDENT");
            if ($students)
            {
                
                foreach($students as $student)
                {
                    
                    // Load each student into the qual, then load their unit data
                    $Qualification->loadStudent($student);
                    $Qualification->loadUnits();
                    
                    // Then calculate their predicted grades
                    $Qualification->calculatePredictedAwards();
                    
                    // Then add the relevant data to an array and append to the results array
                    $result[$student->id] = array();
                    $result[$student->id]['average'] = $Qualification->getUserAwardName('average');
                    $result[$student->id]['final'] = $Qualification->getUserAwardName('final');
                    
                }
                
            }
                                    
            
        } else {
            
            $studentID = $params['studentID'];
            
            $Qualification->loadStudent($studentID);
            $Qualification->loadUnits();
            $Qualification->calculatePredictedAwards();

            $result['average'] = $Qualification->getUserAwardName('average');
            $result['min'] = $Qualification->getUserAwardName('min');
            $result['max'] = $Qualification->getUserAwardName('max');
            $result['final'] = $Qualification->getUserAwardName('final');
            
        }
                
        echo json_encode($result);
        exit;
        
    break;
    
    case 'get_refreshed_gcse_score':
        
        $studentID = $params['studentID'];
        
        $user = new \GT\User($studentID);
        $score = $user->calculateAverageGCSEScore();
        
        $result = array();
        $result['score'] = $score;
        
        echo json_encode($result);
        exit;
        
    break;

    case 'get_refreshed_target_grade':
        
        $result = array();
        
        $studentID = $params['studentID'];
        $qualID = $params['qualID'];
                        
        $user = new \GT\User($studentID);
        
        if (is_array($qualID)){
            
            $result['gradeID'] = array();
            $result['grade'] = array();
            
            foreach($qualID as $qID)
            {
                
                $grade = $user->calculateTargetGrade($qID);
                if ($grade){
                    $result['gradeID'][$qID] = $grade->getID();
                    $result['grade'][$qID] = $grade->getName();
                }
                
            }
            
        } else {
            
            $grade = $user->calculateTargetGrade($qualID);
        
            if ($grade){
                $result['gradeID'] = $grade->getID();
                $result['grade'] = $grade->getName();
            }
            
        }
                
        echo json_encode($result);
        exit;
        
    break;
    
    // Same as above, except this is for all students on a qual, instead of just 1
    case 'get_refreshed_target_grades':
        
        $result = array();
        
        $qualID = $params['qualID'];
        $qualification = new \GT\Qualification($qualID);
        if ($qualification->isValid() && !$qualification->isDeleted())
        {
            
                        
            $users = $qualification->getUsers("STUDENT");
            if ($users)
            {
                foreach($users as $student)
                {
                 
                    $result[$student->id] = array();
           
                    // Target Grades
                    $grade = $student->calculateTargetGrade($qualification->getID());
                    if ($grade)
                    {
                        $result[$student->id]['target'] = array(
                            'result' => 1,
                            'gradeID' => $grade->getID(),
                            'grade' => $grade->getName()
                        );
                    }
                    else
                    {
                        
                        // Does it still have a grade that wasn't overwritten?
                        $oldGrade = $student->getUserGrade('target', array('qualID' => $qualification->getID()), false, true);
                        if ($oldGrade)
                        {
                            $result[$student->id]['target'] = array(
                                'result' => 1,
                                'gradeID' => $oldGrade->getID(),
                                'grade' => $oldGrade->getName(),
                                'error' => (isset($student->TargetCalculationError)) ? $student->TargetCalculationError : ''
                            );
                        }
                        else
                        {
                            $result[$student->id]['target'] = array(
                                'result' => 0,
                                'error' => (isset($student->TargetCalculationError)) ? $student->TargetCalculationError: ''
                            );
                        }
                        
                    }
                    
                    // Weighted Target Grades
                    if ($qualification->isFeatureEnabledByName('weightedtargetgrades'))
                    {
                        
                        $weighted = $student->calculateWeightedTargetGrade($qualification->getID());
                        if ($weighted)
                        {
                            
                            $result[$student->id]['weighted'] = array(
                                'result' => 1,
                                'gradeID' => $weighted->getID(),
                                'grade' => $weighted->getName()
                            );
                            
                        }
                        else
                        {
                            
                            // Does it still have a grade that wasn't overwritten?
                            $oldGrade = $student->getUserGrade('weighted_target', array('qualID' => $qualification->getID()), false, true);
                            if ($oldGrade)
                            {
                                $result[$student->id]['weighted'] = array(
                                    'result' => 1,
                                    'gradeID' => $oldGrade->getID(),
                                    'grade' => $oldGrade->getName(),
                                    'error' => (isset($student->WeightedTargetCalculationError)) ? $student->WeightedTargetCalculationError : ''
                                );
                            }
                            else
                            {
                                $result[$student->id]['weighted'] = array(
                                    'result' => 0,
                                    'error' => (isset($student->WeightedTargetCalculationError)) ? $student->WeightedTargetCalculationError: ''
                                );
                            }
                            
                        }
                        
                    }
                    
                }
            }
        }
                
        echo json_encode($result);
        exit;
        
    break;
    
    case 'get_refreshed_weighted_target_grade':
        
        $result = array();
        
        $studentID = $params['studentID'];
        $qualID = $params['qualID'];
                
        $user = new \GT\User($studentID);
        
        if (is_array($qualID)){
            
            $result['gradeID'] = array();
            $result['grade'] = array();
            
            foreach($qualID as $qID)
            {
                
                $grade = $user->calculateWeightedTargetGrade($qID);
                if ($grade){
                    $result['gradeID'][$qID] = $grade->getID();
                    $result['grade'][$qID] = $grade->getName();
                }
                
            }
            
        } else {
            
            $grade = $user->calculateWeightedTargetGrade($qualID);
        
            if ($grade){
                $result['gradeID'] = $grade->getID();
                $result['grade'] = $grade->getName();
            }
            
        }
                
        echo json_encode($result);
        exit;
        
    break;
    
    case 'get_refreshed_aspirational_grades':
        
        $result = array();
        
        $qualID = $params['qualID'];
        $qualification = new \GT\Qualification($qualID);
        if ($qualification->isValid() && !$qualification->isDeleted())
        {
            $users = $qualification->getUsers("STUDENT");
            if ($users)
            {
                foreach($users as $student)
                {
                    $grade = $student->calculateAspirationalGrade($qualification->getID());
                    if ($grade)
                    {
                        $result[$student->id] = array(
                            'result' => 1,
                            'gradeID' => $grade->getID(),
                            'grade' => $grade->getName()
                        );
                    }
                    else
                    {
                        
                        // Does it still have a grade that wasn't overwritten?
                        $oldGrade = $student->getUserGrade('aspirational', array('qualID' => $qualification->getID()), false, true);
                        if ($oldGrade)
                        {
                            $result[$student->id] = array(
                                'result' => 1,
                                'gradeID' => $oldGrade->getID(),
                                'grade' => $oldGrade->getName(),
                                'error' => (isset($student->AspTargetCalculationError)) ? $student->AspTargetCalculationError : ''
                            );
                        }
                        else
                        {
                            $result[$student->id] = array(
                                'result' => 0,
                                'error' => (isset($student->AspTargetCalculationError)) ? $student->AspTargetCalculationError: ''
                            );
                        }
                        
                    }
                }
            }
        }
        
        echo json_encode($result);
        exit;
        
    break;
    
    case 'get_rule_events':
        $events = \GT\Rule::getEvents();
        echo json_encode($events);
    break;
    
    case 'get_rule_comparisons':
        $rule = new \GT\Rule();
        $comparisons = $rule->getAllOperators();
        echo json_encode($comparisons);
    break;

    case 'get_builds':
        
        $return = array();
        $builds = \GT\QualificationBuild::getAllBuilds($params['structureID']);
        if ($builds)
        {
            foreach($builds as $build)
            {
                $return[$build->getID()] = $build->getName();
            }
        }
        
        echo json_encode($return);
        
    break;

    case 'get_build_defaults':
        
        $buildID = $params['buildID'];
        $build = new \GT\QualificationBuild($buildID);
        $defaults = array();
        if ($build->isValid())
        {
            $defaults = $build->getAllDefaultValues();
        }
        echo json_encode($defaults);
        
    break;
    
    case 'get_unit_defaults':
        
        $build = \GT\UnitBuild::load($params['structureID'], $params['levelID']);
        $defaults = $build->getAllDefaultValues();
        echo json_encode($defaults);
        
    break;

    case 'get_filtered_quals':
        
        $quals = \GT\Qualification::search( array(
            "structureID" => $params['structureID'],
            "levelID" => $params['levelID'],
            "subTypeID" => $params['subTypeID'],
            "name" => $params['name']
        ));
        
        $results = array();
        
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $results[] = array(
                    'id' => $qual->getID(),
                    'name' => $qual->getDisplayName(),
                    'title' => addslashes($qual->getDisplayName())
                );
            }
        }
        
        echo json_encode($results);
        
    break;

    case 'get_filtered_units':
        
        $units = \GT\Unit::search( array(
            "structureID" => $params['structureID'],
            "levelID" => $params['levelID'],
            "nameORcode" => $params['name']
        ) );
        
        $results = array();
        
        if ($units)
        {
            foreach($units as $unit)
            {
                $results[] = array(
                    'id' => $unit->getID(),
                    'name' => $unit->getOptionName(),
                    'title' => addslashes($unit->getDisplayName())
                );
            }
        }
        
        echo json_encode($results);
        
    break;
    
    case 'get_filtered_courses':
        
        $courses = \GT\Course::search( array(
            "name" => $params['name'],
            "catID" => $params['catID']
        ) );
                
        $results = array();
        
        if ($courses)
        {
            foreach($courses as $course)
            {
                $results[] = array(
                    'id' => $course->id,
                    'name' => $course->getName(),
                    'title' => addslashes($course->getName())
                );
            }
        }
                
        echo json_encode($results);
        
    break;
    
    case 'get_criterion_options':
        
        $results = array();
        $criterion = \GT\Criterion::load(false, $params['critType']);
        if ($criterion)
        {
            $results = $criterion->getFormOptions();
            if ($results)
            {
                foreach($results as $result)
                {
                    $element = \GT\FormElement::create($result);
                    $result->element = $element->display( array('name' => 'unit_criteria['.$params['num'].'][options]') );
                }
            }
        }
        
        echo json_encode($results);
        
    break;
    
    case 'get_met_values':
                
        $GradingStructure = new \GT\CriteriaAwardStructure($params['gradingStructureID']);
        if (!$GradingStructure->isValid())
        {
            exit;
        }
        
        $return = array();
        $awards = $GradingStructure->getAwards(true);
        if ($awards)
        {
            foreach($awards as $award)
            {
                $obj = new \stdClass();
                $obj->id = $award->getID();
                $obj->name = $award->getShortName();
                $obj->fullname = $award->getName();
                $return[] = $obj;
            }
        }
        
       echo json_encode($return);
        
    break;
    
    case 'get_criterion_sub_row':
                
        $output = '';
                        
        $criterion = \GT\Criterion::load($params['critID'], $params['critType'], true);
        if ($criterion && $params['num'] > 0)
        {
                       
            $criterion->setGradingStructureID($params['gradingID']);
            $criterion->loadChildren();
                        
            $TPL = new \GT\Template();
            $TPL->set("criterion", $criterion)->set("dynamicNum", $params['num']);
            try {
                $output = $TPL->load($CFG->dirroot . '/blocks/gradetracker/tpl/config/units/criteria_types/'.$criterion->hasFormSubRow().'.html');
            } catch (\GT\GTException $e){
                // do nothing
            }
            
        }
        
        echo json_encode( $output );
        
    break;
    
    case 'get_mod_hook_unit':
        
        // if the course and the course module are set
        if (isset($params['courseID'], $params['cmID'])){
            $course = new \GT\Course($params['courseID']);
            $activity = $course->getActivity($params['cmID']);
        }
                 
        $unit = new \GT\Unit($params['unitID']);
        
        $return = array();
        $return['unit'] = $unit->getDisplayName();
        $return['criteria'] = array();
        if ($unit)
        {
            $criteria = $unit->sortCriteria(false, true);
            if ($criteria)
            {
                foreach($criteria as $criterion)
                {
                    $obj = new \stdClass();
                    $obj->id = $criterion->getID();
                    $obj->name = $criterion->getName();
                    $return['criteria'][] = $obj;
                }
            }
        }
        
        if (isset($activity) && $activity){
            $return['parts'] = $activity->getRecordParts();
        }
        
        echo json_encode( $return );
        
    break;
    
    case 'get_mod_hook_unit_activities':
        
        $unitMods = \GT\ModuleLink::getModulesOnUnit($params['qualID'], $params['unitID'], $params['courseID']);
        $return = array();
        $criteriaArray = array();
        
        // The unit's criteria
        $unit = new \GT\Unit($params['unitID']);
        if ($unit)
        {
            $criteria = $unit->sortCriteria(false, true);
            if ($criteria)
            {
                foreach($criteria as $criterion)
                {
                    $obj = new \stdClass();
                    $obj->id = $criterion->getID();
                    $obj->name = $criterion->getName();
                    $criteriaArray[] = $obj;
                }
            }
        }
        
        // Course Mods on this qual unit
        if ($unitMods)
        {
            foreach($unitMods as $unitMod)
            {
                $obj = new stdClass();
                $obj->id = $unitMod->getCourseModID();
                $obj->modID = $unitMod->getModID();
                $obj->icon = $unitMod->getModIcon();
                $obj->name = $unitMod->getRecordName();
                $obj->due = $unitMod->getRecordDueDate('D jS M Y, H:i');
                $obj->criteria = $criteriaArray;
                
                $obj->linked = array();
                if ($criteriaArray)
                {
                    foreach($criteriaArray as $crit)
                    {
                        if ($check = \GT\Activity::checkExists($unitMod->getCourseModID(), $params['qualID'], $unit->getID(), $crit->id))
                        {
                            $obj->linked[] = $crit->id;
                        }
                    }
                }
                
                $obj->parts = $unitMod->getRecordParts();
                if ($obj->parts)
                {
                    $obj->partsLinked = array();
                    if ($criteriaArray)
                    {
                        foreach($criteriaArray as $crit)
                        {
                            if ($check = \GT\Activity::checkExists($unitMod->getCourseModID(), $params['qualID'], $unit->getID(), $crit->id, false))
                            {
                                if ($check && !is_null($check->partid))
                                {
                                    $obj->partsLinked[$crit->id] = $check->partid;
                                }
                            }
                        }
                    }
                }
                
                $return[] = $obj;
                
            }
        }
                        
        echo json_encode($return);
        
    break;
    
    case 'get_mod_hook_activity':
        
        $course = new \GT\Course($params['courseID']);
        $activity = $course->getActivity($params['cmID']);
        
        $criteriaArray = array();
        
        // The unit's criteria
        $unit = new \GT\Unit($params['unitID']);
        if ($unit)
        {
            $criteria = $unit->sortCriteria(false, true);
            if ($criteria)
            {
                foreach($criteria as $criterion)
                {
                    $obj = new \stdClass();
                    $obj->id = $criterion->getID();
                    $obj->name = $criterion->getName();
                    $criteriaArray[] = $obj;
                }
            }
        }
                
        // The object to return
        $obj = new stdClass();
        $obj->id = $activity->getCourseModID();
        $obj->modID = $activity->getModID();
        $obj->icon = $activity->getModIcon();
        $obj->name = $activity->getRecordName();
        $obj->due = $activity->getRecordDueDate('D jS M Y, H:i');
        $obj->parts = $activity->getRecordParts();
        $obj->criteria = $criteriaArray;
                
        echo json_encode($obj);
        
    break;
    
    case 'get_qualification_report':
                
        global $GTEXE, $User;
        
        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->QUAL_STRUCTURE_MIN_LOAD = true;
        $GTEXE->QUAL_BUILD_MIN_LOAD = true;
        $GTEXE->QUAL_MIN_LOAD = true;
        $GTEXE->UNIT_MIN_LOAD = true;
        $GTEXE->UNIT_NO_SORT = true;
        
        $qualification = new \GT\Qualification\DataQualification($params['qualid']);
        if ($qualification->isValid() && !$qualification->isDeleted()){
            
            $structure = $qualification->getStructure();
            $awards = $qualification->getUnitAwards();
            $names = $qualification->getHeaderCriteriaNames();
            $uniquename = $qualification->getHeaderCriteriaNamesShort($names);
            $view = $structure->getSetting('custom_dashboard_view');
            
            // 20-03-2017 - I need to revisit this, as having so many joins breaks it. e.g. on CG NVQ/VRQ there can be over 60 joins and mysql ahs a limit of 61
            //            - For now adding in a check on the number and just not letting it even try if it's too many
            if (count($names) > 20 || count($uniquename) > 20){
                $view = 'none';
            }
            // End of check
                        
            $studentsReport = $qualification->getQualificationReportStudents($awards, $view, $names, $uniquename);
            $unitsReport = $qualification->getQualificationReportUnits($awards);
            
            $usedFieldNames = array();
            $usedFieldNames['unit'] = array();
            $usedFieldNames['crit'] = array();
            
            $TPL = new \GT\Template();
            $TPL->set('qualification', $qualification);
            $TPL->set('studentsReport', $studentsReport);
            $TPL->set('unitsReport', $unitsReport);
            $TPL->set('awards', $awards);
            $TPL->set('structure', $structure);
            $TPL->set('view', $view);
            $TPL->set('names', $names);
            $TPL->set('uniquename', $uniquename);
            $TPL->set('usedFieldNames', $usedFieldNames);
            $TPL->set('User', $User);
            $TPL->load($CFG->dirroot . '/blocks/gradetracker/tpl/reporting/reporting_table.html');
            $TPL->display();
            exit;
            
        }
        
        
    break;
    
    case 'get_criterion_activities_overview':
        
        $result = array();
        
        $qual = new \GT\Qualification($params['qualID']);
        $unit = ($qual->isValid()) ? $qual->getUnit($params['unitID']) : false;
        $criterion = ($unit && $unit->isValid()) ? $unit->getCriterion($params['critID']) : false;
        if ($qual->isValid() && $unit && $unit->isValid() && $criterion && $criterion->isValid())
        {
        
            $result['qualification'] = $qual->getDisplayName();
            $result['unit'] = $unit->getDisplayName();
            $result['criterion'] = $criterion->getName();
            $result['links'] = array();
            
            $courseModules = \GT\Activity::getCourseModulesLinkedToUnit($params['qualID'], $params['unitID'], $params['critID']);

            if ($courseModules)
            {
                foreach($courseModules as $courseModID)
                {
                    $mod = \GT\ModuleLink::getModuleLinkFromCourseModule($courseModID);
                    $obj = new \stdClass();
                    $obj->cmid = $courseModID;
                    $obj->name = $mod->getRecordName();
                    $obj->modicon = $mod->getModIcon();
                    $obj->modname = $mod->getModName();
                    $obj->criteria = array();
                    $criteria = $mod->getCriteriaOnModule($params['qualID'], $unit, false);
                    if ($criteria)
                    {
                        foreach($criteria as $criterion)
                        {
                            $crit = new \stdClass();
                            $crit->id = $criterion->getID();
                            $crit->name = $criterion->getName();
                            $obj->criteria[$criterion->getID()] = $crit;
                        }
                    }
                    $result['links'][] = $obj;
                }
            }
        
        }
                        
        echo json_encode($result);
        
    break;
    
    
    case 'download_report':
                
        $Report = false;
        
        // Criteria Progress report
        if ($params['report'] == 'CP'){
            $Report = new \GT\Reports\CriteriaProgressReport();
        } elseif ($params['report'] == 'PCP'){
            $Report = new \GT\Reports\PassCriteriaProgressReport();
        } elseif ($params['report'] == 'PCS'){
            $Report = new \GT\Reports\PassCriteriaSummaryReport();
        }
        
        if ($Report){
            $Report->run( $params );
        } 
        
        exit;
        
    break;
    
    case 'get_rule_form':
        
        $TPL = new \GT\Template();
        foreach($params as $param => $val){
            $TPL->set($param, $val);
        }
        $TPL->load($CFG->dirroot . '/blocks/gradetracker/tpl/config/structures/qual/inc/rule.inc.html');
        $TPL->display();
        exit;       
        
    break;
    
    case 'get_rule_fx_panel':
        
        $TPL = new \GT\Template();
        if ($params){
            foreach($params as $param => $val){
                $TPL->set($param, $val);
            }
        }
        $TPL->load($CFG->dirroot . '/blocks/gradetracker/tpl/config/structures/qual/inc/rulefx.inc.html');
        $TPL->display();
        exit;
        
    break;
    
    case 'verify_rule_condition':
        
    break;

    case 'get_rule_fx_element_options':
                                
        $RuleVerifier = new \GT\RuleVerifier();
        $fromType = (isset($params['fromType']) && $params['fromType'] != '') ? $params['fromType'] : null;
        $fromVal = (isset($params['fromVal']) && $params['fromVal'] != '') ? $params['fromVal'] : null;
        $options = array();
        
        switch($params['type'])
        {
        
            case 'object':
                
                $options = $RuleVerifier->getPossibleObjects($fromType, $fromVal);
                
            break;
        
            case 'method':
                
                $options = $RuleVerifier->getPossibleMethods($fromType, $fromVal);
                
            break;
        
            case 'filter':
                
                $options = $RuleVerifier->getPossibleFilters($fromType, $fromVal);
                               
            break;
        
        }
        
        echo json_encode($options);
        exit;
        
    break;

    case 'get_rule_fx_links':
        
        $RuleVerifier = new \GT\RuleVerifier();
        echo json_encode( $RuleVerifier->getPossibleNextStages($params) );
        
    break;

    case 'get_rule_set_template':

        $TPL = new \GT\Template();
        foreach($params as $param => $val){
            $TPL->set($param, $val);
        }
        $TPL->load($CFG->dirroot . '/blocks/gradetracker/tpl/config/structures/qual/inc/ruleset.inc.html');
        $TPL->display();
        exit; 
        
    break;
    
    case 'get_qual_units':
        
        $return = array('units' => array(), 'order' => array());
        
        $qual = new \GT\Qualification($params['qualID']);
        if ($qual->isValid())
        {
            
            $units = $qual->getUnits();
            
            $sort = new \GT\Sorter();
            $sort->sortUnits($units);
                        
            if ($units)
            {
                foreach($units as $unit)
                {
                    $return['units'][$unit->getID()] = $unit->getDisplayName();
                    $return['order'][] = $unit->getID();
                }
            }
            
        }
                
        echo json_encode($return);
        exit;
        
    break;
    
    case 'get_qual_assessments':
        
        $return = array('ass' => array(), 'order' => array());
        
        $qual = new \GT\Qualification($params['qualID']);
        if ($qual->isValid())
        {
            
            $assessments = $qual->getAssessments();                        
            if ($assessments)
            {
                foreach($assessments as $ass)
                {
                    $return['ass'][$ass->getID()] = $ass->getName();
                    $return['order'][] = $ass->getID();
                }
            }
            
        }
                
        echo json_encode($return);
        exit;
        
    break;
    
    case 'get_unit_criteria':
    
        $return = array('criteria' => array(), 'order' => array());
        
        $unit = new \GT\Unit($params['unitID']);
        if ($unit->isValid())
        {
            
            $criteria = $unit->loadCriteriaIntoFlatArray();   
            $sort = new \GT\Sorter();
            $structure = $unit->getStructure();
            $customOrder = $structure->getCustomOrder('criteria');
                        
            // Sort the criteria
            if ($customOrder){
                $sort->sortCriteriaCustom($criteria, $customOrder);
            } else {
                $sort->sortCriteria($criteria);
            }
                        
            // Now loop through each criteria and add to the return array
            if ($criteria)
            {
                foreach($criteria as $crit)
                {
                    $return['criteria'][$crit->getID()] = $crit->getName();
                    $return['order'][] = $crit->getID();
                }
            }
            
        }
                
        echo json_encode($return);
        exit;
        
    break;

}

exit;