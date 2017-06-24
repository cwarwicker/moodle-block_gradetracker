<?php
/**
 * Export something from the system
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
require_once 'lib.php';
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
$PAGE->requires->js( '/blocks/gradetracker/js/scripts.js' );

$GT->loadJavascript();
$GT->loadCSS();

// If course is set, put that into breadcrumb
$PAGE->navbar->add( $GT->getPluginTitle(), $CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->navbar->add( get_string('importdatasheet', 'block_gradetracker'), null);

$title = get_string('import', 'block_gradetracker');
$type = required_param('type', PARAM_TEXT);

switch($type)
{
    
    case 'datasheet':
        
        // Exporting a datasheet from a grid
        $grid = required_param('grid', PARAM_TEXT);
        $ass = optional_param('ass', false, PARAM_INT); // Assessment view
        $TPL->set("grid", $grid);
        $TPL->set('isAssessmentView', $ass);
        
        $qualID = required_param('qualID', PARAM_INT);
        $Qualification = new \GT\Qualification\UserQualification($qualID);
        if (!$Qualification->isValid()){
            print_error('norecord', 'block_gradetracker');
        }
        
        $QualStructure = new \GT\QualificationStructure( $Qualification->getStructureID() );
        
        // Is disabled
        if (!$QualStructure->isEnabled()){
            print_error('structureisdisabled', 'block_gradetracker');
        }
        
        switch($grid)
        {
            
            case 'student':
                
                $studentID = required_param('studentID', PARAM_INT);
                $Student = new \GT\User($studentID);
                if (!$Student->isValid()){
                    print_error('invaliduser', 'block_gradetracker');
                }

                // Make sure we have the permissions to import datasheets
                if (!$User->hasUserCapability('block/gradetracker:import_student_grids', $Student->id, $Qualification->getID())){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                // Next check is to see if the logged in user is a STAFF on the qualification, OR they have the view_all_quals capability OR they are the student
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }                

                // Make sure student is actually on qual
                if (!$Student->isOnQual($qualID, "STUDENT")){
                    print_error('invalidrecord', 'block_gradetracker');
                }
                
                $PAGE->navbar->add( $Qualification->getDisplayName() . " - " . $Student->getName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=student&qualID='.$Qualification->getID().'&id='.$Student->id, navigation_node::TYPE_CUSTOM);
                
                if (isset($_POST['confirm']))
                {
                    $Qualification->loadStudent($Student);
                    $Qualification->import();
                }
                elseif (isset($_POST['submit_sheet']))
                {
                    
                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->setStudentID($studentID);
                    $DataImport->checkFileStudentDataSheet();
                    if ($DataImport->getErrors()){
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
                if (!$Unit || !$Unit->isValid()){
                    print_error('norecord', 'block_gradetracker');
                }
                
                // Do we have the permission to view the unit grids?
                if (!\gt_has_capability('block/gradetracker:import_unit_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this unit and this qual?
                if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $PAGE->navbar->add( $Unit->getDisplayName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=unit&qualID='.$Qualification->getID().'&id='.$Unit->getID(), navigation_node::TYPE_CUSTOM);
                
                if (isset($_POST['confirm']))
                {
                    $Unit->import();
                }
                elseif (isset($_POST['submit_sheet']))
                {
                    
                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->setUnitID($unitID);
                    $DataImport->checkFileUnitDataSheet();
                    if ($DataImport->getErrors()){
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
                if (!\gt_has_capability('block/gradetracker:import_class_grids')){
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this qual? Or can we view all things?
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $PAGE->navbar->add( $Qualification->getDisplayName(), $CFG->wwwroot . '/blocks/gradetracker/grid.php?type=class&id='.$Qualification->getID(), navigation_node::TYPE_CUSTOM);
                
                if (isset($_POST['confirm']))
                {
                    $Qualification->importClass();
                }
                elseif (isset($_POST['submit_sheet']))
                {
                    $DataImport = new \GT\DataImport($_FILES['sheet']);
                    $DataImport->setQualID($qualID);
                    $DataImport->checkFileClassDataSheet();
                    if ($DataImport->getErrors()){
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