<?php
/**
 * Print
 *
 * This generates a simpler, printable tracking grid
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

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

// Need to be logged in to view this page
require_login();

// Parameters
$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$access = optional_param('access', 'v', PARAM_TEXT); // v = View, e = Edit
$advanced = optional_param('adv', 0, PARAM_INT);
$context = context_course::instance(SITEID);
$ass = optional_param('ass', 0, PARAM_INT); 
$courseID = optional_param('courseID', false, PARAM_INT);
$groupID = optional_param('groupID', 0, PARAM_INT);
$view = optional_param('view', false, PARAM_TEXT);

$title = get_string('grid', 'block_gradetracker');
$settings = array();
$settings['cnt'] = 0;
$settings['activitycnt'] = 0;
$settings['percentage'] = false;
$settings['iv'] = false;
        
$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);

// Set print variable so we can show/hide things in template files accordingly
$TPL->set("print", true);

switch($type)
{
    
    case 'student':
        
        $qualID = required_param('qualID', PARAM_INT);
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            print_error('norecord', 'block_gradetracker');
        }
        
        $Student = new \GT\User($id);
        if (!$Student->isValid()){
            print_error('invaliduser', 'block_gradetracker');
        }
        
        $Qualification->getStudentGrid( 'TPL', 
            array(
                'student' => $Student,
                'TPL' => $TPL,
                'access' => $access,
                'courseID' => $courseID,
                'print' => true
            )
        );
                
        $title = get_string('studentgrid', 'block_gradetracker') . ' - ' . $Student->getDisplayName() . ' - ' . $Qualification->getDisplayName();
        
    break;
    
    case 'unit':
        
        $page = optional_param('page', 1, PARAM_INT);
        $qualID = required_param('qualID', PARAM_INT);
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            print_error('norecord', 'block_gradetracker');
        }
        
        $Unit = $Qualification->getUnit($id);
        if (!$Unit){
            print_error('norecord', 'block_gradetracker');
        }

        // Do we have the permission to view the unit grids?
        if (!\gt_has_capability('block/gradetracker:view_unit_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
            print_error('invalidaccess', 'block_gradetracker');
        }
        
        // Are we a staff member on this unit and this qual?
        if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
            print_error('invalidaccess', 'block_gradetracker');
        }
                
        
        $QualStructure = new \GT\QualificationStructure( $Qualification->getStructureID() );
        
        // Is disabled
        if (!$QualStructure->isEnabled()){
            print_error('structureisdisabled', 'block_gradetracker');
        }
        
        // Navigation links
        $TPL->set("links", $GT->getUnitGridNavigation());
        
        // Values for grid key
        $TPL->set("allPossibleValues", $Unit->getAllPossibleValues());
        
        // Main variables
        $TPL->set("Unit", $Unit);
        $TPL->set("Qualification", $Qualification);
        
        $students = $Unit->getUsers("STUDENT", false, $courseID, $groupID);
        $criteriaArray = $Unit->getHeaderCriteriaNamesFlat($view);
                
        $TPL->set("page", $page);
        $TPL->set("students", $students);
        $TPL->set("studentCols", explode(",", $GT->getSetting('student_columns')) );
        $TPL->set("groupID", $groupID);
        $TPL->set("courseID", $courseID);
        $TPL->set("criteria", $criteriaArray);
        
        if ($Qualification->isFeatureEnabledByName('percentagecomp')){
            $settings['percentage'] = true;
        }
        
        // IV Column
        if ($Qualification->getStructure() && $Qualification->getStructure()->getSetting('iv_column') == 1)
        {
            $settings['iv'] = true;
        }

        $title = get_string('unitgrid', 'block_gradetracker') . ' - ' . $Unit->getDisplayName() . ' - ' . $Qualification->getDisplayName();
        
    break;

    case 'class':
        
        $assessmentView = false;
        $gridFile = 'grid';

        $page = optional_param('page', 1, PARAM_INT);
        $qualID = required_param('id', PARAM_INT);
        $courseID = optional_param('courseID', false, PARAM_INT);
        
        $canSeeWeightings = false;
        $hasWeightings = false;
        
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            print_error('norecord', 'block_gradetracker');
        }
        
        // Do we have the permission to view the class grids?
        if (!\gt_has_capability('block/gradetracker:view_class_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
            print_error('invalidaccess', 'block_gradetracker');
        }
        
        // Are we a staff member on this qual? Or can we view all things?
        if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
            print_error('invalidaccess', 'block_gradetracker');
        }
        
        // If the qualification has no units, display the assessment grid instead
        $QualStructure = new \GT\QualificationStructure( $Qualification->getStructureID() );
               
        // Is disabled
        if (!$QualStructure->isEnabled()){
            print_error('structureisdisabled', 'block_gradetracker');
        }
        
        if (!$QualStructure->isLevelEnabled("Units") || ($ass == 1 && $Qualification->getAssessments()) ){
            
            $gridFile = 'assessment_grid';
            $assessmentView = true;
        
            // Check if we are using qual weightings
            if ($Qualification->getBuild()->hasQualWeightings()){

                $hasWeightings = true;                
                $canSeeWeightings = \gt_has_capability('block/gradetracker:see_weighting_percentiles');
                
                $TPL->set("weightingPercentiles", \GT\Setting::getSetting('qual_weighting_percentiles'));

            }
            
            $TPL->set("hasWeightings", $hasWeightings);
            $TPL->set("canSeeWeightings", $canSeeWeightings);
            
            // Assessments may have different colspans, e.g. if they have CETA enabled or have any custom fields
            $allAssessments = $Qualification->getAssessments();
            
            $defaultColspan = 1;
            
            // No comments column in printing
            
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
                    
                    // Comments column
                    $colspanArray[$ass->getID()] = $colspan;
                    
                }
                
            }
            
            $TPL->set("colspanArray", $colspanArray);
            $TPL->set("defaultColspan", $defaultColspan);
            $TPL->set("customFieldsArray", $customFieldsArray);
        
        }
        
        
        
        // Load course into Qual if we are using one
        if ($courseID > 0){
            $Qualification->loadCourse($courseID);
        }
                        
        $TPL->set("Qualification", $Qualification);
        $TPL->set("links", $GT->getClassGridNavigation());
        
        
        // Get the students to display
        $students = $Qualification->getUsers("STUDENT", $courseID, $groupID, false);
                
                
        $TPL->set("page", $page);
        $TPL->set("courseID", $courseID);
        $TPL->set("studentCols", explode(",", $GT->getSetting('student_columns')) );
        $TPL->set("students", $students);
        
        // Weighting variables
        $weightingColspan = 0;
        
        // Columns for the student
        $studentCols = explode(",", $GT->getSetting('student_columns'));
        $weightingColspan += count($studentCols);

        // ALPS weighting
        if ($canSeeWeightings){
            $weightingColspan++;
        }

        if ($Qualification->isFeatureEnabledByName('targetgrades')) $weightingColspan++;
        if ($Qualification->isFeatureEnabledByName('weightedtargetgrades')) $weightingColspan++;
        if ($Qualification->isFeatureEnabledByName('cetagrades')) $weightingColspan++;

        $TPL->set("weightingColspan", $weightingColspan);
        
        // Capabilities
        $TPL->set("canSeeValueAdded", \gt_has_capability('block/gradetracker:see_value_added'));
        $TPL->set("canSeeBothTargets", \gt_has_capability('block/gradetracker:see_both_target_weighted_target_grades'));
        $TPL->set("canSeeTargetGrade", \gt_has_capability('block/gradetracker:see_target_grade'));
        $TPL->set("canSeeWeightedTargetGrade", \gt_has_capability('block/gradetracker:see_weighted_target_grade'));
        
        $TPL->set("assessmentView", $assessmentView);
       
        $TPL->set("groupID", $groupID);
        $TPL->set("gridFile", $gridFile);
        
        $title = get_string('classgrid', 'block_gradetracker') . ' - ' . $Qualification->getDisplayName();
        
    break;
    
    default:
        
        print_error('invalidgridtype', 'block_gradetracker');
        
    break;
    
}





// Set up PAGE
$PAGE->set_context( $context );
$PAGE->set_url($CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->set_heading( get_string('config', 'block_gradetracker') );
$PAGE->set_cacheable(true);
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );

$TPL->set("GT", $GT)
    ->set("User", $User)
    ->set("access", $access)
    ->set("params", array('access' => 'v'))
    ->set("grid", $type)
    ->set("view", $view)
    ->set("settings", $settings);

try {
    $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/print.html' );
    $TPL->display();
} catch (\GT\GTException $e){
    echo $e->getException();
}