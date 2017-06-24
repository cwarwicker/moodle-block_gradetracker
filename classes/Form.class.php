<?php
/**
 * Form class
 *
 * Class for defining custom form elements for use in things such as custom structure forms
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

class FormElement {
        
    private $supportedTypes = array('TEXT', 'NUMBER', 'CHECKBOX', 'TEXTBOX', 'SELECT', 'QUALPICKER');
    private $supportedValidation = array(
            "REQUIRED",
            "TEXT_ONLY",
            "NUMBERS_ONLY",
            "ALPHANUMERIC_ONLY",
            "DATE",
            "EMAIL",
            "PHONE",
            "URL"
        );
    private $supportedForms = array('qualification', 'unit'); // These are the different forms we can add form elements to
    
    private $id = false;
    private $name;
    private $form;
    private $type;
    private $options = array();
    private $validation = array();
    
    private $value;
    
    private $errors = array();
    
    /**
     * Construct element
     */
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_form_elements", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->name = $record->name;
                $this->form = $record->form;
                $this->type = $record->type;
                $this->options = explode("\n", $record->options);
                $this->validation = explode(",", $record->validation);
                
            }
            
        }
        
    }
    
    /**
     * Has an id been set?
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Get the id of the form element
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Set the ID of the form element
     * @param type $id
     */
    public function setID($id){
        $this->id = $id;
    }
    
    /**
     * Set the name of the element
     * @param type $name
     */
    public function setName($name){
        $this->name = \gt_strip_chars($name);
    }
    
    /**
     * Get the name
     * @return type
     */
    public function getName(){
        return $this->name;
    }
    
    /**
     * Get which form the element is for
     * @return type
     */
    public function getForm(){
        return $this->form;
    }
    
    /**
     * Set which form the element is for
     * @param type $form
     */
    public function setForm($form){
        $this->form = $form;
    }
    
    /**
     * Set the type of the element
     * @param type $type
     */
    public function setType($type){
        $this->type = trim($type);
    }
    
    /**
     * Get the type of the element
     * @return type
     */
    public function getType(){
        return $this->type;
    }
    
    /**
     * Get the element options, if element like a select menu
     * @return type
     */
    public function getOptions(){
        return $this->options;
    }
    
    /**
     * Get the element options as a string
     * @return type
     */
    public function getOptionsString(){
        return ($this->options) ? implode(",", $this->options) : "";
    }
    
    /**
     * Set an array of options for the element
     * @param type $options
     */
    public function setOptions($options){
        
        // If we've passed in a string, explode it to array
        if (is_string($options))
        {
            $options = explode(",", $options);
        }
        
        // Trim any extra white space
        if ($options){
            $options = array_map('trim', $options);
        }
        
        $this->options = $options;
    }
    
    /**
     * Add an option to the array for the element
     * @param type $option
     */
    public function addOption($option){
        if (!in_array($this->options, $option)){
            $this->options[] = trim($option);
        }
    }
    
    /**
     * Get the validation on this element
     * @return type
     */
    public function getValidation(){
        return $this->validation;
    }
    
    /**
     * Set an array of validation for this element
     * @param type $validation
     */
    public function setValidation($validation){
        $this->validation = $validation;
    }
    
    /**
     * Add a validation type to the element
     * @param type $validation
     */
    public function addValidation($validation){
        if (!in_array($validation, $this->validation)){
            $this->validation[] = trim($validation);
        }
    }
    
    /**
     * Is a specific validation enabled?
     * @param type $validation
     * @return type
     */
    public function hasValidation($validation){
        return (in_array($validation, $this->validation));
    }
    
    /**
     * Get the value for this element if one has been set
     * @return type
     */
    public function getValue(){
        return $this->value;
    }
    
    /**
     * Set the value for this element
     * @param type $value
     */
    public function setValue($value){
        $this->value = $value;
    }
    
    /**
     * Get the supported element types
     * @return type
     */
    public function getSupportedTypes(){
        
        return $this->supportedTypes;
        
    }
    
    /**
     * Get any errors
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Check to make sure no errors
     */
    public function hasNoErrors(){
        
        // Check element name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:formelement:name', 'block_gradetracker');
        }
        
        // Check element form
        if (!in_array($this->form, $this->supportedForms)){
            $this->errors[] = sprintf( get_string('errors:formelement:form', 'block_gradetracker'), $this->form );
        }
        
        // Check element type
        if (!in_array($this->type, $this->supportedTypes)){
            $this->errors[] = sprintf( get_string('errors:formelement:type', 'block_gradetracker'), $this->type );
        }
        
        // Check validation
        if ($this->validation){
            foreach($this->validation as $validation){
                if (!in_array($validation, $this->supportedValidation) && !empty($validation)){
                    $this->errors[] = sprintf( get_string('errors:formelement:validation', 'block_gradetracker'), $validation );
                }
            }
        }
        
        // Check options
        if ($this->type == 'SELECT' && !$this->options){
            $this->errors[] = get_string('errors:formelement:missingoptions', 'block_gradetracker');
        }
        
        return (!$this->errors);
        
    }
    
    public function save(){
        
        global $DB;
        
        $obj = new \stdClass();
        $obj->name = $this->name;
        $obj->form = $this->form;
        $obj->type = $this->type;
        $obj->options = ($this->options) ? implode("\n", $this->options) : null;
        $obj->validation = ($this->validation) ? implode(",", $this->validation) : null;
                
        if ($this->isValid()){
            $obj->id = $this->id;
            $DB->update_record("bcgt_form_elements", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_form_elements", $obj);
        }
        
    }
    
    /**
     * Display the form element as HTML
     * @param type $options
     * @return string
     */
    public function display($options = false){
        
        global $CFG, $OUTPUT;
        
        $output = "";
        
        $name = \gt_strip_chars($this->name);
        
        // If we want to use the id as the name instead
        if (isset($options['use_id_as_name']) && $options['use_id_as_name'] == true){
            $name = $this->id;
        }
                
        // If we want to change the default name of the html element
        if (isset($options['name']) && !empty($options['name'])){
            $name = "{$options['name']}[{$name}]";
        }
        
        $name = gt_html($name);
        
        
        // Did we load in another value
        if (isset($options['value'])){
            $this->value = $options['value'];
        }
        
        // Did we load in a class?
        $class = "";
        if (isset($options['class'])){
            $class .= $options['class'];
        }
        
                
        switch ($this->type)
        {
            
            case 'TEXT':
                $output .= "<input id='gt_el_{$this->id}' class='{$class}' type='text' name='{$name}' value='{$this->value}' />";
            break;
        
            case 'NUMBER':
                $output .= "<input id='gt_el_{$this->id}' class='{$class}' type='number' name='{$name}' value='{$this->value}' />";
            break;
        
            case 'TEXTBOX':
                $output .= "<textarea id='gt_el_{$this->id}' class='{$class}' name='{$name}'>";
                    $output .= $this->value;
                $output .= "</textarea>";
            break;
        
            case 'SELECT':
                $output .= "<select id='gt_el_{$this->id}' class='{$class}' name='{$name}'>";
                    $output .= "<option value=''></option>";
                    if ($this->options)
                    {
                        foreach($this->options as $opt)
                        {
                            $sel = ($this->value == $opt) ? 'selected' : '';
                            $output .= "<option value='{$opt}' {$sel} >{$opt}</option>";
                        }
                    }
                $output .= "</select>";
            break;
        
            case 'CHECKBOX':
                
                $chk = ($this->value == 1) ? 'checked' : '';
                
                if (isset($options['fancy']) && $options['fancy']){
                    
                    $output .= "<div class='gt_fancy_checkbox'>";
                    $output .= "<input id='gt_el_{$this->id}' type='checkbox' class='gt_middle {$class}' name='{$name}' value='1' {$chk} />";
                    $output .= "<label for='gt_el_{$this->id}'></label>";
                    $output .= "</div>";

                } else {
                    $output .= "<input id='gt_el_{$this->id}' class='{$class}' type='checkbox' name='{$name}' value='1' {$chk} />";
                }
                
            break;
            
            case 'QUALPICKER':
                
                $allStructures = \GT\QualificationStructure::getAllStructures();
                $allLevels = \GT\Level::getAllLevels();
                $allSubTypes = \GT\SubType::getAllSubTypes();
                
                // Load the quals into an array and then sort them
                $qualArray = array();
                
                if ($this->value && is_array($this->value))
                {
                    foreach($this->value as $val)
                    {
                        if (is_numeric($val)){
                            $val = new \GT\Qualification($val);
                        }

                        if ($val->isValid()){
                            $qualArray[$val->getID()] = $val;
                        }
                    }
                }
                
                // Sort
                $Sorter = new \GT\Sorter();
                $Sorter->sortQualifications($qualArray);
                
                $output .= "<div class='gt_qual_picker'>";
                
                    $output .= "<div class='gt_page_col'>";

                        $output .= "<div class='gt_c'>";

                            $output .= "<div class='gt_form_panel_sub_heading gt_form_panel_sub_heading_alt'>".get_string('selectedquals', 'block_gradetracker')."</div>";

                            $output .= "<div>";

                                $output .= "<br><br>";

                                $output .= "<select id='chosen_quals' class='gt_course_select gt_course_select_larger' multiple='multiple'>";

                                if ($this->value && is_array($this->value))
                                {
                                    foreach($qualArray as $qual)
                                    {
                                        $output .= "<option id='chosen_qual_opt_{$qual->getID()}' value='{$qual->getID()}'>{$qual->getDisplayName()}</option>";
                                    }
                                }
                                
                                $output .= "</select>";

                                $output .= "<div id='gt_chosen_quals_hidden_ids'>";
                                    if ($this->value && is_array($this->value))
                                    {
                                        foreach($this->value as $val)
                                        {
                                            if (is_numeric($val)){
                                                $val = new \GT\Qualification($val);
                                            }
                                            
                                            if ($val->isValid()){
                                                $output .= "<input type='hidden' id='hidden_qual_{$val->getID()}' name='quals[]' value='{$val->getID()}' />";
                                            }
                                        }
                                    }

                                $output .= "</div>";

                                $output .= "<br><br>";

                                $output .= "<p>";
                                    $output .= "<input type='button' class='gt_btn' onclick='gtQualPickerRemove();return false;' value='".get_string('remove', 'block_gradetracker')."' />";
                                    $output .= "&nbsp;&nbsp;";
                                    $output .= "<a href='' id='gt_chosen_quals_edit_qual_btn' type='button' class='gt_btn gt_yellow' disabled target='_blank'>".get_string('edit', 'block_gradetracker')."</a>";
                                $output .= "</p>";

                            $output .= "</div>";

                        $output .= "</div>";

                    $output .= "</div>";
                    
                    
                    
                    
                    $output .= "<div class='gt_page_col'>";
                    
                        $output .= "<div class='gt_c'>";

                            $output .= "<div class='gt_form_panel_sub_heading gt_form_panel_sub_heading_alt'>".get_string('qualschoose', 'block_gradetracker')."</div>";

                            $output .= "<div>";

                                $output .= "<br><br>";

                                $output .= "<select id='gt_filter_qual_structure' class='gt_third_width'>";
                                    $output .= "<option value=''></option>";
                                    if ($allStructures)
                                    {
                                        foreach($allStructures as $structure)
                                        {
                                            $output .= "<option value='{$structure->getID()}'>{$structure->getName()}</option>";
                                        }
                                    }
                                $output .= "</select> ";
                                
                                $output .= "<select id='gt_filter_qual_level' class='gt_third_width'>";
                                    $output .= "<option value=''></option>";
                                    if ($allLevels)
                                    {
                                        foreach($allLevels as $level)
                                        {
                                            $output .= "<option value='{$level->getID()}'>{$level->getName()}</option>";
                                        }
                                    }
                                $output .= "</select> ";

                                $output .= "<select id='gt_filter_qual_subtype' class='gt_third_width'>";
                                    $output .= "<option value=''></option>";
                                    if ($allSubTypes)
                                    {
                                        foreach($allSubTypes as $subType)
                                        {
                                            $output .= "<option value='{$subType->getID()}'>{$subType->getName()}</option>";
                                        }
                                    }
                                $output .= "</select>";
                                
                                $output .= "<br><br>";

                                $output .= "<input type='text' id='gt_filter_qual_name' class='gt_80' placeholder='".get_string('name', 'block_gradetracker')."' /> ";
                                
                                $output .= "<a href='#' onclick='gtFilterQualSearch();return false;'>";
                                    $output .= "<img src='".$OUTPUT->pix_url('i/search')."' class='gt_middle' alt='search' />";
                                $output .= "</a>";

                                $output .= "<br>";
                                $output .= "<img src='".$OUTPUT->pix_url('i/loading_small')."' class='gt_hidden' id='gt_filter_quals_loading' />";
                                $output .= "<br>";

                                $output .= "<select id='gt_filter_quals' class='gt_qual_select' multiple='multiple'></select>";

                                $output .= "<br><br>";

                                $output .= "<p>";
                                    $output .= "<input type='button' class='gt_btn' onclick='gtQualPickerAdd();return false;' value='".get_string('add', 'block_gradetracker')."' />";
                                    $output .= "<a href='{$CFG->wwwroot}/blocks/gradetracker/config.php?view=quals&section=new' class='gt_btn' target='_blank'>".get_string('createnew', 'block_gradetracker')."</a>";
                                $output .= "</p>";

                            $output .= "</div>";

                        $output .= "</div>";

                    $output .= "</div>";
                                    
                    $output .= "<br class='gt_cl'><br>";
                    
                $output .= "</div>";
                
            break;
            
        }
        
        
        
        
        return $output;
        
    }
    
    
    
    /**
     * Create an object of FormElement
     * @param type $params
     */
    public static function create(\stdClass $params){
        
        $obj = new \GT\FormElement();
        
        if (isset($params->id)){
            $obj->setID($params->id);
        } else {
            $obj->setID( \gt_rand_str(10) );
        }
        
        if (isset($params->name)){
            $obj->setName($params->name);
        }
        
        if (isset($params->form)){
            $obj->setForm($params->form);
        }
        
        if (isset($params->type)){
            $obj->setType($params->type);
        }
        
        if (isset($params->options)){
            $obj->setOptions($params->options);
        }
        
        if (isset($params->validation)){
            $obj->setValidation($params->validation);
        }
        
        if (isset($params->value)){
            $obj->setValue($params->value);
        }
        
        
        return $obj;
        
    }
    
    /**
     * Update attribute records referencing an old field ID to the new ID
     * @global type $DB
     * @param type $form
     * @param type $oldID
     * @param type $newID
     * @return type
     */
    public static function updateAttributes($form, $oldID, $newID){
        
        global $DB;
        
        if ($form == 'qualification'){
            $table = 'bcgt_qual_attributes';
        } elseif ($form == 'unit'){
            $table = 'bcgt_unit_attributes';
        }
        
        $DB->execute("UPDATE {{$table}} SET attribute = ? WHERE attribute = ?", array("custom_{$newID}", "custom_{$oldID}"));
        $DB->execute("UPDATE {bcgt_qual_build_attributes} SET attribute = ? WHERE attribute = ?", array("default_{$newID}", "default_{$oldID}"));
        
    }
    
    
    
}
