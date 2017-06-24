<?php
global $DB;
global $USER;

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

// Need to be logged in to view this page
require_login();

// Parameters
$cID = optional_param('cID', false, PARAM_INT);

$course = false;
$context = context_course::instance(SITEID);

if ($cID){
    $course = new \GT\Course($cID);
    if (!$course->isValid()){
        print_error( get_string('invalidcourseid') );
    }
    $context = context_course::instance($course->id);
}

$GT = new \GT\GradeTracker();

$PAGE->set_context($context);
$PAGE->set_title ( get_string('dashboard', 'block_gradetracker') );
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );
$PAGE->set_url($CFG->wwwroot . '/blocks/gradetracker/dashboard.php');
$PAGE->navbar->add( $GT->getPluginTitle(), null);
$PAGE->navbar->add( get_string('dashboard', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/dashboard.php', navigation_node::TYPE_CUSTOM);

// prepare JS
$GT->loadJavascript();

$PAGE->requires->js_init_call("gt_bindings", null, true);


echo $OUTPUT->header();

if(gt_has_capability('block/gradetracker:view_all_quals')){
    $user = false;
    $qualifications = \GT\Qualification::getAllQualifications();
    $searchinstance = "searchQualID";
    $submitsearch = "submit_filter_all";
}
else{
    $user = new \GT\User($USER->id);
    $qualifications = $user->getQualifications('STAFF');
    $searchinstance = "myQualID";
    $submitsearch = "submit_filter_my";
}

$studentuser = new \GT\User($USER->id);
$studentquals = $studentuser->getQualifications('STUDENT');

$TPL = new \GT\Template();
$TPL->set("user", $user);
$TPL->set("qualifications", $qualifications);
$TPL->set("searchinstance", $searchinstance);
$TPL->set("submitsearch", $submitsearch);
$TPL->set("studentuser", $studentuser);
$TPL->set("studentquals", $studentquals);

$TPL->load($CFG->dirroot."/blocks/gradetracker/tpl/dashboard.html");
$TPL->display();

echo $OUTPUT->footer();

