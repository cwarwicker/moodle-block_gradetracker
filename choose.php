<?php
/**
 * Choose
 *
 * This is where you get lists of students, units, etc... to choose what grid you want to look at
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
$cID = optional_param('cID', false, PARAM_INT);
$type = optional_param('type', 'student', PARAM_TEXT);


// must be param_text for ctype_digit check
$searchAllQID = optional_param('searchQualID', false, PARAM_TEXT);
$searchMyQID = optional_param('searchMyID', false, PARAM_TEXT);
$searchAllCID = optional_param('searchCourseID', false, PARAM_TEXT);
        
$myQualID = isset($_REQUEST['myQualID']) ? $_REQUEST['myQualID'] : false;
$myCourseID = isset($_REQUEST['myCourseID']) ? $_REQUEST['myCourseID'] : false;

$course = false;
$context = context_course::instance(SITEID);

if ($cID){
    $course = new \GT\Course($cID);
    if (!$course->isValid()){
        print_error( get_string('invalidcourseid') );
    }
    $context = context_course::instance($course->id);
} elseif ($myCourseID){
    $cID = $myCourseID;
}

// Check permissions
if (!gt_has_capability('block/gradetracker:view_'.$type.'_grids')){
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$results = null;

//\  \
// \  \
//  \  \
//   \  \
//    \  \
//     \  \
//      \  \
//       \  \  
$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);

$searchQualification = false;
$searchCourse = false;
    
// Submitted Filter for All Qualifications
if ($User->hasCapability('block/gradetracker:view_all_quals') && isset($_REQUEST['submit_filter_all'])){
            
    $searchQualification = false;
    $searchCourse = false;
    
    // If searching by all Qualifications
    if (ctype_digit($searchAllQID) && $searchAllQID > 0){
        $searchQualification = new \GT\Qualification($searchAllQID);
        if (!$searchQualification->isValid()){
            $searchQualification = false;
        }
    }
    
    // If searching by all Courses
    if (ctype_digit($searchAllCID) && $searchAllCID > 0){
        $searchCourse = new \GT\Course($searchAllCID);
        if (!$searchCourse->isValid()){
            $searchCourse = false;
        }
    }
    
}

elseif (isset($_POST['submit_filter_my']) || $myCourseID > 0 || isset($_REQUEST['submit_filter_my'])){
    
    $searchQualification = false;
    
    // Selecting one of My Quals
    if (ctype_digit($myQualID) && $myQualID > 0){
        $searchQualification = new \GT\Qualification($myQualID);
        if (!$searchQualification->isValid() || !$User->isOnQual($myQualID, "STAFF")){
            $searchQualification = false;
        }
    }
    
    // Selecting one of My Courses
    if (ctype_digit($myCourseID) && $myCourseID > 0){
        $searchCourse = new \GT\Course($myCourseID);
        if (!$searchCourse->isValid()){
            $searchCourse = false;
        }
    }
    
}



switch($type)
{

    // Get results for Student grid
    case 'student':

        // Get list of students on this qual
        if ($searchQualification){
            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification->getUsers("STUDENT");
        }

        // Get list of students on this course
        elseif ($searchCourse){

            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()){

                $results = array();

                foreach($searchCourse->getCourseQualifications() as $courseQual){

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual->getUsers("STUDENT", $searchCourse->id);

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()){

                foreach($searchCourse->getChildCourses() as $child){

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()){

                        foreach($child->getCourseQualifications() as $courseQual){

                            $results[$child->id][$courseQual->getID()] = $courseQual->getUsers("STUDENT", $child->id);

                        }

                    }

                }

            }

        }

    break;
    
    
    case 'unit':
        
        if ($searchQualification){
            
            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification->getUnits();
                    
        } elseif ($searchCourse){
            
            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()){

                $results = array();

                foreach($searchCourse->getCourseQualifications() as $courseQual){

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual->getUnits();

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()){

                foreach($searchCourse->getChildCourses() as $child){

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()){

                        foreach($child->getCourseQualifications() as $courseQual){

                            $results[$child->id][$courseQual->getID()] = $courseQual->getUnits();

                        }

                    }

                }

            }
            
        }
        
    break;
    
    
    
    case 'class':
        
        if ($searchQualification){
            
            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification;
                    
        } elseif ($searchCourse){
            
            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()){

                $results = array();

                foreach($searchCourse->getCourseQualifications() as $courseQual){

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual;

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()){

                foreach($searchCourse->getChildCourses() as $child){

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()){

                        foreach($child->getCourseQualifications() as $courseQual){

                            $results[$child->id][$courseQual->getID()] = $courseQual;

                        }

                    }

                }

            }
            
        }
        
        // If there is only 1 result, just jump straight to the class grid
        if (count($results) == 1 && count(reset($results)) == 1){
            $cID = key($results);
            $result = reset($results);
            $result = reset($result);
            redirect($CFG->wwwroot . "/blocks/gradetracker/grid.php?type=class&id={$result->getID()}&courseID={$cID}&access=v");
        }
        
    break;
    
    

}

$TPL->set("courseID", $cID);
$TPL->set("searchAllQID", $searchAllQID);
$TPL->set("searchAllCID", $searchAllCID);
$TPL->set("myQualID", $myQualID);
$TPL->set("myCourseID", $myCourseID);
$TPL->set("searchQualification", $searchQualification);
$TPL->set("searchCourse", $searchCourse);
        




// Set up PAGE
$PAGE->set_context( $context );
$PAGE->set_url($CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->set_heading( get_string('config', 'block_gradetracker') );
$PAGE->set_cacheable(true);
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );

$GT->loadJavascript();
$GT->loadCSS();

if ( $User->hasCapability('block/gradetracker:configure') ){
    $link = $CFG->wwwroot . '/blocks/gradetracker/config.php';
} else {
    $link = $CFG->wwwroot . '/blocks/gradetracker/my.php';
}

$PAGE->navbar->add( $GT->getPluginTitle(), $link);
$PAGE->navbar->add( get_string('selectgrid', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/choose.php?cID='.$cID, navigation_node::TYPE_CUSTOM);

// If course is set, put that into breadcrumb
if ($course)
{
    $PAGE->navbar->add( $course->fullname, $CFG->wwwroot . '/course/view.php?id='.$course->id, navigation_node::TYPE_CUSTOM);
}

$PAGE->requires->js_init_call('gt_choose_bindings', null, true);

$PAGE->set_title( $SITE->shortname . ': ' . $GT->getPluginTitle() . ': ' . get_string('selectgrid', 'block_gradetracker') );

echo $OUTPUT->header();

$GTEXE = \GT\Execution::getInstance();
$GTEXE->min();

$TPL->set("GT", $GT)
    ->set("type", $type)
    ->set("User", $User)
    ->set("results", $results);

if ($User->hasCapability('block/gradetracker:view_all_quals')){
    $TPL->set("allQuals", \GT\Qualification::getAllQualifications( true ));
    $TPL->set("allCourses", \GT\Course::getAllCoursesWithQuals());
}

try {
    $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/choose.html' );
    $TPL->display();
} catch (\GT\GTException $e){
    echo $e->getException();
}

echo $OUTPUT->footer();