<?php
require_once '../../config.php';
require_once 'lib.php';

if (!gt_has_capability('block/gradetracker:configure') || !gt_has_capability('block/gradetracker:configure_reporting'))
{
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$id = required_param('id', PARAM_INT);

$GT = new \GT\GradeTracker();
$Log = new \GT\Log($id);
$TPL = new \GT\Template();

if ($Log->id){
    $TPL->set("log", $Log);
}

$TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/config/reporting/log.html' );
$TPL->display();