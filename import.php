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
 * Import data into the system
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once('lib.php');
require_login();

$GT = new \GT\GradeTracker();
$User = new \GT\User($USER->id);
$TPL = new \GT\Template();
$MSGS = array();
$TPL->set("GT", $GT);
$TPL->set("User", $User);

// Set up PAGE
$PAGE->set_context( context_course::instance(SITEID) );
$PAGE->set_heading( get_string('importdatasheet', 'block_gradetracker') );
$PAGE->set_cacheable(true);
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );
$PAGE->set_url( $CFG->wwwroot . $_SERVER['REQUEST_URI'] );

$GT->loadJavascript();
$GT->loadCSS();

$PAGE->requires->js_call_amd("block_gradetracker/import", 'init');

// If course is set, put that into breadcrumb
$PAGE->navbar->add( $GT->getPluginTitle(), $CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->navbar->add( get_string('importdatasheet', 'block_gradetracker'), null);

$title = get_string('import', 'block_gradetracker');
$type = required_param('type', PARAM_TEXT);

$submission = array(
    'confirm' => optional_param('confirm', false, PARAM_TEXT),
    'submit_sheet' => optional_param('submit_sheet', false, PARAM_TEXT),
);

switch ($type) {

    case 'datasheet':

        // Exporting a datasheet from a grid
        $grid = required_param('grid', PARAM_TEXT);
        $ass = optional_param('ass', false, PARAM_INT); // Assessment view
        $TPL->set("grid", $grid);
        $TPL->set('isAssessmentView', $ass);

        $qualID = required_param('qualID', PARAM_INT);
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()) {
            print_error('norecord', 'block_gradetracker');
        }

        $QualStructure = new \GT\QualificationStructure( $Qualification->getStructureID() );

        // Is disabled
        if (!$QualStructure->isEnabled()) {
            print_error('structureisdisabled', 'block_gradetracker');
        }

        switch ($grid) {

            case 'student':

                $studentID = required_param('studentID', PARAM_INT);
                $Student = new \GT\User($studentID);
                if (!$Student->isValid()) {
                    print_error('invaliduser', 'block_gradetracker');
                }

                // Make sure we have the permissions to import datasheets
                if (!$User->hasUserCapability('block/gradetracker:import_student_grids', $Student->id, $Qualification->getID())) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Next check is to see if the logged in user is a STAFF on the qualification, OR they have the view_all_quals capability OR they are the student
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Make sure student is actually on qual
                if (!$Student->isOnQual($qualID, "STUDENT")) {
                    print_error('invalidrecord', 'block_gradetracker');
                }

                $PAGE->navbar->add( $Qualification->getDisplayName() . " - " . $Student->getName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=student&qualID='.$Qualification->getID().'&id='.$Student->id, navigation_node::TYPE_CUSTOM);

                if ($submission['confirm']) {
                    $Qualification->loadStudent($Student);
                    $Qualification->import();
                } else if ($submission['submit_sheet']) {

                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->setStudentID($studentID);
                    $DataImport->checkFileStudentDataSheet();
                    if ($DataImport->getErrors()) {
                        $MSGS['errors'] = $DataImport->getErrors();
                    }

                }

                $title .= " - {$Student->getDisplayName()} - {$Qualification->getDisplayName()}";
                $TPL->set("Qualification", $Qualification);
                $TPL->set("Student", $Student);
                $TPL->set("MSGS", $MSGS);
                $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/import.html' );

                break;

            case 'unit':

                $unitID = required_param('unitID', PARAM_INT);

                $Unit = $Qualification->getUnit($unitID);
                if (!$Unit || !$Unit->isValid()) {
                    print_error('norecord', 'block_gradetracker');
                }

                // Do we have the permission to view the unit grids?
                if (!\gt_has_capability('block/gradetracker:import_unit_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this unit and this qual?
                if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                $PAGE->navbar->add( $Unit->getDisplayName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=unit&qualID='.$Qualification->getID().'&id='.$Unit->getID(), navigation_node::TYPE_CUSTOM);

                if ($submission['confirm']) {
                    $Unit->import();
                } else if ($submission['submit_sheet']) {

                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->setUnitID($unitID);
                    $DataImport->checkFileUnitDataSheet();
                    if ($DataImport->getErrors()) {
                        $MSGS['errors'] = $DataImport->getErrors();
                    }

                }

                $title .= " - {$Unit->getDisplayName()} - {$Qualification->getDisplayName()}";
                $TPL->set("Qualification", $Qualification);
                $TPL->set("Unit", $Unit);
                $TPL->set("MSGS", $MSGS);
                $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/import.html' );

                break;

            case 'class':


                // Do we have the permission to view the class grids?
                if (!\gt_has_capability('block/gradetracker:import_class_grids')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this qual? Or can we view all things?
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                $PAGE->navbar->add( $Qualification->getDisplayName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=class&id='.$Qualification->getID(), navigation_node::TYPE_CUSTOM);

                if ($submission['confirm']) {
                    $Qualification->importClass();
                } else if ($submission['submit_sheet']) {
                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->checkFileClassDataSheet();
                    if ($DataImport->getErrors()) {
                        $MSGS['errors'] = $DataImport->getErrors();
                    }
                }

                $title .= " - {$Qualification->getDisplayName()}";
                $TPL->set("Qualification", $Qualification);
                $TPL->set("MSGS", $MSGS);
                $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/import.html' );

                break;

            default:
                print_error( 'errors:invalidparams', 'block_gradetracker' );
                break;

        }

        break;

    default:
        print_error( 'errors:invalidparams', 'block_gradetracker' );
        break;

}


$PAGE->set_title( $SITE->shortname . ': ' . $GT->getPluginTitle() . ': ' . $title );
echo $OUTPUT->header();

$TPL->display();

echo $OUTPUT->footer();
