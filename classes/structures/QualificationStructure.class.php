<?php
/**
 * Qualification Structure
 *
 * The class that defines a qualification structure
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

class QualificationStructure
{
    
    private $id = false;
    private $name;
    private $displayName;
    private $icon;
    private $iconFile;
    private $enabled = 1;
    private $deleted = 0;
    
    private $levels = array();
    private $features = array();
    private $formElements = array();
    private $ruleSets = array();
    private $settings = array();
    private $customOrders = array();
    
    private $errors = array();
    
    public $unitGradingStructuresArray = array();
    public $criteriaGradingStructuresArray = array();
    
        
    /**
     * Construct the structure object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false) {
        
        global $DB;
        
        $GTEXE = \GT\Execution::getInstance();
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_structures", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->name = $record->name;
                $this->displayName = $record->displayname;
                $this->icon = $record->icon;
                $this->enabled = $record->enabled;
                $this->deleted = $record->deleted;
                
                if (!isset($GTEXE->QUAL_STRUCTURE_MIN_LOAD) || !$GTEXE->QUAL_STRUCTURE_MIN_LOAD){
                
                    // Load Features
                    $this->loadFeatures();

                    // Load Levels
                    $this->loadLevels();

                    // Load form elements
                    $this->loadCustomFormElements();

                    // Load rules
                    $this->loadRules();

                    // Load custom orders
                    $this->loadCustomOrders();
                
                }
                
            }
            
        }
        
    }
    
    /**
     * Is the structure a valid one from the database?
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Get the id of the structure
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Set the id
     * @param type $id
     */
    public function setID($id){
        $this->id = $id;
    }
    
    /**
     * Get the name of the structure
     * @return type
     */
    public function getName(){
        return \gt_html($this->name);
    }
    
    /**
     * Get the display name of the structure
     * @return type
     */
    public function getDisplayName(){
        return (strlen($this->displayName) > 0) ? \gt_html($this->displayName) : \gt_html($this->name);
    }
    
    /**
     * Get the description of the structure
     * @return type
     */
    public function getDescription(){
        return $this->description;
    }
    
    /**
     * Get the url for the structure's icon
     * @return type
     */
    public function getImageURL(){
        
        global $CFG;
        
        // If we have a tmp file, e.g. if we've got an error so we haven't got as far as saving img properly yet
        if (isset($this->iconTmp)){
            return $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . gt_get_data_path_code( \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp );
        }
        
        return (!is_null($this->icon) && strlen($this->icon) > 0 && file_exists( \GT\GradeTracker::dataroot() . '/img/' . $this->icon )) 
                    ? 
                        $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . gt_get_data_path_code( \GT\GradeTracker::dataroot() . '/img/' . $this->icon ) 
                    : 
                        $CFG->wwwroot . '/blocks/gradetracker/pix/no_image.jpg';
    }
    
    /**
     * Get the icon file name
     * @return type
     */
    public function getIcon(){
        
        if (isset($this->iconTmp)){
            return "tmp//" . $this->iconTmp;
        } else {
            return $this->icon;
        }
        
    }
    
    /**
     * Set the name of the structure
     * @param type $name
     */
    public function setName($name){
        $this->name = trim($name);
    }
    
    /**
     * Set the display name of the structure
     * @param type $dispayName
     */
    public function setDisplayName($dispayName){
        $this->displayName = trim($dispayName);
    }
    
    /**
     * Set the enabled value
     * @param type $val
     */
    public function setEnabled($val){
        $this->enabled = $val;
    }
    
    /**
     * Set the deleted value
     * @param type $val
     */
    public function setDeleted($val){
        $this->deleted = $val;
    }
    
       
    /**
     * Set an array of enabled level ids
     * @param type $levels
     */
    public function setLevels(array $levels){
        $this->levels = $levels;
    }
    
    /**
     * Set the $_FILES element as the icon file
     * @param type $file
     */
    public function setIconFile($file){
        $this->iconFile = $file;
    }
    
    /**
     * Set the icon filename that is already saved
     * @param type $filename
     */
    public function setIcon($filename){
        $this->icon = $filename;
    }
    
    /**
     * Add an enabled level id
     * @param type $level
     */
    public function addLevel($level, $maxSubCrit = 0){
        
        if (!in_array($level, $this->levels)){
            $this->levels[] = array('ID' => $level, 'MAX_SUB_CRIT' => $maxSubCrit);
        }
        
    }
    
    /**
     * Set an array of enabled feature ids
     * @param type $features
     */
    public function setFeatures(array $features){
        $this->features = $features;
    }
    
    /**
     * Add an enabled feature id
     * @param type $feature
     */
    public function addFeature($feature){
        
        if (!in_array($feature, $this->features)){
            $this->features[] = $feature;
        }
        
    }
    
    /**
     * Get an array of custom form element objects
     * @return type
     */
    public function getCustomFormElements($form = false){
        
        // If a form is specified, only get form elements for this form
        if ($form)
        {
            return array_filter($this->formElements, function($obj) use ($form){
                return ($obj->getForm() == $form);
            });
        }
        
        // Otherwise get all of them
        else
        {
            return $this->formElements;
        }
        
    }
    
    /**
     * Set an array of custom form element objects
     * @param type $elements
     */
    public function setCustomFormElements(array $elements){
        $this->formElements = $elements;
    }
    
    /**
     * Add a custom form element to the array
     * @param type $element
     */
    public function addCustomFormElement(\GT\FormElement $element){
        $this->formElements[] = $element;
    }
    
    /**
     * Get the array of rules on this structure
     * @return type
     */
    public function getRuleSets(){
        return $this->ruleSets;
    }
    
    /**
     * Set an array of rules on this structure
     * @param array $rules
     */
    public function setRuleSets(array $rules){
        $this->ruleSets = $rules;
    }
    
    /**
     * Add a rule to the array for this structure
     * @param \GT\Rule $ruleSet
     */
    public function addRuleSet(\GT\RuleSet $ruleSet){
        
        if ($ruleSet->isValid()){
          
            if ($this->ruleSets)
            {
                foreach($this->ruleSets as $key => $rS)
                {
                    if ($rS->getID() == $ruleSet->getID())
                    {
                        $this->ruleSets[$key] = $ruleSet;
                        return;
                    }
                }
            }
            
        } 
        
        $this->ruleSets[] = $ruleSet;
        
    }
    
    /**
     * Get the default rule set for this qualification structure
     * @return boolean
     */
    public function getDefaultRuleSet(){
        
        if ($this->ruleSets){
            foreach($this->ruleSets as $ruleSet){
                if ($ruleSet->isDefault()){
                    return $ruleSet;
                }
            }
        }
        
        return false;
        
    }
    
    public function getRuleSetByName($name){
        
        $ruleSets = $this->getRuleSets();
        if ($ruleSets)
        {
            foreach($ruleSets as $ruleSet)
            {
                if ($ruleSet->getName() == $name)
                {
                    return $ruleSet;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get the defined criteria letters for this qual structure
     * e.g. for BTEC that would be P,M,D or L1,P,M,D if they are using the "Level1/Level2" builds
     * @return type
     */
    public function getCriteriaLetters(){
        
        $setting = $this->getSetting('criteria_letters');
        $explode = explode(",", $setting);
        
        $return = array();
        
        if ($explode)
        {
            foreach($explode as $letter)
            {
                $letter = trim($letter);
                if ($letter != ''){
                    $return[] = trim($letter);
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * This only gets called in the importXML
     * @param \GT\UnitAwardStructure $structure
     */
    private function addUnitGradingStructure(\GT\UnitAwardStructure $structure){
        $this->unitGradingStructuresArray[] = $structure;
    }
    
    
    /**
     * This only gets called in the importXML
     * @param \GT\UnitAwardStructure $structure
     */
    private function addCriteriaGradingStructure(\GT\CriteriaAwardStructure $structure){
        $this->criteriaGradingStructuresArray[] = $structure;
    }
    
    public function getCustomOrder($type = false){
        if (!$this->customOrders){
            $this->loadCustomOrders();
        }
        return ($type) ? (array_key_exists($type, $this->customOrders) ? $this->customOrders[$type] : false) : $this->customOrders;
    }
    
    public function setSetting($setting, $value){
        $this->settings[$setting] = $value;
    }
    
    public function loadCustomOrders(){
        $this->customOrders['criteria'] = $this->getSetting("custom_order_criteria");
        $this->customOrders['units'] = $this->getSetting("custom_order_units");
    }
    
    /**
     * Get any custom format for the display name
     * @return type
     */
    public function getCustomDisplayNameFormat(){
        
        $format = $this->getSetting("custom_displayname");
        $format = trim($format);
        return ($format != '') ? $format : false;
        
    }
    
    /**
     * For a specific level id, get the max sub criteria we have defined for it
     * @param type $levelID
     */
    public function getLevelMaxSubCriteria($levelID){
        
        if ($this->levels)
        {
            foreach($this->levels as $level)
            {
                if ($level['ID'] == $levelID)
                {
                    return (int)$level['MAX_SUB_CRIT'];
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get all builds associated with this structure
     * @global \GT\type $DB
     * @return \GT\QualificationBuild
     */
    public function getAllBuilds(){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("bcgt_qual_builds", array("structureid" => $this->id, "deleted" => 0));
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\QualificationBuild($record->id);
                if ($obj->isValid())
                {
                    $return[] = $obj;
                }
            }
        }
        
        $Sorter = new \GT\Sorter();
        $Sorter->sortQualificationBuilds($return);
        
        return $return;
        
    }
    
    /**
     * Get all the levels of all the builds on this structure
     * @return type
     */
    public function getAllBuildLevels(){
        
        $return = array();
        $builds = $this->getAllBuilds();
        if ($builds)
        {
            foreach($builds as $build)
            {
                $level = $build->getLevelID();
                if (!array_key_exists($level, $return))
                {
                    $return[$level] = $build->getLevel();
                }
            }
        }
        
        // Order by ordernum
        uasort($return, function($a, $b){
            return ($b->getOrderNumber() < $a->getOrderNumber());
        });
        
        return $return;
        
    }
    
    /**
     * Is the structure enabled?
     * @return type]
     */
    public function isEnabled(){
        return ($this->enabled == 1);
    }
    
    /**
     * Is the structure deleted?
     * @return type
     */
    public function isDeleted(){
        return ($this->deleted == 1);
    }
    
    /**
     * Get a setting for the qual structure
     * @global \GT\type $DB
     * @param type $setting
     * @return type
     */
    public function getSetting($setting){
        
        global $DB;
        
        if (!$this->id) return false;
        
        $record = $DB->get_record("bcgt_qual_structure_settings", array("qualstructureid" => $this->id, "setting" => $setting));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Update a qual structure setting
     * @global \GT\type $DB
     * @param type $setting
     * @param type $value
     * @return boolean
     */
    public function updateSetting($setting, $value){
        
        global $DB;
        
        if (!$this->id) return false;
        
        $record = $DB->get_record("bcgt_qual_structure_settings", array("qualstructureid" => $this->id, "setting" => $setting));
        if ($record)
        {
            $record->value = $value;
            return $DB->update_record("bcgt_qual_structure_settings", $record);
        }
        else
        {
            $record = new \stdClass();
            $record->qualstructureid = $this->id;
            $record->setting = $setting;
            $record->value = $value;
            return $DB->insert_record("bcgt_qual_structure_settings", $record);
        }
        
    }
    
//    /**
//     * Get the id of the criteria grading structure we have locked it down to, or none if we haven't
//     * @return type
//     */
//    public function getCriteriaGradingLockedTo(){
//        return $this->getSetting("crit_grading_locked");
//    }
//    
//    /**
//     * Get the id of the unit grading structure we have locked it down to, or none if we haven't
//     * @return type
//     */
//    public function getUnitGradingLockedTo(){
//        return $this->getSetting("unit_grading_locked");
//    }
//    
    /**
     * Check if a qual structure can have asssesments - Needs to have just 1 criteria grading structure, otherwise
     * we don't know which one to use
     * @return type
     */
    public function canHaveAssessments(){
        
        $cnt = count($this->getCriteriaGradingStructures(true));
        return ($cnt == 1);
        
    }
    
    
    
     /**
     * Get all the grading structures on this build which are marked for use in Assessments
     * @global \GT\type $DB
     * @return boolean
     */
    public function getAssessmentGradingStructures(){
        
        global $DB;
        
        $return = array();
        
        // First check for any assessment grading structures assigned to this Qualification Build
        $records = $DB->get_records("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "assessments" => 1, "deleted" => 0, "enabled" => 1), "name ASC");
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\CriteriaAwardStructure($record->id);
                $return[] = $obj;
            }
            
            return $return;
           
        }
        
        
        return false;
        
    }
    
        
    /**
     * Check to see if a specific level is enabled
     * @param mixed $levelID
     * @return bool
     */
    public function isLevelEnabled($levelID){
        
        // Do we need to load the levels?
        if (!$this->levels){
            $this->loadLevels();
        }
        
        // Is it a name?
        if (is_numeric($levelID))
        {
            $levelObj = new \GT\QualificationStructureLevel($levelID);
        }
        elseif (strlen($levelID) > 0)
        {
            $levelObj = new \GT\QualificationStructureLevel(false, $levelID);
        }
        
        // Loop through levels and look for it
        if ($this->levels)
        {
            foreach($this->levels as $level)
            {
                if ($level['ID'] == $levelObj->getID())
                {
                    return true;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Are any of the criteria levels enabled?
     * @return type
     */
    public function isAnyCriteriaLevelEnabled(){
        
        return ($this->isLevelEnabled('Standard Criteria') || $this->isLevelEnabled('Ranged Criteria') || $this->isLevelEnabled('Numeric Criteria') || $this->isLevelEnabled('Detail Criteria'));
        
    }

    /**
     * Check if a particular feature is enabled
     * @param type $featureID
     * @return bool
     */
    public function isFeatureEnabled($featureID){
        return (in_array($featureID, $this->features));
    }
    
    /**
     * Check if a particular feature is enabled by its name
     * @param type $name
     * @return bool
     */
    public function isFeatureEnabledByName($name){
        
        if (!$this->features){
            $this->loadFeatures();
        }
        
        $feature = new \GT\QualificationStructureFeature(false, $name);
        return (in_array($feature->getID(), $this->features));
        
    }
    
    /**
     * Load the enabled features into the features array
     */
    public function loadFeatures(){
            
        $features = $this->getSetting("enabled_features");
        
        if ($features)
        {
            $this->features = explode(",", $features);
        }
                
    }
    
    /**
     * Load the enabled levels into the levels array
     */
    public function loadLevels(){
        
        $levels = $this->getSetting('enabled_levels');
        if ($levels)
        {
            
            $levels = explode(",", $levels);
            foreach($levels as $level)
            {
                
                $maxSubCrit = $this->getSetting("max_sub_crit_level_{$level}");
                $this->addLevel($level, $maxSubCrit);
                
            }
            
        }
        
    }
    
    /**
     * Load custom form elements
     */
    public function loadCustomFormElements(){
                
        $elementIDs = $this->getSetting("custom_form_elements");
        $elementIDs = explode(",", $elementIDs);
        
        if ($elementIDs)
        {
            foreach($elementIDs as $id)
            {
                
                $element = new \GT\FormElement($id);
                if ($element->isValid())
                {
                    $this->addCustomFormElement($element);
                }
                
            }
        }
        
        // Order them by the form
        usort($this->formElements, function($a, $b){
            $order = array('qualification' => 0, 'unit' => 1, 'criterion' => 2);
            return ($order[$a->getForm()] <> $order[$b->getForm()])
                ? ( $order[$a->getForm()] - $order[$b->getForm()] )
                : ( strcasecmp($a->getName(), $b->getName()) );
        });
        
        
    }
       
    /**
     * Load rules 
     */
    public function loadRules(){
        
        global $DB;
        
        // Find the sets of rules attached to this Structure
        $sets = $DB->get_records("bcgt_qual_structure_rule_set", array("qualstructureid" => $this->id), "id");
        if ($sets)
        {
            foreach($sets as $set)
            {
                
                $RuleSet = new \GT\RuleSet($set->id);
                $this->addRuleSet($RuleSet);
                
            }
        }
        
        
        
        
    }
    
    /**
     * Get an array of the unit grading structures on this qual structure
     * @global \GT\type $DB
     * @param type $enabled
     * @return \GT\UnitAwardStructure
     */
    public function getUnitGradingStructures($enabled = false){
    
        global $DB;
        
        $return = array();
        
        // Only enabled ones
        if ($enabled){
            $records = $DB->get_records("bcgt_unit_award_structures", array("qualstructureid" => $this->id, "enabled" => 1, "deleted" => 0), "id");
        } else {
            $records = $DB->get_records("bcgt_unit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0), "id");
        }
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\UnitAwardStructure($record->id);
            }
        }
        
        return $return;
        
    }
    
    public function getUnitGradingStructureByName($name, $array = false){
        
        $structures = ($array) ? $array : $this->getUnitGradingStructures();
        if ($structures){
            foreach($structures as $structure){
                if ($structure->getName() == $name){
                    return $structure;
                }                
            }
        }
        
        return false;
        
    }
    
    
    
    /**
     * Get an array of the unit grading structures on this qual structure
     * @global \GT\type $DB
     * @param type $enabled
     * @return \GT\UnitAwardStructure
     */
    public function getCriteriaGradingStructures($enabled = false){
    
        global $DB;
        
        $return = array();
        
        // Only enabled ones
        if ($enabled){
            $records = $DB->get_records("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "enabled" => 1, "deleted" => 0), "id");
        } else {
            $records = $DB->get_records("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0), "id");
        }
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[$record->id] = new \GT\CriteriaAwardStructure($record->id);
            }
        }
        
        return $return;
        
    }
    
    public function getCriteriaGradingStructureByName($name, $array = false){
        
        $structures = ($array) ? $array : $this->getCriteriaGradingStructures();
        if ($structures){
            foreach($structures as $structure){
                if ($structure->getName() == $name){
                    return $structure;
                }                
            }
        }
        
        return false;
        
    }
   
    /**
     * Enable or Disable the structure, based on whichever it currently is
     * @global \GT\type $DB
     */
    public function toggleEnabled(){
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->enabled = !$this->enabled;
        $DB->update_record("bcgt_qual_structures", $obj);
        
    }
    
    /**
     * Delete the Qualification Structure and any quals, units, etc... beneath it
     * Doesn't actually delete anything, just marks as deleted in database
     * @global \GT\type $DB
     * @return boolean
     */
    public function delete(){
    
        global $DB;
        
        // Mark the structure as deleted
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->deleted = 1;
        $DB->update_record("bcgt_qual_structures", $obj);
        
        // BCTODO - Have delete() methods on Qualification, QualificationBuild and Unit classes, rather than just setting database row to deleted, in case we need to do more things on deletion.
        
        
        
        // Mark all Qualification Builds, Qualifications and Units of this structure as deleted
        $DB->execute("UPDATE {bcgt_qual_builds} SET deleted = 1 WHERE structureid = ?", array($this->id));
        
        // Don't think I can easily do an update with inner join and make it cross-db compatible, so doing loop
        $quals = $this->getQualifications();
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $qual->deleted = 1;
                $DB->update_record("bcgt_qualifications", $qual);
            }
        }
        
        $DB->execute("UPDATE {bcgt_units} SET deleted = 1 WHERE structureid = ?", array($this->id));
        
        return true;

        
        
    }
    
    /**
     * Save the qual structure
     */
    public function save(){
        
        global $DB;
        
        $obj = new \stdClass();
                
        if ($this->id){
            $obj->id = $this->id;
        }
        
        $obj->name = $this->name;
        $obj->displayname = $this->displayName;
        
        // If we uploaded a new icon
        if (isset($this->iconTmp)){
            
            // Move from tmp to qual structure directory
            if (\gt_save_file(\GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp, 'img', $this->iconTmp, false)){
                
                $this->icon = $this->iconTmp;
                $obj->icon = $this->icon;
                \gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/img/' . $this->icon );
                unset($this->iconTmp);
                
            }
            
        }
                
        $obj->enabled = $this->enabled;
        $obj->deleted = $this->deleted;
        
        // Update existing
        if ($this->id){
            $result = $DB->update_record("bcgt_qual_structures", $obj);
        }
        
        // Insert new
        else {
            $this->id = $DB->insert_record("bcgt_qual_structures", $obj);
            $result = $this->id;
        }
        
        // If we failed, stop
        if (!$result) return false;
        
        
        // Clear existing settings
        # These settings are set on other forms, e.g. Report Settings, so don't delete them
        $params = array('reporting_short_criteria_weighted_scores', 'reporting_pass_criteria_method', 'reporting_pass_criteria_method_value'); 
        $placeholders = \gt_create_sql_placeholders($params);
        $params[] = $this->id;
        $DB->delete_records_select("bcgt_qual_structure_settings", "setting NOT IN ({$placeholders}) AND qualstructureid = ?", $params);
                
        // Enabled levels
        $levels = implode(",", array_map(function($e){
            return $e['ID'];
        }, $this->levels));
        
        $this->updateSetting("enabled_levels", $levels);
                
        if ($this->levels){
            foreach($this->levels as $level){
                if ($level['MAX_SUB_CRIT'] > 0){
                    $this->updateSetting("max_sub_crit_level_{$level['ID']}", $level['MAX_SUB_CRIT']);
                }
            }
        }
        
        
        // Enabled features
        $features = implode(",", $this->features);
        $this->updateSetting("enabled_features", $features);
        
        
        // Custom Form Fields
        $elementIDs = array();
        if ($this->getCustomFormElements())
        {
            foreach($this->getCustomFormElements() as $element)
            {
                $element->save();
                $elementIDs[] = $element->getID();
            }
        }
        
        $this->updateSetting("custom_form_elements", implode(",", $elementIDs));
        
        // Rules Sets
        if ($this->ruleSets)
        {
            foreach($this->ruleSets as $ruleSet)
            {
                $ruleSet->setQualStructureID($this->id);
                $ruleSet->save();
            }
        }
                
        // Delete any rules that were there before but now are not
        $this->deleteRemovedRuleSets();
        
        // Now set the default
        
        
        
        // Custom settings
        foreach($this->settings as $setting => $value)
        {
            $this->updateSetting($setting, $value);
        }
        
        // Reload custom orders
        $this->loadCustomOrders();
                
        return $this->id;
        
    }
    
    /**
     * Delete any rules that were on the structure before but not submitted this time
     * @global \GT\type $DB
     */
    private function deleteRemovedRuleSets(){
        
        global $DB;
        
        // Delete any rule sets not submitted this time
        $oldRuleSetIDs = array();
        $currentRuleSetIDs = array();
        
        $oldRuleSets = $DB->get_records("bcgt_qual_structure_rule_set", array("qualstructureid" => $this->id));
        if ($oldRuleSets)
        {
            foreach($oldRuleSets as $oldRuleSet)
            {
                $oldRuleSetIDs[] = $oldRuleSet->id;
            }
        }
        
        // Now loop through rule sets on this object
        if ($this->ruleSets)
        {
            foreach($this->ruleSets as $ruleSet)
            {
                $currentRuleSetIDs[] = $ruleSet->getID();
            }
        }
        
        // Now remove the ones not present on the object       
        $removeIDs = array_diff($oldRuleSetIDs, $currentRuleSetIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $DB->delete_records("bcgt_qual_structure_rule_set", array("id" => $removeID));
            }
        }
        
        
        // Now loop through the Rule Sets and remove any Rules that were not submitted this time
        if ($this->ruleSets)
        {
            foreach($this->ruleSets as $ruleSet)
            {
                $ruleSet->deleteRemovedRules();
            }
        }
        
        
    }
    
    /**
     * Duplicate the Qual Structure and create a copy of it
     * @global \GT\type $DB
     * @return boolean
     */
    public function duplicate(){
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->name = $this->getName() . ' (Copy)';
        $obj->displayname = $obj->name;
        $obj->icon = $this->icon;
        $obj->enabled = $this->enabled;
        
        $id = $DB->insert_record("bcgt_qual_structures", $obj);
        if (!$id) return false;
        
        // Now the levels, features and form elements - the settings basically
        $settings = $DB->get_records("bcgt_qual_structure_settings", array("qualstructureid" => $this->id));
        if ($settings)
        {
            foreach($settings as $setting)
            {
                $obj = $setting;
                $obj->qualstructureid = $id;
                unset($obj->id);
                $DB->insert_record("bcgt_qual_structure_settings", $obj);
            }
        }
        
        // Now the rule sets
        $ruleSets = $DB->get_records("bcgt_qual_structure_rule_set", array("qualstructureid" => $this->id));
        if ($ruleSets)
        {
            foreach($ruleSets as $ruleSet)
            {
                
                $oldSetID = $ruleSet->id;
                $obj = $ruleSet;
                unset($obj->id);
                $obj->qualstructureid = $id;
                
                $newSetID = $DB->insert_record("bcgt_qual_structure_rule_set", $obj);
                
                // Now the rules
                $rules = $DB->get_records("bcgt_qual_structure_rules", array("setid" => $oldSetID));
                if ($rules)
                {
                    foreach($rules as $rule)
                    {
                        
                        $oldRuleID = $rule->id;
                        $obj = $rule;
                        unset($obj->id);
                        $obj->setid = $newSetID;
                        $rID = $DB->insert_record("bcgt_qual_structure_rules", $obj);

                        // Now the rule steps
                        $steps = $DB->get_records("bcgt_qual_structure_rule_stp", array("ruleid" => $oldRuleID));
                        if ($steps)
                        {
                            foreach($steps as $step)
                            {
                                $obj = $step;
                                unset($obj->id);
                                $obj->ruleid = $rID;
                                $DB->insert_record("bcgt_qual_structure_rule_stp", $obj);
                            }
                        }

                    }
                }  
                
            }
        }
        
        
        // Unit and Criteria Grading Structures
        // TODO
        
              
        
        return true;
        
    }
    
    /**
     * Check the structure and all its components to make sure there are no errors in its setup
     */
    public function hasNoErrors(){
     
        global $DB;
        
        // Check name (if display name not defined it will just use same as name)
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:qualstructure:name', 'block_gradetracker');
        }
        
        // Make sure name isn't already in use
        $check = $DB->get_record("bcgt_qual_structures", array("name" => $this->name, "deleted" => 0));
        if ($check && $check->id <> $this->id){
            $this->errors[] = sprintf( get_string('errors:qualstructure:name:duplicate', 'block_gradetracker'), $this->name );
        }
        
        // Check icon is valid image
        if (isset($this->iconFile) && $this->iconFile['size'] > 0)
        {
            $Upload = new \GT\Upload();
            $Upload->setFile($this->iconFile);
            $Upload->setMimeTypes( array('image/png', 'image/jpeg', 'image/bmp', 'image/gif') );
            $Upload->setUploadDir("tmp");
            $result = $Upload->doUpload();
            if ($result['success'] === true){
                $this->iconTmp = $Upload->getFileName();
                gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp );
            } else {
                $this->errors[] = $result['error'];
            }
            
            
        }
                        
        // Check levels
        if ($this->levels)
        {
            foreach($this->levels as $level)
            {
                // Check the level itself is valid
                $levelObj = new \GT\QualificationStructureLevel($level['ID']);
                if ($levelObj->isValid())
                {
                                        
                    if ( ($level['MAX_SUB_CRIT'] < $levelObj->getMinSubLevels() && !is_null($levelObj->getMinSubLevels())) || ($level['MAX_SUB_CRIT'] > $levelObj->getMaxSubLevels() && !is_null($levelObj->getMaxSubLevels()) ) )
                    {
                        $this->errors[] = sprintf( get_string('errors:qualstructure:level:sub', 'block_gradetracker'), $level['MAX_SUB_CRIT'], $levelObj->getMinSubLevels(), $levelObj->getMaxSubLevels() );
                    }
                }
                else
                {
                    $this->errors[] = get_string('errors:qualstructure:level', 'block_gradetracker') . ' - ' . $level['ID'];
                }
            }
        }
        
        // Check features
        if ($this->features)
        {
            foreach($this->features as $featureID)
            {
                if (!\GT\QualificationStructure::isFeatureValid($featureID))
                {
                    $this->errors[] = get_string('errors:qualstructure:feature', 'block_gradetracker') . ' - ' . $featureID;
                }
            }
        }
        
        // Check form elements
        $formElementsArray = array();
        if ($this->formElements)
        {
            foreach($this->formElements as $element)
            {
                if (!$element->hasNoErrors())
                {
                    foreach($element->getErrors() as $error)
                    {
                        $this->errors[] = $error;
                    }
                }
                else
                {
                    if (!isset($formElementsArray[$element->getForm()])){
                        $formElementsArray[$element->getForm()] = array();
                    }
                    
                    // If this name is already set for this form, can't have it again
                    if (in_array( $element->getName(), $formElementsArray[$element->getForm()] )){
                        $this->errors[] = sprintf( get_string('errors:formelement:duplicatename', 'block_gradetracker'), $element->getName(), $element->getForm() );
                    } else {
                        $formElementsArray[$element->getForm()][] = $element->getName();
                    }
                }
            }
        }
        

        
        // Check rules
        $ruleSetsArray = array();
        if ($this->ruleSets)
        {
            foreach($this->ruleSets as $ruleSet)
            {
                
                if (!$ruleSet->hasNoErrors())
                {
                    foreach($ruleSet->getErrors() as $error)
                    {
                        $this->errors[] = $error;
                    }
                }
                else
                {
                    // If this name is already set for this form, can't have it again
                    if (in_array( $ruleSet->getName(), $ruleSetsArray )){
                        $this->errors[] = sprintf( get_string('errors:ruleset:duplicatename', 'block_gradetracker'), $ruleSet->getName());
                    } else {
                        $ruleSetsArray[] = $ruleSet->getName();
                    }
                }
            }
        }
        
        
        return (!$this->errors);
        
    }
    
    /**
     * We have just submitted the new/edit form and we want to take all of that POST data and load it into
     * a Qual Structure object so that it can be saved
     */
    public function loadPostData(){
                        
        // Set the names
        if (isset($_POST['structure_id'])){
            $this->setID($_POST['structure_id']);
        }
        $this->setName($_POST['structure_name']);
        $this->setDisplayName($_POST['structure_display_name']);
        $this->setEnabled( (isset($_POST['structure_enabled']) && $_POST['structure_enabled'] == 1 ) ? 1 : 0);
        $this->setDeleted($_POST['structure_deleted']);
        
        // Set the enabled levels
        if (isset($_POST['levels']))
        {
            foreach ($_POST['levels'] as $key => $id)
            {
                $maxSubCrit = (isset($_POST['max_sub_crit'][$key])) ? (int)$_POST['max_sub_crit'][$key] : 0;
                $this->addLevel($id, $maxSubCrit);
            }
        }
        
        // Set the enabled features
        if (isset($_POST['features'])){
            $this->setFeatures($_POST['features']);
        }
        
        // Set the defined custom form fields
        if (isset($_POST['custom_form_fields_names']))
        {
            foreach ($_POST['custom_form_fields_names'] as $key => $name)
            {
                                
                $params = new \stdClass();
                $params->id = (isset($_POST['custom_form_fields_ids'][$key])) ? $_POST['custom_form_fields_ids'][$key] : false;
                $params->name = $name;
                $params->form = (isset($_POST['custom_form_fields_forms'][$key])) ? $_POST['custom_form_fields_forms'][$key] : false;
                $params->type = (isset($_POST['custom_form_fields_types'][$key])) ? $_POST['custom_form_fields_types'][$key] : false;
                $params->options = (isset($_POST['custom_form_fields_options'][$key]) && !empty($_POST['custom_form_fields_options'][$key])) ? $_POST['custom_form_fields_options'][$key] : false;
                $params->validation = (isset($_POST['custom_form_fields_req'][$key])) ? array("REQUIRED") : array();
                $element = \GT\FormElement::create($params);
                $this->addCustomFormElement($element);
                
            }
        }
                
        
        // Set the Rule Sets
        if (isset($_POST['rule_sets']))
        {
            
            $defaultNum = (isset($_POST['rule_set_default'])) ? $_POST['rule_set_default'] : false;
            
            foreach($_POST['rule_sets'] as $setNum => $set)
            {
                
                $id = (isset($set['id'])) ? $set['id'] : false;
                $name = (isset($set['name'])) ? $set['name'] : false;
                $enabled = (isset($set['enabled'])) ? $set['enabled'] : false;
                
                // I am loading a blank one and calling setID, otherwise if you load the actual object
                // all the rules and steps, etc... already on it get loaded in, and it's harder to
                // delete ones not passed through in the form, as they are on the object regardless
                $RuleSet = new \GT\RuleSet();
                $RuleSet->setID($id);
                $RuleSet->setName($name);
                $RuleSet->setEnabled($enabled);
                
                if ($defaultNum == $setNum){
                    $RuleSet->setIsDefault(1);
                }
                                
                // Loop the rules submitted for this set
                if (isset($set['rules']) && $set['rules'])
                {
                    foreach($set['rules'] as $rule)
                    {
                        
                        $ruleID = (isset($rule['id'])) ? $rule['id'] : false;
                        $ruleName = (isset($rule['name'])) ? $rule['name'] : false;
                        $ruleDesc = (isset($rule['desc'])) ? $rule['desc'] : false;
                        $ruleEvent = (isset($rule['onevent'])) ? $rule['onevent'] : false;
                        $ruleEnabled = (isset($rule['enabled'])) ? $rule['enabled'] : 0;
                        
                        $Rule = new \GT\Rule();
                        $Rule->setID($ruleID);
                        $Rule->setName($ruleName);
                        $Rule->setDescription($ruleDesc);
                        $Rule->setOnEvent($ruleEvent);
                        $Rule->setEnabled($ruleEnabled);
                                                                        
                        // Steps on this rule
                        if (isset($rule['steps']) && $rule['steps'])
                        {
                            foreach($rule['steps'] as $step)
                            {
                                
                                $stepID = (isset($step['id'])) ? $step['id'] : false;
                                $stepNumber = (isset($step['number'])) ? $step['number'] : false;
                                $stepConditions = (isset($step['conditions'])) ? $step['conditions'] : array();
                                $stepActions = (isset($step['actions'])) ? $step['actions'] : array();
                                
                                $RuleStep = new \GT\RuleStep();
                                $RuleStep->setID($stepID);
                                $RuleStep->setStepNumber($stepNumber);
                                
                                if (isset($stepConditions['v1']))
                                {
                                    $count = count($stepConditions['v1']);
                                    for($i = 0; $i < $count; $i++)
                                    {

                                        $conditionArray = array(
                                            'v1' => $stepConditions['v1'][$i],
                                            'cmp' => $stepConditions['cmp'][$i],
                                            'v2' => $stepConditions['v2'][$i]
                                        );

                                        $conditionObject = new RuleStepCondition();
                                        $conditionObject->setConditionArray($conditionArray);
                                        $RuleStep->addCondition($conditionObject);

                                    }
                                }  
                                
                                // Convert the string actions into an array and add to RuleStep
                                if ($stepActions)
                                {
                                    foreach($stepActions as $action)
                                    {
                                        $action = trim($action);
                                        if (strlen($action) > 0)
                                        {
                                            $actionArray = $RuleStep->convertActionStringToArray($action);
                                            if ($actionArray)
                                            {
                                                foreach($actionArray as $action)
                                                {
                                                    if ($action)
                                                    {
                                                        $RuleStep->addAction($action);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                $Rule->addStep($RuleStep);
                                
                            }
                            
                        }
                        
                        
                        $RuleSet->addRule($Rule);
                        
                    }
                    
                }
                
                $this->addRuleSet($RuleSet);
                
            }
            
        }
                    
               
        // Other settings
        if (isset($_POST['settings']))
        {
            foreach($_POST['settings'] as $name => $value)
            {
                $this->setSetting($name, $value);
            }
        }
        
        
                                
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Count the number of qualification builds this structure has
     * @global \GT\type $DB
     * @return type
     */
    public function countQualificationBuilds(){
        
        global $DB;
        return $DB->count_records("bcgt_qual_builds", array("structureid" => $this->id, "deleted" => 0));
        
    }
    
    /**
     * Count the number of qualifications of this structure
     * @global \GT\type $DB
     * @return type
     */
    public function countQualifications(){
        
        global $DB;
        
        $sql = "SELECT COUNT(q.id)
                FROM {bcgt_qualifications} q
                INNER JOIN {bcgt_qual_builds} qb ON qb.id = q.buildid
                WHERE qb.structureid = ? AND q.deleted = 0";
        
        return $DB->count_records_sql($sql, array($this->id));
        
    }
    
    /**
     * Count the number of units of this structure
     * @global \GT\type $DB
     * @return type
     */
    public function countUnits(){
        
        global $DB;
        return $DB->count_records("bcgt_units", array("structureid" => $this->id, "deleted" => 0));
    }
    
    /**
     * Get all the qualifications of this structure
     * @global \GT\type $DB
     * @return type
     */
    public function getQualifications(){
        
        global $DB;
        
        $sql = "SELECT q.*
                FROM {bcgt_qualifications} q
                INNER JOIN {bcgt_qual_builds} qb ON qb.id = q.buildid
                WHERE qb.structureid = ? AND q.deleted = 0";
        
        return $DB->get_records_sql($sql, array($this->id));
        
    }
    
    
    /**
     * Export a Qualification Structure to an XML document, so it can be imported on another Moodle instance
     * @global type $CFG
     * @global \GT\type $DB
     * @return \SimpleXMLElement
     */
    public function exportXML(){
        
        global $CFG, $DB;
                
        $doc = new \SimpleXMLElement('<xml/>');
        
        $xml = $doc->addChild('QualificationStructure');
        $xml->addChild('name', \gt_html($this->name));
        $xml->addChild('displayName', \gt_html($this->displayName));
        
        
        // Levels enabled
        $lvl = $xml->addChild('levels');
        $allLevels = self::getPossibleStructureLevels();
        if ($allLevels)
        {
            foreach($allLevels as $level)
            {
                if ($this->isLevelEnabled($level->getID()))
                {
                    $l = $lvl->addChild('level', $level->getID());
                    $l->addAttribute('maxSubLevels', $this->getLevelMaxSubCriteria( $level->getID() ));
                }
            }
        }
        
        
        // Features enabled
        $feat = $xml->addChild('features');
        $allFeatures = self::getPossibleStructureFeatures();
        if ($allFeatures)
        {
            foreach($allFeatures as $feature)
            {
                if ($this->isFeatureEnabled($feature->id))
                {
                    $feat->addChild('feature', $feature->id);
                }
            }
        }
        
        
        // Custom Form Fields
        $fields = $xml->addChild('formFields');
        $allFields = $this->getCustomFormElements();
        if ($allFields)
        {
            foreach($allFields as $field)
            {
                $f = $fields->addChild('field', \gt_html($field->getName()));
                $f->addAttribute('form', $field->getForm());
                $f->addAttribute('type', $field->getType());
                $f->addAttribute('options', json_encode($field->getOptions()));
                $f->addAttribute('validation', json_encode($field->getValidation()));
            }
        }
        

        // Rules
        $rules = $xml->addChild('rules');
        $ruleSets = $this->getRuleSets();
        if ($ruleSets)
        {
            foreach($ruleSets as $ruleSet)
            {
                
                $rs = $rules->addChild('ruleSet');
                $rs->addAttribute('name', $ruleSet->getName());
                $rs->addAttribute('isDefault', (int)$ruleSet->getIsDefault());
                
                $allRules = $ruleSet->getRules();
                if ($allRules)
                {
                    foreach($allRules as $rule)
                    {
                        $r = $rs->addChild('rule');
                        $r->addAttribute('name', $rule->getName());
                        $r->addAttribute('onEvent', $rule->getOnEvent());

                        $steps = $r->addChild('steps');
                        $ruleSteps = $rule->getSteps();
                        if ($ruleSteps)
                        {
                            foreach($ruleSteps as $step)
                            {

                                $s = $steps->addChild('step');
                                $s->addAttribute('number', $step->getStepNumber());

                                $conditions = $s->addChild('conditions');
                                $stepConditions = $step->getConditions();
                                if ($stepConditions)
                                {
                                    foreach($stepConditions as $key => $condition)
                                    {
                                        $c = $conditions->addChild('condition');
                                        $c->addAttribute('number', $key);
                                        $c->addAttribute('v1', $condition->getConditionPart('v1'));
                                        $c->addAttribute('v2', $condition->getConditionPart('v2'));
                                        $c->addAttribute('cmp', $condition->getConditionPart('cmp'));
                                    }
                                }


                                $actions = $s->addChild('actions');
                                $stepActions = $step->getActions();
                                if ($stepActions)
                                {
                                    foreach($stepActions as $key => $action)
                                    {
                                        $a = $actions->addChild('action', $action->getAction());
                                        $a->addAttribute('number', $key);
                                    }
                                }

                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        
        
        
        // Settings
        $settings = $xml->addChild('settings');
        
        // Display name
        $s = $settings->addChild('setting', \gt_html($this->getSetting('custom_displayname')));
        $s->addAttribute('name', 'custom_displayname');
        
        // Criteria ordering
        $s = $settings->addChild('setting', \gt_html($this->getSetting('custom_order_criteria')));
        $s->addAttribute('name', 'custom_order_criteria');
        
        // Unit ordering
        $s = $settings->addChild('setting', \gt_html($this->getSetting('custom_order_units')));
        $s->addAttribute('name', 'custom_order_units');
        
        // IV column
        $s = $settings->addChild('setting', \gt_html($this->getSetting('iv_column')));
        $s->addAttribute('name', 'iv_column');
        
        // Dashboard grid display
        $s = $settings->addChild('setting', \gt_html($this->getSetting('custom_dashboard_view')));
        $s->addAttribute('name', 'custom_dashboard_view');
        
        // Force single page
        $s = $settings->addChild('setting', \gt_html($this->getSetting('force_single_page')));
        $s->addAttribute('name', 'force_single_page');
        
        // Criteria letters for quick adding in unit form
        $s = $settings->addChild('setting', \gt_html($this->getSetting('criteria_letters')));
        $s->addAttribute('name', 'criteria_letters');
        
        // Reporting criteria name weightings
        $s = $settings->addChild('setting', \gt_html($this->getSetting('reporting_short_criteria_weighted_scores')));
        $s->addAttribute('name', 'reporting_short_criteria_weighted_scores');
        
        // Reporting - Pass Criteria definition method and value
        $s = $settings->addChild('setting', \gt_html($this->getSetting('reporting_pass_criteria_method')));
        $s->addAttribute('name', 'reporting_pass_criteria_method');
        
        $s = $settings->addChild('setting', \gt_html($this->getSetting('reporting_pass_criteria_method_value')));
        $s->addAttribute('name', 'reporting_pass_criteria_method_value');
        
        
        
        // Grading Structures
        // Unit grading structures
        $grading = $xml->addChild('gradingStructures');
        $unitGrading = $grading->addChild('unit');
        $allUnitGradingStructures = $this->getUnitGradingStructures(true);
        if ($allUnitGradingStructures)
        {
            foreach($allUnitGradingStructures as $structure)
            {
                $unitStructureXML = $unitGrading->addChild('structure');
                $unitStructureXML->addAttribute('name', \gt_html($structure->getName()));
                
                $unitStructureAwardsXML = $unitStructureXML->addChild('awards');
                $unitStructureAwards = $structure->getAwards();
                if ($unitStructureAwards)
                {
                    foreach($unitStructureAwards as $award)
                    {
                        $unitStructureAwardXML = $unitStructureAwardsXML->addChild('award');
                        $unitStructureAwardXML->addAttribute('name', \gt_html($award->getName()));
                        $unitStructureAwardXML->addAttribute('shortName', \gt_html($award->getShortName()));
                        $unitStructureAwardXML->addAttribute('points', $award->getPoints());
                        $unitStructureAwardXML->addAttribute('pointsLower', $award->getPointsLower());
                        $unitStructureAwardXML->addAttribute('pointsUpper', $award->getPointsUpper());
                    }
                }
                
                // Unit Award Points - Need to add this into importXML
                $unitStructureAwardPointsXML = $unitStructureXML->addChild('points');
                $unitPoints = $structure->getAllUnitPoints();
                if ($unitPoints)
                {
                    foreach($unitPoints as $unitPoint)
                    {

                        $award = new \GT\UnitAward($unitPoint->awardid);
                        if ($award->isValid())
                        {
                            $level = new \GT\Level($unitPoint->levelid);
                            $pointsXML = $unitStructureAwardPointsXML->addChild('record');
                            if ($level->isValid()){
                                $pointsXML->addAttribute('level', $level->getName());
                                $pointsXML->addAttribute('award', $award->getName());
                                $pointsXML->addAttribute('points', $unitPoint->points);
                            }                            
                        }                        

                    }
                }
                
            }
        }
        
        
        // Criteria grading structures
        $criteriaGrading = $grading->addChild('criteria');
        $allCriteriaGradingStructures = $this->getCriteriaGradingStructures(true);
        if ($allCriteriaGradingStructures)
        {
            foreach($allCriteriaGradingStructures as $structure)
            {
                
                $criteriaStructureXML = $criteriaGrading->addChild('structure');
                $criteriaStructureXML->addAttribute('name', \gt_html($structure->getName()));
                $criteriaStructureXML->addAttribute('assessments', (int)$structure->isUsedInAssessments());
                
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
                
        
        return $doc;
        
    }
    
    /**
     * Export a Criteria Grading Structure to XML
     * @global \GT\type $CFG
     * @global \GT\type $DB
     * @param type $id
     * @return \SimpleXMLElement
     */
    public function exportCriteriaXML($id) {
                
        $buildID = optional_param('build', false, PARAM_INT);
        $QualBuild = new \GT\QualificationBuild($buildID);
        $Object = ($QualBuild && $QualBuild->isValid()) ? $QualBuild : $this;
                        
        $doc = new \SimpleXMLElement('<xml/>');
        
        $xml = $doc->addChild('Criteria');
        
        // Unit grading structures
        $grading = $xml->addChild('gradingStructures');
        $allCriteriaGradingStructures = $Object->getCriteriaGradingStructures(false);
        
        $singleStructure = $Object->getSingleStructure($id, $allCriteriaGradingStructures);
        
        if ($singleStructure)
        {
            $criteriaStructureXML = $grading->addChild('structure');
            $criteriaStructureXML->addAttribute('name', \gt_html($singleStructure->getName()));
            $criteriaStructureXML->addAttribute('assessments', (int)$singleStructure->isUsedInAssessments());

            $criteriaStructureAwardsXML = $criteriaStructureXML->addChild('awards');
            $criteriaStructureAwards = $singleStructure->getAwards();
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
        
        return $doc;
        
    }
    
    /**
     * Export a Unit Grading Structure to XML
     * Not sure why this is on the QualStructure object and not the UnitAwardStructure object
     * @global \GT\type $CFG
     * @global \GT\type $DB
     * @param type $id
     * @return \SimpleXMLElement
     */
    public function exportUnitXML($id) {
                        
        $doc = new \SimpleXMLElement('<xml/>');
        
        $xml = $doc->addChild('Unit');
        
        // Unit grading structures
        $grading = $xml->addChild('gradingStructures');
        $allUnitGradingStructures = $this->getUnitGradingStructures(false);
        
        $singleStructure = $this->getSingleStructure($id, $allUnitGradingStructures);
       
        if ($singleStructure)
        {
            
            $unitStructureXML = $grading->addChild('structure');
            $unitStructureXML->addAttribute('name', \gt_html($singleStructure->getName()));

            $unitStructureAwardsXML = $unitStructureXML->addChild('awards');
            $unitStructureAwards = $singleStructure->getAwards();
            if ($unitStructureAwards)
            {
                foreach($unitStructureAwards as $award)
                {
                    $unitStructureAwardXML = $unitStructureAwardsXML->addChild('award');
                    $unitStructureAwardXML->addAttribute('name', \gt_html($award->getName()));
                    $unitStructureAwardXML->addAttribute('shortName', \gt_html($award->getShortName()));
                    $unitStructureAwardXML->addAttribute('points', $award->getPoints());
                    $unitStructureAwardXML->addAttribute('pointsLower', $award->getPointsLower());
                    $unitStructureAwardXML->addAttribute('pointsUpper', $award->getPointsUpper());
                }
            }
            
            unset($award);
            
            // Unit Award Points
            $unitStructureAwardPointsXML = $unitStructureXML->addChild('points');
            $unitPoints = $singleStructure->getAllUnitPoints();
            if ($unitPoints)
            {
                foreach($unitPoints as $unitPoint)
                {
                                        
                    $award = new \GT\UnitAward($unitPoint->awardid);
                    if ($award->isValid())
                    {
                        $level = new \GT\Level($unitPoint->levelid);
                        $build = new \GT\QualificationBuild($unitPoint->qualbuildid);
                        $pointsXML = $unitStructureAwardPointsXML->addChild('record');
                        if ($build->isValid()){
                            $pointsXML->addAttribute('build', $build->getNameWithSeparator());
                        }
                        elseif ($level->isValid()){
                            $pointsXML->addAttribute('level', $level->getName());
                        }
                        $pointsXML->addAttribute('award', $award->getName());
                        $pointsXML->addAttribute('points', $unitPoint->points);
                    }
                    
                }
            }
            
        }
        
        return $doc;
        
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
     * Check if a grading structure already exists on this Qual Structure with a given name
     * @global \GT\type $DB
     * @param type $type
     * @param type $name
     * @return type
     */
    public function doesGradingStructureExistByName($type, $name)
    {
        
        global $DB;
        
        if ($type == 'unit')
        {
            $record = $DB->get_record("bcgt_unit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0, "name" => $name), "id", IGNORE_MISSING);
            return ($record !== false);
        }
        elseif ($type == 'crit')
        {
            $record = $DB->get_record("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0, "name" => $name), "id", IGNORE_MISSING);
            return ($record !== false);
        }

        
    }
    
    /**
     * Delete any removed unit grading structures and their awards
     * @global \GT\type $DB
     * @return string
     */
    public function deleteRemovedUnitGradingStructures(){
        
        global $DB;
        
        $return = "";
        
        $structures = $DB->get_records("bcgt_unit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0));
        if ($structures)
        {
            foreach($structures as $structure)
            {
                // Does it exist on the object now?
                $exists = $this->getUnitGradingStructureByName($structure->name, $this->unitGradingStructuresArray);
                if (!$exists)
                {
                    $structure->deleted = 1;
                    $DB->update_record("bcgt_unit_award_structures", $structure);
                    $return .= get_string('gradingstructuredeleted', 'block_gradetracker') . ' - '. $structure->name . '<br>';
                }
            }
        }
        
        
        // Now loop through the grading structures on the QualStructure and remove any deleted awards
        $structures = $DB->get_records("bcgt_unit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0));
        if ($structures)
        {
            foreach($structures as $structure)
            {
                // This is the grading structure object, in the array on this qual structure object
                // So this contains what was submitted and processed in the form
                $structureObject = $this->getUnitGradingStructureByName($structure->name, $this->unitGradingStructuresArray);
                if ($structureObject)
                {
                    $structureObject->deleteRemovedAwards();
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * Delete any removed criteria grading structures and their awards
     * @global \GT\type $DB
     * @return string
     */
    public function deleteRemovedCriteriaGradingStructures(){
        
        global $DB;
        
        $return = "";
        
        $structures = $DB->get_records("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0));
        if ($structures)
        {
            foreach($structures as $structure)
            {
                // Does it exist on the object now?
                $exists = $this->getCriteriaGradingStructureByName($structure->name, $this->criteriaGradingStructuresArray);
                if (!$exists)
                {
                    $structure->deleted = 1;
                    $DB->update_record("bcgt_crit_award_structures", $structure);
                    $return .= get_string('gradingstructuredeleted', 'block_gradetracker') . ' - '. $structure->name . '<br>';
                }
            }
        }
        
        // Now loop through the grading structures on the QualStructure and remove any deleted awards
        $structures = $DB->get_records("bcgt_crit_award_structures", array("qualstructureid" => $this->id, "deleted" => 0));
        if ($structures)
        {
            foreach($structures as $structure)
            {
                // This is the grading structure object, in the array on this qual structure object
                // So this contains what was submitted and processed in the form
                $structureObject = $this->getCriteriaGradingStructureByName($structure->name, $this->criteriaGradingStructuresArray);
                if ($structureObject)
                {
                    $structureObject->deleteRemovedAwards();
                }
            }
        }
        
        return $return;
        
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
        $result['files'] = array();
        $result['output'] = '';
        
        
        // Unzip the file        
        $fp = \get_file_packer();
        $tmpFileName = 'import-qual-structure-' . time() . '-' . $USER->id . '.zip';
        $extracted = $fp->extract_to_pathname($file, \GT\GradeTracker::dataroot() . '/tmp/' . $tmpFileName);

        if ($extracted)
        {
            foreach($extracted as $extractedFile => $bool)
            {
                
                $result['output'] .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $extractedFile ) . '<br>';
                
                $load = \GT\GradeTracker::dataroot() . '/tmp/' . $tmpFileName . '/' . $extractedFile;
                $import = \GT\QualificationStructure::importXML($load);
                
                // Append to result
                $result['result'] = $result['result'] && $import['result'];
                $result['errors'][] = $import['errors'];
                $result['files'][] = $extractedFile;
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
     * Import a Qualification Structure from an Exported XML document
     * @param type $file
     * @return string
     */
    public static function importXML($file){
        
        $updateMethod = (isset($_POST['update_method'])) ? $_POST['update_method'] : false;
        
        $result = array();
        $result['result'] = false;
        $result['errors'] = array();
        $result['output'] = '';
        
        // Required XML nodes
        $requiredNodes = array('name', 'levels', 'features', 'formFields', 'rules', 'settings', 'gradingStructures');
        
        // CHeck file exists
        if (!file_exists($file)){
            $result['errors'][] = get_string('errors:import:file', 'block_gradetracker') . ' - ' . $file;
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Check mime type of file to make sure it is XML
        $fInfo = \finfo_open(FILEINFO_MIME_TYPE);
            $mime = \finfo_file($fInfo, $file);
        \finfo_close($fInfo);           
        
        // Has to be XML file, otherwise error and return
        if ($mime != 'application/xml' && $mime != 'text/plain' && $mime != 'application/zip'){
            $result['errors'][] = sprintf(get_string('errors:import:mimetype', 'block_gradetracker'), 'application/xml or application/zip', $mime);
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        // If it's a zip file, we need to unzip it and run on each of the XML files inside
        if ($mime == 'application/zip'){
           return \GT\QualificationStructure::importXMLZip($file) ;
        }
           
        
        // Open file
        $doc = \simplexml_load_file($file);
        if (!$doc){
            $result['errors'][] = get_string('errors:import:xml:load', 'block_gradetracker');
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Make sure it is wrapped in QualificationStructure tag
        if (!isset($doc->QualificationStructure)){
            $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - QualificationStructure';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Get the nodes inside that tag
        $xml = $doc->QualificationStructure;
        
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
        
        $name = (string)$xml->name;
        $newStructure = false;
        
        if ($updateMethod == 'overwrite' || $updateMethod == 'merge'){
            $newStructure = \GT\QualificationStructure::findByName($name);
            if ($newStructure && $updateMethod == 'overwrite'){
                
                // Firstly find the old Custom Form Elements and Rule Sets, so don't lose the references to them
                $oldCustomFields = array();
                $custFields = $newStructure->getCustomFormElements();
                if ($custFields){
                    foreach($custFields as $field){
                        if (!isset($oldCustomFields[$field->getForm()])){
                            $oldCustomFields[$field->getForm()] = array();
                        }
                        $oldCustomFields[$field->getForm()][$field->getID()] = $field->getName();
                    }
                }
                                
                $oldRuleSets = array();
                $ruleSets = $newStructure->getRuleSets();
                if ($ruleSets){
                    foreach($ruleSets as $set){
                        $oldRuleSets[$set->getID()] = $set->getName();
                    }
                }
                
                                
                // Now set everything back to empty
                $newStructure->setLevels( array() );
                $newStructure->setFeatures( array() );
                $newStructure->setCustomFormElements( array() );
                $newStructure->setRuleSets( array() );
                
            }
        } 
                
        
        if (!$newStructure){
            $newStructure = new \GT\QualificationStructure();
            $newStructure->setEnabled(0);
        }
        
        $newStructure->setName($name);
        $newStructure->setDisplayName((string)$xml->displayName);
                
        // Levels
        $levelsXML = $xml->levels;
        if ($levelsXML)
        {
            foreach($levelsXML->children() as $level)
            {
                $newStructure->addLevel( (string)$level, (string)$level['maxSubLevels'] );
            }
        }
        
        // Features
        $featuresXML = $xml->features;
        if ($featuresXML)
        {
            foreach($featuresXML->children() as $feature)
            {
                $newStructure->addFeature( (int)$feature );
            }
        }
       
        
        // Form Fields
        $fieldsXML = $xml->formFields;
        if ($fieldsXML)
        {
            foreach($fieldsXML->children() as $field)
            {
                
                $params = new \stdClass();
                $params->id = false;
                $params->name = (string)$field;
                $params->form = (string)$field['form'];
                $params->type = (string)$field['type'];
                $params->options = json_decode((string)$field['options']);
                $params->validation = json_decode((string)$field['validation']);
                $element = \GT\FormElement::create($params);
                $newStructure->addCustomFormElement($element);
                
            }
        }
        
        
                

        
        // Rules
        $ruleSetsXML = $xml->rules;
        if ($ruleSetsXML)
        {
            foreach($ruleSetsXML->children() as $ruleSetNode)
            {
                
                // RuleSet
                $name = (string)$ruleSetNode['name'];
                                
                if ($newStructure->isValid()){
                    $ruleSet = $newStructure->getRuleSetByName($name);
                    if (!$ruleSet){
                        $ruleSet = new \GT\RuleSet();
                    }
                } else {
                    $ruleSet = new \GT\RuleSet();
                }
                
                
                $ruleSet->setName( $name );
                $ruleSet->setIsDefault( (int)$ruleSetNode['isDefault'] );
                
                // RuleSet Rules
                foreach($ruleSetNode->children() as $ruleNode)
                {
                
                    $name = (string)$ruleNode['name'];                    
                    if ($ruleSet->isValid()){
                        $rule = $ruleSet->getRuleByName($name);
                        if (!$rule){
                            $rule = new \GT\Rule();
                        }
                    } else {
                        $rule = new \GT\Rule();
                    }
                                        
                    $rule->setName( $name );
                    $rule->setOnEvent( (string)$ruleNode['onEvent'] );
                    
                    // Rule Steps
                    $stepsXML = $ruleNode->steps;
                    if ($stepsXML)
                    {
                        
                        foreach ($stepsXML->children() as $stepNode)
                        {

                            $number = (int)$stepNode['number'];                            
                            if ($rule->isValid()){
                                $step = $rule->getStepByNumber($number);
                                if (!$step){
                                    $step = new \GT\RuleStep();
                                }
                            } else {
                                $step = new \GT\RuleStep();
                            }
                                                                                    
                            $step->setStepNumber( $number );
                            $step->setActions( array() );
                            $step->setConditions( array() );

                            // Step Conditions
                            $conditionsXML = $stepNode->conditions;
                            if ($conditionsXML)
                            {
                                foreach($conditionsXML->children() as $conditionNode)
                                {

                                    $condition = new \GT\RuleStepCondition();
                                    $conditionArray = array(
                                        'v1' => (string)$conditionNode['v1'],
                                        'v2' => (string)$conditionNode['v2'],
                                        'cmp' => (string)$conditionNode['cmp']
                                    );
                                    $condition->setConditionArray($conditionArray);
                                    $step->addCondition($condition);

                                }
                            }

                            // Step Actions
                            $actionsXML = $stepNode->actions;
                            if ($actionsXML)
                            {
                                foreach($actionsXML->children() as $actionNode)
                                {

                                    $action = new \GT\RuleStepAction();
                                    $action->setAction( (string)$actionNode );
                                    $step->addAction($action);

                                }
                            }

                            $rule->addStep($step);

                        }
                        
                    }
                    
                    $ruleSet->addRule($rule);
                
                }
                
                $newStructure->addRuleSet($ruleSet);
                
            }
            
        }
                
        // Settings
        $settingsXML = $xml->settings;
        if ($settingsXML)
        {
            foreach($settingsXML->children() as $settingNode)
            {
                $newStructure->setSetting( (string)$settingNode['name'], (string)$settingNode );
            }
        }
        
        
        // Grading Structures
        $gradingStructureXML = $xml->gradingStructures;
        
        // Unit
        $unitXML = $gradingStructureXML->unit;
        if ($unitXML)
        {
            foreach($unitXML->children() as $structureNode)
            {

                $unitPointsArray = array();
                $name = (string)$structureNode['name'];
                
                $unitGradingStructure = false;
                
                if ($newStructure->isValid() && ($updateMethod == 'overwrite' || $updateMethod == 'merge')){
                    $unitGradingStructure = $newStructure->getUnitGradingStructureByName($name);
                } 
                
                if (!$unitGradingStructure){
                    $unitGradingStructure = new \GT\UnitAwardStructure();
                    $unitGradingStructure->setName($name);
                    $unitGradingStructure->setEnabled(1);
                }
                                
                
                $awardsXML = $structureNode->awards;
                if ($awardsXML)
                {
                    foreach($awardsXML->children() as $awardNode)
                    {

                        $name = (string)$awardNode['name'];
                        $award = false;
                        
                        if ($unitGradingStructure->isValid()){
                            $award = $unitGradingStructure->getAwardByName($name);
                        }
                        
                        if (!$award){                        
                            $award = new \GT\UnitAward();
                            $award->setGradingStructureID( $unitGradingStructure->getID() ); // This will be false
                        }
                        
                        $award->setName( $name );
                        $award->setShortName( (string)$awardNode['shortName'] );
                        $award->setPoints( (string)$awardNode['points'] );
                        $award->setPointsLower( (string)$awardNode['pointsLower'] );
                        $award->setPointsUpper( (string)$awardNode['pointsUpper'] );
                        $unitGradingStructure->addAward($award);

                    }
                }
                
                // Points
                $unitPoints = $structureNode->points;
                if ($unitPoints)
                {
                    foreach($unitPoints->children() as $pointNode)
                    {
                        if (isset($pointNode['build'])){
                            $type = 'builds';
                            $val = (string)$pointNode['build'];
                        } elseif (isset($pointNode['level'])){
                            $type = 'levels';
                            $val = (string)$pointNode['level'];
                        }
                        $awrd = (string)$pointNode['award'];
                        $pnt = (float)$pointNode['points'];
                        $unitPointsArray[$type][$val][$awrd] = $pnt;
                    }
                }
                
                $unitGradingStructure->unitPointsArray = $unitPointsArray;
                
                $newStructure->addUnitGradingStructure($unitGradingStructure);

            }
        }

        // Criteria
        $criteriaXML = $gradingStructureXML->criteria;
        if ($criteriaXML)
        {
            foreach($criteriaXML->children() as $structureNode)
            {

                $name = (string)$structureNode['name'];
                $criteriaGradingStructure = false;
                
                if ($newStructure->isValid()){
                    $criteriaGradingStructure = $newStructure->getCriteriaGradingStructureByName($name);
                }
                
                if (!$criteriaGradingStructure){                
                    $criteriaGradingStructure = new \GT\CriteriaAwardStructure();
                    $criteriaGradingStructure->setName($name);
                    $criteriaGradingStructure->setEnabled(1);
                }
                
                // Clear the awards off the object
                $criteriaGradingStructure->setAwards( array() );
                $criteriaGradingStructure->setIsUsedForAssessments((string)$structureNode['assessments']);

                $awardsXML = $structureNode->awards;
                if ($awardsXML)
                {
                    
                    foreach($awardsXML->children() as $awardNode)
                    {

                        $name = (string)$awardNode['name'];
                        $award = false;
                        if ($criteriaGradingStructure->isValid()){
                            $award = $criteriaGradingStructure->getAwardByNameDB($name);
                        }
                        
                        if (!$award){
                            $award = new \GT\CriteriaAward();
                        }
                        
                        $award->setName( $name );
                        $award->setShortName( (string)$awardNode['shortName'] );
                        $award->setSpecialVal( (string)$awardNode['specialVal'] );
                        $award->setPoints( (string)$awardNode['points'] );
                        $award->setPointsLower( (string)$awardNode['pointsLower'] );
                        $award->setPointsUpper( (string)$awardNode['pointsUpper'] );
                        $award->setMet( ((int)$awardNode['met'] == 1) ? 1 : 0 );
                        $award->setImageData( (string)$awardNode['img'] );                            
                        $criteriaGradingStructure->addAward($award);

                    }
                    
                }

                $newStructure->addCriteriaGradingStructure($criteriaGradingStructure);

            }          

        }
        
              
        // Try and save the Qual Structure itself
        if ($newStructure->hasNoErrors())
        {
            
            if ($newStructure->save())
            {
                
                // Structure has saved, now to save the grading structures
                $result['output'] .= get_string('structuresaved', 'block_gradetracker') . ' - '. $newStructure->getName() . '<br>';
                
                // Unit grading structures
                if ($newStructure->unitGradingStructuresArray)
                {
                    foreach($newStructure->unitGradingStructuresArray as $structure)
                    {
                        $structure->setQualStructureID( $newStructure->getID() );
                        if ($structure->hasNoErrors() && $structure->save())
                        {
                            
                            // Now do the Unit Award Points, since the awards should now have been saved
                            if ($structure->unitPointsArray)
                            {

                                foreach($structure->unitPointsArray as $type => $array)
                                {

                                    foreach($array as $val => $pointsArray)
                                    {

                                        // Builds
                                        if ($type == 'builds')
                                        {

                                            $split = explode("//", $val);
                                            $levelName = isset($split[1]) ? $split[1] : false;
                                            $subTypeName = isset($split[2]) ? $split[2] : false;

                                            $level = \GT\Level::findByName($levelName);
                                            $subType = \GT\SubType::findByName($subTypeName);
                                            
                                            if ($level && $level->isValid() && $subType && $subType->isValid())
                                            {

                                                $build = \GT\QualificationBuild::find($newStructure->getID(), $level->getID(), $subType->getID());
                                                if ($build && $build->isValid())
                                                {

                                                    // Get the level id
                                                    $structure->unitPointsArray['builds'][$build->getID()] = $array[$val];
                                                    unset($structure->unitPointsArray['builds'][$val]);

                                                    // Get the award ids
                                                    if ($pointsArray)
                                                    {
                                                        foreach($pointsArray as $awardName => $points)
                                                        {
                                                            $award = $structure->getAwardByName($awardName);
                                                            if ($award && $award->isValid())
                                                            {
                                                                $structure->unitPointsArray['builds'][$build->getID()][$award->getID()] = $points;
                                                                unset($structure->unitPointsArray['builds'][$build->getID()][$awardName]);
                                                            }
                                                        }
                                                    }

                                                }
                                            
                                            }

                                        }

                                        // Levels
                                        elseif ($type == 'levels')
                                        {
                                            $levelName = $val;
                                            $level = \GT\Level::findByName($levelName);
                                            if ($level && $level->isValid())
                                            {

                                                // Get the level id
                                                $structure->unitPointsArray['levels'][$level->getID()] = $array[$levelName];
                                                unset($structure->unitPointsArray['levels'][$levelName]);

                                                // Get the award ids
                                                if ($pointsArray)
                                                {
                                                    foreach($pointsArray as $awardName => $points)
                                                    {
                                                        $award = $structure->getAwardByName($awardName);
                                                        if ($award && $award->isValid())
                                                        {
                                                            $structure->unitPointsArray['levels'][$level->getID()][$award->getID()] = $points;
                                                            unset($structure->unitPointsArray['levels'][$level->getID()][$awardName]);
                                                        }
                                                    }
                                                }

                                            }
                                        }
                                    }

                                }

                                // Save the unit award points
                                $structure->saveUnitPoints( $structure->unitPointsArray );

                            }
                            
                            $result['output'] .= get_string('unitgradingstructuresaved', 'block_gradetracker') . ' - '. $structure->getName() . '<br>';
                        }
                        else
                        {
                            $result['result'] = false;
                            $result['errors'][] = $structure->getErrors();
                        }
                    }
                }
                
                // Criteria grading structures
                if ($newStructure->criteriaGradingStructuresArray)
                {
                    foreach($newStructure->criteriaGradingStructuresArray as $structure)
                    {
                        $structure->setQualStructureID( $newStructure->getID() );
                        if ($structure->hasNoErrors() && $structure->save(false))
                        {
                            $result['output'] .= get_string('critgradingstructuresaved', 'block_gradetracker') . ' - '. $structure->getName() . '<br>';
                        }
                        else
                        {
                            $result['result'] = false;
                            $result['errors'][] = $structure->getErrors();
                        }
                    }
                }
                
                
                // Remove any Grading structures we didn't submit this time (if doing "overwrite")
                if ($updateMethod == 'overwrite'){
                    
                    // Unit grading structures
                    $result['output'] .= $newStructure->deleteRemovedUnitGradingStructures();
                    
                    // Criteria grading structures
                    $result['output'] .= $newStructure->deleteRemovedCriteriaGradingStructures();
                    
                    // Now sort out the old form fields and rule sets
                    
                    // Loop through new form fields and see if its name exists in the old array
                    if ($newStructure->getCustomFormElements()){
                        foreach($newStructure->getCustomFormElements() as $newElement){
                            if (in_array($newElement->getName(), $oldCustomFields[$newElement->getForm()])){
                                $oldID = array_search($newElement->getName(), $oldCustomFields[$newElement->getForm()]);
                                \GT\FormElement::updateAttributes($newElement->getForm(), $oldID, $newElement->getID());
                            }
                        }
                    }
                    
                    // Loop through the new rule sets and see if name matches old array
                    if ($newStructure->getRuleSets()){
                        foreach($newStructure->getRuleSets() as $newRuleSet){
                            if (in_array($newRuleSet->getName(), $oldRuleSets)){
                                $oldID = array_search($newRuleSet->getName(), $oldRuleSets);
                                \GT\RuleSet::updateAttributes($oldID, $newRuleSet->getID());
                            }
                        }
                    }
                    
                    
                }
                
                // If no errors after that, was successful
                if (!$result['errors']){
                    $result['output'] .= get_string('importcomplete', 'block_gradetracker') . '<br>';
                    $result['result'] = true;
                } else {
                    $result['output'] .= get_string('errors:import:qualstructure:gradingstructures', 'block_gradetracker') . '<br>';
                }
                
            }
            else
            {
                $result['errors'] = get_string('errors:save', 'block_gradetracker');
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            }
            
        }
        else
        {
            $result['errors'] = $newStructure->getErrors();
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        
        return $result;
        
    }
    
    /**
     * Import a Criteria Grading Structure from XML
     * @global \GT\type $DB
     * @param type $file
     * @param type $structureID
     * @return string
     */
    public static function importCriteriaXML($file, $structureID, $buildID = false){
        
        global $DB;
                
        $result = array();
        $result['result'] = false;
        $result['errors'] = array();
        $result['output'] = '';
        
        // Required XML nodes
        $requiredNodes = array('gradingStructures');
        
        // CHeck file exists
        if (!file_exists($file)){
            $result['errors'][] = get_string('errors:import:file', 'block_gradetracker') . ' - ' . $file;
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
           return \GT\QualificationStructure::importXMLZip($file) ;
        }
           
        
        // Open file
        $doc = \simplexml_load_file($file);
        if (!$doc){
            $result['errors'][] = get_string('errors:import:xml:load', 'block_gradetracker');
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Make sure it is wrapped in Criteria tag
        if (!isset($doc->Criteria)){
            $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - Criteria';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Get the nodes inside that tag
        $xml = $doc->Criteria;
        
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
        
        // If we are doing it by Build instead, get the build first, then get the structure from that    
        $Build = false;
        
        if ($buildID){
            $Build = new \GT\QualificationBuild($buildID);
            if ($Build->isValid()){
                $Structure = new \GT\QualificationStructure($Build->getStructureID());
                $isAssEnabled = true;
            } else {
                $result['errors'][] = get_string('errors:gradestructures:qualbuild', 'block_gradetracker');
                return $result;
            }
        } else {        
            $Structure = new \GT\QualificationStructure($structureID);
            $isAssEnabled = false;
        }
        
        // Grading Structures
        $gradingStructureXML = $xml->gradingStructures;
        
        // Criteria
        $criteriaXML = $gradingStructureXML->structure;
        
        if ($criteriaXML)
        {
            
            $criteriaGradingStructure = new \GT\CriteriaAwardStructure();
            $criteriaGradingStructure->setName((string)$criteriaXML['name']);
            $criteriaGradingStructure->setIsUsedForAssessments($isAssEnabled);

            $criteriaName = (string)$criteriaXML['name'];
            $criteriaNameRecords = ($Build) ? $Build->doesGradingStructureExistByName($criteriaName) : $Structure->doesGradingStructureExistByName('crit', $criteriaName);

            if($criteriaNameRecords){
                $result['errors'][] = get_string('errors:import:xml:structureexists', 'block_gradetracker') . ' - ' . $criteriaName;
                $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                return $result;
            }

            $awardsXML = $criteriaXML->awards;
            if ($awardsXML)
            {
                foreach($awardsXML->children() as $awardNode)
                {

                    $award = new \GT\CriteriaAward();
                    $award->setName( (string)$awardNode['name'] );
                    $award->setShortName( (string)$awardNode['shortName'] );
                    $award->setSpecialVal( (string)$awardNode['specialVal'] );
                    $award->setPoints( (string)$awardNode['points'] );
                    $award->setPointsLower( (string)$awardNode['pointsLower'] );
                    $award->setPointsUpper( (string)$awardNode['pointsUpper'] );
                    $award->setMet( ((int)$awardNode['met'] == 1) ? 1 : 0 );
                    $award->setImageData( (string)$awardNode['img'] );                            
                    $criteriaGradingStructure->addAward($award);

                }
            }

            $Structure->addCriteriaGradingStructure($criteriaGradingStructure);
            
        }
        
        // Save Criteria grading structures
        if ($Structure->criteriaGradingStructuresArray)
        {
            foreach($Structure->criteriaGradingStructuresArray as $gradingStructure)
            {
                
                // Set either Build ID or Structure ID
                if ($Build){
                    $criteriaGradingStructure->setQualBuildID($Build->getID());
                } else {
                    $gradingStructure->setQualStructureID( $Structure->getID() );
                }
                
                if ($gradingStructure->hasNoErrors() && $gradingStructure->save())
                {
                    $result['output'] .= get_string('critgradingstructuresaved', 'block_gradetracker') . ' - '. $gradingStructure->getName() . '<br>';
                }
                else
                {
                    $result['result'] = false;
                    $result['errors'][] = $gradingStructure->getErrors();
                }
            }
        }

        // If no errors after that, was successful
        if (!$result['errors']){
            $result['output'] .= get_string('importcomplete', 'block_gradetracker') . '<br>';
            $result['result'] = true;
        } else {
            $result['output'] .= get_string('errors:import:qualstructure:gradingstructures', 'block_gradetracker') . '<br>';
        }
        
        return $result;
        
    }
    
    /**
     * Import a Unit Grading Structure from XML
     * @global \GT\type $DB
     * @param type $file
     * @param type $structureID
     * @return string
     */
    public static function importUnitXML($file, $structureID){
                
        $result = array();
        $result['result'] = false;
        $result['errors'] = array();
        $result['output'] = '';
        
        // Required XML nodes
        $requiredNodes = array('gradingStructures');
        
        // CHeck file exists
        if (!file_exists($file)){
            $result['errors'][] = get_string('errors:import:file', 'block_gradetracker') . ' - ' . $file;
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
           return \GT\QualificationStructure::importXMLZip($file) ;
        }
           
        
        // Open file
        $doc = \simplexml_load_file($file);
        if (!$doc){
            $result['errors'][] = get_string('errors:import:xml:load', 'block_gradetracker');
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Make sure it is wrapped in Unit tag
        if (!isset($doc->Unit)){
            $result['errors'][] = get_string('errors:import:xml:missingnodes', 'block_gradetracker') . ' - Unit';
            $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
            return $result;
        }
        
        // Get the nodes inside that tag
        $xml = $doc->Unit;
        
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
        
        $Structure = new \GT\QualificationStructure($structureID);
        
        // Grading Structures
        $gradingStructureXML = $xml->gradingStructures;
       
        if ($gradingStructureXML)
        {
            foreach($gradingStructureXML->children() as $structureNode)
            {

                $unitPointsArray = array();
                $unitGradingStructure = new \GT\UnitAwardStructure();
                $unitGradingStructure->setName((string)$structureNode['name']);
                $unitName = (string)$structureNode['name'];
                
                // Awards
                $awardsXML = $structureNode->awards;
                $unitNameRecords = $Structure->doesGradingStructureExistByName('unit', $unitName);
                
                if($unitNameRecords){
                    $result['errors'][] = get_string('errors:import:xml:structureexists', 'block_gradetracker') . ' - ' . $unitName;
                    $result['output'] .= get_string('errorsfound', 'block_gradetracker') . '<br>';
                    return $result;
                }
                
                if ($awardsXML)
                {
                    foreach($awardsXML->children() as $awardNode)
                    {

                        $award = new \GT\UnitAward();
                        $award->setGradingStructureID( $unitGradingStructure->getID() ); // This will be false
                        $award->setName( (string)$awardNode['name'] );
                        $award->setShortName( (string)$awardNode['shortName'] );
                        $award->setPoints( (string)$awardNode['points'] );
                        $award->setPointsLower( (string)$awardNode['pointsLower'] );
                        $award->setPointsUpper( (string)$awardNode['pointsUpper'] );
                        $unitGradingStructure->addAward($award);

                    }
                }
                
                // Points
                $unitPoints = $structureNode->points;
                if ($unitPoints)
                {
                    foreach($unitPoints->children() as $pointNode)
                    {
                        if (isset($pointNode['build'])){
                            $type = 'builds';
                            $val = (string)$pointNode['build'];
                        } elseif (isset($pointNode['level'])){
                            $type = 'levels';
                            $val = (string)$pointNode['level'];
                        }
                        $awrd = (string)$pointNode['award'];
                        $pnt = (float)$pointNode['points'];
                        $unitPointsArray[$type][$val][$awrd] = $pnt;
                    }
                }
                
                $unitGradingStructure->unitPointsArray = $unitPointsArray;

                $Structure->addUnitGradingStructure($unitGradingStructure);

            }
        }
        

        
        // Save Unit grading structures
        if ($Structure->unitGradingStructuresArray)
        {
            foreach($Structure->unitGradingStructuresArray as $gradingStructure)
            {
                                
                $gradingStructure->setQualStructureID( $Structure->getID() );
                if ($gradingStructure->hasNoErrors() && $gradingStructure->save())
                {
                    
                    // Now do the Unit Award Points, since the awards should now have been saved
                    if ($gradingStructure->unitPointsArray)
                    {
                        
                        foreach($gradingStructure->unitPointsArray as $type => $array)
                        {
                            
                            foreach($array as $val => $pointsArray)
                            {
                                
                                // Builds
                                if ($type == 'builds')
                                {
                                 
                                    $split = explode("//", $val);
                                    $levelName = isset($split[1]) ? $split[1] : false;
                                    $subTypeName = isset($split[2]) ? $split[2] : false;
                                    
                                    $level = \GT\Level::findByName($levelName);
                                    $subType = \GT\SubType::findByName($subTypeName);
                                    
                                    $build = \GT\QualificationBuild::find($Structure->getID(), $level->getID(), $subType->getID());
                                    if ($build && $build->isValid())
                                    {
                                        
                                        // Get the level id
                                        $gradingStructure->unitPointsArray['builds'][$build->getID()] = $array[$val];
                                        unset($gradingStructure->unitPointsArray['builds'][$val]);

                                        // Get the award ids
                                        if ($pointsArray)
                                        {
                                            foreach($pointsArray as $awardName => $points)
                                            {
                                                $award = $gradingStructure->getAwardByName($awardName);
                                                if ($award && $award->isValid())
                                                {
                                                    $gradingStructure->unitPointsArray['builds'][$build->getID()][$award->getID()] = $points;
                                                    unset($gradingStructure->unitPointsArray['builds'][$build->getID()][$awardName]);
                                                }
                                            }
                                        }
                                        
                                    }
                                    
                                }
                                
                                // Levels
                                elseif ($type == 'levels')
                                {
                                    $levelName = $val;
                                    $level = \GT\Level::findByName($levelName);
                                    if ($level && $level->isValid())
                                    {

                                        // Get the level id
                                        $gradingStructure->unitPointsArray['levels'][$level->getID()] = $array[$levelName];
                                        unset($gradingStructure->unitPointsArray['levels'][$levelName]);

                                        // Get the award ids
                                        if ($pointsArray)
                                        {
                                            foreach($pointsArray as $awardName => $points)
                                            {
                                                $award = $gradingStructure->getAwardByName($awardName);
                                                if ($award && $award->isValid())
                                                {
                                                    $gradingStructure->unitPointsArray['levels'][$level->getID()][$award->getID()] = $points;
                                                    unset($gradingStructure->unitPointsArray['levels'][$level->getID()][$awardName]);
                                                }
                                            }
                                        }

                                    }
                                }
                            }
                            
                        }
                                                             
                        // Save the unit award points
                        $gradingStructure->saveUnitPoints( $gradingStructure->unitPointsArray );
                        
                    }

                    
                    $result['output'] .= get_string('unitgradingstructuresaved', 'block_gradetracker') . ' - '. $gradingStructure->getName() . '<br>';
                }
                else
                {
                    $result['result'] = false;
                    $result['errors'][] = $gradingStructure->getErrors();
                }
            }
        }
                        
        
        // If no errors after that, was successful
        if (!$result['errors']){
            $result['output'] .= get_string('importcomplete', 'block_gradetracker') . '<br>';
            $result['result'] = true;
        } else {
            $result['output'] .= get_string('errors:import:qualstructure:gradingstructures', 'block_gradetracker') . '<br>';
        }
        
        return $result;
        
    }
    
    
    /**
     * Is an ID a valid level a qual structure can have?
     * @global \GT\type $DB
     * @param type $id
     */
    public static function isLevelValid($id){
       
        global $DB;
        return ($DB->get_record("bcgt_qual_structure_levels", array("id" => $id)) !== false);
        
    }
    
    /**
     * Is an ID a valid feature a qual structure can have?
     * @global \GT\type $DB
     * @param type $id
     */
    public static function isFeatureValid($id){
       
        global $DB;
        return ($DB->get_record("bcgt_qual_structure_features", array("id" => $id)) !== false);
        
    }
    
    
    /**
     * Get all the possible levels a qualification structure can have
     * @global \GT\type $DB
     * @return type
     */
    public static function getPossibleStructureLevels(){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records("bcgt_qual_structure_levels", null, "id ASC", "id");
        
        if ($records)
        {
            foreach($records as $record)
            {
                $level = new \GT\QualificationStructureLevel($record->id);
                if ($level->isValid())
                {
                    $return[] = $level;
                }
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get all the possible features a qualification structure can have
     * @global \GT\type $DB
     * @return type
     */
    public static function getPossibleStructureFeatures(){
        
        global $DB;
        return $DB->get_records("bcgt_qual_structure_features", null, "name ASC");
        
    }
    
    /**
     * Get all the created qual structures
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllStructures(){
    
        global $DB;
        
        $return = array();
        $records = $DB->get_records("bcgt_qual_structures", array("deleted" => 0), "name ASC", "id");
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\QualificationStructure($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Find a Qual Structure by its name
     * @global \GT\type $DB
     * @param type $name
     * @return type
     */
    public static function findByName($name){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qual_structures", array("name" => $name, "deleted" => 0));
        return ($record) ? new \GT\QualificationStructure($record->id) : false;
        
    }
    
    /**
     * Get an array of qual structures based on a setting they have
     * @global \GT\type $DB
     * @param type $setting
     * @param type $value
     * @return \GT\QualificationStructure
     */
    public static function getStructuresBySetting($setting, $value){
        
        global $DB;
        
        $return = array();
        $params = array($setting);
        
        // If we pass through TRUE, it means just find any value that isn't null
        if ($value === true){
            $valueSQL = 'AND ss.value IS NOT NULL';
        } else {
            $valueSQL = 'AND ss.value = ?';
            $params[] = $value;
        }
        
        $records = $DB->get_records_sql("SELECT DISTINCT ss.qualstructureid    
                                        FROM {bcgt_qual_structure_settings} ss
                                        INNER JOIN {bcgt_qual_structures} s ON s.id = ss.qualstructureid
                                        WHERE ss.setting = ? {$valueSQL}
                                        ORDER BY s.name", $params);
        if ($records)
        {
            foreach($records as $record)
            {
                $obj = new \GT\QualificationStructure($record->qualstructureid);
                if ($obj->isValid() && !$obj->isDeleted())
                {
                    $return[$record->qualstructureid] = $obj;   
                }                
            }
        }
        
        return $return;
        
    }
    
    /**
     * Return an array of all the supported features, to populate the features table on block install
     * @return type
     */
    public static function _features(){
        return array('targetgrades', 'predictedminmaxgrades', 'targetgradesauto', 'aspirationalgrades', 'predictedgrades', 'datasheets', 'percentagecomp', 'weightedtargetgrades', 'cetagrades');
    }
    
    /**
     * Return an array of all the supported structure levels, to populate the level table on block install
     * @return type
     */
    public static function _levels(){
        return array(
                'Units' => array(null, null), 
                'Standard Criteria' => array(0, 2), 
                'Detail Criteria' => array(0, 2), 
                'Ranged Criteria' => array(1, 2), 
                'Numeric Criteria' => array(1, 2)
            );
    }
    
    
}
