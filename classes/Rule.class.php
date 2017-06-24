<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GT;

define('GT_RULE_OP', '.');
define('GT_RULE_SEP', '@@');

define('GT_RULE_REGEX_SPLIT', "/(?<!\\\\)\\".GT_RULE_OP."/");
define('GT_RULE_REGEX_QUOTE_WRAPPED', "/^(?:\"|\')(.*?)(?:\"|\')$/");

// Using a global variable to add all the responses to to send through to JSON, to do things
if (!isset($GLOBALS['rule_response'])){
    $GLOBALS['rule_response'] = array();
}




class RuleSet {
    
    private $id = false;
    private $qualStructureID;
    private $name;
    private $enabled = 1;
    private $isDefault = 0;

    private $rules = array();
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qual_structure_rule_set", array("id" => $id));
        if ($record)
        {
            $this->id = $record->id;
            $this->qualStructureID = $record->qualstructureid;
            $this->name = $record->name;
            $this->enabled = $record->enabled;
            $this->isDefault = $record->isdefault;
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isEnabled(){
        return ($this->enabled == 1);
    }
    
    public function isDefault(){
        return ($this->isDefault == 1);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getQualStructureID(){
        return $this->qualStructureID;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function getIsDefault(){
        return $this->isDefault;
    }
    
    public function setQualStructureID($id){
        $this->qualStructureID = $id;
    }
    
    public function setID($id){
        $this->id = $id;
    }
    
    public function setName($name){
        $this->name = $name;
    }
    
    public function setEnabled($val){
        $this->enabled = $val;
    }
    
    public function setIsDefault($val){
        $this->isDefault = $val;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    
    /**
     * Get the Rules on this Rule Set and load them from DB if they haven't been loaded yet
     * @return type
     */
    public function getRules(){
        
        if (!$this->rules){
            $this->loadRules();
        }
        
        return $this->rules;
        
    }
    
    public function setRules(array $rules){
        $this->rules = $rules;
    }
    
    /**
     * Add a Rule to the array 
     * @param \GT\Rule $rule
     */
    public function addRule(\GT\Rule $rule){
        
        if ($rule->isValid()){
          
            if ($this->rules)
            {
                foreach($this->rules as $key => $r)
                {
                    if ($r->getID() == $rule->getID())
                    {
                        $this->rules[$key] = $rule;
                        return;
                    }
                }
            }
            
        } 
        
        $this->rules[] = $rule;
    }
    
    /**
     * Load the rules on this Rule Set from the DB
     * @global type $DB
     */
    private function loadRules(){
        
        global $DB;
        
        $this->rules = array();
        
        $rules = $DB->get_records("bcgt_qual_structure_rules", array("setid" => $this->id), "id");
        if ($rules)
        {
            foreach($rules as $rule)
            {
                $ruleObj = new \GT\Rule($rule->id);
                $this->addRule($ruleObj);
            }
        }
        
    }
    
    public function getRuleByName($name){
        
        $rules = $this->getRules();
        if ($rules)
        {
            foreach($rules as $rule)
            {
                if ($rule->getName() == $name)
                {
                    return $rule;
                }
            }
        }
        
        return false;
        
    }
    
    public function hasNoErrors(){
        
        if ($this->rules)
        {
            foreach($this->rules as $rule)
            {
                if (!$rule->hasNoErrors())
                {
                    foreach($rule->getErrors() as $error)
                    {
                        $this->errors[] = $error;
                    }
                }
            }
        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Save the rule set
     * @global \GT\type $DB
     * @return boolean
     */
    public function save(){
        
        global $DB;
        
        if (is_null($this->qualStructureID)){
            return false;
        }
        
        $obj = new \stdClass();
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->name = $this->name;
        $obj->qualstructureid = $this->qualStructureID;
        $obj->enabled = $this->enabled;
        $obj->isdefault = $this->isDefault;
        
        if ($this->isValid()){
            $DB->update_record("bcgt_qual_structure_rule_set", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_qual_structure_rule_set", $obj);
        }
        
        // Now save the rules on it
        if ($this->rules)
        {
            foreach($this->rules as $rule)
            {
                $rule->setSetID($this->id);
                $rule->save();
            }
        }
        
    }
    
    /**
     * Delete any rules attached to this set in the DB that have not been submitted through the form
     */
    public function deleteRemovedRules(){
        
        global $DB;
        
        $oldIDs = array();
        $currentIDs = array();
        
        $oldRules = $DB->get_records("bcgt_qual_structure_rules", array("setid" => $this->id));
        if ($oldRules)
        {
            foreach($oldRules as $oldRule)
            {
                $oldIDs[] = $oldRule->id;
            }
        }
        
        // Now loop through rule sets on this object
        if ($this->rules)
        {
            foreach($this->rules as $rule)
            {
                $currentIDs[] = $rule->getID();
            }
        }
        
        // Now remove the ones not present on the object       
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $DB->delete_records("bcgt_qual_structure_rules", array("id" => $removeID));
            }
        }
        
        // Loop through the Rules and removed any Rule Steps not submitted this time
        if ($this->rules)
        {
            foreach($this->rules as $rule)
            {
                $rule->deleteRemovedSteps();
            }
        }
        
        
    }
    
    
    /**
     * Notify the Rule class of an event that has taken place
     * This is run on a blank Rule object and then the actual rules are loaded from the qualstructure
     * @param type $event
     * @param type $params
     * @return boolean
     */
    public function notify($event, $params)
    {

        \gt_debug("Received notification of event...");
        
        // Needs to have a qualification id
        if (!isset($params['qID'])){
            \gt_debug("Error: Missing qID");
            return false;
        }

        // Get the QualStructure of the qualification to see if there are any rules for this event
        $qualification = new \GT\Qualification($params['qID']);
        if (!$qualification->isValid()){
            \gt_debug("Error: Invalid Qualification");
            return false;
        }

        // Find out which RuleSet the qualification is using, based on its build and structure
        $qualBuild = $qualification->getBuild();
        if (!$qualBuild->isValid()){
            \gt_debug("Error: Invalid Qual Build");
            return false;
        }
        
        $ruleSet = $qualBuild->getDefaultRuleSet();
        if (!$ruleSet || !$ruleSet->isValid() || !$ruleSet->isEnabled()){
            \gt_debug("Error: Invalid Rule Set");
            return false;
        }
        
        \gt_debug("Loaded RuleSet: {$ruleSet->getName()}");
        \gt_debug("Looping through Rules...");
        
        $rules = $ruleSet->getRules();
        if ($rules)
        {

            foreach($rules as $rule)
            {

                if ($rule->isEnabled() && $rule->getOnEvent() == $event)
                {
                    $rule->apply( $params );
                }

            }

        }

    }
    
    /**
     * Get a Qual Structure's RuleSet by its name
     * @global \GT\type $DB
     * @param type $qualStructureID
     * @param type $name
     * @return type
     */
    public static function getByName($qualStructureID, $name){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qual_structure_rule_set", array("qualstructureid" => $qualStructureID, "name" => $name), "id");
        return ($record) ? new \GT\RuleSet($record->id) : false;
        
    }
    
    /**
     * Update references to old ruleset id to new id
     * @global \GT\type $DB
     * @param type $oldID
     * @param type $newID
     * @return type
     */
    public static function updateAttributes($oldID, $newID){
        
        global $DB;
        return $DB->execute("UPDATE {bcgt_qual_build_attributes} SET value = ? WHERE attribute = ? AND value = ?", array($newID, "build_default_ruleset", $oldID));
        
    }
    
    
}





/**
 * Description of Rule
 *
 * @author cwarwicker
 */
class Rule {
  
   private $id = false;
   private $setID;
   private $name;
   private $description;
   private $onEvent;
   private $enabled = 1;
   private $steps = array();
    
   private $errors = array();
   private $_operators = array();
   
   private static $events = array(GT_EVENT_CRIT_UPDATE, GT_EVENT_UNIT_UPDATE);
   
   public function __construct($id = false) {
       
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_structure_rules", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->setID = $record->setid;
                $this->name = $record->name;
                $this->description = $record->description;
                $this->onEvent = $record->onevent;
                $this->enabled = $record->enabled;
                
                // Steps
                $this->loadSteps();
                
            }
            
        }
        
        
       
       // Set the operators
       $this->setOperator('equals', 'is_equal');
       $this->setOperator('not equals', 'is_not_equal');
       $this->setOperator('is met', 'is_met');
       $this->setOperator('is not met', 'is_not_met');      
       
       
   }
   
   
    /**
     * Check if a valid Rule from database
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isEnabled(){
        return ($this->enabled == 1);
    }
    
    /**
     * Get the id of the rule
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    public function setID($id){
        $this->id= $id;
    }
    
    /**
     * Get the id of the qual structure this rule is on
     * @return type
     */
    public function getSetID(){
        return $this->setID;
    }
    
    /**
     * Set the id of the qual structure this rule is on
     * @param type $id
     */
    public function setSetID($id){
        $this->setID = $id;
    }
        
    /**
     * Get the name of the rule
     * @return type
     */
    public function getName(){
        return $this->name;
    }
    
    /**
     * Set the name of the rule
     * @param type $name
     */
    public function setName($name){
        $this->name = trim($name);
    }
    
    public function getDescription(){
        return $this->description;
    }
    
    public function setDescription($desc){
        $this->description = trim($desc);
    }
    
    /**
     * Get which event this rule is called on
     * @return type
     */
    public function getOnEvent(){
        return $this->onEvent;
    }
    
    /**
     * Set which event this rule is called on
     * @param type $event
     */
    public function setOnEvent($event){
        $this->onEvent = trim($event);
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function setEnabled($val){
        $this->enabled = $val;
    }
    
      
    private function getOperator($operator){
       return (array_key_exists($operator, $this->_operators)) ? $this->_operators[$operator] : false;
    }
   
    private function setOperator($operator, $callback){
       $this->_operators[$operator] = $callback;
    }
   
    public function getAllOperators(){
       return $this->_operators;
    }
    
    /**
     * Get the array of steps on this rule
     * @return type
     */
    public function getSteps(){
                
        // Order them by the step number
        usort($this->steps, function($a, $b){
            return ($a->getStepNumber() > $b->getStepNumber());
        });
        
        return $this->steps;
    }
    
    /**
     * Set an array of steps for this rule
     * @param array $steps
     */
    public function setSteps(array $steps){
        $this->steps = $steps;
    }
    
    /**
     * Add a step to the array of steps
     * @param type $step
     */
    public function addStep(\GT\RuleStep $step){
        if ($step->getID()){
            if ($this->steps)
            {
                foreach($this->steps as $key => $stp)
                {
                    if ($stp->getID() == $step->getID())
                    {
                        $this->steps[$key] = $step;
                        return;
                    }
                }
            }
            
            $this->steps[$step->getID()] = $step;
            
        } else {
            $this->steps[] = $step;
        }
    }
    
    /**
     * Load the steps into the rule
     * @global type $DB
     */
    public function loadSteps(){
        
        global $DB;
                
        $steps = $DB->get_records("bcgt_qual_structure_rule_stp", array("ruleid" => $this->id));
        if ($steps)
        {
            foreach($steps as $step)
            {
                $stepObj = new \GT\RuleStep($step->id);
                $this->addStep($stepObj);
            }
        }
        
    }
    
    public function getStepByNumber($number){
        
        $steps = $this->getSteps();
        if ($steps)
        {
            foreach($steps as $step)
            {
                if ($step->getStepNumber() == $number)
                {
                    return $step;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    public function hasNoErrors(){
        
        // TODO
        return true;
        
    }
    
    public function save(){
        
        global $DB;
                
        $obj = new \stdClass();
        if ($this->id){
            $obj->id = $this->id;
        }
        
        $obj->setid = $this->getSetID();
        $obj->name = $this->getName();
        $obj->description = $this->getDescription();
        $obj->onevent = $this->getOnEvent();
        $obj->enabled = $this->getEnabled();
        
        // Update existing
        if ($this->id)
        {
            $DB->update_record("bcgt_qual_structure_rules", $obj);
        }
        // Insert new
        else
        {
            $this->id = $DB->insert_record("bcgt_qual_structure_rules", $obj);
        }
        
        
        // Steps
        if ($this->steps)
        {
            foreach($this->steps as $step)
            {
                
                $step->setRuleID($this->id);
                $step->save();
                
            }
        }
        
        
    }
    
    
    public function deleteRemovedSteps(){
    
        global $DB;

        $oldIDs = array();
        $currentIDs = array();
        
        $oldSteps = $DB->get_records("bcgt_qual_structure_rule_stp", array("ruleid" => $this->id));
        if ($oldSteps)
        {
            foreach($oldSteps as $oldStep)
            {
                $oldIDs[] = $oldStep->id;
            }
        }
        
        
        // Now loop through rule sets on this object
        if ($this->steps)
        {
            foreach($this->steps as $step)
            {
                $currentIDs[] = $step->getID();
            }
        }
        
       
        
        // Now remove the ones not present on the object       
        $removeIDs = array_diff($oldIDs, $currentIDs);
        if ($removeIDs)
        {
            foreach($removeIDs as $removeID)
            {
                $DB->delete_records("bcgt_qual_structure_rule_stp", array("id" => $removeID));
            }
        }
        
                
        
    }
    
    public function apply($params){
        
        \gt_debug("Applying rule {$this->name}...");
        
        $steps = $this->getSteps();
        if ($steps)
        {
            foreach($steps as $step)
            {
                \gt_debug("Checking conditions of step {$step->getStepNumber()}...");
                if ($step->areConditionsMet( $params ))
                {
                    \gt_debug("Conditions are met... Running actions...");
                    $step->runActions( $params );
                    break;
                }
            }
        }
        
        \gt_debug("Completed rule {$this->name}.");
        
    }
    
    /**
     * Get template content, replacing placeholders with actual values
     * @global type $CFG
     * @param type $file
     * @param type $array
     * @return type
     */
    public function getTemplateContent($file, $array){
        
        global $CFG, $OUTPUT;
        
        $rule = $this;
        
        $func = function() use ($CFG, $OUTPUT, $file, $rule, $array){
            ob_start();
            require $CFG->dirroot . '/blocks/gradetracker/tpl/' . $file;
            return ob_get_clean();
        };
        
        $content = $func();
                
        $replace = array();
        $with = array();
        
        foreach($array as $key => $val){
            if (is_scalar($val)){
                $replace[] = '/\['.$key.'\]/';
                $with[] = $val;
            }
        }
        
        $content = preg_replace($replace, $with, $content);
                
        // Remove template
        $content = str_replace('gt_rule_step_template', 'gt_rule_step_' . $array['RS'] . '_' . $array['R'] . '_' . $array['S'], $content);
        $content = str_replace('gt_rule_step_condition_template', '', $content);
        $content = str_replace('gt_rule_step_action_template', '', $content);
        
        // Hidden class will be done in javascript, as other classes have similar names, so is easier
                
        return $content;
        
    }
    
    
   
    /**
     * Get the array of possible events
     * @return type
     */
    public static function getEvents(){
        return self::$events;
    }
    
    /**
     * Get all comparisons
     * @return type
     */
    public static function getComparisons(){
        
        $rule = new \GT\Rule();
        return $rule->getAllOperators();
        
    }
    
    
    /**
     * Call a method on an object, stripped from the condition string
     * E.g. Object: Qual, Method: getUnits()
     * @param type $object
     * @param type $parts
     * @return boolean
     */
    public static function call($object, $parts){
                       
        \gt_debug("Called static Rule::call() method");
        
        if (!$parts){
            \gt_debug("Error: no parts parameter");
            return false;
        }
                            
        $method = $parts[0];
        unset($parts[0]);

        preg_match("/([a-z]+)\((.*?)\)/i", $method, $matches);
        $method = $matches[1];

        // Strip out any quotes put around the arguments in the string
        $methodParams = array_map( function($e){
            $e = str_replace("'", "", $e);
            $e = str_replace('"', "", $e);
            return trim($e);
        }, explode(",", $matches[2]) );

        // Fix array keys
        $parts = array_values($parts);
        
        \gt_debug("Parsed parts into method: {$method} and parameters: " . print_r($methodParams, true));
        
        // If the object is an array, loop through and call the method on all elements
        if (is_array($object)){

            \gt_debug("Object is an array, so loop through and calling method {$method} on each element");
            $result = array();

            foreach($object as $obj){

                if (method_exists($obj, $method)){
                    $result[] = $obj->$method($methodParams);
                }

            }

            // Flatten the array into one array instead of multi-dimensional
            $result = \gt_flatten_array($result);
            #\gt_debug("Result - " . print_r($result, true));
           
            // If there is anything left, call again
            if ($parts){
                \gt_debug("More parts remain, so calling again on the result we just got");
                return self::call($result, $parts);
            } else {
                return $result;
            }


        } else {

            // Call the method on the object
            if (!method_exists($object, $method)){
                \gt_debug("Error: Method {$method} does not exist on object " . get_class($object));
                return false;
            }

            $result = $object->$method($methodParams);

            // If there are more parts left to be run, call them on what we just returned
            if ($parts){
                \gt_debug("More parts remain, so calling again on the result we just got");
                return self::call($result, $parts);
            } else {
                return $result;
            }

        }
            
    }
    
    
    
    /**
     * Get the RUleObject_ object of a given type
     * @param type $obj
     * @return \GT\RuleObject_Criterion|\GT\RuleObject_Unit|boolean|\GT\RuleObject_Qual
     */
    public static function getObject($obj){
        
        switch($obj)
        {
            case 'qual':
                return new \GT\RuleObject_Qual();
            break;
            case 'unit':
                return new \GT\RuleObject_Unit();
            break;
            case 'criterion':
                return new \GT\RuleObject_Criterion();
            break;
            default:
                return false;
            break;
        }
        
    }
   
    
}


class RuleStep {
    
    private $id = false;
    private $ruleID;
    private $stepNum;
    private $conditions = array();
    private $actions = array();
    
    private $errors = array();
    
    /**
     * Construct the step object
     */
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_structure_rule_stp", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->ruleID = $record->ruleid;
                $this->stepNum = $record->step;
                
                // Conditions
                $this->conditions = $this->convertConditionStringToArray($record->conditions);
                
                // Actions
                $this->actions = $this->convertActionStringToArray($record->action, false);
                
            }
            
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Get the id of the step
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Set the id of the step
     * @param type $id
     */
    public function setID($id){
        $this->id = $id;
    }
    
    /**
     * Get the rule id
     * @return type
     */
    public function getRuleID(){
        return $this->ruleID;
    }
    
    /**
     * Set the rule id
     * @param type $id
     */
    public function setRuleID($id){
        $this->ruleID = $id;
    }
    
    /**
     * Get the order number of the step
     * @return type
     */
    public function getStepNumber(){
        return $this->stepNum;
    }
    
    /**
     * Get the array of conditions for this step
     * @return type
     */
    public function getConditions(){
        return $this->conditions;
    }
    
    /**
     * Get the array of actions for this step
     * @return type
     */
    public function getActions(){
        return $this->actions;
    }
    
    /**
     * Set the step order number
     * @param type $number
     */
    public function setStepNumber($number){
        $this->stepNum = (int)$number;
    }
    
    /**
     * Set the array of conditions for this step
     * @param array $conditions
     */
    public function setConditions(array $conditions){
        $this->conditions = $conditions;
    }
    
    /**
     * Add a condition to the array for this step
     * @param \GT\RuleStepCondition $condition
     */
    public function addCondition(\GT\RuleStepCondition $condition){
        $this->conditions[] = $condition;
    }
    
    /**
     * Set the array of actions for this step
     * @param array $actions
     */
    public function setActions(array $actions){
        $this->actions = $actions;
    }
    
    /**
     * Add an action to the array for this step
     * @param \GT\RuleStepAction $action
     */
    public function addAction(\GT\RuleStepAction $action){
        $this->actions[] = $action;
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    
    /**
     * Take the string from the DB field and convert it into an array of conditions
     */
    public function convertConditionStringToArray($str){
        
        $return = array();
        
        $lines = explode("\n", $str);
        if ($lines)
        {
            foreach($lines as $line)
            {
                $return[] = \GT\RuleStepCondition::createFromString($line);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Convert the database value into action array
     * @param type $str
     * @return type
     */
    public function convertActionStringToArray($str, $convert = true){
        
        $return = array();
        
        $lines = explode("\n", $str);
        if ($lines)
        {
            foreach($lines as $line)
            {
                $return[] = \GT\RuleStepAction::createFromString($line, $convert);
            }
        }
        
        return $return;
        
    }
    
    
    /**
     * Convert all the conditions in this step to one string to store in the database
     * @return type
     */
    public function convertConditionsToString(){
        
        $return = array();
        
        if ($this->conditions)
        {
            foreach($this->conditions as $condition)
            {
                $return[] = $condition->convertConditionToString();
            }
        }
        
        return implode("\n", $return);
        
    }
    
    /**
     * Convert the actions to a string to store in DB
     * @return type
     */
    public function convertActionsToString(){
        
        $return = array();
        
        if ($this->actions)
        {
            foreach($this->actions as $action)
            {
                $return[] = $action->convertActionToString();
            }
        }
        
        return implode("\n", $return);
        
    }
    
    /**
     * Save the step
     * @global type $DB
     * @return type
     */
    public function save(){
    
        global $DB;
        
        $obj = new \stdClass();
                
        if ($this->id){
            $obj->id = $this->id;
        }
        
        $obj->ruleid = $this->ruleID;
        $obj->step = $this->stepNum;
        
        // Conditions
        $obj->conditions = $this->convertConditionsToString();
        
        // Actions
        $obj->action = $this->convertActionsToString();
                
        if ($this->id){
            return $DB->update_record("bcgt_qual_structure_rule_stp", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_qual_structure_rule_stp", $obj);
            return $this->id;
        }
        
    }
    
    /**
     * Check if all the conditions on this step are met
     * @return type
     */
    public function areConditionsMet($params){
        
        $result = true;
        
        $conditions = $this->getConditions();
        if ($conditions)
        {
            foreach($conditions as $condition)
            {
                $result = $result && $condition->isMet($params);
            }
        }
                        
        return $result;
        
    }
    
    /**
     * Run the actions of this step
     * @param type $params
     */
    public function runActions($params){
        
        $result = true;
        
        $actions = $this->getActions();        
        if ($actions)
        {
            foreach($actions as $action)
            {
                $action->run( $params );
            }
        }
        
        return $result;
        
    }
   
    
    
}


class RuleStepCondition {
    
    private $conditionArray = array();
    private $errors = array();
    
    public function setConditionArray($conditionArray){
        $this->conditionArray = $conditionArray;
        return $this;
    }
    
    public function getConditionArray(){
        return $this->conditionArray;
    }
    
    public function isMet($params){
                
        \gt_debug("Checking if condition is met " . print_r($this->conditionArray, true));
        
        // If v1 & v2 are null, that means always run this
        if (is_null($this->conditionArray['v1']) && is_null($this->conditionArray['v2'])){
            \gt_debug("Conditions are null, meaning we always want to run this, so returning true");
            return true;
        }
        
        // Check to see if comparison function exists
        $method = "comparison_{$this->conditionArray['cmp']}";
                        
        if (!method_exists($this, $method)){
            \gt_debug("Error: Missing method - {$method}");
            return false;
        }
                
        // Get the final value of the V1 comparison element
        \gt_debug("Working out v1 value...");
                
        // If the value is wrapped in quotes, then it is just a static value we do nothing with
        // e.g. "Distinction" or "5", etc...
        $v1 = false;
        if (strlen($this->conditionArray['v1']) > 0){
            if (preg_match(GT_RULE_REGEX_QUOTE_WRAPPED, $this->conditionArray['v1'])){
                $v1 = str_replace( array("'", '"'), "", $this->conditionArray['v1'] );
            } elseif (is_numeric($this->conditionArray['v1'])){
                $v1 = $this->conditionArray['v1'];
            } else {

                $split = preg_split(GT_RULE_REGEX_SPLIT, $this->conditionArray['v1']);
                $obj = $split[0];
                unset($split[0]);
                
                // Rebase array keys
                $split = array_values($split);

                // Check to see if object exists
                $object = \GT\Rule::getObject($obj);
                if (!$object){
                    \gt_debug("Error: Invalid RuleObject - {$obj}");
                    return false;
                }
                
                $object->loadParams($params);
                
                $v1 = Rule::call($object, $split);

            }
        }
                
        
        \gt_debug("v1: " . print_r($v1, true));
        
        
        // Now do the same for V2
        
        \gt_debug("Working out v2 value...");
        // Get the final value of the V1 comparison element
        
        // If the value is wrapped in quotes, then it is just a static value we do nothing with
        // e.g. "Distinction" or "5", etc...
        $v2 = false;
        if (strlen($this->conditionArray['v2']) > 0){
            if (preg_match(GT_RULE_REGEX_QUOTE_WRAPPED, $this->conditionArray['v2'])){
                $v2 = str_replace( array("'", '"'), "", $this->conditionArray['v2'] );
            } elseif (is_numeric($this->conditionArray['v2'])){
                $v2 = $this->conditionArray['v2'];
            } else {

                $split = preg_split(GT_RULE_REGEX_SPLIT, $this->conditionArray['v2']);
                $obj = $split[0];
                unset($split[0]);

                // Rebase array keys
                $split = array_values($split);

                // Check to see if object exists
                $object = \GT\Rule::getObject($obj);
                if (!$object){
                    \gt_debug("Error: Invalid RuleObject - {$obj}");
                    return false;
                }

                $object->loadParams($params);

                $v2 = Rule::call($object, $split);

            }
        }
        
        \gt_debug("v2: " . print_r($v2, true));
        
        
        
        // If it's an array of only 1 element, just send that one element
        if (is_array($v1) && count($v1) == 1){
            $v1 = reset($v1);
        }
        
        if (is_array($v2) && count($v2) == 1){
            $v2 = reset($v2);
        }
        
        return $this->$method($v1, $v2);           
                    
    }
    
    /**
     * The IS_EQUAL comparison
     * Compare if two things are equal. 
     * These could be singular values (scalar or Award objects), or arrays of those things
     * If one of the values is an array, all its elements will be compared to the other value
     * You cannot have both values as arrays
     * @param type $v1
     * @param type $v2
     * @return boolean
     */
    protected function comparison_is_equal($v1, $v2){
                        
        \gt_debug("Called comparison_is_equal method...");
        
        // If both values are arrays, we can't do that, it's just too complicated
        if (is_array($v1) && is_array($v2)){
            \gt_debug("Error: v1 and v2 are both arrays");
            return false;
        }
        

        // If v1 is an array
        if (is_array($v1) && !empty($v1)){
            
            $result = true;
            
            // Loop through v1 elements
            foreach($v1 as $v1Element){
                $result = $result && $this->comparison_is_equal($v1Element, $v2);
            }
            
            \gt_debug("v1 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }
        
        // If v2 is an array
        elseif (is_array($v2) && !empty($v2)){
            
            $result = true;
            
            // Loop through v1 elements
            foreach($v2 as $v2Element){
                $result = $result && $this->comparison_is_equal($v2Element, $v1);
            }
            
            \gt_debug("v2 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }        
        
        // If both are scalar values
        elseif (is_scalar($v1) && is_scalar($v2)){

            // If both are strings
            if (is_string($v1) && is_string($v2)){
                $result = ( strcasecmp($v1, $v2) == 0 );
                \gt_debug("v1 & v2 string comparison - Final result - " . print_r($result, true));
                return $result;
            } else {
                $result = ($v1 == $v2);
                \gt_debug("v1 & v2 comparison - Final result - " . print_r($result, true));
                return $result;
            }

        }
        
        else {
            
            // Get any class names if either of them are objects
            $v1Class = (is_object($v1)) ? get_class($v1) : false;
            $v2Class = (is_object($v2)) ? get_class($v2) : false;
            
            // If v1 is an Award object, use it's award name
            if ( in_array($v1Class, array('GT\CriteriaAward', 'GT\UnitAward')) )
            {
                $v1 = $v1->getName();
            }
            
            // If v2 is an Award object, use it's award name
            if ( in_array($v2Class, array('GT\CriteriaAward', 'GT\UnitAward')) )
            {
                $v2 = $v2->getName();
            }
            
            \gt_debug("v1 or v2 (or both) are objects - New values: v1 ({$v1}), v2 ({$v2})...");
            
            // If both are now strings
            if (is_string($v1) && is_string($v2)){
                $result = ( strcasecmp($v1, $v2) == 0 );
                \gt_debug("v1 & v2 string comparison - Final result - " . (int)$result);
                return $result;
            } else {
                $result = ($v1 == $v2);
                \gt_debug("v1 & v2 comparison - Final result - " . (int)$result);
                return $result;
            }
            
            
        }
                    
    }
    
    
    /**
     * The IS_NOT_EQUAL comparison
     * Compare if two things are not equal. 
     * These could be singular values (scalar or Award objects), or arrays of those things
     * If one of the values is an array, all its elements will be compared to the other value
     * You cannot have both values as arrays
     * @param type $v1
     * @param type $v2
     * @return boolean
     */
    protected function comparison_is_not_equal($v1, $v2){
                        
        \gt_debug("Called comparison_is_not_equal method...");
        
        // If both values are arrays, we can't do that, it's just too complicated
        if (is_array($v1) && is_array($v2)){
            \gt_debug("Error: v1 and v2 are both arrays");
            return false;
        }
        

        // If v1 is an array
        if (is_array($v1)){
            
            $result = true;
            
            // Loop through v1 elements
            foreach($v1 as $v1Element){
                
                $result = $result && $this->comparison_is_not_equal($v1Element, $v2);
                
            }

            \gt_debug("v1 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }
        
        // If v2 is an array
        elseif (is_array($v2)){
            
            $result = true;
            
            // Loop through v1 elements
            foreach($v2 as $v2Element){
                
                $result = $result && $this->comparison_is_not_equal($v2Element, $v1);
                
            }
            
            \gt_debug("v2 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }        
        
        // If both are scalar values
        elseif (is_scalar($v1) && is_scalar($v2)){

            // If both are strings
            if (is_string($v1) && is_string($v2)){
                $result = ( strcasecmp($v1, $v2) <> 0 );
                \gt_debug("v1 & v2 string comparison - Final result - " . print_r($result, true));
                return $result;
            } else {
                $result = ($v1 != $v2);
                \gt_debug("v1 & v2 comparison - Final result - " . print_r($result, true));
                return $result;
            }

        }
        
        else {
            
            // Get any class names if either of them are objects
            $v1Class = (is_object($v1)) ? get_class($v1) : false;
            $v2Class = (is_object($v2)) ? get_class($v2) : false;
            
            // If v1 is an Award object, use it's award name
            if ( in_array($v1Class, array('GT\CriteriaAward', 'GT\UnitAward')) )
            {
                $v1 = $v1->getName();
            }
            
            // If v2 is an Award object, use it's award name
            if ( in_array($v2Class, array('GT\CriteriaAward', 'GT\UnitAward')) )
            {
                $v2 = $v2->getName();
            }
            
            \gt_debug("v1 or v2 (or both) are objects - New values: v1 ({$v1}), v2 ({$v2})");
            
            // If both are now strings
            if (is_string($v1) && is_string($v2)){
                $result = ( strcasecmp($v1, $v2) <> 0 );
                \gt_debug("v1 & v2 string comparison - Final result - " . print_r($result, true));
                return $result;
            } else {
                $result = ($v1 != $v2);
                \gt_debug("v1 & v2 comparison - Final result - " . print_r($result, true));
                return $result;
            }
            
            
        }
                    
    }
    
    
    /**
     * The IS_MET comparison to check awards
     * @param type $value
     * @return boolean
     */
    protected function comparison_is_met($value){
                
        \gt_debug("Called comparison_is_met method...");
        
        // If it's an array
        if (is_array($value)){
            
            // If empty array return false
            if (empty($value)){
                \gt_debug("Array is empty - Final Result - 0");
                return false;
            }
            
            $result = true;
            
            foreach($value as $key => $val){

                // Check class of object
                if (!is_object($val) || !in_array(get_class($val), array('GT\CriteriaAward', 'GT\UnitAward'))){
                    \gt_debug("Error: Value[{$key}] is either not an object or not a valid CriteriaAward or UnitAward object");
                    return false;
                }
                
                $result = $result && ($val->isMet());
                
            }
            
            \gt_debug("Value is an array - Final result - " . (int)$result);
            return $result;
            
        } else {
            
            // Check it's the right type of object
            if (!is_object($value) || !in_array(get_class($value), array('GT\CriteriaAward', 'GT\UnitAward'))){
                \gt_debug("Error: Value is either not an object or not a valid CriteriaAward or UnitAward object");
                return false;
            }
            
            // Return if it's met or not
            $result = ($value->isMet());
            \gt_debug("Final result - " . (int)$result);
            return $result;
            
        }
        
    }
    
    /**
     * The IS_NOT_MET comparison to check awards
     * @param type $value
     * @return boolean
     */
    protected function comparison_is_not_met($value){
        
        \gt_debug("Called comparison_is_not_met method...");

        // If it's an array
        if (is_array($value)){
            
            $result = true;
            
            foreach($value as $val){

                // Check class of object
                if (!is_object($val) || !in_array(get_class($val), array('GT\CriteriaAward', 'GT\UnitAward'))){
                    \gt_debug("Error: Value is either not an object or not a valid CriteriaAward or UnitAward object");
                    return false;
                }
                
                $result = $result && (!$val->isMet());
                
            }
            
            \gt_debug("Value is an array - Final result - " . print_r($result, true));
            return $result;
            
        } else {
            
            // Check it's the right type of object
            if (!is_object($value) || !in_array(get_class($value), array('GT\CriteriaAward', 'GT\UnitAward'))){
                \gt_debug("Error: Value is either not an object or not a valid CriteriaAward or UnitAward object");
                return false;
            }
            
            // Return if it's met or not
            $result = (!$value->isMet());
            \gt_debug("Final result - " . print_r($result, true));
            return $result;
            
        }
        
    }
    
    /**
     * Convert a condition to a string to store in DB
     * @return boolean|string
     */
    public function convertConditionToString(){
        
        $str = "";
        
        // If no comparison given, or both values are empty, just return blank string
        if ($this->conditionArray['cmp'] == '' || ($this->conditionArray['v1'] == '' && $this->conditionArray['v2'] == '')){
            return '';
        }
                        
        $str .= $this->conditionArray['cmp'] . "(";
        
            switch ($this->conditionArray['cmp'])
            {
                
                // Only has 1 param
                case 'is_met':
                case 'is_not_met':
                    $str .= $this->conditionArray['v1'];
                break;
                
                // Others have 2
                default:
                    $str .= $this->conditionArray['v1'];
                    $str .= GT_RULE_SEP;
                    $str .= $this->conditionArray['v2'];
                break;
            
            }
        
        $str .= ")";
                
        return $str;
        
    }
    
    /**
     * Get a particlar part of the array
     * @param type $part
     * @return type
     */
    public function getConditionPart($part, $default = false){
        
        // If this is true, then if nothing is set for the part we want to display the default value
        if ($default)
        {
            if (array_key_exists($part, $this->conditionArray) && strlen($this->conditionArray[$part]) > 0)
            {
                return $this->conditionArray[$part];
            }
            else
            {
                if ($part == 'v1' || $part == 'v2')
                {
                    return "value";
                }
                elseif ($part == 'cmp')
                {
                    return "comparison";
                }
            }
        }
        else
        {
            return (array_key_exists($part, $this->conditionArray) && strlen($this->conditionArray[$part]) > 0) ? $this->conditionArray[$part] : '';
        }
        
    }
    
    /**
     * Create condition from string
     * @param type $str
     * @return \GT\RuleStepCondition
     */
    public static function createFromString($str){
        
        preg_match("/([a-z_]+?)\((.+?)\)/Ui", $str, $split);
                      
        $cmp = (isset($split[1]) && strlen($split[1])) ? $split[1] : null;
        $params = (isset($split[2]) && strlen($split[2])) ? $split[2] : null;
        
        switch($cmp)
        {
            
            case 'is_met':
            case 'is_not_met':
                
                $v1 = trim($params);
                $v2 = null;
                
            break;
        
            default: 
                
                $params = explode(GT_RULE_SEP, $params);
                $v1 = (isset($params[0]) && strlen($params[0])) ? trim($params[0]) : null;
                $v2 = (isset($params[1]) && strlen($params[1])) ? trim($params[1]) : null;
                
            break;
            
        }
        
        
        $array = array(
            'v1' => $v1,
            'v2' => $v2,
            'cmp' => $cmp
        );
                
        $condition = new RuleStepCondition();
        $condition->setConditionArray($array);
        
        return $condition;
        
    }
    
}


class RuleStepAction {
    
    private $action;
    private $errors = array();
    
    public function getAction(){
        return $this->action;
    }
    
    public function setAction($action){
        $this->action = $action;
        return $this;
    }
    
    /**
     * Convert action to string to store in DB
     * @return boolean|string
     */
    public function convertActionToString(){
        
        return $this->action;
    }
    
    /**
     * Run the action
     * @param type $params The params sent by the event to the listener
     */
    public function run($params){
        
        \gt_debug("Running action - " . $this->action . "...");
        
        // Split the action string so we can work out what the method is
        // e.g. set_award(unit@@'Merit') - The method is set_award
        preg_match("/([a-z_]+?)\((.+?)\)/Ui", $this->action, $split);
        if (!$split){
            \gt_debug("Error: Could not find an action in the correct format");
            return false;
        }
        
        $method = "action_".$split[1];
        $methodParams = $split[2];
        if (!method_exists($this, $method)){
            \gt_debug("Error: Method does not exist - {$method}");
            return false;
        }
        
        // Explode what is inside the method by the seperator @@ to work out the parameters to use
        $explode = explode(GT_RULE_SEP, $methodParams);
        if (!$explode){
            \gt_debug("Error: Could not split action by seperator (".GT_RULE_SEP.") to find 2nd parameter");
            return false;
        }
        
        $v1 = $explode[0];
        $v2 = (isset($explode[1])) ? $explode[1] : false;
                
        
        
        // v1 must be a valid RuleObject, it cannot be a scalar or anything else
        $split = preg_split(GT_RULE_REGEX_SPLIT, $v1);
        if (!$split){
            \gt_debug("Error: Could not find any methods on v1 - {$v1}");
            return false;
        }
        
        \gt_debug("Working out value from v1 - {$v1}...");
        
        $obj = $split[0];
        unset($split[0]);

        // Rebase array keys
        $split = array_values($split);
        
        \gt_debug("Split v1 into object: {$obj} and parts: " . print_r($split, true));
        
        // Check to see if object exists
        $object = \GT\Rule::getObject($obj);
        if (!$object){
            \gt_debug("Error: Invalid RuleObject found on v1 - {$obj}");
            return false;
        }
                        
        $object->loadParams($params);
        
        if ($split){
            $v1 = Rule::call($object, $split);
        } else {
            $v1 = $object;
        }
                
        if (!$v1){
            \gt_debug("Error: Result of Rule::call() was false");
            return false;
        }
      
        
        
        
        // V2 can be either a scalar value (string or numeric) or it can be the result of a ::call()
        // e.g. it can be another Award object, but it cannot be an array of them, it would have to
        // be only 1, as you can't do something like set_award and pass in multiple awards
        \gt_debug("Working out value from v2 - {$v2}...");
        
        if (strlen($v2) > 0){
            if (preg_match(GT_RULE_REGEX_QUOTE_WRAPPED, $v2)){
                $v2 = str_replace( array("'", '"'), "", $v2 );
            } elseif (is_numeric($v2)){
                // leave as it is
            } else {

                $split = preg_split(GT_RULE_REGEX_SPLIT, $v2);
                $obj = $split[0];
                unset($split[0]);

                // Rebase array keys
                $split = array_values($split);
                
                \gt_debug("Split v2 into object: {$obj} and parts: " . print_r($split, true));

                // Check to see if object exists
                $object = \GT\Rule::getObject($obj);
                if (!$object){
                    \gt_debug("Error: Invalid RuleObject found on v2 - {$obj}");
                    return false;
                }

                $object->loadParams($params);
                
                if ($split){
                    $v2 = Rule::call($object, $split);
                } else {
                    $v2 = $object;
                }
                
                // If v2 is an array, with more than 1 element, no..just...no
                if (is_array($v2) && count($v2) > 1){
                    \gt_debug("Error: Result of Rule::call() on v2 returned an array with more than 1 element");
                    return false;
                }

            }
        }
                
        // Now let's call the method
        
        // If it's an array of only 1 element, just use that one element
        if (is_array($v1) && count($v1) == 1){
            $v1 = reset($v1);
        }
        
        if (is_array($v2) && count($v2) == 1){
            $v2 = reset($v2);
        }
                
        // Call the method
        $this->$method($v1, $v2);         
   
    }
    
    /**
     * Set the award of something
     * @param type $v1
     * @param type $v2
     * @return boolean
     */
    protected function action_set_award($v1, $v2){
        
        \gt_debug("Called action_set_award method...");
        
        // If v1 is an array, loop through and call on each element
        if (is_array($v1)){
            
            $result = true;
            
            foreach($v1 as $v1Element){
                
                $result = $result && $this->action_set_award($v1Element, $v2);
                
            }
            
            \gt_debug("v1 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }
        
        // Otherwise
        else
        {
            
            // Does the object have the method we want?
            if (!method_exists($v1, 'getAwardByName') || !method_exists($v1, 'setAward')){
                \gt_debug("Error: v1 object is missing required methods: getAwardByName, setAward");
                return false;
            }
                                    
            // If v2 is an object, it must be a CriteriaAward or UnitAward
            if (is_object($v2)){
                $v2Class = get_class($v2);
                if (!in_array($v2Class, array('GT\CriteriaAward', 'GT\UnitAward'))){
                    \gt_debug("Error: v2 is an object of an invalid class - {$v2Class}");
                    return false;
                }
            } elseif (!is_string($v2)){
                // If it's not an object, it must be a string, otherwise stop
                \gt_debug("Error: v2 is neither an object or a string");
                return false;
            }
                                    
            // If v2 is a string, see if we can get the award by that name
            if (is_string($v2)){
                $award = $v1->getAwardByName($v2);
            } else {
                // Otherwise, based on previous conditions, it must be an *Award object
                $award = $v1->getAwardByName( $v2->getName() );
            }
                        
            // If the award is valid set it
            if ($award && $award->isValid()){
                $result = $v1->setAward($award);
                \gt_debug("Final result of setAward() method [".\get_class($v1)."] - " . print_r($result, true));
                return $result;
            }
            
        }
        
        \gt_debug("Error: Reached end of method without returning a result");
        return false;
        
    }
    
    protected function action_unset_award($v1){
        
        \gt_debug("Called action_unset_award method...");
        
        // If v1 is an array, loop through and call on each element
        if (is_array($v1)){
            
            $result = true;
            
            foreach($v1 as $v1Element){
                
                $result = $result && $this->action_unset_award($v1Element);
                
            }
            
            \gt_debug("v1 is an array - Final result - " . print_r($result, true));
            return $result;
            
        }
        
        // Otherwise
        else
        {
            
            // If the award is valid set it
            $result = $v1->unsetAward();
            \gt_debug("Final result of unsetAward() method [".\get_class($v1)."] - " . print_r($result, true));
            return $result;
            
        }
        
    }
    
    public static function createFromString($str, $convert= true){
     
        if ($convert)
        {
           
            $split = explode(".", $str);
            $arr = array();
            $imploding = false;
            $imp = array();

            foreach($split as $e)
            {

                $l = substr_count($e, '(');
                $r = substr_count($e, ')');
                // Same number of open and closing brackets, so is all good
                if ($l == $r ){
                    if ($imploding){
                        $imp[] = $e;
                    } else {
                        $arr[] = $e;
                    }
                }
                
                // Different numbers (more open than closing)
                elseif ($l > $r){
                    $imp[] = $e;
                    $imploding = true;
                }
                
                elseif ($r > $l){
                    $imp[] = $e;
                    $arr[] = implode(".", $imp);
                    $imp = array();
                    $imploding = false;
                }
                                

            }
                        
            // Get the last method used
            $last = end($arr);            
            

            // Remove last element
            array_pop($arr);
            $str = implode(".", $arr);
            
            preg_match("/([a-z]+)\((.*?)\)/iU", $last, $matches);
            

            $method = isset($matches[1]) ? $matches[1] : false;
            $value = isset($matches[2]) ? $matches[2] : false;

            if (!$method){
                return false;
            }

            
            
            // Split by uppercase letter
            $methodSplit = preg_split("/(?=[A-Z])/", $method);
            array_walk( $methodSplit, function(&$item){
                $item = strtoupper($item);
            } );
            $method = implode("_", $methodSplit);
            

            
            
            $newStr = '';
            $newStr .= $method;
            $newStr .= '(';
            $newStr .= $str;
            if ($value && $value != ''){
                $newStr .= GT_RULE_SEP . $value;
            }
            $newStr .= ')';
        
        } else {
            $newStr = $str;
        }
        

        
        $action = new \GT\RuleStepAction();
        $action->setAction($newStr);
        return $action;
        
    }
    
    /**
     * Reverse the METHOD(object.method()@@'value') back into how it looks on the rule form, e.g. object.method().setWhatever('value')
     * @return type
     */
    public function reverseConversionFromString(){
                
        // Split the action string so we can work out what the method is
        // e.g. set_award(unit@@'Merit') - The method is set_award
        preg_match("/([a-z_]+?)\((.+?)\)/Ui", $this->action, $split);
        if (!$split){
            return false;
        }
        
        $method = $split[1];
        $methodParams = $split[2];
                
        // Explode what is inside the method by the seperator @@ to work out the parameters to use
        $explode = explode(GT_RULE_SEP, $methodParams);
        if (!$explode){
            return false;
        }
        
        $v1 = $explode[0];
        $v2 = (isset($explode[1])) ? $explode[1] : false;
                
        $methodSplit = explode("_", $method);
        array_walk( $methodSplit, function(&$item, $k){
            $item = ($k > 0) ? ucfirst(strtolower($item)) : strtolower($item);  
        } );
        $method = implode("", $methodSplit);

        
        $str = $v1 . GT_RULE_OP . $method . '(x)';
        
        if ($v2){
            $str = str_replace("(x)", "({$v2})", $str);
        } else {
            $str = str_replace("(x)", "()", $str);
        }
        
        
        return $str;
        
    }
    
    
    
}

abstract class RuleObject {
    
    protected $params = array();
    protected $qual = false;
    protected $unit = false;
    protected $criterion = false;
    protected $student = false;
    
    public function setParams($params){
        $this->params = $params;
    }
    
    public function loadParams($params){
        
        $this->params = $params;
        
        // Student
        if (isset($this->params['sID'])){
            $this->student = new \GT\User($this->params['sID']);
        }
        
        // Qual
        if (isset($this->params['qID'])){
            
            // If there is a student, load a UserQual
            if ($this->student && $this->student->isOnQual($this->params['qID'], "STUDENT")){
                $this->qual = new \GT\Qualification\UserQualification($this->params['qID']);
                $this->qual->loadStudent($this->student);
            }
            
            // Else just load a normal qual object
            else {
                $this->qual = new \GT\Qualification($this->params['qID']);
            }
            
        }
                
        // Unit
        if (isset($this->params['uID']) && $this->qual){
            $this->unit = $this->qual->getOneUnit($this->params['uID']);
        }
        
        
        // Criterion
        if (isset($this->params['cID']) && $this->unit){
            $this->criterion = $this->unit->getCriterion($this->params['cID']);
        }
                       
    }
    
    
    public static function getAllObjects(){
        return array('qual', 'unit', 'criterion');
    }
    
}


class RuleObject_Qual extends RuleObject {
    
    public static $methods = array( array('name' => 'getUnits', 'return' => 'object', 'object' => 'unit', 'filter' => true) );
    
    public function getUnits($args){
                   
        $this->qual->loadUnits();
        $units = $this->qual->getUnits();
        $filters = array();
        
        // Remove any empty array elements
        $args = array_filter($args);
        
        // Filters
        if ($args)
        {
            $filter = new \GT\Filter();
            $filters['field'] = $args[1];
            $filters['conjunction'] = $args[0];
            
            unset($args[0], $args[1]);
            $args = array_values($args);
            
            $filters['value'] = $args;
            $units = $filter->filterUnits($units, $filters);
        }
        
        $return = array();
        
        if ($units)
        {
            foreach($units as $unit)
            {
                $obj = new \GT\RuleObject_Unit();
                $obj->setParams($this->params);
                $obj->setUnitObject($unit);
                $return[] = $obj;
            }
        }    
            
        return $return;
        
    }
   
    
}

class RuleObject_Unit extends RuleObject {
    
    public static $methods = array( array('name' => 'getCriteria', 'return' => 'object', 'object' => 'criterion', 'filter' => true), 
        array('name' => 'getAward', 'return' => 'string'), 
        array('name' => 'setAward', 'return' => 'input'), 
        array('name' => 'unsetAward'));
    protected $unitObject = false;
    
    public function loadParams($params) {
        parent::loadParams($params);
        $this->unitObject = $this->unit;
    }
    
    public function setUnitObject($obj){
        $this->unitObject = $obj;
        return $this;
    }
    
    public function getCriteria($args){
        
        // If the unitObject isn't set, nothing we can do
        if (!$this->unitObject || !$this->unitObject->isValid()) return false;
        
        $criteria = $this->unitObject->loadCriteriaIntoFlatArray();
        $filters = array();
        
        $filter = new \GT\Filter();
                
        // Filters
        if ($args && isset($args[0]) && isset($args[1]))
        {
            
            $filters['field'] = $args[1];
            $filters['conjunction'] = $args[0];
            
            unset($args[0], $args[1]);
            $args = array_values($args);
            
            $filters['value'] = $args;
            $criteria = $filter->filterCriteria($criteria, $filters);
            
        }
                
        // Filter out readonly criteria
        $criteria = $filter->filterCriteriaNotReadOnly($criteria);
        
        $return = array();
        
        if ($criteria)
        {
            foreach($criteria as $criterion)
            {
                $obj = new \GT\RuleObject_Criterion();
                $obj->setParams($this->params);
                $obj->setCriterionObject($criterion);
                $return[] = $obj;
            }
        }    
                    
        return $return;
                
    }
    
    /**
     * Get the unit award
     */
    public function getAward(){
        
        if (!$this->unitObject || !$this->unitObject->isValid()) return false;
        return $this->unitObject->getUserAward();
        
    }
    
     /**
     * Get unit award by name
     * @param type $name
     * @return boolean
     */
    public function getAwardByName($name){
             
        // Get the criteria grading structure
        $gradingStructure = $this->unitObject->getGradingStructure();        
        if (!$gradingStructure) return false;
        
        return $gradingStructure->getAwardByName($name);
        
    }
    
    /**
     * Set criterion award
     * @param \GT\UnitAward $award
     */
    public function setAward(\GT\UnitAward $award){
        
        // If it already has this award, don't bother actually doing it - waste of processing
        if ($this->unitObject->getUserAward() && $this->unitObject->getUserAward()->getID() == $award->getID()){
            return true;
        }
        
        $GLOBALS['rule_response']['unitawards'][$this->unitObject->getID()] = $award->getID();
        $this->unitObject->setUserAward($award);
        return $this->unitObject->saveUser(false); // Do NOT notify about user unit event
        
    }
    
    /**
     * Unset the unit award
     */
    public function unsetAward(){
        
        // If it already has this award, don't bother actually doing it - waste of processing
        if ($this->unitObject->getUserAward() && $this->unitObject->getUserAward()->getID() == false){
            \gt_debug("Already has this award");
            return true;
        }
        
        $GLOBALS['rule_response']['unitawards'][$this->unitObject->getID()] = '0';
        $this->unitObject->setUserAwardID( false );
                
        return $this->unitObject->saveUser(false);
    }
    
    
    
    
}

class RuleObject_Criterion extends RuleObject {
    
    public static $methods = array( array('name' => 'getAward', 'return' => 'string'), 
        array('name' => 'getPoints', 'return' => 'number'), 
        array('name' => 'getName', 'return' => 'string'), 
        array('name' => 'setAward', 'return' => 'input'), 
        array('name' => 'unsetAward'));
    protected $criterionObject = false;
   
    public function loadParams($params) {
        parent::loadParams($params);
        $this->criterionObject = $this->criterion;
    }
    
    public function setCriterionObject($obj){
        $this->criterionObject = $obj;
        return $this;
    }
    
    /**
     * Get the award of the criterion
     * @return boolean
     */
    public function getAward(){
                        
        if (!$this->criterionObject || !$this->criterionObject->isValid()) return false;
        return $this->criterionObject->getUserAward();
                
    }
    
    /**
     * Return the points of the criterion award
     * @return boolean
     */
    public function getPoints(){
                
        if (!$this->criterionObject || !$this->criterionObject->isValid()) return false;
        return $this->criterionObject->getUserAward()->getPoints();
        
    }
    
    /**
     * Get the name of the criterion
     * @return boolean
     */
    public function getName(){
        
        if (!$this->criterionObject || !$this->criterionObject->isValid()) return false;
        return $this->criterionObject->getName();
        
    }
    
    /**
     * Get criteria award by name
     * @param type $name
     * @return boolean
     */
    public function getAwardByName($name){
             
        // Get the criteria grading structure
        $gradingStructure = $this->criterionObject->getGradingStructure();        
        if (!$gradingStructure) return false;
        
        return $gradingStructure->getAwardByName($name);
        
    }
    
    /**
     * Set criterion award
     * @param \GT\CriteriaAward $award
     */
    public function setAward(\GT\CriteriaAward $award){
        
        // If it already has this award, don't bother actually doing it - waste of processing
        if ($this->criterionObject->getUserAward() && $this->criterionObject->getUserAward()->getID() == $award->getID()){
            return true;
        }
        
        $GLOBALS['rule_response']['awards'][$this->criterionObject->getID()] = $award->getID();
        $this->criterionObject->setUserAward($award);
        return $this->criterionObject->saveUser(true, true); // Save the user award. Don't do auto cals. Or events.
        
    }
    
    /**
     * Unset the criterion award
     */
    public function unsetAward(){
        
        // If it already has this award, don't bother actually doing it - waste of processing
        if ($this->criterionObject->getUserAward() && $this->criterionObject->getUserAward()->getID() == false){
            return true;
        }
        
        $GLOBALS['rule_response']['awards'][$this->criterionObject->getID()] = '0';
        $this->criterionObject->setUserAwardID( false );
        return $this->criterionObject->saveUser(true, true); // Save the user award. Don't do auto cals. Or events.
    }
    
    
}




class RuleVerifier
{
    
    public function getPossibleObjects($fromType = null, $fromVal = null){
        
        $return = array();
        
        if (is_null($fromType)){
            $return = \GT\RuleObject::getAllObjects();
        } 
        
        return $return;
        
    }
    
    public function getPossibleMethods($fromType, $fromVal){
        
        $return = array();
                
        if ($fromType == 'object'){
            
            $class = "\GT\RuleObject_" . ucfirst($fromVal);
            if (class_exists($class)){
                $return = $class::$methods;
                $return = array_map( function($v) use ($fromVal) {
                    $v['longName'] = $fromVal . '.' . $v['name'];
                    return $v;
                }, $return);
            }
            
        } elseif ($fromType == 'method'){
            
            // Look the method up
            $explode = explode(".", $fromVal);
            $object = $explode[0];
            $method = $explode[1];
            
            $class = "\GT\RuleObject_" . ucfirst($object);
            if (class_exists($class)){
                    
                $method = \gt_find_element_in_array($class::$methods, 'name', $method);
                if ($method && $method['return'] == 'object'){
                    
                    $returnClass = "\GT\RuleObject_" . ucfirst($method['object']);
                    $return = $returnClass::$methods;
                    $return = array_map( function($v) use ($method) {
                        $v['longName'] = $method['object'] . '.' . $v['name'];
                        return $v;
                    }, $return);
                    
                }
                
            }
            
        }
        
        
        return $return;
        
    }
    
    /**
     * Get the links for what can be done in the next stage
     * params['type'] - This is what we just changed in the select menu
     * params['value'] - The value of the select menu
     * @param type $params
     * @return string
     */
    public function getPossibleNextStages($params){
        
        /**
Array
(
    [type] => method - The thing we just changed the value of, e.g. method
    [value] => getUnits - That value, e.g. getUnits
    [returnType] => object - What this thing returns, e.g. object
    [returnValue] => unit - The type of the thing this returns, e.g. unit
    [objStr] => object[qual].method[getUnits]
)
         */
                
        $return = array();
        
        if ($params['type'] == 'object'){
            $return[] = 'method';
        } elseif ($params['type'] == 'method'){
            
            $explode = explode(".", $params['longName']);
            $object = $explode[0];
            $method = $explode[1];
            
            $class = "\GT\RuleObject_" . ucfirst($object);
            if (class_exists($class)){
                    
                $methods = $class::$methods;
                $method = \gt_find_element_in_array($methods, 'name', $params['value']);
                    
                // Check if the method can be filtered
                if ($method && isset($method['filter']) && $method['filter'] === true){
                    $return[] = 'filter';
                }
                    
                // Check if the method returns anything
                if ($method && isset($method['return'])){
                    
                    // Uf the method returns any other objects it can have methods added on to it
                    if ($method['return'] == 'object'){
                    
                        $returnClass = "\GT\RuleObject_" . ucfirst($method['object']);
                        if (class_exists($returnClass)){

                            $methods = $returnClass::$methods;
                            if ($methods){
                                $return[] = 'method';
                            }

                        }
                    
                    } elseif ($method['return'] == 'input'){
                        $return[] = 'input';
                    }
                    
                }
                    
            }
                            
        }
        
        return $return;
        
    }
    
    /**
     * Get the possible conjunctions and fields to be used in a filter
     * @param type $fromType
     * @param type $fromVal
     * @return type
     */
    public function getPossibleFilters($fromType, $fromVal){
        
        $return = array();
        $returnObject = false;
        
        $explode = explode(".", $fromVal);
        $object = $explode[0];
        $method = $explode[1];

        $class = "\GT\RuleObject_" . ucfirst($object);
        if (class_exists($class)){

            $methods = $class::$methods;
            $method = \gt_find_element_in_array($methods, 'name', $method);

            // Check if the method can be filtered
            if ($method && $method['return'] == 'object'){
                $returnObject = $method['object'];
            }

        }
            
        $return['conjunctions'] = \GT\Filter::getAllFilters();
        $return['fields'] = ($returnObject) ? \GT\Filter::getFilterableFields($returnObject) : array();
        
        return $return;
        
    }
    
    
}