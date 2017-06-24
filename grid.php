<?php
/**
 * Grid
 *
 * This displays the actual tracking grids
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
$groupID = optional_param('groupID', 0 ,PARAM_INT);
$ass = optional_param('ass', 0, PARAM_INT); 
$courseID = optional_param('courseID', false, PARAM_INT);
$context = context_course::instance(SITEID);

$title = get_string('grid', 'block_gradetracker');
$gridFile = 'grid';


$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);
$Log = new \GT\Log();
$Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;

switch($type)
{
    
    case 'student':
        
        // Force access to "view" in case they tried to go to edit but don't have permission
        if (!\gt_has_capability('block/gradetracker:edit_student_grids')){
            $access = 'v';
        }
        
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
                'courseID' => $courseID
            )
        );
        
        $title = get_string('studentgrid', 'block_gradetracker') . ' - ' . $Student->getDisplayName() . ' - ' . $Qualification->getDisplayName();
        
        // Log info
        $Log->details = \GT\Log::GT_LOG_DETAILS_VIEWED_STUDENT_GRID;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $Qualification->getID())
            ->addAttribute(\GT\Log::GT_LOG_ATT_STUDID, $Student->id);        
        
       
        
    break;
    
    case 'unit':
        
        // Force access to "view" in case they tried to go to edit but don't have permission
        if (!\gt_has_capability('block/gradetracker:edit_unit_grids')){
            $access = 'v';
        }
        
        $view = optional_param('view', false, PARAM_TEXT);
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
        
        // Are we a staff member on this unit and this qual? Or can we view all things?
        if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
            print_error('invalidaccess', 'block_gradetracker');
        }
        
        // Is disabled
        $QualStructure = new \GT\QualificationStructure( $Qualification->getStructureID() );
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
        
        // Page related variables
        $perPage = $GT->getSetting('unit_grid_paging');
        if ($perPage > 0){
            $cntStudents = count( $Unit->getUsers("STUDENT", false, $courseID, $groupID) );
            $reqPages = ceil( $cntStudents / $perPage );
            $TPL->set("reqPages", $reqPages);
        }    
        
        if ($courseID > 0){
            $Qualification->loadCourse($courseID);
            $Course = new \GT\Course($courseID);
            $TPL->set("Course", $Course);
        }
        
        $TPL->set("page", $page);
        $TPL->set("courseID", $courseID);
        $TPL->set("groupID", $groupID);
        $TPL->set("view", $view);
        $TPL->set("gridFile", $gridFile);
        
        $title = get_string('unitgrid', 'block_gradetracker') . ' - ' . $Unit->getDisplayName() . ' - ' . $Qualification->getDisplayName();
        
        // Log info
        $Log->details = \GT\Log::GT_LOG_DETAILS_VIEWED_UNIT_GRID;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $Qualification->getID())
            ->addAttribute(\GT\Log::GT_LOG_ATT_UNITID, $Unit->getID());
        
    break;

    case 'class':
        
        $assessmentView = false;
        
        // Force access to "view" in case they tried to go to edit but don't have permission
        if (!\gt_has_capability('block/gradetracker:edit_class_grids')){
            $access = 'v';
        }
        
        $page = optional_param('page', 1, PARAM_INT);
        $qualID = required_param('id', PARAM_INT);
        
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
        } 
        
        // Check if we are using qual weightings
        $hasWeightings = false;
        if ($Qualification->getBuild()->hasQualWeightings()){
            $hasWeightings = true;
            $TPL->set("weightingPercentiles", \GT\Setting::getSetting('qual_weighting_percentiles'));            
        }
               
        if ($courseID > 0){
            $Qualification->loadCourse($courseID);
            $Course = new \GT\Course($courseID);
            $TPL->set("Course", $Course);
        }
        
        $TPL->set("Qualification", $Qualification);
        $TPL->set("links", $GT->getClassGridNavigation());
        
        $students = $Qualification->getUsers("STUDENT", $courseID, $groupID, false);
       
        // Page related variables
        $perPage = $GT->getSetting('class_grid_paging');
        if ($perPage > 0){
            $cntStudents = count($students);
            $reqPages = ceil( $cntStudents / $perPage );
            $TPL->set("reqPages", $reqPages);
        }    
                
        $TPL->set("page", $page);
        $TPL->set("courseID", $courseID);
        $TPL->set("groupID", $groupID);
        
        // Weighting variable
        $TPL->set("hasWeightings", $hasWeightings);
        
        // Capabilities
        $TPL->set("canSeeValueAdded", \gt_has_capability('block/gradetracker:see_value_added'));
        $TPL->set("canSeeBothTargets", \gt_has_capability('block/gradetracker:see_both_target_weighted_target_grades'));
        $TPL->set("canSeeTargetGrade", \gt_has_capability('block/gradetracker:see_target_grade'));
        $TPL->set("canSeeWeightedTargetGrade", \gt_has_capability('block/gradetracker:see_weighted_target_grade'));
        
        $TPL->set("assessmentView", $assessmentView);
        $TPL->set("gridFile", $gridFile);
        
        $title = get_string('classgrid', 'block_gradetracker') . ' - ' . $Qualification->getDisplayName();
        
        // Log info
        $Log->details = \GT\Log::GT_LOG_DETAILS_VIEWED_CLASS_GRID;
        $Log->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $Qualification->getID());
        
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

$GT->loadJavascript();
$GT->loadCSS();

if ( gt_has_capability('block/gradetracker:configure') ){
    $link = $CFG->wwwroot . '/blocks/gradetracker/config.php';
} else {
    $link = $CFG->wwwroot . '/blocks/gradetracker/my.php';
}

if (isset($Course) && $Course->isValid()){
    $PAGE->navbar->add( $Course->getName(), $CFG->wwwroot . '/course/view.php?id=' . $Course->id );
}
$PAGE->navbar->add( $GT->getPluginTitle(), $link);
$PAGE->navbar->add( get_string('trackers', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/choose.php', navigation_node::TYPE_CUSTOM);

$PAGE->requires->js( '/blocks/gradetracker/js/grids/scripts.js', true );
$PAGE->requires->js( '/blocks/gradetracker/js/grids/'.$type.'.js', true );
$PAGE->requires->js_init_call("gt_bindings", null, true);
$PAGE->requires->js_init_call("grid_bindings", null, true);
$PAGE->requires->js_init_call("{$type}_grid_bindings", null, true);


$PAGE->set_title( $SITE->shortname . ': ' . $GT->getPluginTitle() . ': ' . $title );

echo $OUTPUT->header();

$TPL->set("GT", $GT)
    ->set("User", $User)
    ->set("access", $access)
    ->set("courseID", $courseID);

try {
    $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/'.$type.'.html' );
    $TPL->display();
} catch (\GT\GTException $e){
    echo $e->getException();
}

echo $OUTPUT->footer();

// Log the viewing event
$Log->save();