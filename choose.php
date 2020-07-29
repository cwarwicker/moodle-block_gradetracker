<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * This is where you get lists of students, units, etc... to choose what grid you want to look at
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

// Need to be logged in to view this page
require_login();

// Parameters
$cID = optional_param('cID', false, PARAM_INT);
$type = optional_param('type', 'student', PARAM_TEXT);

// must be param_text for ctype_digit check
$searchAllQID = optional_param('searchQualID', false, PARAM_TEXT);
$searchAllCID = optional_param('searchCourseID', false, PARAM_TEXT);
$myQualID = optional_param('myQualID', false, PARAM_INT);
$myCourseID = optional_param('myCourseID', false, PARAM_INT);

$submission = array(
    'submit_filter_my' => optional_param('submit_filter_my', false, PARAM_TEXT),
    'submit_filter_all' => optional_param('submit_filter_all', false, PARAM_TEXT),
);


$course = false;
$context = context_course::instance(SITEID);

if ($cID) {
    $course = new \GT\Course($cID);
    if (!$course->isValid()) {
        print_error( get_string('invalidcourseid') );
    }
    $context = context_course::instance($course->id);
} else if ($myCourseID) {
    $cID = $myCourseID;
}

// Check permissions
if (!gt_has_capability('block/gradetracker:view_'.$type.'_grids')) {
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$results = null;

$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$User = new \GT\User($USER->id);

$searchQualification = false;
$searchCourse = false;

// Submitted Filter for All Qualifications
if ($User->hasCapability('block/gradetracker:view_all_quals') && $submission['submit_filter_all']) {

    $searchQualification = false;
    $searchCourse = false;

    // If searching by all Qualifications
    if (ctype_digit($searchAllQID) && $searchAllQID > 0) {
        $searchQualification = new \GT\Qualification($searchAllQID);
        if (!$searchQualification->isValid()) {
            $searchQualification = false;
        }
    }

    // If searching by all Courses
    if (ctype_digit($searchAllCID) && $searchAllCID > 0) {
        $searchCourse = new \GT\Course($searchAllCID);
        if (!$searchCourse->isValid()) {
            $searchCourse = false;
        }
    }

} else if ($submission['submit_filter_my'] || $myCourseID > 0) {

    $searchQualification = false;

    // Selecting one of My Quals
    if (ctype_digit($myQualID) && $myQualID > 0) {
        $searchQualification = new \GT\Qualification($myQualID);
        if (!$searchQualification->isValid() || !$User->isOnQual($myQualID, "STAFF")) {
            $searchQualification = false;
        }
    }

    // Selecting one of My Courses
    if (ctype_digit($myCourseID) && $myCourseID > 0) {
        $searchCourse = new \GT\Course($myCourseID);
        if (!$searchCourse->isValid()) {
            $searchCourse = false;
        }
    }

}



switch ($type) {

    // Get results for Student grid
    case 'student':

        // Get list of students on this qual
        if ($searchQualification) {
            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification->getUsers("STUDENT");
        } else if ($searchCourse) {

            // Get list of students on this course
            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()) {

                $results = array();

                foreach ($searchCourse->getCourseQualifications() as $courseQual) {

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual->getUsers("STUDENT", $searchCourse->id);

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()) {

                foreach ($searchCourse->getChildCourses() as $child) {

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()) {

                        foreach ($child->getCourseQualifications() as $courseQual) {

                            $results[$child->id][$courseQual->getID()] = $courseQual->getUsers("STUDENT", $child->id);

                        }

                    }

                }

            }

        }

        break;


    case 'unit':

        if ($searchQualification) {

            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification->getUnits();

        } else if ($searchCourse) {

            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()) {

                $results = array();

                foreach ($searchCourse->getCourseQualifications() as $courseQual) {

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual->getUnits();

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()) {

                foreach ($searchCourse->getChildCourses() as $child) {

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()) {

                        foreach ($child->getCourseQualifications() as $courseQual) {

                            $results[$child->id][$courseQual->getID()] = $courseQual->getUnits();

                        }

                    }

                }

            }

        }

        break;



    case 'class':

        if ($searchQualification) {

            $results = array();
            $results[0][$searchQualification->getID()] = $searchQualification;

        } else if ($searchCourse) {

            $results = array();

            // Does this course have quals?
            if ($searchCourse->getCourseQualifications()) {

                $results = array();

                foreach ($searchCourse->getCourseQualifications() as $courseQual) {

                    $results[$searchCourse->id][$courseQual->getID()] = $courseQual;

                }

            }

            // Does this have any children with qualifications?
            if ($searchCourse->getChildCourses()) {

                foreach ($searchCourse->getChildCourses() as $child) {

                    // Does this child have any qualifications?
                    if ($child->getCourseQualifications()) {

                        foreach ($child->getCourseQualifications() as $courseQual) {

                            $results[$child->id][$courseQual->getID()] = $courseQual;

                        }

                    }

                }

            }

        }

        // If there is only 1 result, just jump straight to the class grid
        if ($results && count($results) == 1 && count(reset($results)) == 1) {
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

if ( $User->hasCapability('block/gradetracker:configure') ) {
    $link = $CFG->wwwroot . '/blocks/gradetracker/config.php';
} else {
    $link = $CFG->wwwroot . '/blocks/gradetracker/my.php';
}

$PAGE->navbar->add( $GT->getPluginTitle(), $link);
$PAGE->navbar->add( get_string('selectgrid', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/choose.php?cID='.$cID, navigation_node::TYPE_CUSTOM);

// If course is set, put that into breadcrumb
if ($course) {
    $PAGE->navbar->add( $course->fullname, $CFG->wwwroot . '/course/view.php?id='.$course->id, navigation_node::TYPE_CUSTOM);
}

$PAGE->set_title( $SITE->shortname . ': ' . $GT->getPluginTitle() . ': ' . get_string('selectgrid', 'block_gradetracker') );

echo $OUTPUT->header();

$GTEXE = \GT\Execution::getInstance();
$GTEXE->min();

$TPL->set("GT", $GT)
    ->set("type", $type)
    ->set("User", $User)
    ->set("results", $results);

if ($User->hasCapability('block/gradetracker:view_all_quals')) {
    $TPL->set("allQuals", \GT\Qualification::getAllQualifications( true ));
    $TPL->set("allCourses", \GT\Course::getAllCoursesWithQuals());
}

try {
    $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/choose.html' );
    $TPL->display();
} catch (\GT\GTException $e) {
    echo $e->getException();
}

echo $OUTPUT->footer();
