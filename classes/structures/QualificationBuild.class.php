<?php
/**
 * QualificationBuild
 *
 * The class for QUalification Builds - The combination of levels, types, sub types
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

class QualificationBuild {
    
    private $id = false;
    private $structureID;
    private $levelID;
    private $subTypeID;
    private $deleted = 0;
    
    private $awards = array();
    
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        $GTEXE = \GT\Execution::getInstance();
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_builds", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->structureID = $record->structureid;
                $this->levelID = $record->levelid;
                $this->subTypeID = $record->subtypeid;
                $this->deleted = $record->deleted;
                
                // Load awards
                if (!isset($GTEXE->QUAL_BUILD_MIN_LOAD) || !$GTEXE->QUAL_BUILD_MIN_LOAD){
                    $this->loadAwards();
                }
                
            }
            
        }
        
    }
    
    /**
     * Is it a valid record from the database
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Is this build deleted?
     * @return type
     */
    public function isDeleted(){
        return ($this->deleted === 1);
    }
    
    /**
     * Get the id of the qual build
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Set the id of the qual build
     * @param type $id
     */
    public function setID($id){
        $this->id = $id;
    }
    
    /**
     * Get the structure id of the build
     * @return type
     */
    public function getStructureID(){
        return $this->structureID;
    }
    
    /**
     * Set the structure id of the build
     * @param type $id
     */
    public function setStructureID($id){
        $this->structureID = $id;
    }
    
    /**
     * Get the display name of the structure used
     * @return type
     */
    public function getStructureName(){
        
        $structure = new \GT\QualificationStructure($this->structureID);
        return ($structure->isValid()) ? $structure->getDisplayName() : false;
        
    }
    
    /**
     * Get the real name of the structure
     * @return type
     */
    public function getStructureRealName(){
        
        $structure = new \GT\QualificationStructure($this->structureID);
        return ($structure->isValid()) ? $structure->getName() : false;
        
    }
    
    /**
     * Get the level id of the build
     * @return type
     */
    public function getLevelID(){
        return $this->levelID;
    }
    
    /**
     * Set the level id of the build
     * @param type $id
     */
    public function setLevelID($id){
        $this->levelID = $id;
    }
    
    /**
     * Get the name of the level used
     * @return type
     */
    public function getLevelName(){
        
        $level = new \GT\Level($this->levelID);
        return ($level->isValid()) ? $level->getName() : false;
        
    }
    
    /**
     * Get the level object
     * @return \GT\Level
     */
    public function getLevel(){
        $level = new \GT\Level($this->levelID);
        return ($level->isValid()) ? $level : false;
    }
    
    /**
     * Get the subtype id of the build
     * @return type
     */
    public function getSubTypeID(){
        return $this->subTypeID;
    }
    
    /**
     * Set the subtype id of the build
     * @param type $id
     */
    public function setSubTypeID($id){
        $this->subTypeID = $id;
    }
    
    /**
     * Get the name of the sub type used
     * @return type
     */
    public function getSubTypeName(){
        
        $subType = new \GT\SubType($this->subTypeID);
        return ($subType) ? $subType->getName() : false;
        
    }
    
    /**
     * Get the subtype object
     * @return \GT\Level
     */
    public function getSubType(){
        $subType = new \GT\SubType($this->subTypeID);
        return ($subType->isValid()) ? $subType : false;
    }
    
    /**
     * Get the combined name of the structure, level and subtype
     * @return type
     */
    public function getName(){
        return $this->getNameWithSeparator(" ");
    }
    
    public function getNameWithSeparator($sep = '//'){
        return $this->getStructureName() . $sep . $this->getLevelName() . $sep . $this->getSubTypeName();
    }
        
    /**
     * Set the deleted flag to 0 or 1
     * @param type $val
     */
    public function setDeleted($val){
        $this->deleted = (int)$val;
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Get the default rule set for this qualification build
     * @return \GT\RuleSet
     */
    public function getDefaultRuleSet(){
        
        $ruleSetID = $this->getAttribute("build_default_ruleset");
        if ($ruleSetID){
            $ruleSet = new \GT\RuleSet($ruleSetID);
            if ($ruleSet->isValid() && $ruleSet->isEnabled() && $ruleSet->getQualStructureID() == $this->structureID){
                return $ruleSet;
            }
        }
        
        $Structure = new \GT\QualificationStructure($this->structureID);
        return $Structure->getDefaultRuleSet();
        
    }
    
    public function getDefaultRuleSetID(){
        $ruleSet = $this->getDefaultRuleSet();
        return ($ruleSet) ? $ruleSet->getID() : false;
    }
    
    /**
     * Get any loaded awards
     * @return type
     */
    public function getAwards($order = false){
        
        if (!$this->awards){
            $this->loadAwards();
        }
        
        // Do we want to specifically sort them?
        if ($order){
        
            $Sorter = new \GT\Sorter();
            $Sorter->sortQualAwards($this->awards, $order);
            
        } 
        
        return $this->awards;
        
    }
    
    /**
     * Get a setting of this qual build
     * @global \GT\type $DB
     * @param type $setting
     * @return type
     */
    public function getAttribute($attribute){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_qual_build_attributes", array("buildid" => $this->id, "attribute" => $attribute));
        return ($check) ? $check->value : false;
        
    }
    
    /**
     * Update a setting of this qual build
     * @global \GT\type $DB
     * @param type $setting
     * @param type $value
     */
    public function updateAttribute($attribute, $value){
        
        global $DB;
        
        $check = $DB->get_record("bcgt_qual_build_attributes", array("buildid" => $this->id, "attribute" => $attribute));
        if ($check)
        {
            $check->value = $value;
            $DB->update_record("bcgt_qual_build_attributes", $check);
        }
        else
        {
            $ins = new \stdClass();
            $ins->buildid = $this->id;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $DB->insert_record("bcgt_qual_build_attributes", $ins);
        }
        
        
    }
    
    /**
     * Get the default number of credits expected for this build
     * @return type
     */
    public function getDefaultCredits(){
        $credits = $this->getAttribute('build_default_credits');
        return ($credits && $credits > 0) ? (int)$credits : false;
    }
    
    /**
     * Get the points per credit for qual award calculations
     * @return type
     */
    public function getPointsPerCredit(){
        $points = $this->getAttribute('build_default_points_per_credit');
        return ($points && $points > 0) ? (int)$points : false;
    }
    
    
    /**
     * Load any awards this qual build has
     * @global \GT\type $DB
     * @return \GT\QualificationAward
     */
    public function loadAwards(){
        
        global $DB;
        
        $this->awards = array();
        
        $records = $DB->get_records("bcgt_qual_build_awards", array("buildid" => $this->id), "rank ASC", "id");
        
        if ($records)
        {
            
            foreach($records as $record)
            {
                $this->awards[$record->id] = new \GT\QualificationAward($record->id);
            }
            
        }
                
    }
    
    /**
     * Get an award by its points range
     * @param type $points
     * @return boolean
     */
    public function getAwardByPoints($points, $exact = false){
                
        if (!$this->awards){
            $this->loadAwards();
        }
        
        if ($this->awards)
        {
            
            foreach($this->awards as $award)
            {
                                
                // If exact, the rank must meet this value exactly
                if ($exact && $award->getRank() == $points)
                {
                    return $award;
                }
                
                // If the points are between the 2 ranges, return that award
                elseif (!$exact && $points >= $award->getPointsLower() && $points <= $award->getPointsUpper())
                {
                    return $award;
                }
                
            }
            
        }
        
        return false;
        
    }
        
    /**
     * Get award based on AVG GCSE score
     * @param type $score
     * @return boolean
     */
    public function getAwardByAvgGCSEScore($score){
        
        if (!$this->awards){
            $this->loadAwards();
        }
        
        if ($this->awards)
        {
            
            foreach($this->awards as $award)
            {
                
                // If lower & upper are both 0, skip it
                if ($award->getQOELower() == 0 && $award->getQOEUpper() == 0) continue;
                
                // If the score falls between its lower & upper ranges, return it
                if ($score >= $award->getQOELower() && $score <= $award->getQOEUpper())
                {
                    return $award;
                }
                
            }
            
        }
        
        return false;
        
    }
    
    
    /**
     * Get award based on UCAS Points
     * @param int $points
     * @return boolean
     */
    public function getAwardByUCASPoints($points, $direction){
        
        global $DB;
        
        $record = false;
        
        // Get the next award above or equal to these points
        if ($direction == 'UP'){
            
            $record = $DB->get_record_sql("SELECT a.id
                                            FROM {bcgt_qual_build_awards} a
                                            WHERE a.buildid = ? AND a.ucas IN
                                            (
                                                SELECT MIN(ucas)
                                                FROM {bcgt_qual_build_awards}
                                                where buildid = ?
                                                and ucas >= ?
                                            )", array($this->id, $this->id, $points));
            
            // If there are none, then look for the first one less than this
            // E.g. A2 top UCAS score is 140, if weighted score is 146, it won't find anything, but we want top
            if (!$record)
            {
                $record = $DB->get_record_sql("SELECT a.id
                                            FROM {bcgt_qual_build_awards} a
                                            WHERE a.buildid = ? AND a.ucas IN
                                            (
                                                SELECT MAX(ucas)
                                                FROM {bcgt_qual_build_awards}
                                                where buildid = ?
                                                and ucas <= ?
                                            )", array($this->id, $this->id, $points));
            }
            
        } elseif ($direction == 'DOWN'){
            
            $record = $DB->get_record_sql("SELECT a.id
                                            FROM {bcgt_qual_build_awards} a
                                            WHERE a.buildid = ? AND a.ucas IN
                                            (
                                                SELECT MAX(ucas)
                                                FROM {bcgt_qual_build_awards}
                                                where buildid = ?
                                                and ucas <= ?
                                            )", array($this->id, $this->id, $points));
            
        }
        
        return ($record) ? new \GT\QualificationAward($record->id) : false;
        
    }
    
    
    /**
     * Get an award by its name
     * @param type $name
     * @return boolean
     */
    public function getAwardByName($name){
        
        if (!$this->awards){
            $this->loadAwards();
        }
        
        if ($this->awards)
        {
            
            foreach($this->awards as $award)
            {
                
                // If the points are between the 2 ranges, return that award
                if ($award->getName() == $name)
                {
                    return $award;
                }
                
            }
            
        }
        
        return false;
        
    }
    
    
    /**
     * Get the maximum number of points of this award structure
     * @return type
     */
    public function getMaxRank(){
        
        $max = 0;
        $awards = $this->getAwards();
        if ($awards){
            foreach($awards as $award){
                if ($award->getRank() > $max){
                    $max = $award->getRank();
                }
            }
        }
        
        return $max;
        
    }
    
     /**
     * Get the maximum number of points of this award structure
     * @return type
     */
    public function getMinRank(){
        
        $min = false;
        $awards = $this->getAwards();
        if ($awards){
            foreach($awards as $award){
                if ($award->getRank() < $min || ($min === false)){
                    $min = $award->getRank();
                }
            }
        }
        
        return ($min !== false) ? $min : 0;
        
    }
    
    
    /**
     * Count the number of qualifications using this build
     * @global \GT\type $DB
     * @return type
     */
    public function countQualifications(){
       
        global $DB;
        return $DB->count_records("bcgt_qualifications", array("buildid" => $this->id, "deleted" => 0));
        
    }
    
    /**
     * Get the qualifications using this build
     * @global \GT\type $DB
     * @return type
     */
    public function getQualifications(){
       
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("bcgt_qualifications", array("buildid" => $this->id, "deleted" => 0));
        
        if ($records)
        {
            
            foreach($records as $record)
            {
                $return[] = new \GT\Qualification($record->id);
            }
            
        }
        
        return $return;
        
    }
    
    /**
     * Check if this qual build is using qual weightings
     * @return type
     */
    public function hasQualWeightings()
    {
        global $DB;
        return ( \GT\Setting::getSetting('qual_weighting_percentiles') > 0 && $DB->get_record_sql("SELECT id FROM {bcgt_settings} WHERE setting LIKE ?", array('build_coefficient_'.$this->id.'_%'), IGNORE_MULTIPLE) );        
    }
    
    /**
     * Check there are no errors from the submitted data
     * @global \GT\type $DB
     * @return type
     */
    public function hasNoErrors(){
        
        global $DB;
        
        // Check type exists
        $check = $DB->get_record("bcgt_qual_structures", array("id" => $this->structureID, "deleted" => 0));
        if (!$check)
        {
            $this->errors[] = get_string('errors:qualbuild:type', 'block_gradetracker');
        }
        
        // Check level exists
        $check = $DB->get_record("bcgt_qual_levels", array("id" => $this->levelID));
        if (!$check)
        {
            $this->errors[] = get_string('errors:qualbuild:level', 'block_gradetracker');
        }
        
        // Check subtype exists
        $check = $DB->get_record("bcgt_qual_subtypes", array("id" => $this->subTypeID));
        if (!$check)
        {
            $this->errors[] = get_string('errors:qualbuild:subtype', 'block_gradetracker');
        }
        
        // Check this combination doesn't already exist
        $check = $DB->get_record("bcgt_qual_builds", array("structureid" => $this->structureID, "levelid" => $this->levelID, "subtypeid" => $this->subTypeID, "deleted" => 0));
        if ($check && $check->id <> $this->id)
        {
            $this->errors[] = get_string('errors:qualbuild:duplicate', 'block_gradetracker');
        }
        
        return (!$this->errors);
        
    }
    
    
    public function save(){
                
        global $DB, $MSGS;
        
        $obj = new \stdClass();
                
        if ($this->id){
            $obj->id = $this->id;
        }
        
        $obj->structureid = $this->structureID;
        $obj->levelid = $this->levelID;
        $obj->subtypeid = $this->subTypeID;
        $obj->deleted = $this->deleted;
        
        // Update existing
        if ($this->id){
            $result = $DB->update_record("bcgt_qual_builds", $obj);
        }
        
        // Insert new
        else {
            $this->id = $DB->insert_record("bcgt_qual_builds", $obj);
            $result = $this->id;
        }
        
        // If we failed somehow, stop
        if (!$result) return false;
        
        // Awards
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                if ($award->hasNoErrors())
                {
                    $award->save();
                }
                else
                {
                    foreach($award->getErrors() as $err)
                    {
                        $MSGS['errors'][] = $err;
                    }
                }
            }
        }
        
        // Get rid of ones not wanted any more
        $this->deleteRemovedAwards();
        
        return true;
        
        
    }
    
    
    public function delete()
    {
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = 1;
        
        // Update this to deleted
        $DB->update_record("bcgt_qual_builds", $obj);
        
        // 
        
        // Then delete any qualifications using this build
        
        $quals = $DB->get_records("bcgt_qualifications", array("buildid" => $this->id, "deleted" => 0));
        foreach ($quals as $qual) {
            $qual->deleted = 1;
            $DB->update_record("bcgt_qualifications", $qual);
        }
        
        return true;
    }
    
     /**
     * Delete any awards that were on the build before but not submitted this time
     * @global \GT\type $DB
     */
    private function deleteRemovedAwards(){
        
        global $DB;
        
        $oldIDs = array();
        $currentIDs = array();
        
        // Old ones
        $old = $DB->get_records("bcgt_qual_build_awards", array("buildid" => $this->id));
        if ($old)
        {
            foreach($old as $o)
            {
                $oldIDs[] = $o->id;
            }
        }
        
        // Current ones
        if ($this->awards)
        {
            foreach($this->awards as $award)
            {
                $currentIDs[] = $award->getID();
            }
        }
                
        // Remove
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $DB->delete_records("bcgt_qual_build_awards", array("id" => $removeID));
            }
        }
        
    }
    
    /**
     * Save the default form values for this build
     * @global \GT\type $DB
     * @param type $defaults
     */
    public function saveDefaults($customDefaults, $buildDefaults){
        
        global $DB;
        
        $DB->delete_records_select("bcgt_qual_build_attributes", "buildid = ? AND (attribute LIKE 'default_%' OR attribute LIKE 'build_default_%') ", array($this->id));
                
        $structure = new \GT\QualificationStructure($this->getStructureID());
        if ($structure->isValid())
        {
            
            // Custom fields
            if ($structure->getCustomFormElements())
            {
                
                $unitBuild = \GT\UnitBuild::load($this->getStructureID(), $this->getLevelID());
                
                foreach($structure->getCustomFormElements() as $element)
                {
                                        
                    // Qualification form
                    if ($element->getForm() == "qualification")
                    {
                    
                        if (array_key_exists($element->getName(), $customDefaults))
                        {
                            $this->updateAttribute("default_{$element->getID()}", $customDefaults[$element->getName()]);
                        }
                    
                    }
                    elseif ($element->getForm() == "unit" || $element->getForm() == "criterion")
                    {
                        if (array_key_exists($element->getName(), $customDefaults))
                        {
                            $unitBuild->updateAttribute("default_{$element->getID()}", $customDefaults[$element->getName()]);
                        }
                    }
                    
                }
                
            }
            
            // Build fields
            if ($buildDefaults)
            {
                foreach($buildDefaults as $att => $val)
                {
                    $this->updateAttribute("build_default_{$att}", $val);
                }
            }
            
        }
        
    }
    
    /**
     * Get the default value for a form element
     * @param type $elementID
     * @return type
     */
    public function getDefaultValue($elementID, $form){
        
       // Qualification form, just get the default for this build
        if ($form == 'qualification'){
            return $this->getAttribute("default_{$elementID}");
        }
        
        elseif ($form == 'build'){
            return $this->getAttribute("build_default_{$elementID}");
        }
        
        // Unit form, these defaults are stored against the type and level, as the unit could be on multiple builds
        // So get these in a different way
        elseif ($form == 'unit' || $form == 'criterion'){
            return \GT\UnitBuild::load($this->getStructureID(), $this->getLevelID())->getAttribute("default_{$elementID}");
        }
        
        
    }
    
    /**
     * Get all default values for this build
     * @global \GT\type $DB
     * @return type
     */
    public function getAllDefaultValues(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records_select("bcgt_qual_build_attributes", "buildid = ? AND attribute LIKE 'default_%'", array($this->id));
        if ($records)
        {
            foreach($records as $record)
            {
                $id = str_replace("default_", "", $record->attribute);
                $return[$id] = $record->value;
            }
        }
        
        return $return;
        
    }
    
    /**
     * We have just submitted the new/edit form and we want to take all of that POST data and load it into
     * a Qual Build object so that it can be saved
     */
    public function loadPostData(){
        
        if (isset($_POST['build_id'])){
            $this->setID($_POST['build_id']);
        }
        
        $this->setStructureID($_POST['build_structure_id']);
        $this->setLevelID($_POST['build_level_id']);
        $this->setSubTypeID($_POST['build_subtype_id']);
        $this->setDeleted($_POST['build_deleted']);
                
    }
    
    /**
     * 
     */
    public function loadAwardPostData(){
        
        // CLear loaded awards
        $this->awards = array();
        
        $awardIDs = $_POST['build_award_id'];
        $awardRank = $_POST['build_award_rank'];
        $awardName = $_POST['build_award_name'];
        $awardPointsLower = $_POST['build_award_points_lower'];
        $awardPointsUpper = $_POST['build_award_points_upper'];
        $awardQOELower = $_POST['build_award_qoe_lower'];
        $awardQOEUpper = $_POST['build_award_qoe_upper'];
        $awardUCAS = $_POST['build_award_ucas'];
                                
        if ($awardIDs)
        {
            
            foreach($awardIDs as $key => $id)
            {
                
                if ($awardPointsLower[$key] == '') $awardPointsLower[$key] = null;
                if ($awardPointsUpper[$key] == '') $awardPointsUpper[$key] = null;
                if ($awardQOELower[$key] == '') $awardQOELower[$key] = null;
                if ($awardQOEUpper[$key] == '') $awardQOEUpper[$key] = null;
                if ($awardUCAS[$key] == '') $awardUCAS[$key] = null;
                
                $award = new \GT\QualificationAward($id);
                $award->setBuildID( $this->id );
                $award->setRank( $awardRank[$key] );
                $award->setName( $awardName[$key] );
                $award->setPointsLower( $awardPointsLower[$key] );
                $award->setPointsUpper( $awardPointsUpper[$key] );
                $award->setQOELower( $awardQOELower[$key] );
                $award->setQOEUpper( $awardQOEUpper[$key] );
                $award->setUcas( $awardUCAS[$key] );
                
                if ($award->isValid())
                {
                    $this->awards[$id] = $award;
                }
                else
                {
                    $this->awards[] = $award;
                }
                
            }
            
        }
        
    }
    
    
    
    /**
     * Export a Qualification Build to an XML document, so it can be imported on another Moodle instance
     * @global type $CFG
     * @global \GT\type $DB
     * @return \SimpleXMLElement
     */
    public function exportXML(){
                        
        $QualStructure = new \GT\QualificationStructure($this->structureID);

        $doc = new \SimpleXMLElement('<xml/>');
        
        $xml = $doc->addChild('QualificationBuild');
        $xml->addChild('type', \gt_html($this->getStructureRealName()));
        $xml->addChild('level', $this->getLevelName());
        $xml->addChild('subType', $this->getSubTypeName());
        
        // Qual Awards
        $awardsXML = $xml->addChild('awards');
        $awards = $this->getAwards();
        if ($awards)
        {
            foreach($awards as $award)
            {
                $awardXML = $awardsXML->addChild('award', \gt_html($award->getName()));
                $awardXML->addAttribute('rank', $award->getRank());
                $awardXML->addAttribute('pointsLower', $award->getPointsLower());
                $awardXML->addAttribute('pointsUpper', $award->getPointsUpper());
                $awardXML->addAttribute('ucas', $award->getUcas());
                $awardXML->addAttribute('qoeLower', $award->getQOELower());
                $awardXML->addAttribute('qoeUpper', $award->getQOEUpper());
            }
        }
        
        
        // Criteria grading structures (assessments)
        $allCriteriaGradingStructures = $this->getCriteriaGradingStructures();
        $criteriaGradingXML = $xml->addChild('assessments');
        if ($allCriteriaGradingStructures)
        {
            foreach($allCriteriaGradingStructures as $structure)
            {
                
                $criteriaStructureXML = $criteriaGradingXML->addChild('structure');
                $criteriaStructureXML->addAttribute('name', \gt_html($structure->getName()));
                
                $criteriaStructureAwardsXML = $criteriaStructureXML->addChild('awards');
                $criteriaStructureAwards = $structure->getAwards();
                if ($criteriaStructureAwards)
                {
                    foreach($criteriaStructureAwards as $award)
                    {
                        $criteriaStructureAwardXML = $criteriaStructureAwardsXML->addChild('award');
                        $criteriaStructureAwardXML->addAttribute('name', \gt_html($award->getName()));
                        $criteriaStructureAwardXML->addAttribute('shortName', \gt_html($award->getShortName()));
                        $criteriaStructureAwardXML->addAttribute('specialVal', $award->getSpecialVal());
                        $criteriaStructureAwardXML->addAttribute('points', $award->getPoints());
                        $criteriaStructureAwardXML->addAttribute('pointsLower', $award->getPointsLower());
                        $criteriaStructureAwardXML->addAttribute('pointsUpper', $award->getPointsUpper());
                        $criteriaStructureAwardXML->addAttribute('met', $award->getMet());
                        $criteriaStructureAwardXML->addAttribute('img', \gt_img_to_data($award->getImagePath()));
                    }
                }
            }
        }
        
        
        // Build Unit Points
        $unitStructureAwardPointsXML = $xml->addChild('unitGradingPoints');
        
        // Find Unit grading structures on the QualStructure
        $unitGradingStructures = $QualStructure->getUnitGradingStructures();
        if ($unitGradingStructures)
        {
            foreach($unitGradingStructures as $unitGradingStructure)
            {
                $unitPoints = $unitGradingStructure->getAllUnitPoints();
                if ($unitPoints)
                {
                    foreach($unitPoints as $unitPoint)
                    {

                        $award = new \GT\UnitAward($unitPoint->awardid);
                        if ($award->isValid())
                        {
                            if ($unitPoint->qualbuildid == $this->id)
                            {
                                $pointsXML = $unitStructureAwardPointsXML->addChild('record');
                                $pointsXML->addAttribute('gradingStructure', $unitGradingStructure->getName());
                                $pointsXML->addAttribute('award', $award->getName());
                                $pointsXML->addAttribute('points', $unitPoint->points);
                            }
                        }                        

                    }
                }
            }
        }
        
                
        // Weighting coefficients
        $weightingXML = $xml->addChild('weightings');
        $percentiles = \GT\Setting::getSetting('qual_weighting_percentiles');
        if ($percentiles)
        {
            for ($col = 1; $col <= $percentiles; $col++)
            {
                $val = \GT\Setting::getSetting('build_coefficient_'.$this->getID().'_'.$col);
                $percentileXML = $weightingXML->addChild('percentile', $val);
                $percentileXML->addAttribute('number', $col);
            }
        }
                
        // Defaults
        $defaultsXML = $xml->addChild('defaults');
        
        $ruleSet = $this->getDefaultRuleSet();
        $ruleSetName = ($ruleSet) ? $ruleSet->getName() : '';
        
        // Weighting constant/multiplier
        $defaultsXML->addChild('default', $this->getAttribute('build_default_weighting_constant'))->addAttribute('name', 'build_default_weighting_constant');
        $defaultsXML->addChild('default', $this->getAttribute('build_default_weighting_multiplier'))->addAttribute('name', 'build_default_weighting_multiplier');
        
        $defaultsXML->addChild('default', $this->getDefaultCredits())->addAttribute('name', 'build_default_credits');
        $defaultsXML->addChild('default', $this->getPointsPerCredit())->addAttribute('name', 'build_default_points_per_credit');
        $defaultsXML->addChild('default', $ruleSetName)->addAttribute('name', 'build_default_ruleset_name');
        
        
        $structure = new \GT\QualificationStructure($this->structureID);
        $customFields = $structure->getCustomFormElements();
        if ($customFields)
        {
            foreach($customFields as $customField)
            {
                $defaultsXML->addChild('default', $this->getDefaultValue($customField->getID(), $customField->getForm()))->addAttribute('name', $customField->getName());
            }
        }
        
        \gt_create_data_directory("tmp");
        
        $name = preg_replace("/[^a-z0-9]/i", "", $this->getName());
        $name = str_replace(" ", "_", $name);
        
        $doc->saveXML(\GT\GradeTracker::dataroot() . "/tmp/gt_qual_build_" . $name . ".xml");
        
        return $doc;
        
    }
    
    private function getAssessmentGradingStructureByName($name, $buildOnly = false){
        
        $gradingStructures = $this->getAssessmentGradingStructures($buildOnly);
        if ($gradingStructures)
        {
            foreach($gradingStructures as $gradingStructure)
            {
                if ($gradingStructure->getName() == $name)
                {
                    return $gradingStructure;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get all the grading structures on this build which are marked for use in Assessments
     * @global \GT\type $DB
     * @return boolean
     */
    public function getAssessmentGradingStructures($buildOnly = false){
        
        global $DB;
        
        $return = array();
        
        // First check for any assessment grading structures assigned to this Qualification Build
        $records = $DB->get_records("bcgt_crit_award_structures", array("buildid" => $this->id, "assessments" => 1, "deleted" => 0, "enabled" => 1), "name ASC");
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\CriteriaAwardStructure($record->id);
                $return[] = $obj;
            }
            
            return $return;
           
        }
        
        // Otherwise check for ones on the Qualification Structure itself
        elseif (!$buildOnly)
        {
            
            $QualStructure = new \GT\QualificationStructure( $this->structureID );
            if ($QualStructure && $QualStructure->isValid())
            {
                return $QualStructure->getAssessmentGradingStructures();
            }
            
        }
        
        return false;
        
    }
    
    
   
    
    /**
     * Get a specific Grading Structure from Unit or Criteria Grading Structure array
     * @param type $id
     * @param type $structures
     * @return boolean
     */
    public function getSingleStructure($id, $structures)
    {
        
        foreach($structures as $single)
        {
            if ($single->getID() == $id){
                return $single;
            }
        }
        
        return false;
        
    }
    
    
    
    /**
     * Get an array of the unit grading structures on this qual build
     * @global \GT\type $DB
     * @param type $enabled Do we want just the ones that are enabled?
     * @param bool Do we want to get the criteria grading structreus on the Qual Structure as well as the Build?
     * @return \GT\UnitAwardStructure
     */
    public function getCriteriaGradingStructures($enabled = false, $structureAsWell = false){
    
        global $DB;
        
        $return = array();
              
        // Only enabled ones
        if ($enabled){
            $records = $DB->get_records("bcgt_crit_award_structures", array("buildid" => $this->id, "enabled" => 1, "deleted" => 0), "id");
        } else {
            $records = $DB->get_records("bcgt_crit_award_structures", array("buildid" => $this->id, "deleted" => 0), "id");
        }
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[$record->id] = new \GT\CriteriaAwardStructure($record->id);
            }
        }
        
        
        // Do we also want the QualStructure ones?
        if ($structureAsWell)
        {
            $structure = new \GT\QualificationStructure($this->getStructureID());
            $qualStructureRecords = $structure->getCriteriaGradingStructures($enabled);
            $return = array_merge($return, $qualStructureRecords);
        }
        
        return $return;
        
    }
    
    
    /**
     * Check if a grading structure already exists on this Qual Structure with a given name
     * @global \GT\type $DB
     * @param type $type
     * @param type $name
     * @return type
     */
    public function doesGradingStructureExistByName($name)
    {
        
        global $DB;
        
        $record = $DB->get_record("bcgt_crit_award_structures", array("buildid" => $this->id, "deleted" => 0, "name" => $name), "id", IGNORE_MISSING);
        return ($record !== false);
        
    }
    
    
    
    /**
     * Import a zip file of XML documents
     * Open all the XML documents and run them through the importXML method
     * @global type $USER
     * @param type $file
     * @return type
     */
    public static function importXMLZip($file){
        
        global $USER;
        
        $result = array();
        $result['result'] = true;
        $result['errors'] = array();
        $result['output'] = '';
        
        // Unzip the file        
        $fp = \get_file_packer();
        $tmpFileName = 'import-qual-build-' . time() . '-' . $USER->id . '.zip';
        $extracted = $fp->extract_to_pathname($file, \GT\GradeTracker::dataroot() . '/tmp/' . $tmpFileName);

        if ($extracted)
        {
            foreach($extracted as $extractedFile => $bool)
            {
                
                $result['output'] .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $extractedFile ) . '<br>';
                
                $load = \GT\GradeTracker::dataroot() . '/tmp/' . $tmpFileName . '/' . $extractedFile;
                $import = \GT\QualificationBuild::importXML($load);
                
                // Append to result
                $result['result'] = $result['result'] && $import['result'];
                $result['errors'][] = $import['errors'];
                $result['output'] .= $import['output'];
                
            }
        }
        else
        {
            $result['result'] = false;
            $result['errors'][] = get_string('errors:import:zipfile', 'block_gradetracker');
        }
        
        return $result;
        
    }
    
    /**
     * Import a Qualification Build from an Exported XML document
     * @param type $file
     * @return string
     */
    public static function importXML($file, $create = false){
        
        $updateMethod = (isset($_POST['update_method'])) ? $_POST['update_method'] : false;
                
        $result = array();
        $result['result'] = false;
        $result['errors'] = array();
        $result['output'] = '';
        
        if ($create == false){
            $create = (isset($_POST['create'])) ? true : false;
        }
        
        // Required XML nodes
        $requiredNodes = array('type', 'level', 'subType', 'awards', 'defaults');
        
        // CHeck file exists
        if (!file_exists($file)){
            $result['errors'][] = get_string('errors:import:file', 'block_gradetracker') . ' - (' . $file . ')';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Check mime type of file to make sure it is XML
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fInfo, $file);
        finfo_close($fInfo);
                        
        // Has to be XML file, otherwise error and return
        if ($mime != 'application/xml' && $mime != 'text/plain' && $mime != 'application/zip'){
            $result['errors'][] = sprintf(get_string('errors:import:mimetype', 'block_gradetracker'), 'application/xml or application/zip', $mime);
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // If it's a zip file, we need to unzip it and run on each of the XML files inside
        if ($mime == 'application/zip'){
           return \GT\QualificationBuild::importXMLZip($file) ;
        }
        
        // Open file
        $doc = \simplexml_load_file($file);
        if (!$doc){
            $result['errors'][] = get_string('errors:import:xml:load', 'block_gradetracker') . ' - (' . $file . ')';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Make sure it is wrapped in QualificationStructure tag
        if (!isset($doc->QualificationBuild)){
            $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - QualificationBuild';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Get the nodes inside that tag
        $xml = $doc->QualificationBuild;
        
        // CHeck for required nodes
        $missingNodes = array();
        foreach($requiredNodes as $node)
        {
            if (!property_exists($xml, $node))
            {
                $missingNodes[] = $node;
            }
        }
        
        if ($missingNodes){
            foreach($missingNodes as $node){
                $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - ' . $node;
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                return $result;
            }
        }
        
        $type = (string)$xml->type;
        $level = (string)$xml->level;
        $subType = (string)$xml->subType;
        
        // Check Type (qual structure) exists
        $QualStructure = \GT\QualificationStructure::findByName($type);
        if (!$QualStructure){
            $result['errors'][] = get_string('errors:qualbuild:type', 'block_gradetracker') . ' - ' . $type;
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Check Level exists
        $Level = \GT\Level::findByName($level);
        if (!$Level){
            
            // Try and create it
            if ($create){
                
                $newLevel = new \GT\Level();
                $newLevel->setName($level);
                $newLevel->setShortName($level);
                $newLevel->setOrderNum(0);
                if ($newLevel->hasNoErrors() && $newLevel->save()){
                    $Level = $newLevel;
                    $result['output'] .= get_string('levelsaved', 'block_gradetracker') . ' - ' . $level . '<br>';
                } else {
                    $result['errors'][] = get_string('errors:quallevel', 'block_gradetracker') . ' - ' . $level;
                    $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                    return $result;
                }
                
            } else {
                $result['errors'][] = get_string('errors:qualbuild:level', 'block_gradetracker') . ' - ' . $level;
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                return $result;
            }
            
        }
        
        // Check SubType exists
        $SubType = \GT\SubType::findByName($subType);
        if (!$SubType){
            
            // Try and create
            if ($create){
                
                $newSubType = new \GT\SubType();
                $newSubType->setName($subType);
                $newSubType->setShortName($subType);
                if ($newSubType->hasNoErrors() && $newSubType->save()){    
                    $SubType = $newSubType;
                    $result['output'] .= get_string('subtypesaved', 'block_gradetracker') . ' - ' . $subType . '<br>';
                } else {
                    $result['errors'][] = get_string('errors:qualsubtype', 'block_gradetracker') . ' - ' . $subType;
                    $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                    return $result;
                }
                
            } else {
                
                $result['errors'][] = get_string('errors:qualbuild:subtype', 'block_gradetracker') . ' - ' . $subType;
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                return $result;
                
            }
            
        }
        
        
        // Check that this build doesn't already exist, and we haven't chosen to update existing ones
        $exists = \GT\QualificationBuild::exists($QualStructure->getID(), $Level->getID(), $SubType->getID());
        if ($exists && $updateMethod != 'overwrite' && $updateMethod != 'merge'){
            $result['errors'][] = get_string('errors:qualbuild:duplicate', 'block_gradetracker') . ' - ' . $type . ', ' . $level . ', ' . $subType;
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        if ($exists && ($updateMethod == 'overwrite' || $updateMethod == 'merge') ){
            $newBuild = $exists;
        } else {
            $newBuild = new \GT\QualificationBuild();
            $newBuild->setStructureID($QualStructure->getID());
            $newBuild->setLevelID($Level->getID());
            $newBuild->setSubTypeID($SubType->getID());
        }
                
        
        if ($newBuild->hasNoErrors() && $newBuild->save()){
            
            $result['result'] = true;
            $result['output'] .= get_string('buildsaved', 'block_gradetracker') . ' - ' . $type . ', ' . $level . ', ' . $subType . '<br>';
            
            $awardNamesSubmitted = array();
            
            // Now do the awards and defaults
            if ($xml->awards)
            {
                foreach($xml->awards->children() as $awardNode)
                {
                    
                    $name = (string)$awardNode;
                    $pointsLower = (string)$awardNode['pointsLower'];
                    $pointsUpper = (string)$awardNode['pointsUpper'];
                    $qoeLower = (string)$awardNode['qoeLower'];
                    $qoeUpper = (string)$awardNode['qoeUpper'];
                    $ucas = (string)$awardNode['ucas'];
                    
                    if ($pointsLower == '') $pointsLower = null;
                    if ($pointsUpper == '') $pointsUpper = null;
                    if ($qoeLower == '') $qoeLower = null;
                    if ($qoeUpper == '') $qoeUpper = null;
                    if ($ucas == '') $ucas = null;
                    
                    // If we are updating an existing build, check for the award by its name
                    if ($newBuild->isValid()){
                        $award = $newBuild->getAwardByName($name);
                        if (!$award){
                            // If the build exists, but this award doesn't, create new award on it
                            $award = new \GT\QualificationAward();
                        }
                    } else {
                        $award = new \GT\QualificationAward();
                    }
                    
                    $award->setBuildID($newBuild->getID());
                    $award->setName($name);
                    $award->setRank((string)$awardNode['rank']);
                    $award->setPointsLower($pointsLower);
                    $award->setPointsUpper($pointsUpper);
                    $award->setUcas($ucas);
                    $award->setQOELower($qoeLower);
                    $award->setQOEUpper($qoeUpper);
                    
                    if ($award->hasNoErrors() && $award->save())
                    {
                        $result['output'] .= get_string('awardsaved', 'block_gradetracker') . ' - ' . (string)$awardNode . '<br>';
                    }
                    else
                    {
                        $result['result'] = false;
                        $result['output'] .= get_string('errors:qualaward', 'block_gradetracker') . ' - ' . (string)$awardNode . '<br>';
                    }
                    
                    $awardNamesSubmitted[] = $name;
                    
                }
            }
            
            // If we are updating an existing one, remove any awards we didn't submit this time
            // Only if we chose to Overwrite though, if we are merging then we keep the old stuff as well
            if ($exists && $updateMethod == 'overwrite'){
                if ($newBuild->getAwards())
                {
                    foreach($newBuild->getAwards() as $existingAward)
                    {
                        if (!in_array($existingAward->getName(), $awardNamesSubmitted))
                        {
                            $existingAward->delete();
                            $result['output'] .= get_string('awarddeleted', 'block_gradetracker') . ' - ' . $existingAward->getName() . '<br>';
                        }
                    }
                }
            }

            
            
            // Assessment Grading Structures 
            if ($xml->assessments)
            {
                
                foreach($xml->assessments->children() as $assessmentStructureNode)
                {
                    
                    $gradingStructureAwardNamesSubmitted = array();
                    $structureName = (string)$assessmentStructureNode['name'];

                    // If we are updating, check to see if this already exists
                    if ( $exists && ($updateMethod == 'overwrite' || $updateMethod == 'merge') ){
                        $criteriaGradingStructure = $newBuild->getAssessmentGradingStructureByName($structureName);
                        if (!$criteriaGradingStructure){
                            $criteriaGradingStructure = new \GT\CriteriaAwardStructure();
                            $criteriaGradingStructure->setEnabled(0);
                        }
                    } else {                    
                        $criteriaGradingStructure = new \GT\CriteriaAwardStructure();
                        $criteriaGradingStructure->setEnabled(0);
                    }
                    
                    $criteriaGradingStructure->setQualBuildID($newBuild->getID());
                    $criteriaGradingStructure->setName($structureName);
                    $criteriaGradingStructure->setIsUsedForAssessments(1);

                    // Make sure name doesn't already exist on this build
                    $doesExist = $newBuild->getAssessmentGradingStructureByName($structureName);
                    if( $doesExist && $doesExist->getID() <> $criteriaGradingStructure->getID() ){
                        $result['errors'][] = get_string('errors:import:xml:structureexists', 'block_gradetracker') . ' - ' . $structureName;
                        $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                        return $result;
                    }

                    $awardsXML = $assessmentStructureNode->awards;
                    if ($awardsXML)
                    {
                        foreach($awardsXML->children() as $awardNode)
                        {

                            $name = (string)$awardNode['name'];
                            
                            // If we are updating an existing grading structure, look for existing award
                            if ($criteriaGradingStructure->isValid()){
                                $award = $criteriaGradingStructure->getAwardByName($name);
                                if (!$award){
                                    $award = new \GT\CriteriaAward();
                                }
                            } else {
                                $award = new \GT\CriteriaAward();
                            }
                            
                            $award->setName($name);
                            $award->setShortName( (string)$awardNode['shortName'] );
                            $award->setSpecialVal( (string)$awardNode['specialVal'] );
                            $award->setPoints( (string)$awardNode['points'] );
                            $award->setPointsLower( (string)$awardNode['pointsLower'] );
                            $award->setPointsUpper( (string)$awardNode['pointsUpper'] );
                            $award->setMet( ((int)$awardNode['met'] == 1) ? 1 : 0 );
                            $award->setImageData( (string)$awardNode['img'] );                            
                            $criteriaGradingStructure->addAward($award);
                            
                            $gradingStructureAwardNamesSubmitted[] = $name;

                        }
                    }

                    
                    // Save the assessment grading structure
                    if ($criteriaGradingStructure->hasNoErrors() && $criteriaGradingStructure->save())
                    {
                        $result['output'] .= get_string('critgradingstructuresaved', 'block_gradetracker') . ' - ' . (string)$criteriaGradingStructure->getName() . '<br>';
                    }
                    else
                    {
                        $result['result'] = false;
                        $result['output'] .= get_string('errors:gradestructure', 'block_gradetracker') . ' - ' . (string)$criteriaGradingStructure->getName() . '<br>';
                        $result['output'] .= implode(", ", $criteriaGradingStructure->getErrors()) . '<br>';
                    }
                    
                    // If we are updating an existing one, remove any awards we didn't submit this time
                    if ($exists && $updateMethod == 'overwrite'){
                        if ($criteriaGradingStructure->getAwards())
                        {
                            foreach($criteriaGradingStructure->getAwards() as $existingAward)
                            {
                                if (!in_array($existingAward->getName(), $gradingStructureAwardNamesSubmitted))
                                {
                                    $existingAward->delete();
                                    $result['output'] .= get_string('awarddeleted', 'block_gradetracker') . ' - ' . $existingAward->getName() . '<br>';
                                }
                            }
                        }
                    }
                    
                    
                }
                
            }
            
            
            
            // Unit Grad Structure - Unit Points
            $unitGradingStructuresArray = array();
            $unitPointsArray = array();
            
            $unitPoints = $xml->unitGradingPoints;
            if ($unitPoints)
            {
                foreach($unitPoints->children() as $pointNode)
                {
                    
                    $unitGradingStructure = $QualStructure->getUnitGradingStructureByName((string)$pointNode['gradingStructure']);
                    if ($unitGradingStructure)
                    {
                        
                        if (!array_key_exists($unitGradingStructure->getID(), $unitPointsArray)){
                            $unitPointsArray[$unitGradingStructure->getID()] = array();
                        }
                        
                        $awardName = (string)$pointNode['award'];
                        $pnt = (float)$pointNode['points'];
                        
                        $award = $unitGradingStructure->getAwardByName($awardName);
                        if ($award)
                        {
                            
                            $unitPointsArray[$unitGradingStructure->getID()]['builds'][$newBuild->getID()][$award->getID()] = $pnt;
                            $unitGradingStructuresArray[$unitGradingStructure->getID()] = $unitGradingStructure;
                            
                        }
                        
                        
                    }
                    
                }
            }
          

            if ($unitPointsArray)
            {
                foreach($unitPointsArray as $unitGradingStructureID => $array)
                {
                    $unitGradingStructure = (isset($unitGradingStructuresArray[$unitGradingStructureID])) ? $unitGradingStructuresArray[$unitGradingStructureID] : false;
                    if ($unitGradingStructure)
                    {
                        $unitGradingStructure->saveUnitPoints($array);
                    }
                }
            }
            
            
            
            
            // We don't want to remove Grading Structures not submitted, as some institutions may
            // have made their own grading structures, so leave those, even if we use "overwrite"
            
            
            
            
            // Weighting coefficients
            if ($xml->weightings)
            {
                
                foreach($xml->weightings->children() as $percentile)
                {
                    
                    $number = (int)$percentile['number'];
                    $value = (string)$percentile;
                    $setting = \GT\Setting::updateSetting('build_coefficient_'.$newBuild->getID().'_'.$number, $value);
                    if ($setting)
                    {
                        $result['output'] .= sprintf( get_string('weightingcoefficientsaved', 'block_gradetracker'), $number, $value ) . '<br>';
                    }
                    else
                    {
                        $result['result'] = false;
                        $result['output'] .= get_string('errors:weightingcoefficient', 'block_gradetracker') . ' - ' . $number . '<br>';
                    }
                    
                }
                
            }
            
            
            
            // Defaults
            if ($xml->defaults)
            {
                
                $customDefaults = array();
                $buildDefaults = array();
                
                foreach($xml->defaults->children() as $defaultNode)
                {
                    
                    $name = (string)$defaultNode['name'];
                    $value = (string)$defaultNode;
                                        
                    // For the RuleSet, we have passed a name through in the XML, since IDs can differ, so need to look it up
                    if ($name == 'build_default_ruleset_name'){
                                                
                        $ruleSet = \GT\RuleSet::getByName($QualStructure->getID(), $value);
                        if ($ruleSet){
                            $name = 'ruleset';
                            $value = $ruleSet->getID();
                            $buildDefaults[$name] = $value;
                        }
                        
                    } else {
                    
                        // If name starts with "default_" that is a Qual Build defaults hard-coded into the system
                        if ( strpos($name, 'build_default_') === 0 )
                        {
                            $name = str_replace('build_default_', '', $name);
                            $buildDefaults[$name] = $value;
                        }
                        // Otherwise it is one of the Qual Structure defaults, as defined in the custom form fields, for this build or some other attribute
                        else
                        {
                            $customDefaults[$name] = $value;
                        }
                    
                    }
                    
                }
                
                $newBuild->saveDefaults($customDefaults, $buildDefaults);
                
            }
            
        }
                
        return $result;
        
    }
    
    
    /**
     * Get all the current Qualification Builds
     * @global type $DB
     * @return type
     */
    public static function getAllBuilds($structureID = false){
        
        global $DB;
        
        $params = array();
        $structure = "";
        
        if ($structureID)
        {
            $structure = "AND s.id = ?";
            $params[] = $structureID;
        }
        
        $records = $DB->get_records_sql("SELECT b.id
                                         FROM {bcgt_qual_builds} b
                                         INNER JOIN {bcgt_qual_structures} s ON s.id = b.structureid
                                         INNER JOIN {bcgt_qual_levels} l ON l.id = b.levelid
                                         INNER JOIN {bcgt_qual_subtypes} st ON st.id = b.subtypeid
                                         WHERE b.deleted = 0 {$structure}
                                         ORDER BY s.name ASC, l.ordernum ASC, st.name", $params);
        
        $return = array();
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\QualificationBuild($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Find builds with a given structure id and level id and subtype id
     * @global \GT\type $DB
     * @param type $structureID
     * @param type $levelID
     * @param int $subTypeID
     * @return type
     */
    public static function find($structureID, $levelID, $subTypeID = false){
    
        global $DB;
        
        if ($subTypeID){
            $check = $DB->get_record("bcgt_qual_builds", array("structureid" => $structureID, "levelid" => $levelID, "subtypeid" => $subTypeID, "deleted" => 0));
            return ($check) ? new \GT\QualificationBuild($check->id) : false;
        } else {
            $check = $DB->get_records("bcgt_qual_builds", array("structureid" => $structureID, "levelid" => $levelID, "deleted" => 0));
            return $check;
        }
                
    }
    
    /**
     * Check if any builds exist with a given structure id and level id
     * @global \GT\type $DB
     * @param type $structureID
     * @param type $levelID
     * @param int $subTypeID
     * @return type
     */
    public static function exists($structureID, $levelID, $subTypeID = false){
            
        $check = \GT\QualificationBuild::find($structureID, $levelID, $subTypeID);
        return $check;
        
    }
    
}
