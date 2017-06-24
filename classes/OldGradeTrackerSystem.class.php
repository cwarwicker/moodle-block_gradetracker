<?php
/**
 * OldGradeTrackerSystem
 *
 * This class deals with the transferring of data from the old block_bcgt system to this new one.
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

namespace GT;


class OldGradeTrackerSystem {
    
    private $mappingsArray = array();
    private $output = array();
    
    public function getOutput(){
        return $this->output;
    }
    
    public function addOutput($str, $append = ''){
        $this->output[] = '<span>' .get_string('blockbcgtdata:process:'.$str, 'block_gradetracker') . ' - ' . $append . '</span>';
    }
    
    public function addOutputSf($str){
        $args = func_get_args();
        unset($args[0]); // $str
        $this->output[] = '<span>' . vsprintf(get_string('blockbcgtdata:process:'.$str, 'block_gradetracker'), $args) . '</span>';
    }
    
    public function addError($str, $append = ''){
        $this->output[] = '<span class="gt_process_error">'.get_string('blockbcgtdata:err:'.$str, 'block_gradetracker') . ' - ' . $append . '</span>';
    }
    
    /**
     * Get all qual families from old system
     * @global \GT\type $DB
     * @return type
     */
    public function getAllQualificationFamilies()
    {
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_type_family");
        return $records;
        
    }
    
    /**
     * Get all quals from old system (not bespoke)
     * @global type $DB
     * @param type $familyID
     * @return type
     */
    public function getAllQualifications()
    {
        
        global $DB;
        
        $returnArray = array();
        $records = $DB->get_records_sql("SELECT q.*, f.family, t.type, s.subtype, l.trackinglevel, f.id as familyid, tq.id as tqid, cq.countcourse
                                            FROM {block_bcgt_qualification} q
                                            INNER JOIN {block_bcgt_target_qual} tq ON tq.id = q.bcgttargetqualid
                                            INNER JOIN {block_bcgt_type} t ON t.id = tq.bcgttypeid
                                            INNER JOIN {block_bcgt_subtype} s ON s.id = tq.bcgtsubtypeid
                                            INNER JOIN {block_bcgt_level} l ON l.id = tq.bcgtlevelid
                                            INNER JOIN {block_bcgt_type_family} f ON f.id = t.bcgttypefamilyid
                                            LEFT JOIN
                                            (
                                                SELECT bcgtqualificationid, COUNT(courseid) AS countcourse 
                                                FROM {block_bcgt_course_qual}
                                                GROUP BY bcgtqualificationid
                                            ) AS cq ON cq.bcgtqualificationid = q.id
                                            ORDER BY f.family, l.trackinglevel, s.subtype, q.name");
                
        if ($records)
        {
            foreach($records as $record)
            {
                
                // Get old type
                $record->oldtype = $this->getOldType($record);
                $returnArray[] = $record;
                
            }
        }
        
        return $returnArray;
        
        
    }
    
    private function getOldType($record){
        
        if ($record->family == 'BTEC'){
            return 'btec';
        } elseif ($record->type == 'CG HB VRQ'){
            return 'cghbvrq';
        } elseif ($record->type == 'CG HB NVQ'){
            return 'cghbnvq';
        } elseif ($record->family == 'CG'){
            return 'cg';
        } elseif ($record->family == 'GCSE'){
            return 'gcse';
        } elseif ($record->family == 'ALevel'){
            return 'alvl';
        }
        
        return false;
        
    }
    
    /**
     * Get the unit records that are attached to a qualification
     * @global \GT\type $DB
     * @param type $qualID
     * @return type
     */
    private function getQualificationUnits($qualID){
        
        global $DB;
        
        $records = $DB->get_records_sql("SELECT u.*
                                        FROM {block_bcgt_unit} u
                                        INNER JOIN {block_bcgt_qual_units} qu ON qu.bcgtunitid = u.id
                                        WHERE qu.bcgtqualificationid = ?", array($qualID));
        
        return $records;
        
    }
   
    
    public function saveStructureMappings(){
        
        global $MSGS;
                        
        if (isset($_POST['block_bcgt_structure_maps']))
        {
            
            // Qual Structures
            if (isset($_POST['block_bcgt_structure_maps']['qual']))
            {
                foreach($_POST['block_bcgt_structure_maps']['qual'] as $family => $structureID)
                {
                    
                    $this->addMapping('qual_structure', $family, $structureID);
                    
                }
            }
            
            
            // Unit grading Structures
            if (isset($_POST['block_bcgt_structure_maps']['unit']))
            {
                
                foreach($_POST['block_bcgt_structure_maps']['unit'] as $family => $qualMapping)
                {
                    
                    if ($qualMapping)
                    {
                        foreach($qualMapping as $type => $structureID)
                        {
                            $this->addMapping('unit_grading_structure_' . $family, $type, $structureID);
                        }
                    }
                   
                }
                
            }
            
            
            // Unit grading Structures
            if (isset($_POST['block_bcgt_structure_maps']['crit']))
            {
                
                foreach($_POST['block_bcgt_structure_maps']['crit'] as $family => $qualMapping)
                {
                    
                    if ($qualMapping)
                    {
                        foreach($qualMapping as $type => $structureID)
                        {
                            $this->addMapping('crit_grading_structure_' . $family, $type, $structureID);
                        }
                    }
                   
                }
                
            }
            
            
        }
        
        
    }
    
    /**
     * Handle the transfer of data from old system to new
     * @global type $CFG
     * @global \GT\type $DB
     * @global type $MSGS
     * @return boolean
     */
    public function handleDataTransfer($method, $data)
    {
        
        global $CFG, $DB, $MSGS;
                
        // First check the method is valid
        if (!in_array($method, array('specs', 'data'))){
            $MSGS['errors'][] = get_string('blockbcgtdata:err:invalidmethod', 'block_gradetracker');
            return false;
        }
        
        if (empty($data) || !$data){
            $MSGS['errors'][] = get_string('blockbcgtdata:err:qualarray', 'block_gradetracker');
            return false;
        }
        
        
               
        
        // Now, depending on which method we are using, we need to do various different things
        switch($method)
        {
            
            
            // Transfer the Specifications of Qualifications from the old bcgt block to the new gradetracker block
            case 'specs':
                
                $this->addOutput('transferspecs');
                
                $qualStructuresArray = array();
                $qualIDArray = $data;
                
                // Loop through selected qualifications
                foreach($qualIDArray as $oldQualID)
                {
                    
                    $this->addOutput('HR');
                    
                    // Get the old qualification from the database
                    $oldQual = $this->getOldQualification($oldQualID);
                    
                    // Check old qual is valid
                    if ($oldQual){
                        $this->addOutput('loadqual', '['.$oldQual->id.'] ' .  $oldQual->family . ' ' . $oldQual->trackinglevel . ' ' . $oldQual->subtype . ' ' . $oldQual->name);
                    } else {
                        $this->addError('loadqual', $oldQualID);
                        continue;
                    }
                    
                    
                    // Check type is valid                    
                    $oldType = $this->getOldType($oldQual);
                    if (!$oldType){
                        $this->addError('qualtype', $oldQual->family . ' // ' . $oldQual->type);
                        continue;
                    }
                    
                    
                    // Check type has all required mappings saved
                    $mappings = $this->getRequiredMappings($oldType);
                    if (!$mappings){
                        $this->addError('coding:mappings', $oldType);
                        continue;
                    }
                    
                    // Check to make sure that all the required mappings for this old type have been saved
                    // E.g. the unit and criteria grading structure mappings
                    foreach($mappings as $m => $v)
                    {
                        if (!$v)
                        {
                            // If any of the mappings are missing, we can't transfer this type at all
                            // So "continue" out of the qual loop as well
                            $this->addError('missingmapping', $m);
                            continue 2;
                        }
                    }
                        
                        
                    // Load up the new QualificationStructure we are mapping it to
                    if (isset($qualStructuresArray[$oldType])){
                        $QualStructure = $qualStructuresArray[$oldType];
                    } else {
                        $QualStructure = new \GT\QualificationStructure($mappings['structure']);
                        $qualStructuresArray[$oldType] = $QualStructure;
                    }
                    
                    
                    // Check QualStructure is valid
                    if (!$QualStructure->isValid() || $QualStructure->isDeleted()){
                        $this->addError('invalidqualstructure', $mappings['structure']);
                        continue;
                    }
                    
                    
                    // Check to see if this qualification has already been mapped
                    // If it hasn't, then we can do all the creation of levels, subtypes, builds as required
                    // and then create the new qualification and map it
                    $newQualID = $this->getMappingNewValue('qualification', $oldQualID);
                    if (!$newQualID)
                    {
                    
                        $newQualification = $this->createNewQualification($oldQual, $QualStructure);
                        
                        // If that failed, skip the rest of it for this qualification
                        if (!$newQualification){
                            continue;
                        }                      
                                            
                    } else {
                        
                        // Otherwise if it is already mapped, skip past all that to the units
                        $newQualification = new \GT\Qualification($newQualID);
                        if (!$newQualification->isValid()){
                            $this->addError('invalidqual');
                            continue;
                        }
                        
                        $newQualification->loadUnits();
                        $this->addOutputSf('alreadymapped', 'qualification', $oldQualID, $newQualID);
                        
                    }
                    
                    // Transfer settings, like weightings
                    $this->transferQualificationSettings($newQualification, $oldQual);
                    $this->addOutputSf('transferredsettings');
                    
                    
                    // If we are transfering a BTEC or a CG, do the units
                    if ($oldType == 'btec' || $oldType == 'cg' || $oldType == 'cghbvrq' || $oldType == 'cghbnvq')
                    {
                        $this->createNewUnits($oldQual, $newQualification, $QualStructure, $oldType, $mappings);
                        $newQualification->saveQualUnits();
                    }
                                 

                    
                    
                    
                    // Did we tick the option saying we wanted to link the qual to the same courses?
                    if (isset($_POST['opt']['course_link']) && $_POST['opt']['course_link'] == 1){
                        
                        $oldCourseLinks = $DB->get_records("block_bcgt_course_qual", array("bcgtqualificationid" => $oldQual->id));
                        if ($oldCourseLinks)
                        {
                            foreach($oldCourseLinks as $link)
                            {
                                $course = new \GT\Course($link->courseid);
                                if ( $course->isValid() && $course->addCourseQual($newQualification->getID()) ){
                                    $this->addOutputSf('linkedcourse', $course->getName());
                                }
                            }
                        }
                        
                    }
                                        
                }
                                                
            break;
            
            case 'data':
                
                // Loop through new qualifications
                if ($data)
                {
                
                    $qualArray = array();
                    
                    foreach($data as $qualID => $units)
                    {
                        
                        $this->addOutput('HR');
                        
                        // Load new qualification we are transferring into
                        if (array_key_exists($qualID, $qualArray)){
                            $qual = $qualArray[$qualID];
                        } else {
                            $qual = new \GT\Qualification\UserQualification($qualID);
                            $qualArray[$qualID] = $qual;
                        }
                        
                        if (!$qual->isValid() || $qual->isDeleted()){
                            $this->addError('loadnewqual', $qualID);
                            continue;
                        }
                        
                        $this->addOutput('loadnewqual', '['.$qual->getID().'] ' .  $qual->getDisplayName());
                        
                        // Find old qual mapping
                        $oldQualID = $this->getMappingOldValueFromNew('qualification', $qualID);
                        if (!$oldQualID){
                            $this->addError('notmapped', $qualID);
                            continue;
                        }
                        
                        
                        // Get old qual from ID
                        $oldQual = $this->getOldQualification($oldQualID);
                        
                        // Check old qual is valid
                        if ($oldQual){
                            $this->addOutput('loadqual', '['.$oldQual->id.'] ' .  $oldQual->family . ' ' . $oldQual->trackinglevel . ' ' . $oldQual->subtype . ' ' . $oldQual->name);
                        } else {
                            $this->addError('loadqual', $oldQualID);
                            continue;
                        }
                        
                        
                        // Get old type
                        $oldType = $this->getOldType($oldQual);
                        if (!$oldType){
                            $this->addError('qualtype', $oldQual->family . ' // ' . $oldQual->type);
                            continue;
                        }


                        // Check type has all required mappings saved
                        $mappings = $this->getRequiredMappings($oldType);
                        if (!$mappings){
                            $this->addError('coding:mappings', $oldType);
                            continue;
                        }

                        // Check to make sure that all the required mappings for this old type have been saved
                        // E.g. the unit and criteria grading structure mappings
                        foreach($mappings as $m => $v)
                        {
                            if (!$v)
                            {
                                // If any of the mappings are missing, we can't transfer this type at all
                                // So "continue" out of the qual loop as well
                                $this->addError('missingmapping', $m);
                                continue 2;
                            }
                        }
                                 
                        
                        // Everything ok with the qual
                        // Now load the units
                        foreach($units as $unitID => $students)
                        {
                            
                            $unit = $qual->getUnit($unitID);
                            if (!$unit || !$unit->isValid() || $unit->isDeleted()){
                                $this->addError('loadnewunit', $unitID);
                                continue; # Skip to next unit
                            }
                            
                            $this->addOutput('loadnewunit', '['.$unit->getID().'] ' .  $unit->getDisplayName());
                            
                            // Find mapping to old unit
                            $oldUnitID = $this->getMappingOldValueFromNew('unit', $unit->getID());
                            if (!$oldQualID){
                                $this->addError('notmapped', $unit->getID());
                                continue; # Skip to next unit
                            }
                            
                            // Check old unit is valid
                            $oldUnit = $this->getOldUnit($oldUnitID);
                            if ($oldUnit){
                                $this->addOutput('loadunit', '['.$oldUnit->id.'] ' . $oldUnit->type . ' ' . $oldUnit->trackinglevel . ' ' . $oldUnit->name);
                            } else {
                                $this->addError('loadunit', $oldUnitID);
                                continue; # Skip to next unit
                            }
                            
                            
                            
                            // At this point we've got the old unit that the new one was mapped from, so now we can check the students
                            if ($students)
                            {
                                foreach($students as $studentID => $chk)
                                {
                                    
                                    // Check if this student is on this qual and unit
                                    $student = new \GT\User($studentID);
                                    if (!$student->isValid()){
                                        $this->addError('invaliduser', $studentID);
                                        continue; # Skip to next student
                                    }
                                    
                                    $this->addOutput('loaduser', '['.$student->id.'] ' . $student->getDisplayName());
                                    
                                    if (!$student->isOnQualUnit($qualID, $unitID, "STUDENT")){
                                        $this->addError('notonunit', $studentID);
                                        continue; # Skip to next student
                                    }
                                    
                                    // Load the student into the qualification
                                    $qual->loadStudent($studentID);
                                    
                                    // Find the criteria on the old unit
                                    $criteria = $this->getOldUnitCriteria($oldUnit->id);
                                    if ($criteria)
                                    {
                                        foreach($criteria as $oldCrit)
                                        {
                                            
                                            // Get mapping to new criteria
                                            $this->addOutput('loadcrit', '['.$oldCrit->id.'] ' . $oldCrit->name);
                                            
                                            $critID = $this->getMappingNewValue('criterion', $oldCrit->id);
                                            if (!$critID){
                                                $this->addError('notmapped', $oldCrit->id);
                                                continue; # Skip to next criterion
                                            }
                                                                                        
                                            // Found the criterion ok, now double check that is actually linked to this unit
                                            $criterion = $unit->getCriterion($critID);
                                            if (!$criterion){
                                                $this->addError('mapping:crit', $critID);
                                                continue; # Skip to next criterion
                                            }
                                            
                                            // At this point everything is ok, so lets try and do the data transfer
                                            $oldUserCrit = $this->getOldUserCriterionValue($oldQual->id, $oldCrit->id, $studentID);
                                            if ($oldUserCrit){
                                                
                                                $this->addOutput('loadusercrit');
                                                $oldValueID = $oldUserCrit->bcgtvalueid;
                                                $oldValue = $this->getOldRecord('block_bcgt_value', $oldValueID);
                                                if (!$oldValue){
                                                    $this->addError('invalidoldcritval', $oldValueID);
                                                    continue; # Skip to next criterion
                                                }
                                                
                                                $this->addOutput('loadcritval', '['.$oldValue->id.'] ' . $oldValue->value . ' ('.$oldValue->shortvalue.')');
                                                
                                                $gradingStructure = $criterion->getGradingStructure();
                                                $award = $gradingStructure->getAwardByShortName($oldValue->shortvalue);
                                                if ($award){
                                                    
                                                    $this->addOutput('foundnewcritval', '['.$award->getID().'] ' . $award->getName() . ' ('.$award->getShortName().')');
                                                    
                                                    // Save the new value
                                                    $criterion->setUserAward($award);
                                                    $criterion->setUserComments( $oldUserCrit->comments );
                                                    $criterion->setUserAwardDate( $oldUserCrit->awarddate );
                                                    if ($criterion->saveUser(true, true)){
                                                        $this->addOutput('saveusercrit');
                                                    } else {
                                                        $this->addError('saveusercrit');
                                                        continue;
                                                    }
                                                                                                        
                                                } else {
                                                    $this->addError('invalidnewcritval', $oldValue->shortvalue);
                                                    continue;
                                                }
                                                
                                                
                                            } else {
                                                $this->addOutput('nousercrit');
                                                continue;
                                            }
                                            
                                            
                                        }
                                    }
                                                                        
                                    // Was it a complicated Hair & Beauty one?
                                    // All I can really do is the ranges, it's too complicated to get the values for each criteria on a range, or the observations on an NVQ outcome or signoff sheet
                                    // So it's not worth the time and effort to do, considering most of the HB stuff is all 1 year anyway
                                    if ($oldType == 'cghbvrq')
                                    {
                                        
                                        // Get the ranges off the old unit
                                        $ranges = $this->getOldUnitRanges($oldUnitID);
                                        if ($ranges)
                                        {
                                            
                                            foreach($ranges as $oldRange)
                                            {
                                                
                                                // Get mapping to new criteria
                                                $this->addOutput('loadcrit', 'RANGE['.$oldRange->id.'] ' . $oldRange->name);

                                                $newRangeID = $this->getMappingNewValue('range', $oldRange->id);
                                                if (!$newRangeID){
                                                    $this->addError('notmapped', $oldRange->id);
                                                    continue; # Skip to next criterion
                                                }

                                                // Found the criterion ok, now double check that is actually linked to this unit
                                                $newRange = $unit->getCriterion($newRangeID);
                                                if (!$newRange){
                                                    $this->addError('mapping:crit', $newRangeID);
                                                    continue; # Skip to next criterion
                                                }

                                                // At this point everything is ok, so lets try and do the data transfer
                                                $oldUserRange = $this->getOldUserRangeValue($oldQual->id, $oldRange->id, $studentID);
                                                if ($oldUserRange){

                                                    $this->addOutput('loadusercrit');
                                                    $oldValueID = $oldUserRange->bcgtvalueid;
                                                    $oldValue = $this->getOldRecord('block_bcgt_value', $oldValueID);
                                                    if (!$oldValue){
                                                        $this->addError('invalidoldcritval', $oldValueID);
                                                        continue; # Skip to next criterion
                                                    }

                                                    $this->addOutput('loadcritval', '['.$oldValue->id.'] ' . $oldValue->value . ' ('.$oldValue->shortvalue.')');

                                                    $gradingStructure = $newRange->getGradingStructure();
                                                    $award = $gradingStructure->getAwardByShortName($oldValue->shortvalue);
                                                    if ($award){

                                                        $this->addOutput('foundnewcritval', '['.$award->getID().'] ' . $award->getName() . ' ('.$award->getShortName().')');

                                                        // Save the new value
                                                        $newRange->setUserAward($award);
                                                        $newRange->setUserAwardDate( $oldUserRange->awarddate );
                                                        if ($newRange->saveUser(true, true)){
                                                            $this->addOutput('saveusercrit');
                                                        } else {
                                                            $this->addError('saveusercrit');
                                                            continue;
                                                        }

                                                    } else {
                                                        $this->addError('invalidnewcritval', $oldValue->shortvalue);
                                                        continue;
                                                    }
                                                    
                                                    
                                                    // Now the numeric points of the criteria on this range
                                                    $oldRangeCriteria = $this->getOldUnitRangeCriteria($oldRange->id);
                                                    if ($oldRangeCriteria)
                                                    {
                                                        foreach($oldRangeCriteria as $oldRangeCrit)
                                                        {
                                                            
                                                            $this->addOutput('loadcrit', 'RANGECRIT['.$oldRangeCrit->bcgtcriteriaid.'] ' . $oldRangeCrit->name);
                                                            
                                                            $newCritID = $this->getMappingNewValue('criterion', $oldRangeCrit->bcgtcriteriaid);
                                                            if (!$newCritID){
                                                                $this->addError('notmapped', $oldRangeCrit->bcgtcriteriaid);
                                                                continue; # Skip to next criterion
                                                            }

                                                            // Found the criterion ok, now double check that is actually linked to this unit
                                                            $newCrit = $unit->getCriterion($newCritID);
                                                            if (!$newCrit){
                                                                $this->addError('mapping:crit', $newCritID);
                                                                continue; # Skip to next criterion
                                                            }
                                                            
                                                            // Get the user value
                                                            $oldUserRangeCritValue = $this->getOldUserRangeCriterionValue($oldQual->id, $oldRange->id, $oldRangeCrit->bcgtcriteriaid, $studentID);
                                                            if ($oldUserRangeCritValue)
                                                            {
                                                                
                                                                // Set the value
                                                                if ($newCrit->setRangeCriterionValue($newRange->getID(), $newCrit->getID(), $oldUserRangeCritValue->bcgtvalueid) ){
                                                                    $this->addOutput('saveusercrit', $oldUserRangeCritValue->bcgtvalueid);
                                                                } else {
                                                                    $this->addError('saveusercrit');
                                                                }
                                                                
                                                            }
                                                            else
                                                            {
                                                                $this->addOutput('nousercrit');
                                                            }
                                                            
                                                        }
                                                    }
                                                    

                                                } else {
                                                    $this->addOutput('nousercrit');
                                                    continue;
                                                }
                                                
                                            }
                                            
                                        }
                                        
                                    }
                                    
                                    
                                    // CG HB NVQ
                                    elseif ($oldType == 'cghbnvq')
                                    {
                                        
                                        // Sign Off Sheets
                                        
                                        // Find sign off sheets on unit
                                        $sheets = $this->getOldUnitSignOffSheets($oldUnitID);
                                        if ($sheets)
                                        {
                                            foreach($sheets as $oldSheet)
                                            {
                                                
                                                // Get mapping to new criteria
                                                $this->addOutput('loadcrit', 'SIGNOFFSHEET['.$oldSheet->id.'] ' . $oldSheet->name);
                                                
                                                $newSheetID = $this->getMappingNewValue('signoff_sheet', $oldSheet->id);
                                                if (!$newSheetID){
                                                    $this->addError('notmapped', $oldSheet->id);
                                                    continue; # Skip to next criterion
                                                }

                                                // Found the criterion ok, now double check that is actually linked to this unit
                                                $newSheet = $unit->getCriterion($newSheetID);
                                                if (!$newSheet){
                                                    $this->addError('mapping:crit', $newSheetID);
                                                    continue; # Skip to next criterion
                                                }
                                                
                                                // Get the old signoff sheet ranges
                                                $oldSheetRanges = $this->getOldUnitSignOffSheetRanges($oldSheet->id);
                                                if ($oldSheetRanges)
                                                {
                                                    foreach($oldSheetRanges as $oldSheetRange)
                                                    {
                                                        
                                                        // Get the mapping
                                                        $this->addOutput('loadcrit', 'SHEETRANGE['.$oldSheetRange->id.'] ' . $oldSheetRange->name);
                                                        
                                                        $newSheetRangeID = $this->getMappingNewValue('signoff_sheet_criterion', $oldSheetRange->id);
                                                        if (!$newSheetRangeID){
                                                            $this->addError('notmapped', $oldSheetRange->id);
                                                            continue; # Skip to next criterion
                                                        }

                                                        // Found the criterion ok, now double check that is actually linked to this unit
                                                        $newSheetRange = $unit->getCriterion($newSheetRangeID);
                                                        if (!$newSheetRange){
                                                            $this->addError('mapping:crit', $newSheetRangeID);
                                                            continue; # Skip to next criterion
                                                        }
                                                        
                                                        // Get user observation values
                                                        $oldUserSignOffRangeValues = $this->getOldUserSignOffSheetRangeValues($oldQual->id, $oldSheet->id, $oldSheetRange->id, $studentID);
                                                        if ($oldUserSignOffRangeValues)
                                                        {
                                                            
                                                            foreach($oldUserSignOffRangeValues as $oldRecord)
                                                            {
                                                                
                                                                $this->addOutput('loadrngob', '['.$oldRecord->id.'] OB ' . $oldRecord->observationnum . ' ('.$oldRecord->value.')');

                                                                // In the old system it just stored 0 or 1, not an actual value
                                                                // So get the actual value
                                                                if ($oldRecord->value == 1)
                                                                {
                                                                
                                                                    $awards = $newSheetRange->getPossibleValues(true);
                                                                    if (count($awards) <> 1){
                                                                        $this->addError('metaward', $newSheetRangeID);
                                                                        continue 2;
                                                                    }
                                                                    
                                                                    $award = reset($awards);                                                                    
                                                                    if ($newSheetRange->setCriterionObservationValue($oldRecord->observationnum, $newSheet->getID(), $award->getID()) ) {
                                                                        $this->addOutput('saveusercrit');
                                                                    } else {
                                                                        $this->addError('saveusercrit');
                                                                    }

                                                                }
                                                                
                                                            }
                                                            
                                                        }
                                                        
                                                    }
                                                }                                                                                               
                                            }
                                        }
                                        
                                    }
                                    
                                    
                                    // Now do the unit award
                                    $oldUserUnit = $this->getOldUserUnitValue($oldQual->id, $oldUnit->id, $studentID);
                                    if ($oldUserUnit){
                                        
                                        $this->addOutput('loaduserunit');
                                        $oldValueID = $oldUserUnit->bcgttypeawardid;
                                        $oldValue = $this->getOldRecord('block_bcgt_type_award', $oldValueID);
                                        if (!$oldValue){
                                            $this->addError('invalidoldunitval', $oldValueID);
                                            continue; # Skip to next unit
                                        }

                                        $this->addOutput('loadunitval', '['.$oldValue->id.'] ' . $oldValue->award);
                                        
                                        // Find corresponding unit award with same name
                                        $gradingStructure = $unit->getGradingStructure();
                                        $award = $gradingStructure->getAwardByName($oldValue->award);
                                        if ($award){

                                            $this->addOutput('foundnewunitval', '['.$award->getID().'] ' . $award->getName());

                                            // Save the new value
                                            $unit->setUserAward($award);
                                            if ($unit->saveUser(false)){
                                                $this->addOutput('saveuserunit');
                                            } else {
                                                $this->addError('saveuserunit');
                                                continue;
                                            }

                                        } else {
                                            $this->addError('invalidnewunitval', $oldValue->shortvalue);
                                            continue;
                                        }
                                                                                
                                    } else {
                                        $this->addOutput('nouserunit');
                                        continue; # Skip to next unit
                                    }
                                    
                                    
                                }
                            }
                                                        
                            
                        }
                        
                        $this->addOutput("OK");
                        
                    }
                }
                
            break;
            
        }
        
        return $this->output;
        
    }
    
    
    
    private function createNewQualification($oldQual, $QualStructure){
        
        global $DB;
        
        // Check to see if this level exists, and if not then create it
        // If the level is Level 1 & 2, I changed that so change the variable
        if ($oldQual->trackinglevel == 'Level 1 & 2'){
            $oldQual->trackinglevel = 'Level 1 / Level 2';
        }
        
        // A Level sub types have changed names
        if ($oldQual->subtype == 'A2 Level'){
            $oldQual->subtype = 'A2';
        } elseif ($oldQual->subtype == 'AS Level'){
            $oldQual->subtype = 'AS';
        }

        $Level = \GT\Level::findByName($oldQual->trackinglevel);
        if (!$Level){
            $Level = new \GT\Level();
            $Level->setName($oldQual->trackinglevel);
            $Level->setShortName($oldQual->trackinglevel);
            $Level->setOrderNum(0);
            $Level->save();
            $this->addOutput('createlvl', $oldQual->trackinglevel);
        }

        if (!$Level->isValid()){
            $this->addError('invalidlevel', $oldQual->trackinglevel);
            return false;
        }


        // Check to see if this subtype exists, and if not then create it
        $SubType = \GT\SubType::findByName($oldQual->subtype);
        if (!$SubType){
            $SubType = new \GT\SubType();
            $SubType->setName($oldQual->subtype);
            $SubType->setShortName($oldQual->subtype);
            $SubType->save();
            $this->addOutput('createsubtype', $oldQual->subtype);
        }

        if (!$SubType->isValid()){
            $this->addError('invalidsubtype', $oldQual->subtype);
            return false;
        }


        // Check for a build of this structure, level and subtype
        $this->addOutputSf('findbuild', $QualStructure->getName(), $Level->getName(), $SubType->getName());

        $QualBuild = \GT\QualificationBuild::find($QualStructure->getID(), $Level->getID(), $SubType->getID());
        if (!$QualBuild){

            $this->addOutputSf('createbuild', $QualStructure->getName(), $Level->getName(), $SubType->getName());

            $QualBuild = new \GT\QualificationBuild();
            $QualBuild->setStructureID($QualStructure->getID());
            $QualBuild->setLevelID($Level->getID());
            $QualBuild->setSubTypeID($SubType->getID());
            $QualBuild->save();

            // Look up the default credits of the old TargetQual and apply to QualBuild
            if ($QualBuild->isValid())
            {
                $this->addOutput('success');
                $oldCredits = $DB->get_record("block_bcgt_target_qual_att", array("bcgttargetqualid" => $oldQual->tqid, "name" => "DEFAULT_CREDITS"));
                if ($oldCredits){
                    $QualBuild->updateAttribute('build_default_credits', $oldCredits->value);
                }
            }

        }

        if (!$QualBuild->isValid()){
            $this->addError('invalidbuild');
            return false;
        }



        // Create a new qualification based on the old one
        $newQualification = new \GT\Qualification();
        $newQualification->setBuildID($QualBuild->getID());
        $newQualification->setName($oldQual->name);
        $newQualification->save();
        $this->addOutputSf('createqual', $QualStructure->getName(), $Level->getName(), $SubType->getName(), $oldQual->name );

        if (!$newQualification->isValid()){
            $this->addError('invalidqual');
            return false;
        }

        // Add qual to mappings
        $this->addMapping('qualification', $oldQual->id, $newQualification->getID());
        $this->addOutput('success', $newQualification->getID());
        
        return $newQualification;
        
    }
    
    private function transferQualificationSettings($newQualification, $oldQual){
        
        global $DB;
        
        // Find old settings
        
        // Qual weighting coefficients
        $weights = $DB->get_records("block_bcgt_qual_weighting", array("bcgtqualificationid" => $oldQual->id));
        if ($weights)
        {
            foreach($weights as $weight)
            {
                $newQualification->updateAttribute('coefficient_' . $weight->number, $weight->coefficient);
            }
        }
        
    }
    
    /**
     * Check that all the units on the old qual are mapped to new units
     * @param type $oldQual
     * @return boolean
     */
    private function checkForNewUnits($oldQual){
        
        $return = true;
        
        $this->addOutput('checkingunits');
        
        // Find the units on the old qualification
        $units = $this->getQualificationUnits($oldQual->id);
        $this->addOutputSf('foundunits', count($units));
        
        if ($units)
        {
            foreach($units as $unit)
            {
                
                $this->addOutput('loadunit', '['.$unit->id.'] ' .  $unit->name);
                $check = $this->getMappingNewValue('unit', $unit->id);
                
                if ($check){
                    $this->addOutput('OK');
                } else {
                    $this->addError('notmapped');
                    $return = false;
                    continue;
                }
                
            }
            
            return $return;
            
        }
        else
        {
            return null;
        }
        
        
    }
    
    
    private function createNewUnits($oldQual, $newQualification, $QualStructure, $oldType, $mappings){
        
        $this->addOutput('loadqualunits', $oldQual->name);

        // Find the units on the old qualification
        $units = $this->getQualificationUnits($oldQual->id);
        $this->addOutputSf('foundunits', count($units));

        // If we found some, loop through them
        if ($units)
        {

            foreach($units as $unit)
            {

                $this->addOutput('loadunit', '['.$unit->id.'] ' .  $unit->name);

                // Check to see if this has already been mapped
                $check = $this->getMappingNewValue('unit', $unit->id);
                if ($check){
                    
                    // It's already been mapped, so doesn't need to be created, just attach it to the qual
                    $newUnit = new \GT\Unit($check);
                    $newQualification->addUnit( $newUnit );
                    $this->addOutputSf('alreadymapped', 'unit', $unit->id, $check);
                    continue;
                } else {

                    $newUnit = $this->createNewUnit($unit, $newQualification, $QualStructure, $oldType, $mappings);
                    
                    // If it failed, skip anything further and move onto next unit
                    if (!$newUnit){
                        continue;
                    }                   

                }

            }

        }
        
        return true;
        
    }
    
    private function createNewUnit($unit, $newQualification, $QualStructure, $oldType, $mappings){
        
        global $DB;
        
        // Set defaults for levelID and levelName to use
        $levelID = 0;
        $levelName = false;
        
        // Check to make sure the old level actually exists, as it seems some old units have been
        // saved with invalid levels, e.g. -1
        $level = $DB->get_record("block_bcgt_level", array("id" => $unit->bcgtlevelid));

        // If the old level exists, find the new one with the same name
        if ($level){
            $UnitLevel = \GT\Level::findByName($level->trackinglevel);
            if (!$UnitLevel){
                $UnitLevel = new \GT\Level();
                $UnitLevel->setName($level->trackinglevel);
                $UnitLevel->setShortName($level->trackinglevel);
                $UnitLevel->setOrderNum(0);
                $UnitLevel->save();
                $this->addOutput('createlvl', $level->trackinglevel);
            }
            $levelID = $UnitLevel->getID();
            $levelName = $UnitLevel->getName();
        }
        
        
        // Create new unit
        $newUnit = new \GT\Unit();
        $newUnit->setCode($unit->uniqueid);
        $newUnit->setCredits($unit->credits);
        $newUnit->setDescription( (is_null($unit->details)) ? '' : $unit->details );
        $newUnit->setLevelID($levelID);
        $newUnit->setStructureID($QualStructure->getID());

        $split = $this->splitUnitNameNumber($unit->name);
        $newUnit->setName($split['name']);
        $newUnit->setUnitNumber($split['number']);

        // Get the mapped grading structure for a unit of this type and level
        $key = $this->getMappedUnitGradingStructure($oldType, $levelName, $unit);
        $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;
        
        // Check grading structure still exists and hasn't been deleted
        $gradingStructure = new \GT\UnitAwardStructure($gradingStructureID);
        if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
            $this->addError('invalidgradingstructure');
            return false;
        }
        
        $newUnit->setGradingStructureID($gradingStructureID);

        // Save the unit
        $this->addOutputSf('createunit', $newUnit->getName(), $levelName );
        $newUnit->save();

        if (!$newUnit->isValid()){
            $this->addError('invalidunit');
            return false;
        }

        // Map the unit
        $this->addMapping('unit', $unit->id, $newUnit->getID());
        $this->addOutput('success', $newUnit->getID());
        
        // If the level wasn't found, the new level will be saved with a levelID of 0, so the unit
        // will still exist, but it will need to be updated with a valid level ID
        if (!$levelID){
            $this->addOutput('warning:oldlevel');
        }
        
        // If the grading structure ID is invalid, they will need to update the unit
        if (!$gradingStructureID){
            $this->addOutput('warning:gradingstructure');
        }

        
        // Now link up the unit to the qualification
        $newQualification->addUnit( $newUnit );
        
        
        
        // Now create the criteria
        
        // If it's a BTEC or a CG (general) just do the normal criteria
        if ($oldType == 'btec' || $oldType == 'cg')
        {
            $this->createNewCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings);           
        }
        
        // If it's a more complex one - CG HB, we need to do it differently, to put in all the ranges
        // and observations and all that stuff
        elseif ($oldType == 'cghbnvq')
        {
            
            $this->createNewNVQCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings);
            
        }
        
        elseif ($oldType == 'cghbvrq')
        {
            $this->createNewVRQCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings);
        }
        

        return true;
        
    }
    
    private function createNewVRQCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings)
    {
        
        global $DB;
        
        // Find just the top level criteria, not sub criteria
        $criteria = $DB->get_records("block_bcgt_criteria", array("bcgtunitid" => $unit->id, "parentcriteriaid" => null));
        
        if ($criteria)
        {
                        
            foreach($criteria as $crit)
            {
        
                // FOrmatives
                // These need to be standard criteria, with sub criteria, with Pass Only grading structure
                // The sub criteria should be DATE grading type
                if ($crit->type == 'Formative')
                {
                    
                    // Grading structure for this is Pass Only
                    $key = "crit_pass"; # The old system is wrong, it stores PMD in the crit attributes
                    $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;
                                        
                    // Standard Criteria
                    $newCriterion = new \GT\Criteria\StandardCriterion();
                    $newCriterion->setUnitID($newUnit->getID());
                    $newCriterion->setName($crit->name);
                    $newCriterion->setDescription($crit->details);
                    $newCriterion->setGradingStructureID($gradingStructureID);
                    
                    $this->addOutputSf('createcrit', $newCriterion->getName() );
                    
                    // Check grading structure still exists and hasn't been deleted
                    $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                    if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
                        $this->addError('invalidgradingstructure');
                        continue;
                    }

                    // Save the criterion
                    $newCriterion->save();
                    
                    // Make sure it saved ok
                    if (!$newCriterion->isValid()){
                        $this->addError('invalidcrit');
                        continue;
                    }
                    
                    // Needs the "force popup" option
                    $newCriterion->updateAttribute("forcepopup", 1);
                    
                    // If the grading structure ID is invalid, they will need to update the criterion
                    if (!$gradingStructureID){
                        $this->addOutput('warning:gradingstructure');
                    }
                    
                    // Map the criterion
                    $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                    $this->addOutput('success', $newCriterion->getID());
                    
                    
                    
                    
                    
                    // Now the sub criteria
                    $checkSubCriteria = $DB->get_records_sql("SELECT * 
                                                              FROM {block_bcgt_criteria}
                                                              WHERE parentcriteriaid = ?", array($crit->id));
                    if ($checkSubCriteria)
                    {
                        
                        foreach($checkSubCriteria as $subCrit)
                        {
                            
                            // Standard Criteria
                            $newSubCriterion = new \GT\Criteria\StandardCriterion();
                            $newSubCriterion->setUnitID($newUnit->getID());
                            $newSubCriterion->setName($subCrit->name);
                            $newSubCriterion->setDescription($subCrit->details);
                            $newSubCriterion->setGradingStructureID($gradingStructureID);
                            $newSubCriterion->setParentID($newCriterion->getID());

                            // Save the criterion
                            $newSubCriterion->save();
                            $this->addOutputSf('createcrit', $newSubCriterion->getName() );

                            // Make sure it saved ok
                            if (!$newSubCriterion->isValid()){
                                $this->addError('invalidcrit');
                                continue;
                            }
                            
                            // Grading type should be DATE
                            $newSubCriterion->updateAttribute("gradingtype", "DATE");

                            // If the grading structure ID is invalid, they will need to update the criterion
                            if (!$gradingStructureID){
                                $this->addOutput('warning:gradingstructure');
                            }

                            // Map the criterion
                            $this->addMapping('criterion', $subCrit->id, $newSubCriterion->getID());
                            $this->addOutput('success', $newSubCriterion->getID());
                            
                        }
                        
                    }
                    
                } # End formatives
                
                // Summatives
                // These can be just single standard criteria, or criteria with ranges
                elseif ($crit->type == 'Summative' || $crit->type == '' || is_null($crit->type))
                {
                    
                    // Does it have sub criteria?
                    $checkSubCriteria = $DB->get_records_sql("SELECT * 
                                                              FROM {block_bcgt_criteria}
                                                              WHERE parentcriteriaid = ?", array($crit->id));
                    
                    // If it does, it is one with ranges
                    if ($checkSubCriteria)
                    {
                        
                        // Set the main criterion first
                        // Numeric Criteria
                        $key = "crit_pmd"; # The old system is wrong
                        $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;
                    
                        $newCriterion = new \GT\Criteria\NumericCriterion();
                        $newCriterion->setUnitID($newUnit->getID());
                        $newCriterion->setName($crit->name);
                        $newCriterion->setDescription($crit->details);
                        $newCriterion->setGradingStructureID($gradingStructureID);
                        
                        $this->addOutputSf('createcrit', $newCriterion->getName() );
                        
                        // Check grading structure still exists and hasn't been deleted
                        $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                        if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
                            $this->addError('invalidgradingstructure');
                            continue;
                        }

                        // Save the criterion
                        $newCriterion->save();

                        // Make sure it saved ok
                        if (!$newCriterion->isValid()){
                            $this->addError('invalidcrit');
                            continue;
                        }

                       
                        // If the grading structure ID is invalid, they will need to update the criterion
                        if (!$gradingStructureID){
                            $this->addOutput('warning:gradingstructure');
                        }

                        // Map the criterion
                        $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                        $this->addOutput('success', $newCriterion->getID());
                        
                        // These are the sub criteria themselves, the Ranges are stored elsewhere in the
                        // old system, so will need to be found as we loop through
                        $rangeArray = array();
                        
                        foreach($checkSubCriteria as $subCrit)
                        {
                            
                            // Store the sub criteria as Numeric as well
                            $newSubCriterion = new \GT\Criteria\NumericCriterion();
                            $newSubCriterion->setUnitID($newUnit->getID());
                            $newSubCriterion->setName($subCrit->name);
                            $newSubCriterion->setDescription($subCrit->details);
                            $newSubCriterion->setGradingStructureID($gradingStructureID);
                            $newSubCriterion->setSubCritType("Criterion");
                            $newSubCriterion->setParentID($newCriterion->getID());

                            // Save the criterion
                            $newSubCriterion->save();
                            $this->addOutputSf('createcrit', $newSubCriterion->getName() );

                            // Make sure it saved ok
                            if (!$newSubCriterion->isValid()){
                                $this->addError('invalidcrit');
                                continue;
                            }
                            

                            // If the grading structure ID is invalid, they will need to update the criterion
                            if (!$gradingStructureID){
                                $this->addOutput('warning:gradingstructure');
                            }

                            // Map the criterion
                            $this->addMapping('criterion', $subCrit->id, $newSubCriterion->getID());
                            $this->addOutput('success', $newSubCriterion->getID());
                            
                            
                            
                            
                            // Check for any ranges attached to this criterion
                            $checkRange = $DB->get_records("block_bcgt_range_criteria", array("bcgtcriteriaid" => $subCrit->id));
                            if ($checkRange)
                            {
                                foreach($checkRange as $chkRange)
                                {
                                    $rangeArray[$newSubCriterion->getID()][] = array("rangeID" => $chkRange->bcgtrangeid, "maxPoints" => $chkRange->maxpoints);
                                }
                            }
                                                                                    
                        }
                        
                        // Now, loop through the Ranges we found attached to the sub criteria, as we need
                        // to attach those Ranges as criteria on the parent here
                        if ($rangeArray)
                        {
                            foreach($rangeArray as $newSubCritID => $ranges)
                            {
                                
                                foreach($ranges as $rangeInfo)
                                {
                                    
                                    // Make sure we haven't already created this Range criterion
                                    $newRangeID = $this->getMappingNewValue('range', $rangeInfo['rangeID']);
                                    if (!$newRangeID)
                                    {

                                        $range = $DB->get_record("block_bcgt_range", array("id" => $rangeInfo['rangeID']));
                                        if ($range)
                                        {

                                            $newRangeCriterion = new \GT\Criteria\NumericCriterion();
                                            $newRangeCriterion->setUnitID($newUnit->getID());
                                            $newRangeCriterion->setName($range->name);
                                            $newRangeCriterion->setDescription($range->details);
                                            $newRangeCriterion->setGradingStructureID($gradingStructureID);
                                            $newRangeCriterion->setSubCritType("Range");
                                            $newRangeCriterion->setParentID($newCriterion->getID());
                                            
                                            // Save the criterion
                                            $newRangeCriterion->save();
                                            $this->addOutputSf('createcrit', $newRangeCriterion->getName() );

                                            // Make sure it saved ok
                                            if (!$newRangeCriterion->isValid()){
                                                $this->addError('invalidcrit');
                                                continue;
                                            }

                                            $newRangeID = $newRangeCriterion->getID();

                                            // If the grading structure ID is invalid, they will need to update the criterion
                                            if (!$gradingStructureID){
                                                $this->addOutput('warning:gradingstructure');
                                            }

                                            // Map the criterion
                                            $this->addMapping('range', $range->id, $newRangeCriterion->getID());
                                            $this->addOutput('success', $newRangeCriterion->getID());

                                            
                                            
                                            // Now we need to save the conversion chart
                                            // Find the old one
                                            $charts = $DB->get_records("block_bcgt_range_chart", array("bcgtrangeid" => $range->id));
                                            if ($charts)
                                            {
                                                foreach($charts as $chart)
                                                {
                                                    
                                                    // The old system stored these against text grades "P", "M" and "D"
                                                    // We need to store them against award IDs now, so need to find the id of the
                                                    // new grade based on its shortname of the old one!
                                                    $letter = $chart->grade;
                                                    
                                                    $newGradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                                                    $award = $newGradingStructure->getAwardByShortName($letter);
                                                    $this->addOutputSf('createconvchart', $newRangeCriterion->getName(), $letter );
                                                    if ($award && $award->isValid())
                                                    {
                                                        // Add the conversion chart attribute
                                                        $newRangeCriterion->updateAttribute("conversion_chart_{$award->getID()}", $chart->points);
                                                    }
                                                    else
                                                    {
                                                        $this->addOutput('warning:gradingstructure');
                                                    }
                                                    
                                                }
                                            }
                                            

                                        }

                                    }

                                    // Having created the range, we now need to link this range to the sub criteria with points
                                    $newCriterion->addPointsLink($newSubCritID, $newRangeID, $rangeInfo['maxPoints']);
                                
                                }
                                
                            }
                        }
                        
                        // Now save the points links
                        $newCriterion->savePointLinks();
                        
                    }
                    
                    // If not, it's just a simple DATE criterion
                    else
                    {
                        
                        // Grading structure for this is Pass Only
                        $key = "crit_pass"; # The old system is wrong, it stores PMD in the crit attributes
                        $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;

                        // Standard Criteria
                        $newCriterion = new \GT\Criteria\StandardCriterion();
                        $newCriterion->setUnitID($newUnit->getID());
                        $newCriterion->setName($crit->name);
                        $newCriterion->setDescription($crit->details);
                        $newCriterion->setGradingStructureID($gradingStructureID);
                        
                        $this->addOutputSf('createcrit', $newCriterion->getName() );
                        
                         // Check grading structure still exists and hasn't been deleted
                        $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                        if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
                            $this->addError('invalidgradingstructure');
                            continue;
                        }

                        // Save the criterion
                        $newCriterion->save();

                        // Make sure it saved ok
                        if (!$newCriterion->isValid()){
                            $this->addError('invalidcrit');
                            continue;
                        }

                        // Grading type should be DATE
                        $newCriterion->updateAttribute("gradingtype", "DATE");

                        // If the grading structure ID is invalid, they will need to update the criterion
                        if (!$gradingStructureID){
                            $this->addOutput('warning:gradingstructure');
                        }

                        // Map the criterion
                        $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                        $this->addOutput('success', $newCriterion->getID());
                        
                    }
                    
                } # End summatives
                
            }
            
        }
        
    }
    
    /**
     * Create new criteria and sub criteria for a HB NVQ unit, getting all the ranges and signoff sheets
     * from the old system into the new one
     * @global \GT\type $DB
     * @param type $unit
     * @param type $newUnit
     * @param type $newQualification
     * @param type $oldType
     * @param type $levelName
     * @param type $mappings
     * @return boolean
     */
    private function createNewNVQCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings){
        
        global $DB;
                
        // Find just the top level criteria, not sub criteria
        $criteria = $DB->get_records("block_bcgt_criteria", array("bcgtunitid" => $unit->id, "parentcriteriaid" => null));
        if ($criteria)
        {
                        
            foreach($criteria as $crit)
            {
         
                // Change the name to fit into the new format
                $crit->name = str_ireplace("Unit Knowledge", "UK", $crit->name);
                $crit->name = preg_replace("/[^0-9a-z- \._ \/]/i", "", $crit->name);
                
                
                // All HB NVQ criteria are Pass/Fail, so only have one grading structure to find
                // Get the mapped grading structure for a unit of this type and level
                $key = $this->getMappedCritGradingStructure($oldType, $levelName, $crit);
                $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;

                // Check grading structure still exists and hasn't been deleted
                $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
                    $this->addError('invalidgradingstructure');
                    continue;
                }
       
                
                // Does the criterion have sub criteria?
                // If so, it could be E1, which should be Ranged
                $checkSubCriteria = $DB->get_records_sql("SELECT * FROM {block_bcgt_criteria}
                                                          WHERE parentcriteriaid = ?
                                                          AND numofobservations > 0", array($crit->id));
                
                // Or it could have sub criteria without any observations, which is probably E3 and E4
                $checkSubCriteriaNoObservations = $DB->get_records_sql("SELECT * FROM {block_bcgt_criteria}
                                                          WHERE parentcriteriaid = ?
                                                          AND numofobservations IS NULL", array($crit->id));
                
                
                
                
                
                // Has some with observations, so this criterion must be a Ranged, with Ranges as sub criteria
                // e.g. E1
                if ($checkSubCriteria)
                {
                    
                    $newCriterion = new \GT\Criteria\RangedCriterion();
                    $newCriterion->setUnitID($newUnit->getID());
                    $newCriterion->setName($crit->name);
                    $newCriterion->setDescription($crit->details);
                    $newCriterion->setGradingStructureID($gradingStructureID);
                    
                    // Save the criterion
                    $newCriterion->save();
                    $this->addOutputSf('createcrit', $newCriterion->getName() );
                    
                    // Make sure it saved ok
                    if (!$newCriterion->isValid()){
                        $this->addError('invalidcrit');
                        continue;
                    }
                    
                    // If the grading structure ID is invalid, they will need to update the criterion
                    if (!$gradingStructureID){
                        $this->addOutput('warning:gradingstructure');
                    }
                    
                    // Map the criterion
                    $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                    $this->addOutput('success', $newCriterion->getID());
                
                                    
                    
                    // Now loop through those sub criteria
                    // E.g. OC1
                    foreach($checkSubCriteria as $subCrit)
                    {
                        
                        // Save this one as a Ranged Criterion as well
                        $newSubCriterion = new \GT\Criteria\RangedCriterion();
                        $newSubCriterion->setUnitID($newUnit->getID());
                        $newSubCriterion->setName($subCrit->name);
                        $newSubCriterion->setDescription($subCrit->details);
                        $newSubCriterion->setGradingStructureID($gradingStructureID);
                        $newSubCriterion->setParentID($newCriterion->getID());
                        $newSubCriterion->setSubCritType("Range");
                        $newSubCriterion->save();
                        $this->addOutputSf('createcrit', $newSubCriterion->getName() );
                        
                        // Make sure it saved ok
                        if (!$newSubCriterion->isValid()){
                            $this->addError('invalidcrit');
                            continue;
                        }
                        
                        // Number of observations attribute
                        $newSubCriterion->updateAttribute('numobservations', $subCrit->numofobservations);
                        
                        // Map the criterion
                        $this->addMapping('criterion', $subCrit->id, $newSubCriterion->getID());
                        $this->addOutput('success', $newSubCriterion->getID());
                        

                        
                        // Now this should also have Sub Criteria, which are used in the ranges
                        $rangeSubCriteria = $DB->get_records("block_bcgt_criteria", array("parentcriteriaid" => $subCrit->id));
                        if ($rangeSubCriteria)
                        {
                            foreach($rangeSubCriteria as $rangeSubCrit)
                            {
                                
                                // Save this one as a Ranged Criterion as well
                                $newSubSubCriterion = new \GT\Criteria\RangedCriterion();
                                $newSubSubCriterion->setUnitID($newUnit->getID());
                                $newSubSubCriterion->setName($rangeSubCrit->name);
                                $newSubSubCriterion->setDescription($rangeSubCrit->details);
                                
                                // For the outcomes, the sub criteria were "Descriptive criteria" only
                                $newSubSubCriterion->setGradingStructureID(null);

                                $newSubSubCriterion->setParentID($newSubCriterion->getID());
                                $newSubSubCriterion->setSubCritType("Criterion");
                                $newSubSubCriterion->save();
                                $this->addOutputSf('createcrit', $newSubSubCriterion->getName() );

                                // Make sure it saved ok
                                if (!$newSubSubCriterion->isValid()){
                                    $this->addError('invalidcrit');
                                    continue;
                                }
                                
                                // Should be grading type DATE instead of NORMAL
                                $newSubSubCriterion->updateAttribute("gradingtype", "DATE");

                                // Map the criterion
                                $this->addMapping('criterion', $rangeSubCrit->id, $newSubSubCriterion->getID());
                                $this->addOutput('success', $newSubSubCriterion->getID());
                                
                            }
                        }
                        
                    }
                    
                }
                
                // Criterion with sub criteria, but no observations (E3/E4)
                elseif ($checkSubCriteriaNoObservations)
                {
                    
                    // Standard Criteria
                    $newCriterion = new \GT\Criteria\StandardCriterion();
                    $newCriterion->setUnitID($newUnit->getID());
                    $newCriterion->setName($crit->name);
                    $newCriterion->setDescription($crit->details);
                    $newCriterion->setGradingStructureID($gradingStructureID);
                    
                    // Save the criterion
                    $newCriterion->save();
                    $this->addOutputSf('createcrit', $newCriterion->getName() );
                    
                    // Make sure it saved ok
                    if (!$newCriterion->isValid()){
                        $this->addError('invalidcrit');
                        continue;
                    }
                    
                    // If the grading structure ID is invalid, they will need to update the criterion
                    if (!$gradingStructureID){
                        $this->addOutput('warning:gradingstructure');
                    }
                    
                    // Map the criterion
                    $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                    $this->addOutput('success', $newCriterion->getID());
                    
                    
                    // Needs the "force popup" option
                    $newCriterion->updateAttribute("forcepopup", 1);
                    
                    
                    // Now create its sub criteria
                    foreach($checkSubCriteriaNoObservations as $subCrit)
                    {
                        
                        // Save this one as a Standard Criterion as well
                        $newSubCriterion = new \GT\Criteria\StandardCriterion();
                        $newSubCriterion->setUnitID($newUnit->getID());
                        $newSubCriterion->setName($newCriterion->getName() . '-' . $subCrit->name);
                        $newSubCriterion->setDescription($subCrit->details);
                        
                        if ($subCrit->type == 'Read Only'){
                            $newSubCriterion->setGradingStructureID(null);
                        } else {
                            $newSubCriterion->setGradingStructureID($gradingStructureID);
                        }
                        
                        $newSubCriterion->setParentID($newCriterion->getID());
                        $newSubCriterion->save();
                        $this->addOutputSf('createcrit', $newSubCriterion->getName() );
                        
                        // Make sure it saved ok
                        if (!$newSubCriterion->isValid()){
                            $this->addError('invalidcrit');
                            continue;
                        }
                        
                        // Map the criterion
                        $this->addMapping('criterion', $subCrit->id, $newSubCriterion->getID());
                        $this->addOutput('success', $newSubCriterion->getID());
                        
                    }
                    
                    
                    
                }
                
                // Else it might have no sub criteria at all - Unit Knowledge
                else
                {
                    
                    $newCriterion = new \GT\Criteria\StandardCriterion();
                    $newCriterion->setUnitID($newUnit->getID());
                    $newCriterion->setName($crit->name);
                    $newCriterion->setDescription($crit->details);
                    $newCriterion->setGradingStructureID($gradingStructureID);
                                        
                    // Save the criterion
                    $newCriterion->save();
                    $this->addOutputSf('createcrit', $newCriterion->getName() );
                    
                    // Make sure it saved ok
                    if (!$newCriterion->isValid()){
                        $this->addError('invalidcrit');
                        continue;
                    }
                    
                    // If the grading structure ID is invalid, they will need to update the criterion
                    if (!$gradingStructureID){
                        $this->addOutput('warning:gradingstructure');
                    }
                    
                    // Map the criterion
                    $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                    $this->addOutput('success', $newCriterion->getID());
                    
                }                
                
            }
            
        }
        
        
        // Now the sign-off sheets
        $signOffSheets = $DB->get_records("block_bcgt_signoff_sheet", array("bcgtunitid" => $unit->id));
        if ($signOffSheets)
        {
            
            // Create a criterion for the Sign-Off sheet
            $signOffCriterion = new \GT\Criteria\RangedCriterion();
            $signOffCriterion->setUnitID($newUnit->getID());
            $signOffCriterion->setName("Sign-Off Sheets");
            $signOffCriterion->setDescription("");
            $signOffCriterion->setGradingStructureID($gradingStructureID);
            
            // Save the criterion
            $signOffCriterion->save();
            $this->addOutputSf('createcrit', $signOffCriterion->getName() );

            // Make sure it saved ok
            if (!$signOffCriterion->isValid()){
                $this->addError('invalidcrit');
                return false;
            }

            // If the grading structure ID is invalid, they will need to update the criterion
            if (!$gradingStructureID){
                $this->addOutput('warning:gradingstructure');
            }

            // Map the criterion
            $this->addMapping('signoff_sheets_unit', $unit->id, $signOffCriterion->getID());
            $this->addOutput('success', $signOffCriterion->getID());
            
            foreach($signOffSheets as $sheet)
            {
                
                // Save this one as a Ranged Criterion as well
                $newSubCriterion = new \GT\Criteria\RangedCriterion();
                $newSubCriterion->setUnitID($newUnit->getID());
                $newSubCriterion->setName($sheet->name);
                $newSubCriterion->setDescription("");
                $newSubCriterion->setGradingStructureID($gradingStructureID);
                $newSubCriterion->setParentID($signOffCriterion->getID());
                $newSubCriterion->setSubCritType("Range");
                $newSubCriterion->save();
                $this->addOutputSf('createcrit', $newSubCriterion->getName() );

                // Make sure it saved ok
                if (!$newSubCriterion->isValid()){
                    $this->addError('invalidcrit');
                    continue;
                }

                // Map the criterion
                $this->addMapping('signoff_sheet', $sheet->id, $newSubCriterion->getID());
                $this->addOutput('success', $newSubCriterion->getID());
                
                // Now store the number of observations against it
                $newSubCriterion->updateAttribute('numobservations', $sheet->numofobservations);
                
                
                // Now the sub criteria on the sheet
                $sheetCriteria = $DB->get_records("block_bcgt_soff_sheet_ranges", array("bcgtsignoffsheetid" => $sheet->id));
                if ($sheetCriteria)
                {
                    
                    foreach($sheetCriteria as $sheetCrit)
                    {
                        
                        // Save this one as a Ranged Criterion as well
                        $newSheetCriterion = new \GT\Criteria\RangedCriterion();
                        $newSheetCriterion->setUnitID($newUnit->getID());
                        $newSheetCriterion->setName($sheetCrit->name);
                        $newSheetCriterion->setDescription("");
                        $newSheetCriterion->setGradingStructureID($gradingStructureID);
                        $newSheetCriterion->setParentID($newSubCriterion->getID());
                        $newSheetCriterion->setSubCritType("Criterion");
                        $newSheetCriterion->save();
                        $this->addOutputSf('createcrit', $newSheetCriterion->getName() );

                        // Make sure it saved ok
                        if (!$newSheetCriterion->isValid()){
                            $this->addError('invalidcrit');
                            continue;
                        }

                        // Map the criterion
                        $this->addMapping('signoff_sheet_criterion', $sheetCrit->id, $newSheetCriterion->getID());
                        $this->addOutput('success', $newSheetCriterion->getID());
                        
                    }
                    
                }                
                
            }
            
        }
        
    }
    
    private function createNewCriteria($unit, $newUnit, $newQualification, $oldType, $levelName, $mappings){
        
        global $DB;
        
        // Array to store parent/child id relationships, so when we've created all the criteria
        // we can go through and link up the children to the parents, using the new criteria IDs
        $parentIDArray = array();
        
        // Find all the criteria on the old unit
        $criteria = $DB->get_records("block_bcgt_criteria", array("bcgtunitid" => $unit->id));
        if ($criteria)
        {
            foreach($criteria as $crit)
            {

                $newCriterion = new \GT\Criteria\StandardCriterion();
                $newCriterion->setUnitID($newUnit->getID());
                $newCriterion->setName($crit->name);
                $newCriterion->setDescription($crit->details);
                
                $this->addOutputSf('createcrit', $newCriterion->getName() );

                // BTEC and CG (general) will just use the Standard Criteria
                $type = \GT\QualificationStructureLevel::getByName("Standard Criteria");
                $newCriterion->setType( ($type) ? $type->getID() : 0 );

                // Get the mapped grading structure for a unit of this type and level
                $key = $this->getMappedCritGradingStructure($oldType, $levelName, $crit);
                $gradingStructureID = isset($mappings[$key]) ? $mappings[$key] : false;
                
                // Check grading structure still exists and hasn't been deleted
                $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
                if (!$gradingStructure->isValid() || $gradingStructure->isDeleted()){
                    $this->addError('invalidgradingstructure');
                    continue;
                }
                
                $newCriterion->setGradingStructureID($gradingStructureID);

                // Save the criterion
                $newCriterion->save();

                if (!$newCriterion->isValid()){
                    $this->addError('invalidcrit');
                    continue;
                }
                
                // If the grading structure ID is invalid, they will need to update the criterion
                if (!$gradingStructureID){
                    $this->addOutput('warning:gradingstructure');
                }

                // Map the criterion
                $this->addMapping('criterion', $crit->id, $newCriterion->getID());
                $this->addOutput('success', $newCriterion->getID());
                
                // Did the old criterion have a parent? If so, link up the new ID of this criterion
                // to the old ID of the parent, and we can loop through and find the new parent
                if ($crit->parentcriteriaid > 0){
                    $parentIDArray[$newCriterion->getID()] = $crit->parentcriteriaid;
                }

            }
        }

        // Now loop through any parent relationships to link them up
        if ($parentIDArray)
        {
            foreach($parentIDArray as $newCritID => $oldParentID)
            {
                
                // Find the criterion id of the new Criterion, mapped to the old parent ID
                $newParentID = $this->getMappingNewValue('criterion', $oldParentID);
                if ($newParentID)
                {
                    
                    $parent = \GT\Criterion::load($newParentID);
                    $criterion = \GT\Criterion::load($newCritID);
                    
                    if ($criterion && $criterion->isValid())
                    {
                        
                        $criterion->setParentID($newParentID);
                        $criterion->save();
                        $this->addOutputSf('parentlink', $criterion->getName(), $parent->getName());
                        
                    }
                    
                }
                else
                {
                    $this->addError('mapping:critparent');
                    continue;
                }
                
            }
        }
        
        return true;
        
    }
    
    private function getMappedUnitGradingStructure($type, $levelName, $oldUnit){
        
        global $DB;
        
        switch ($type)
        {
            
            case 'btec':
                
                // Unit Level
                
                // Level 1 BTEC is a Pass/Fail unit
                if ($levelName == 'Level 1')
                {
                    return "unit_pf";
                }
                
                // Level 1 and 2 is a special grading structure
                elseif ($levelName == "Level 1 & 2" || $levelName == "Level 1 / Level 2")
                {
                    return "unit_l1l2";
                }
                
                // All others are PMD
                else
                {
                    return "unit_pmd";
                }
                
                
            break;
            
            case 'cg':
                
                    // Find out the grading type used on the old unit and then find the mapping from that
                    // to the new Grading Structure to be used
                    $record = $DB->get_record("block_bcgt_unit_attributes", array("bcgtunitid" => $oldUnit->id, "attribute" => "GRADING"));
                    if ($record)
                    {
                        if ($record->value == 'PMD')
                        {
                            return "unit_pmd";
                        }
                        elseif ($record->value == 'PCD')
                        {
                            return "unit_pcd";
                        }
                        elseif ($record->value == 'P')
                        {
                            return "unit_pass";
                        }
                        else
                        {
                            // Seems that some of the old units didn't have a grading type set, and it
                            // was defaulting to PMD
                            return "unit_pmd";
                        }
                    }
                
            break;
            
            case 'cghbnvq':
                
                return "unit_pass";
                
            break;
        
            case 'cghbvrq':
                
                return "unit_pmd";
                
            break;
         
            default:
                return false;
            
        }
        
        return false;
        
    }
    
    
    
    private function getMappedCritGradingStructure($type, $levelName, $oldCriterion){
        
        global $DB;
        
        switch ($type)
        {
            
            case 'btec':
                
                return "crit_achieved";
                
            break;
        
            case 'cg':
                
                // Find out the grading type used on the old criterion and then find the mapping from that
                // to the new Grading Structure to be used
                $record = $DB->get_record("block_bcgt_criteria_att", array("bcgtcriteriaid" => $oldCriterion->id, "attribute" => "GRADING"));
                if ($record)
                {
                    if ($record->value == 'PMD')
                    {
                        return "crit_pmd";
                    }
                    elseif ($record->value == 'PCD')
                    {
                        return "crit_pcd";
                    }
                    elseif ($record->value == 'P')
                    {
                        return "crit_pass";
                    }
                    elseif ($record->value == 'DATE')
                    {
                        return "crit_date";
                    }
                    elseif ($record->value == 'TEXT')
                    {
                        return "crit_freetext";
                    }
                }
                
                
            break;
            
            case 'cghbnvq':
                
                return "crit_pass";
                
            break;
        
            case 'cghbvrq':
                
                $record = $DB->get_record("block_bcgt_criteria_att", array("bcgtcriteriaid" => $oldCriterion->id, "attribute" => "GRADING"));
                if ($record->value == 'PMD')
                {
                    return "crit_pmd";
                }
                elseif ($record->value == 'P')
                {
                    return "crit_pass";
                }
                
            break;
         
            default:
                return false;
            
        }
        
    }
    
    private function getRequiredMappings($type){
        
        $return = array();
        
        switch($type)
        {
            
            case 'btec':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                $return['unit_pmd'] = $this->getMappingNewValue('unit_grading_structure_btec', 'pmd');
                $return['unit_pf'] = $this->getMappingNewValue('unit_grading_structure_btec', 'pf');
                $return['unit_l1l2'] = $this->getMappingNewValue('unit_grading_structure_btec', 'l1l2');
                $return['crit_achieved'] = $this->getMappingNewValue('crit_grading_structure_btec', 'achieved');
                
            break;
        
            case 'cg':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                $return['unit_pmd'] = $this->getMappingNewValue('unit_grading_structure_cg', 'pmd');
                $return['unit_pcd'] = $this->getMappingNewValue('unit_grading_structure_cg', 'pcd');
                $return['unit_pass'] = $this->getMappingNewValue('unit_grading_structure_cg', 'pass');
                $return['crit_pmd'] = $this->getMappingNewValue('crit_grading_structure_cg', 'pmd');
                $return['crit_pcd'] = $this->getMappingNewValue('crit_grading_structure_cg', 'pcd');
                $return['crit_pass'] = $this->getMappingNewValue('crit_grading_structure_cg', 'pass');
                $return['crit_date'] = $this->getMappingNewValue('crit_grading_structure_cg', 'date');
                $return['crit_freetext'] = $this->getMappingNewValue('crit_grading_structure_cg', 'freetext');
                
            break;
        
            case 'cghbnvq':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                $return['unit_pass'] = $this->getMappingNewValue('unit_grading_structure_cghbnvq', 'pass');
                $return['crit_pass'] = $this->getMappingNewValue('crit_grading_structure_cghbnvq', 'pass');
                
            break;
        
            case 'cghbvrq':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                $return['unit_pmd'] = $this->getMappingNewValue('unit_grading_structure_cghbvrq', 'pmd');
                $return['crit_pmd'] = $this->getMappingNewValue('crit_grading_structure_cghbvrq', 'pmd');
                $return['crit_pass'] = $this->getMappingNewValue('crit_grading_structure_cghbvrq', 'pass');
                
            break;
        
            case 'alvl':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                
            break;
        
            case 'gcse':
                
                $return['structure'] = $this->getMappingNewValue('qual_structure', $type);
                
            break;
                
        }
        
        return $return;
        
    }
    
    /**
     * Get an old qual record from the DB
     * @global \GT\type $DB
     * @param type $qualID
     * @return type
     */
    private function getOldQualification($qualID){
        
        global $DB;
        
        $record = $DB->get_record_sql("SELECT q.*, f.family, t.type, s.subtype, l.trackinglevel, f.id as familyid, tq.id as tqid
                                        FROM {block_bcgt_qualification} q
                                        INNER JOIN {block_bcgt_target_qual} tq ON tq.id = q.bcgttargetqualid
                                        INNER JOIN {block_bcgt_type} t ON t.id = tq.bcgttypeid
                                        INNER JOIN {block_bcgt_subtype} s ON s.id = tq.bcgtsubtypeid
                                        INNER JOIN {block_bcgt_level} l ON l.id = tq.bcgtlevelid
                                        INNER JOIN {block_bcgt_type_family} f ON f.id = t.bcgttypefamilyid
                                        WHERE q.id = ?", array($qualID));
        
        return $record;
        
    }
    
    /**
     * Get an old unit record from the DB
     * @global \GT\type $DB
     * @param type $unitID
     * @return type
     */
    private function getOldUnit($unitID){
        
        global $DB;
        
        $record = $DB->get_record_sql("select u.*, t.type, l.trackinglevel
                                        from {block_bcgt_unit} u
                                        inner join {block_bcgt_type} t on t.id = u.bcgttypeid
                                        left join {block_bcgt_level} l on l.id = u.bcgtlevelid
                                        where u.id = ?", array($unitID));
        
        return $record;
        
    }
    
    /**
     * Get records for the old criteria on an old unit
     * @global \GT\type $DB
     * @param type $unitID
     * @return type
     */
    private function getOldUnitCriteria($unitID){
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_criteria", array("bcgtunitid" => $unitID));
        return $records;
        
    }
    
    /**
     * Get records for the old ranges on a unit (CGHBVRQ)
     * @global \GT\type $DB
     * @param type $unitID
     * @return type
     */
    private function getOldUnitRanges($unitID){
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_range", array("bcgtunitid" => $unitID));
        return $records;
        
    }
    
    /**
     * Get records for the old range criteria on a unit (CGHBVRQ)
     * @global \GT\type $DB
     * @param type $rangeID
     * @return type
     */
    private function getOldUnitRangeCriteria($rangeID){
        
        global $DB;
        
        $records = $DB->get_records_sql("SELECT rc.*, c.name
                                         FROM {block_bcgt_range_criteria} rc
                                         INNER JOIN {block_bcgt_criteria} c ON c.id = rc.bcgtcriteriaid
                                         WHERE rc.bcgtrangeid = ?", array($rangeID));
        return $records;
        
    }
    
    /**
     * Get records for the old signoff sheets on a unit (CGHBNVQ)
     * @global \GT\type $DB
     * @param type $unitID
     * @return type
     */
    private function getOldUnitSignOffSheets($unitID){
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_signoff_sheet", array("bcgtunitid" => $unitID));
        return $records;
        
    }
    
    /**
     * Get records for the old ranges on an old signoff sheet on a unit
     * @global \GT\type $DB
     * @param type $sheetID
     * @return type
     */
    private function getOldUnitSignOffSheetRanges($sheetID){
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_soff_sheet_ranges", array("bcgtsignoffsheetid" => $sheetID));
        return $records;
        
    }
    
    /**
     * Get any record from the old system by its ID
     * @global \GT\type $DB
     * @param type $table
     * @param type $id
     * @return type
     */
    private function getOldRecord($table, $id){
        
        global $DB;
        return $DB->get_record($table, array("id" => $id));
        
    }
    
    private function loadOldQualificationSpec($qual){
        
        
        
    }
    
    /**
     * Get a mapping from the database
     * @global \GT\type $DB
     * @param type $item
     * @param type $oldID
     * @return type
     */
    private function getMapping($item, $oldID){
        
        global $DB;
        return $DB->get_record("bcgt_data_mapping", array("context" => "block_bcgt", "item" => $item, "oldid" => $oldID));
        
    }
    
    public function getMappingNewValue($item, $oldID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_data_mapping", array("context" => "block_bcgt", "item" => $item, "oldid" => $oldID));
        return ($record) ? $record->newid : false;
        
    }
    
    public function getMappingOldValueFromNew($item, $newID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_data_mapping", array("context" => "block_bcgt", "item" => $item, "newid" => $newID));
        return ($record) ? $record->oldid : false;
        
    }
    
    /**
     * Add a new mapping to the database
     * @global \GT\type $DB
     * @param type $item
     * @param type $oldID
     * @param type $newID
     * @return type
     */
    private function addMapping($item, $oldID, $newID){
        
        global $DB;

        // Check to see if this mapping already exists
        $record = $this->getMapping($item, $oldID);

        // If it exists, update the record
        if ($record)
        {
            $record->newid = $newID;
            return $DB->update_record("bcgt_data_mapping", $record);
        }
        // Otherwise create new one
        else
        {
            $ins = new \stdClass();
            $ins->context = "block_bcgt";
            $ins->item = $item;
            $ins->oldid = $oldID;
            $ins->newid = $newID;
            return $DB->insert_record("bcgt_data_mapping", $ins);
        }
        
    }
    
    /**
     * Split unit name into number and name
     * @param type $name
     * @return type
     */
    private function splitUnitNameNumber($name){
        return \gt_split_unit_name_number($name);
    }
    
    /**
     * Get an old user_criteria record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $criterionID
     * @param type $userID
     * @return type
     */
    private function getOldUserCriterionValue($qualID, $criterionID, $userID){
        
        global $DB;
        
        $record = $DB->get_record("block_bcgt_user_criteria", array("userid" => $userID, "bcgtqualificationid" => $qualID, "bcgtcriteriaid" => $criterionID));
        return $record;
        
    }
    
    /**
     * Get an old user_range record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $criterionID
     * @param type $userID
     * @return type
     */
    private function getOldUserRangeValue($qualID, $rangeID, $userID){
        
        global $DB;
        
        $record = $DB->get_record("block_bcgt_user_range", array("userid" => $userID, "bcgtqualificationid" => $qualID, "bcgtrangeid" => $rangeID));
        return $record;
        
    }
    
    /**
     * Get an old user_crit_range record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $rangeID
     * @param type $critID
     * @param type $userID
     * @return type
     */
    private function getOldUserRangeCriterionValue($qualID, $rangeID, $critID, $userID){
        
        global $DB;
        
        $record = $DB->get_record("block_bcgt_user_crit_range", array("bcgtqualificationid" => $qualID, "bcgtrangeid" => $rangeID, "bcgtcriteriaid" => $critID, "userid" => $userID));
        return $record;
        
    }
    
    /**
     * Get the old records for a user's signoff sheet range values
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $sheetID
     * @param type $sheetRangeID
     * @param type $userID
     * @return type
     */
    private function getOldUserSignOffSheetRangeValues($qualID, $sheetID, $sheetRangeID, $userID){
        
        global $DB;
        
        $records = $DB->get_records("block_bcgt_user_soff_sht_rgs", array("bcgtqualificationid" => $qualID, "bcgtsignoffsheetid" => $sheetID, "bcgtsignoffrangeid" => $sheetRangeID, "userid" => $userID));
        return $records;
        
    }
    
    /**
     * Get an old user_unit record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @param type $userID
     * @return type
     */
    private function getOldUserUnitValue($qualID, $unitID, $userID){
        
        global $DB;
        
        $record = $DB->get_record("block_bcgt_user_unit", array("userid" => $userID, "bcgtqualificationid" => $qualID, "bcgtunitid" => $unitID));
        return $record;
        
    }
    
    /**
     * Get all the data mapped by transfers
     * @global \GT\type $DB
     */
    public static function getAllDataMapped(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records_sql("SELECT * FROM {bcgt_data_mapping} WHERE context = 'block_bcgt' AND item not like '%_structure%' ORDER BY item, oldid");
        if ($records)
        {
            foreach($records as $record)
            {
                if (!array_key_exists($record->item, $return)){
                    $return[$record->item] = array();
                }
                
                $return[$record->item][] = $record;
                
            }
        }
                
        return $return;
        
    }
    
    /**
     * Get new Qualification records for any qualification that was created from a data transfer
     * @global \GT\type $DB
     * @return \GT\Qualification
     */
    public static function getNewMappedQualifications(){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("bcgt_data_mapping", array("context" => "block_bcgt", "item" => "qualification"));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\Qualification\UserQualification($record->newid);
                if ($obj->isValid() && !$obj->isDeleted())
                {
                    $return[$obj->getID()] = $obj;
                }
            }
        }
        
        $sort = new \GT\Sorter();
        $sort->sortQualifications($return);
        
        return $return;
        
    }
    
    /**
     * Get the name of the old qualification that is mapped to this new qual
     * @global \GT\type $DB
     * @param type $qualID
     * @return string
     */
    public static function getOldQualNameFromNew($qualID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_data_mapping", array("context" => "block_bcgt", "item" => "qualification", "newid" => $qualID));
        if ($record)
        {
            $oldQual = $DB->get_record_sql("SELECT q.id, q.name, t.type, l.trackinglevel, s.subtype
                                            FROM {block_bcgt_qualification} q
                                            INNER JOIN {block_bcgt_target_qual} tq ON tq.id = q.bcgttargetqualid
                                            INNER JOIN {block_bcgt_type} t ON t.id = tq.bcgttypeid
                                            INNER JOIN {block_bcgt_level} l ON l.id = tq.bcgtlevelid
                                            INNER JOIN {block_bcgt_subtype} s ON s.id = tq.bcgtsubtypeid
                                            WHERE q.id = ?", array($record->oldid));
            if ($oldQual)
            {
                return '['.$oldQual->id.'] ' . $oldQual->type . ' ' . $oldQual->trackinglevel . ' ' . $oldQual->subtype . ' ' . $oldQual->name;
            }
        }
        
        return '-';
        
    }
    
    
    
}
