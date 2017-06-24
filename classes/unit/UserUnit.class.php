<?php
/**
 * GT\Unit\User
 *
 * This class handles all the user unit data and functionality, such as user criteria, comments, etc... 
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

namespace GT\Unit;

require_once 'Unit.class.php';

class UserUnit extends \GT\Unit {
    
    protected $qualID = false;
    protected $student = false;
    
    protected $userUnitRecordID = false;
    protected $userAward = false;
    protected $userComments;
    protected $userStudentComments;
    protected $userLastUpdate;
    protected $userLastUpdateBy;
    
    /**
     * Get the qual id
     * @return type
     */
    public function getQualID(){
        return $this->qualID;
    }
    
    /**
     * Calculate the percentage completion of the unit
     * @return string
     */
    public function unitCal(){
        $critt = $this->getCriteria();
        $count = 0;
        $award_count = 0;
        if ($critt){
            foreach ($critt as $awardd){
                
                // Check if it has a gradfing structure (if not, must be readonly)
                if ($awardd->getGradingStructure()->isValid()){
                    $count++;
                }
                
                // Check if it's met
                if ($awardd->getUserAward() && $awardd->getUserAward()->isMet()){
                    $award_count += 1;
                }
                
            }
            return ($count > 0) ? round($award_count / $count * 100) : 0;
        } else {
            return get_string('na', 'block_gradetracker');
        }
    }
    
    
    /**
     * Set which qualification this userunit is for
     * @param type $id
     * @return \GT\Unit\UserUnit
     */
    public function setQualID($id){
        $this->qualID = $id;
        return $this;
    }
        
    /**
     * Get the user's award
     * @global type $DB
     * @return boolean
     */
    public function getUserAward(){
                
        // If the QualID or student have not been loaded we cannot do anything
        if (!$this->qualID || !$this->student){
            return false;
        }
        
        return $this->userAward;
        
    }
    
    public function getUserComments(){
        return $this->userComments;
    }
    
    public function getUserStudentComments(){
        return $this->userStudentComments;
    }
    
    public function getUserLastUpdate(){
        return $this->userLastUpdate;
    }
    
    public function getUserLastUpdateByUserID(){
        return $this->userLastUpdateBy;
    }
    
    public function getUserLastUpdateBy(){
        return new \GT\User($this->userLastUpdateBy);
    }
    
    public function setUserAward(\GT\UnitAward $award){
        $this->userAward = $award;
        return $this;
    }
    
    public function setUserAwardID($id){
        $this->userAward = new \GT\UnitAward($id);
    }
    
    public function setUserComments($comments){
        $this->userComments = $comments;
        return $this;
    }
    
    public function setUserStudentComments($comments){
        $this->userStudentComments = $comments;
        return $this;
    }
    
    /**
     * Count all the users so we can make pages
     * @param type $role
     * @return type
     */
    public function countUsers($role = "STUDENT"){
        
        $users = $this->getUsers($role, false);
        return count($users);
        
    }
    
    public function countUserAwards($role = "STUDENT"){
        
        $users = $this->getUsers($role, false);
        $userAwards = 0;
        foreach($users as $u){
            $this->loadStudent($u);
            $award = $this->getUserAward();
            if($award && $award->isValid()){
                $userAwards++;
            }
        }
        return $userAwards;
    }
    
    public function countUnitAwards($unitawardname, $role = "STUDENT"){
        
        $users = $this->getUsers($role, false);
        $numberofunitawards = 0;
        foreach ($users as $u){
            $this->loadStudent($u);
            $unitaward = $this->getUserAward();
            if ($unitaward && $unitawardname == $unitaward->getName()){
                $numberofunitawards++;
            }
        }
        return $numberofunitawards;
    }
    
    /**
     * Get the students or staff on this unit on this qual
     * @global \GT\Unit\type $DB
     * @param $role
     * @return boolean|\GT\User
     */
    public function getUsers($role = "STUDENT", $page = 1, $courseID = false, $groupID = false){
        
        global $DB, $GT;
        
        if (!$this->qualID) return false;
        
        $return = array();
        $params = array();
                
        $sql = "SELECT DISTINCT uqu.userid
                FROM {user} u
                INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = u.id
                INNER JOIN {bcgt_user_quals} uq ON (uq.userid = u.id AND uq.qualid = uqu.qualid AND uq.role = uqu.role) ";
        
                
        // Only apply course & group filters when we're getting students
        if ($role == 'STUDENT')
        {
        
            // Group ID
            if ($groupID > 0){
                
                $sql .= "
                            INNER JOIN
                            (
                                SELECT userid
                                FROM {groups_members}
                                WHERE groupid = ?
                            ) gm ON gm.userid = u.id
                        ";
                
                $params[] = $groupID;
                
            }
            
            // Course ID
            elseif ($courseID > 0){

                $shortnames = $GT->getStudentRoles();
                $in = \gt_create_sql_placeholders($shortnames);
                
                $sql .= "
                            INNER JOIN
                            (
                                SELECT ra.userid
                                FROM {role_assignments} ra
                                INNER JOIN {context} x ON x.id = ra.contextid
                                INNER JOIN {role} r ON r.id = ra.roleid
                                WHERE x.instanceid = ? AND r.shortname IN ({$in})
                            ) ra ON ra.userid = u.id 
                        ";

                $params[] = $courseID;
                $params = array_merge($params, $shortnames);
                
            }
        
        }
        
        $sql .= " WHERE uqu.qualid = ? AND uqu.unitid = ? AND uqu.role = ? AND u.deleted = 0 
                ORDER BY u.lastname, u.firstname, u.username";
        
        $params[] = $this->qualID;
        $params[] = $this->id;
        $params[] = $role;
        
        // Page
        $limit = \GT\Setting::getSetting('unit_grid_paging');
        if ($limit <= 0) $limit = false;
        
        if ($page && is_numeric($limit)){
            $start = ($page - 1) * $limit;
        } else {
            $limit = null;
            $start = null;
        }
        
        $records = $DB->get_records_sql($sql, $params, $start, $limit);
        
        if ($records)
        {
            foreach($records as $record)
            {
                $user = new \GT\User($record->userid);
                $return[] = $user;
            }
        }
                        
        // Sort them
        $Sorter = new \GT\Sorter();
        $Sorter->sortUsers($return);
        
        return $return;
        
    }
    
    public function getStudent(){
        return $this->student;
    }
    
    /**
     * Clear any loaded student from the object
     */
    public function clearStudent($useCriteria = false){
        
        $GTEXE = \GT\Execution::getInstance();
        
        $this->student = false;
        $this->userUnitRecordID = false;
        $this->userAward = false;
        $this->userComments = null;
        $this->userStudentComments = false;
        $this->userLastUpdate = false;
        $this->userLastUpdateBy = false;
        $this->_userRow = false;
        
        // Now load into all the criteria
        if (!isset($GTEXE->STUDENT_LOAD_LEVEL) || $GTEXE->STUDENT_LOAD_LEVEL >= $GTEXE::STUD_LOAD_LEVEL_ALL)
        {
            $criteria = ($useCriteria) ? $useCriteria : $this->loadCriteriaIntoFlatArray();
            if ($criteria)
            {
                foreach($criteria as $crit)
                {
                    $crit->clearStudent();
                }
            }
        }
        
    }
    
    /**
     * Load a student into the userunit object
     * @param \GT\User $student
     */
    public function loadStudent($student){
                
        $criteria = false;
        $GTEXE = \GT\Execution::getInstance();
                
        // Now load into all the criteria
        if (!isset($GTEXE->UNIT_MIN_LOAD) || !$GTEXE->UNIT_MIN_LOAD){
            $criteria = $this->loadCriteriaIntoFlatArray();
        }
        
        if (!isset($GTEXE->STUDENT_LOAD_LEVEL) || $GTEXE->STUDENT_LOAD_LEVEL >= $GTEXE::STUD_LOAD_LEVEL_ALL){
            $this->clearStudent($criteria);
        }
                
        if (!$student){
            return;
        }
        
        // Might be a User object we passed in
        if ($student instanceof \GT\User){
            
            if ($student->isValid()){
                $this->student = $student;
            }
            
        } else {
        
            // Or might be just an ID
            $user = new \GT\User($student);
            if ($user->isValid())
            {
                $this->student = $user;
            }
        
        }
        
        // Load info from user_units table
        if ($this->student)
        {
            
            global $DB;
            
            $record = $DB->get_record("bcgt_user_units", array("userid" => $this->student->id, "unitid" => $this->id));
            $this->_userRow = $record;
            if ($record)
            {
                $this->userUnitRecordID = $record->id;
                $this->userAward = new \GT\UnitAward($record->awardid);
                $this->userComments = $record->comments;
                $this->userStudentComments = $record->studentcomments;
                $this->userLastUpdate = $record->lastupdate;
                $this->userLastUpdateBy = $record->lastupdateby;
            }
            
        }
        
        // Now load into each criteria as well
        if (!isset($GTEXE->STUDENT_LOAD_LEVEL) || $GTEXE->STUDENT_LOAD_LEVEL >= $GTEXE::STUD_LOAD_LEVEL_ALL)
        {
            if ($criteria)
            {
                foreach($criteria as $crit)
                {
                    $crit->loadStudent($this->student);
                }
            }
        }
                        
    }
    
    /**
     * Reload the user award from the database
     * @global \GT\Unit\type $DB
     */
    public function reloadUserAward(){
        
        global $DB;
            
        $record = $DB->get_record("bcgt_user_units", array("userid" => $this->student->id, "unitid" => $this->id));
        if ($record)
        {
            $this->userAward = new \GT\UnitAward($record->awardid);
        }
        else
        {
            $this->userAward = new \GT\UnitAward();
        }
        
    }
    
    /**
     * Save the user's unit data
     * @global \GT\Unit\type $DB
     * @global type $USER
     * @return boolean
     */
    public function saveUser($notifyEvent = true){
        
        global $DB, $USER;
        
        \gt_debug("Saving User Unit Award. With parameter notifyEvent (".(int)$notifyEvent.")");
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_USER_UNIT;
        $Log->beforejson = array(
            'awardid' => ($this->_userRow) ? $this->_userRow->awardid : null
        );
        // ------------ Logging Info
        
        
        
        if (!$this->student || !$this->qualID){
            return false;
        }
        
        $obj = new \stdClass();
        
        if ($this->userUnitRecordID){
            $obj->id = $this->userUnitRecordID;
        } else {
            $obj->userid = $this->student->id;
            $obj->unitid = $this->id;
        }
        
        $obj->awardid = ($this->getUserAward() && $this->getUserAward()->isValid()) ? $this->getUserAward()->getID() : null;
        $obj->comments = $this->userComments;
        $obj->studentcomments = $this->userStudentComments;
        $obj->lastupdate = time();
        $obj->lastupdateby = $USER->id;
        
        if ($this->userUnitRecordID){
            $DB->update_record("bcgt_user_units", $obj);
        } else {
            $this->userUnitRecordID = $DB->insert_record("bcgt_user_units", $obj);
        }
        
        
        // Notify Listeners of the event that just took place
        // We don't want this to call multiple times as it does parent auto calcuations, only once
        // So we do it if no parents, that way if it's just a singular criterion with no parents it does it
        // Otherwise it keeps autocalculating through until the top level, with no parents and does it then
        if ($notifyEvent)
        {
                        
            $Event = new \GT\Event( GT_EVENT_UNIT_UPDATE, array(
                'sID' => $this->student->id,
                'qID' => $this->qualID,
                'uID' => $this->id,
                'cID' => null,
                'value' => $obj->awardid
            ) );

            $Event->notify();
        }
        
        
        
        // ----------- Log the action
        $Log->afterjson = array(
            'awardid' => $obj->awardid
        ); 
        
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                \GT\Log::GT_LOG_ATT_UNITID => $this->id,
                \GT\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        
        $Log->save();
        // ----------- Log the action
                
        return true;
        
    }
    
    
    /**
     * Get the Unit Award cell on the grid
     * @global type $User
     * @param string $access
     * @return type
     */
    public function getAwardCell($access){
        
        global $User;

        // If they are not on this unit then return nothing
        if (!$this->student || !$this->student->isOnQualUnit($this->qualID, $this->id, "STUDENT")){
            return '';
        }
        
        $output = "";
        
        $elID = "S{$this->student->id}_Q{$this->qualID}_U{$this->id}";
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( ($access == 'e' || $access == 'ae') && !$User->canEditUnit($this->qualID, $this->id) ){
            $access = 'v';
        }
        
        
        // Check things are valid
        if (!$this->getGradingStructure()->isValid()){
            return get_string('invalidgradingstructure', 'block_gradetracker');
        }
        
        $possibleAwards = $this->getPossibleAwards();
        $userAward = $this->getUserAward();
        if ($userAward && $userAward->isValid() && !array_key_exists($userAward->getID(), $possibleAwards) && $access == 'v'){
            return get_string('invalidaward', 'block_gradetracker');
        }
        
        
        
        if ($access == 'e' || $access == 'ae'){
                        
            $output .= "<select id='{$elID}' class='{$elID} gt_grid_unit_award'>";
            
                $output .= "<option value=''></option>";
                
                if ($possibleAwards)
                {
                    foreach($possibleAwards as $award)
                    {
                        $sel = ($this->getUserAward() && $this->getUserAward()->getID() == $award->getID()) ? 'selected' : '';
                        $output .= "<option value='{$award->getID()}' {$sel} >{$award->getShortName()} - {$award->getName()}</option>";
                    }
                }
            
            $output .= "</select>";
            
        } else {
            
            if ($this->getUserAward() && $this->getUserAward()->isValid()){
                
                $output .= $this->getUserAward()->getName();
                
            } else {
                
                $output .= get_string('na', 'block_gradetracker');
                
            }
            
        }
        
        return $output;
                
    }
    
    /**
     * Get the IV cell for a student's unit
     * @global \GT\Unit\type $User
     * @param type $access
     * @return string
     */
    public function getIVCell($access){
        
        global $User;

        // If they are not on this unit then return nothing
        if (!$this->student || !$this->student->isOnQualUnit($this->qualID, $this->id, "STUDENT")){
            return '';
        }
        
        // If we want to edit but we don't have the permission, reset to "view"
        if ( ($access == 'e' || $access == 'ae') && !$User->canEditUnit($this->qualID, $this->id) ){
            $access = 'v';
        }
        
        $output = "";
        
        $elID = "S{$this->student->id}_Q{$this->qualID}_U{$this->id}_IV";
        
        $output .= "<td id='{$elID}' sID='{$this->student->id}' uID='{$this->id}' qID='{$this->qualID}'>";
        
            // The date is stored as text, not as a unix timestamp, as it's not exact
            $date = $this->getAttribute('IV_date', $this->student->id);
            $date = \gt_html($date);
            
            $who = $this->getAttribute('IV_who', $this->student->id);
            $who = \gt_html($who);
            
        
            $output .= "<small><b>".get_string('date', 'block_gradetracker')."</b></small><br>";
        
            if ($access == 'e' || $access == 'ae')
            {
                $output .= "<input type='text' value='{$date}' class='gt_stud_unit_IV_date' sID='{$this->student->id}' uID='{$this->id}' qID='{$this->qualID}' />";
            }
            else
            {
                $output .= $date;
            }
            
            $output .= "<br><small><b>".get_string('verifier', 'block_gradetracker')."</b></small><br>";
            
            if ($access == 'e' || $access == 'ae')
            {
                $output .= "<input type='text' value='{$who}' class='gt_stud_unit_IV_who' />";
            }
            else
            {
                $output .= $who;
            }
        
        $output .= "</td>";
        
        return $output;
        
    }
    
    /**
     * Import the unit data sheet
     * @global $CFG
     * @global type $MSGS
     * @param string $file
     * @return boolean
     */
    public function import($file = false)
    {
        
        global $CFG, $MSGS;
        
        
        // ------------ Logging Info
        $Log = new \GT\Log();
        $Log->context = \GT\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \GT\Log::GT_LOG_DETAILS_IMPORTED_UNIT_GRID;
        $Log->attributes = array(
                \GT\Log::GT_LOG_ATT_QUALID => $this->qualID,
                \GT\Log::GT_LOG_ATT_UNITID => $this->id
            );

        $Log->save();
        // ------------ Logging Info
        
        
        if (!$file){
            
            if (!isset($_POST['qualID']) || !isset($_POST['unitID']) || !isset($_POST['now'])){
                print_error('errors:missingparams', 'block_gradetracker');
            }
            
            $file = \GT\GradeTracker::dataroot() . '/tmp/U_' . $_POST['unitID'] . '_' . $_POST['qualID'] . '_' . $_POST['now'] . '.xlsx';
                    
        }
                
        // Try to open file
        
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Open with PHPExcel reader
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($file);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file);
        } catch(Exception $e){
            print_error($e->getMessage());
            return false;
        }
        
        $qual = new \GT\Qualification( $this->qualID );
        
        // Checkboxes
        $studentFilter = (isset($_POST['students'])) ? $_POST['students'] : array();
        
        $output = "";
        $output .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $file ) . '<br>';
        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $commentsWorkSheet = $objPHPExcel->getSheet(1);

        $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $objWorksheet->getTitle() ) . '<br>';
        $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $commentsWorkSheet->getTitle() ) . '<br>';

        // This one is for working out when the criteria end in the header
        $highestColumn = $objWorksheet->getHighestColumn();
        
        // If we are using the IV cols, then the last criteria will be 2 less
        if ($qual->getStructure() && $qual->getStructure()->getSetting('iv_column') == 1){
            $highestColumn = \gt_decrement_letter_excel($highestColumn, 2);
        }
        
        // This one is for looping through all columns with data
        $lastCol = $objWorksheet->getHighestColumn();
        $lastCol++;
        $lastRow = $objWorksheet->getHighestRow();
        
        $possibleValues = $this->getAllPossibleValues();
        $possibleValueArray = array();
        if ($possibleValues){
            foreach($possibleValues as $value){
                $possibleValueArray[$value->getShortName()] = $value;
            }
        }
        
        $eventCriteria = false;
        $studentArray = array();
        $naValueObj = new \GT\CriteriaAward();
        
        $cnt = 0;
                
        // Loop through rows to get students
        for ($row = 2; $row <= $lastRow; $row++)
        {

            $student = false;

            // Loop columns
            for ($col = 'A'; $col != $lastCol; $col++){

                $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                if ($col == 'A'){
                    
                    $studentID = $cellValue;
                    
                    // If not ticked, don't bother going any further
                    if (!in_array($studentID, $studentFilter)){
                        break;
                    }
                    
                    $student = new \GT\User($studentID);
                    if (!$student->isValid()){
                        $output .= "[{$row}] " . get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                        break;
                    }
                    
                    // Make sure student is actually on this qual and unit
                    if (!$student->isOnQualUnit($qual->getID(), $this->id, "STUDENT")){
                        $output .= "[{$row}] " . get_string('usernotonunit', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                        break;
                    }
                    
                    $this->loadStudent($student);
                    $studentArray[$studentID] = $student;
                    $output .= "<br>";
                    $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:student', 'block_gradetracker'), $student->getDisplayName() ) . '<br>';
                    continue; // Don't want to print the id out
                }

                // A, B, C and D are the ID, firstname, lastname and username
                if ($col != 'A' && $col != 'B' && $col != 'C' && $col != 'D'){
                    
                    $value = $cellValue;

                    // Get studentCriteria to see if it has been updated since we downloaded the sheet
                    $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                    $studentCriterion = $this->getCriterionByName($criteriaName);
                    
                    $eventCriteria = $studentCriterion;

                    if ($studentCriterion)
                    {

                        // Set new value
                        if (array_key_exists($value, $possibleValueArray) !== false || $value == $naValueObj->getShortName())
                        {

                            $valueObj = (array_key_exists($value, $possibleValueArray)) ? $possibleValueArray[$value] : $naValueObj;
                            
                            // Update user
                            $studentCriterion->setUserAward($valueObj);
                            $commentsCellValue = (string)$commentsWorkSheet->getCell($col . $row)->getCalculatedValue();
                            $studentCriterion->setUserComments($commentsCellValue);

                            // If this is the last criteria on the unit, do the events
                            $noEvent = ($col == $highestColumn) ? 'force' : true;
                            $studentCriterion->saveUser(true, $noEvent);

                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success', 'block_gradetracker'), $criteriaName, $value) . '<br>';
                            $cnt++;

                        }
                        else
                        {
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:value', 'block_gradetracker'), $value ) . '<br>';
                        }

                    } 
                    else
                    {

                        // Was it an IV column?
                        if ($qual->getStructure() && $qual->getStructure()->getSetting('iv_column') == 1)
                        {

                            // Get the string to compare the column headers
                            $attribute = false;
                            $ivDateString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('date');
                            $ivWhoString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker');

                            // Check if we are in the date column
                            if ($criteriaName == $ivDateString)
                            {
                                // If it's an excel date convert to unix and back to string
                                // Otherwise just insert whatever string it says
                                if (is_float($value) && $value > 0)
                                {
                                    $value = \gt_convert_excel_date_unix($value);
                                    $value = date('d-m-Y', $value);
                                }

                                $attribute = 'IV_date';

                            }
                            elseif ($criteriaName == $ivWhoString)
                            {
                                $attribute = 'IV_who';
                            }

                            // If attribute is valid save it
                            if ($attribute)
                            {

                                $value = trim($value);
                                if ($value == ''){
                                    $value = null;
                                }

                                $this->updateAttribute($attribute, $value, $this->student->id);

                                $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:misc', 'block_gradetracker'), $criteriaName, $value) . '<br>';
                                $cnt++;

                            }

                        }

                    }

                }

            }

        }
        
        $output .= "<br>";
        
        // Recalculate unit awards
        if ($studentArray){
            foreach($studentArray as $stud){
                
                $this->loadStudent($stud);
                $this->autoCalculateAwards();
                $this->reloadUserAward(); // Reload it from DB incase we didn't do auto calc but did rule instead
                $output .= sprintf( get_string('import:datasheet:process:autocalcunit', 'block_gradetracker'), $stud->getName(), $this->getUserAward()->getName()) . '<br>';
                
                // Recalculate predicted grades
                $userQual = new \GT\Qualification\UserQualification($qual->getID());
                $userQual->loadStudent($stud);
                $userQual->loadUnits();
                $userQual->calculatePredictedAwards();
                
            }
        }
        
        $output .= "<br>";

        // Delete file
        $del = unlink($file);
        if ($del){
            $output .= sprintf( get_string('import:datasheet:process:deletedfile', 'block_gradetracker'), $file) . '<br>';
        }
        
        $output .= get_string('import:datasheet:process:end', 'block_gradetracker') . '<br>';
        
        $MSGS['confirmed'] = true;
        $MSGS['output'] = $output;
        $MSGS['cnt'] = $cnt;
        
        
//        // Log the action
//        $Log = new \GT\Log();
//        $Log->addLog(\GT\Log::GT_LOG_CONTEXT_GRID, \GT\Log::GT_LOG_DETAILS_IMPORTED_GRID, array(
//            "attributes" => array(
//                "type" => "unit",
//                "qualID" => $this->qualID,
//                "unitID" => $this->id,
//            )
//        ));
        
    }
    
    
    
    
    /**
     * Export unit data sheet
     * @global type $CFG
     * @global \GT\Unit\type $USER
     */
    public function export(){
        
        global $CFG, $USER;
        
        $courseID = optional_param('courseID', false, PARAM_INT);
        $groupID = optional_param('groupID', false, PARAM_INT);
        
        $qual = new \GT\Qualification( $this->qualID );
        $name = preg_replace("/[^a-z 0-9]/i", "", $this->getDisplayName() . ' - ' . $qual->getDisplayName());
       
        
        // Require PHPExcel library
        require_once $CFG->dirroot . '/lib/phpexcel/PHPExcel.php';
        
        // Setup Spreadsheet
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
                     ->setCreator(fullname($USER))
                     ->setLastModifiedBy(fullname($USER))
                     ->setTitle( $this->getDisplayName() . " - " . $qual->getDisplayName() )
                     ->setSubject( $this->getDisplayName() . " - " . $qual->getDisplayName() )
                     ->setDescription( $this->getDisplayName() . " - " . $qual->getDisplayName() . " " . get_string('generatedbygt', 'block_gradetracker'))
                     ->setCustomProperty( "GT-DATASHEET-TYPE" , "UNIT", 's')
                     ->setCustomProperty( "GT-DATASHEET-DOWNLOADED" , time(), 'i');

        // Remove default sheet
        $objPHPExcel->removeSheetByIndex(0);
        
        // Style for blank cells - criteria not on that unit
        $blankCellStyle = array(
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'EDEDED')
            )
        );
        
        // Set current sheet
        $objPHPExcel->createSheet(0);
        $objPHPExcel->createSheet(1);
        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->setTitle( get_string('comments', 'block_gradetracker') );
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle( get_string('grades', 'block_gradetracker') );
        
        
        // User Headers
        $objPHPExcel->getActiveSheet()->setCellValue("A1", "ID");
        $objPHPExcel->getActiveSheet()->setCellValue("B1", get_string('firstname'));
        $objPHPExcel->getActiveSheet()->setCellValue("C1", get_string('lastname'));
        $objPHPExcel->getActiveSheet()->setCellValue("D1", get_string('username'));
        
        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->setCellValue("A1", "ID");
        $objPHPExcel->getActiveSheet()->setCellValue("B1", get_string('firstname'));
        $objPHPExcel->getActiveSheet()->setCellValue("C1", get_string('lastname'));
        $objPHPExcel->getActiveSheet()->setCellValue("D1", get_string('username'));
        $objPHPExcel->setActiveSheetIndex(0);

        $letter = 'E';
        
        // Criteria headers
        $criteria = $this->getHeaderCriteriaNames();
        if ($criteria)
        {
            
            foreach($criteria as $criterion)
            {
                
                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", $criterion['name']);
                $objPHPExcel->setActiveSheetIndex(1);
                $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", $criterion['name']);
                $objPHPExcel->setActiveSheetIndex(0);
                $letter++;
                
                if (isset($criterion['sub']) && $criterion['sub'])
                {
                    foreach($criterion['sub'] as $sub)
                    {
                        $subName = (isset($sub['name'])) ? $sub['name'] : $sub;
                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", $subName);
                        $objPHPExcel->setActiveSheetIndex(1);
                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", $subName);
                        $objPHPExcel->setActiveSheetIndex(0);
                        $letter++;
                    }
                }
                
            }
            
        }
        
        
        // IV Column?
        if ($qual->getStructure() && $qual->getStructure()->getSetting('iv_column') == 1)
        {
            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", get_string('iv', 'block_gradetracker') . ' - ' . get_string('date'));                    
            $letter++;
            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}1", get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker'));                    
            $letter++;
        }
        
        
        $rowNum = 2;
        
        $students = $this->getUsers("STUDENT", false, $courseID, $groupID);
        
        if ($students)
        {
            
            foreach($students as $student)
            {
                
                $this->loadStudent($student);
                if ($this->student->isOnQualUnit($this->qualID, $this->id, "STUDENT"))
                {
                    
                    // User cells
                    $objPHPExcel->getActiveSheet()->setCellValue("A{$rowNum}", $this->student->id);
                    $objPHPExcel->getActiveSheet()->setCellValue("B{$rowNum}", $this->student->firstname);
                    $objPHPExcel->getActiveSheet()->setCellValue("C{$rowNum}", $this->student->lastname);
                    $objPHPExcel->getActiveSheet()->setCellValue("D{$rowNum}", $this->student->username);
                    $objPHPExcel->setActiveSheetIndex(1);
                    $objPHPExcel->getActiveSheet()->setCellValue("A{$rowNum}", $this->student->id);
                    $objPHPExcel->getActiveSheet()->setCellValue("B{$rowNum}", $this->student->firstname);
                    $objPHPExcel->getActiveSheet()->setCellValue("C{$rowNum}", $this->student->lastname);
                    $objPHPExcel->getActiveSheet()->setCellValue("D{$rowNum}", $this->student->username);
                    $objPHPExcel->setActiveSheetIndex(0);
                    $letter = 'E';
                    
                    // Criteria cells
                    if ($criteria)
                    {

                        foreach($criteria as $crit)
                        {
                            
                            $criterion = $this->getCriterionByName($crit['name']);
                            
                            // Value
                            $criterion->getExcelCell($objPHPExcel, $rowNum, $letter);
                            
                            // Comment
                            $objPHPExcel->setActiveSheetIndex(1);
                            $comments = ($criterion->getUserComments()) ? $criterion->getUserComments() : '';
                            $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $comments);
                            $objPHPExcel->setActiveSheetIndex(0);

                            
                            $letter++;
                            
                            // Sub Criteria
                            if (isset($crit['sub']) && $crit['sub'])
                            {
                                
                                foreach($crit['sub'] as $sub)
                                {
                                    
                                    $subName = (isset($sub['name'])) ? $sub['name'] : $sub;
                                    $subCrit = $this->getCriterionByName($subName);
                                    
                                    // Value
                                    $subCrit->getExcelCell($objPHPExcel, $rowNum, $letter);
                                    
                                    // Comment
                                    $objPHPExcel->setActiveSheetIndex(1);
                                    $comments = ($subCrit->getUserComments()) ? $criterion->getUserComments() : '';   
                                    $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $comments);
                                    $objPHPExcel->setActiveSheetIndex(0);
                                    
                                    $letter++;
                                    
                                }
                                
                            }

                        }

                    }
                    
                    // IV Column?
                    if ($qual->getStructure() && $qual->getStructure()->getSetting('iv_column') == 1)
                    {

                        $ivDate = $this->getAttribute('IV_date', $this->student->id);
                        if (!$ivDate){
                            $ivDate = '';
                        }

                        $ivWho = $this->getAttribute('IV_who', $this->student->id);
                        if (!$ivWho){
                            $ivWho = '';
                        }

                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $ivDate);                    
                        $letter++;
                        $objPHPExcel->getActiveSheet()->setCellValue("{$letter}{$rowNum}", $ivWho);                    
                        $letter++;

                    }
                    
                    $rowNum++;
                    
                }
                
            }
            
        }
        
        
        
        
        
        
        // Freeze rows and cols (everything to the left of E and above 2)
        $objPHPExcel->getActiveSheet()->freezePane('E2');
        $objPHPExcel->setActiveSheetIndex(1);
        $objPHPExcel->getActiveSheet()->freezePane('E2');
        
        
        // End
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        // Set headers to download spreadsheet
        ob_clean();
        header("Pragma: public");
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header('Content-Disposition: attachment; filename="'.$name.'.xlsx"');     
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        ob_clean();
        $objWriter->save('php://output');
        exit;   
        
    }
    
    
    /**
     * Auto calculate the award of the unit
     * @return boolean
     */
    public function autoCalculateAwards($notifyEvent = true){
                        
        // If it doesn't have any criteria then nothing for it to do
        if (!$this->getCriteria()) return false;

        $criteria = $this->getCriteria();
        
        $userAward = false;
       
        // Now auto calculate this criterion        
        $currentUserAward = $this->getUserAward();
                
        // Get the grading structure of this unit, so we can use its point ranges
        $gradingStructure = $this->getGradingStructure();
        if (!$gradingStructure->isValid()) return false;

        $possibleAwardArray = array();
        $possibleAwards = $gradingStructure->getAwards();
        if (!$possibleAwards) return false;

        // Check if at least one of the awards is using point ranges
        foreach($possibleAwards as $possibleAward)
        {
            if ($possibleAward->getPointsLower() > 0 || $possibleAward->getPointsUpper() > 0)
            {
                $possibleAwardArray[] = $possibleAward;
            }
        }
               
        if (!$possibleAwardArray) return false;

        $Sorter = new \GT\Sorter();
        $Sorter->sortUnitValues($possibleAwardArray, 'asc');
        
        $maxPoints = $gradingStructure->getMaxPoints();
        $minPoints = $gradingStructure->getMinPoints();
                
        $criteriaMaxPointArray = array();
                
        // Check all the criteria to see if at least one has a grading structure with the same
        // max points, otherwise we cannot do an auto calculation
        foreach($criteria as $criterion)
        {
            $criterionGradingStructure = $criterion->getGradingStructure();
            $criteriaMaxPointArray[$criterion->getID()] = $criterionGradingStructure->getMaxPoints();
        }
                
        // If none have a max points of the same as the parent, we cannot proceed        
        if (!in_array($maxPoints, $criteriaMaxPointArray)) return false;

        // Now loop through the top level criteria again and see if they are all met
        // And if they are, get the point score so we can work out the average
        $cnt = count($criteria);
        $cntMet = 0;
        $pointsArray = array();
        
        foreach($criteria as $criterion)
        {
                        
            // Reload user award, as doesn't always update from previous loop iteration as object
            // in various places and not always a reference
            $criterion->loadStudent( $this->student );            
            if ($criterion->getUserAward() && $criterion->getUserAward()->isMet())
            {
                
                $cntMet++;
                
                $points = $criterion->getUserAward()->getPoints();
                                                
                // If this only has one possible award (e.g. Achieved) but the parent has multiple
                // (e.g. PMD) then don't include this in the calculations as it will throw it off
                $criterionPossibleAwards = $criterion->getGradingStructure()->getAwards(true);
                
                if (count($criterionPossibleAwards) == 1 && count($possibleAwardArray) > 1)
                {
                    continue;
                }
                
                // If this doesn't have any awards with a points score above 0, skip it as well
                if ($criterion->getGradingStructure()->getMaxPoints() == 0)
                {
                    continue;
                }
                
                // If the max points of this is different to that of the parent, adjust it up or down
                // to ensure calculation is accurate
                $criterionMaxPoints = $criteriaMaxPointArray[$criterion->getID()];
                if ($criterionMaxPoints <> $maxPoints)
                {
                                        
                    // Get the difference between the max and min of the parent's structure
                    $diff = $maxPoints - $minPoints;
                    $steps = count($criterionPossibleAwards) - 1;
                    $fraction = $diff / $steps;
                    
                    // Are we adjusting from a larger scale to a smaller scale, or the other way?
                    if ( count($criterionPossibleAwards) > count($possibleAwardArray) )
                    {
                        $adjusted = $criterion->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'down');
                    } 
                    elseif ( count($criterionPossibleAwards) < count($possibleAwardArray) )
                    {
                        $adjusted = $criterion->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'up');
                    }
                    else
                    {
                        $adjusted = $criterion->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray);
                    }
                    
                    $points = $adjusted[$points];
                                        
                }
                
                $pointsArray[$criterion->getID()] = $points;
                
            }
            
        }
                        
        // Only auto calculate an award if they are all met
        if ($cntMet === $cnt)
        {
        
            \gt_debug("All criteria met, so calculating Unit Award");
            
            $totalPoints = array_sum($pointsArray);
            $avgPoints = round( ($totalPoints / count($pointsArray)), 1 );
            
            // Re-order from highest to lowest
            $Sorter->sortUnitValues($possibleAwardArray, 'desc');

            \gt_debug("Total Criteria Points: {$totalPoints}, Avg Criteria Points: {$avgPoints}");
            
            // Work out which award to use
            foreach($possibleAwardArray as $award)
            {
                                
                // If it has both a lower and upper range
                if ($award->getPointsLower() > 0 && $award->getPointsUpper() > 0)
                {
                    
                    if ($avgPoints >= $award->getPointsLower() && $avgPoints <= $award->getPointsUpper())
                    {
                        $userAward = $award;
                        break;
                    }
                    
                }
                // Else if it has only a lower score
                elseif ($award->getPointsLower() > 0)
                {
                    if ($avgPoints >= $award->getPointsLower())
                    {
                        $userAward = $award;
                        break;
                    }
                }
                // Else if it has only a upper score
                elseif ($award->getPointsUpper() > 0)
                {
                    if ($avgPoints <= $award->getPointsUpper())
                    {
                        $userAward = $award;
                        break;
                    }
                }
                
            }
            
            // If an award has been found to use
            if ($userAward)
            {
                \gt_debug("Found Unit Award ({$userAward->getName()}) [{$award->getPointsLower()} - {$award->getPointsUpper()}]");
                $this->setUserAward($userAward);
                $this->saveUser($notifyEvent);
                $this->jsonResult = array( $this->id => $userAward->getID() );
            }
            else
            {
                \gt_debug("Error: Could not find Unit Award using criteria points ({$avgPoints}) ");
            }
            
            
        }
        
        
        // If no award has been found that matches the criteria values, but the unit already has
        // one, change it back to N/A
        if (!$userAward && $currentUserAward && $currentUserAward->isValid())
        {

            $na = new \GT\UnitAward(false);
            $this->setUserAward($na);
            $this->saveUser($notifyEvent);
            $this->jsonResult = array( $this->id => false );

        }
        
    }
    
    
}
