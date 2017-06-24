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
require_once "{$CFG->dirroot}/lib/filelib.php";
require_login();

$type = required_param('type', PARAM_TEXT);
$subtype = optional_param('subtype', ' ', PARAM_TEXT);
$ass = optional_param('ass', false, PARAM_INT);

$GT = new \GT\GradeTracker();
$User = new \GT\User($USER->id);

switch($type)
{
    
    case 'datasheet':
        
        // Exporting a datasheet from a grid
        $grid = required_param('grid', PARAM_TEXT);
        
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

                $isTheStudent = ($User->id == $Student->id);

                // First check is to see if they have view_student_grids capability OR they are the student themselves instead
                if (!$User->hasUserCapability('block/gradetracker:export_student_grids', $Student->id, $Qualification->getID()) && !$isTheStudent)
                {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Next check is to see if the logged in user is a STAFF on the qualification, OR they have the view_all_quals capability OR they are the student
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals') && !$isTheStudent){
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Final check is to see if the student is actually on this qual
                if (!$Student->isOnQual($Qualification->getID(), "STUDENT")){
                    print_error('invalidrecord', 'block_gradetracker');
                }
                
                $Qualification->loadStudent($Student);
                $Qualification->export($ass);
                exit;
                
            break;
            
            case 'unit':
                
                $unitID = required_param('unitID', PARAM_INT);
                $Unit = $Qualification->getUnit($unitID);
                if (!$Unit || !$Unit->isValid()){
                    print_error('norecord', 'block_gradetracker');
                }
                
                // Do we have the permission to view the unit grids?
                if (!\gt_has_capability('block/gradetracker:export_unit_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this unit and this qual?
                if (!$User->isOnQualUnit($Qualification->getID(), $Unit->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $Unit->export();
                exit;
                
            break;
            
            case 'class':
                                
                // Do we have the permission to view the class grids?
                if (!\gt_has_capability('block/gradetracker:export_class_grids') && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }

                // Are we a staff member on this qual? Or can we view all things?
                if (!$User->isOnQual($Qualification->getID(), "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $Qualification->exportClass();
                exit;
                
            break;
        
            default:
                print_error( 'errors:invalidparams', 'block_gradetracker' );
            break;
            
        }
        
    break;
    case 'data':
        
        switch($subtype):
            
            case 'qoe':
                
                $export = new \GT\DataExport; 
                $all_users_qoe = $export->getUsersQoe();
                $export->downloadUsersQoe($all_users_qoe);
                
            break;
        
            case 'avggcse':
                
                $export = new \GT\DataExport; 
                $all_users_qoe = $export->getUsersAverageGCSE();
                $export->downloadUsersAverageGCSE($all_users_qoe);
                
            break;
            
            case 'tg':
                
                $options = (isset($_POST['options'])) ? array_keys($_POST['options']) : array();
                
                $export = new \GT\DataExport; 
                $all_users_tg = $export->getUsersTg($options);
                $export->downloadUsersTg($all_users_tg, $options);
                
            break;
        
            case 'ag':
                
                $export = new \GT\DataExport; 
                $all_users_ag = $export->getUsersAg();
                $export->downloadUsersAg($all_users_ag);
                
            break;
            case 'ceta':
                
                $export = new \GT\DataExport; 
                $all_users_cg = $export->getUsersCg();
                $export->downloadUsersCg($all_users_cg);
                
            break;
            case 'wcoe':
                
                $export = new \GT\DataExport; 
                $all_wcoe = $export->getWCoe();
                $export->downloadWCoe($all_wcoe);
                
            break;
        
            case 'ass':
                
                // This needs to have an assessment ID passed through
                if (!isset($_POST['assID']) || !$_POST['assID']){
                    print_error('errors:import:ass:id', 'block_gradetracker');
                }
                
                $names = (isset($_POST['include_names']));
                
                $export = new \GT\DataExport();
                $data = $export->getUsersAssGrades($_POST['assID']);
                $export->downloadUsersAssGrades($data, $names);
                
            break;
        
            default:
                print_error( 'errors:invalidparams', 'block_gradetracker' );
            break;
        
        endswitch;
    break;
    
    case 'sql':
        if ($User->hasCapability('block/gradetracker:run_sql_report'))
        {
            $id = required_param('id', PARAM_INT);
            $export = new \GT\DataExport();
            $export->downloadSQLReport($id);
        }
        else
        {
            print_error('invalidaccess', 'block_gradetracker');
        }
        
    break;
        
    default:
        print_error( 'errors:invalidparams', 'block_gradetracker' );
    break;
    
}