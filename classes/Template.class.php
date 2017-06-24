<?php

/**
 * Class for simple HTML templating
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

/**
 * 
 */
class Template {
    
    private $variables;
    private $output;
    
    /**
     * Construct the template and set the global variables all templates will need access to
     * @global type $CFG
     * @global type $OUTPUT
     * @global type $USER
     */
    public function __construct() {
        
        global $CFG, $OUTPUT, $USER;
        
        $string = get_string_manager()->load_component_strings('block_gradetracker', $CFG->lang);
        $this->loadStringsForJS();
        
        $this->final = array();
        $this->variables = array();
        $this->output = '';
        $this->set("string", $string);
        $this->set("CFG", $CFG);
        $this->set("OUTPUT", $OUTPUT);
        $this->set("USER", $USER);
        
    }
    
    
    /**
     * Set a variable to be used in the template
     * @param type $var
     * @param type $val
     * @return \GT\Template
     */
    public function set($var, $val, $final = false)
    {
        
        // If already set as a final variable, can't do it
        if (in_array($var, $this->final)){
            return $this;
        }
        
        $this->variables[$var] = $val;
        if ($final){
            $this->final[] = $var;
        }
        
        return $this;
        
    }
    
    /**
     * Check if variable has already been set
     * @param type $var
     * @return type
     */
    public function exists($var)
    {
        return (isset($this->variables[$var]));
    }
    
    /**
     * Get the output if we don't want to call display() and instead use it some other way
     * @return type
     */
    public function getOutput()
    {
        return $this->output;
    }
    
    /**
     * Get all variables set in the template
     * @return type
     */
    public function getVars()
    {
        return $this->variables;
    }
    
    public function clearVars(){
        $this->variables = array();
        return $this;
    }
    
    public function loadStringsForJS(){
        
        global $CFG, $PAGE;
        
        $str = file_get_contents($CFG->dirroot . '/blocks/gradetracker/lang/js_strings.txt');
        if ($str){
            $keys = preg_split("/\\r\\n|\\r|\\n/", $str);
            $PAGE->requires->strings_for_js( $keys, 'block_gradetracker' );
        }
        
    }
    
    /**
     * Load a template file
     * @param type $template
     * @return type
     * @throws \GT\GTException
     */
    public function load($template)
    {
                
        global $CFG;
        
        // Reset the output
        $this->output = ''; 
                        
        // Are we using custom templating?
        $GT = new \GT\GradeTracker();
        if ($GT->getSetting('use_custom_templating') == 1){
            
            // Strip the standard path from it and see if one exists in Moodledata for this
            $stripped = str_replace($CFG->dirroot . '/blocks/gradetracker/tpl/', '', $template);
            
            // If it does, load that instead
            if (file_exists( $GT::dataroot() . '/tpl/' . $stripped )){
                $template = $GT::dataroot() . '/tpl/' . $stripped;
            }
            
        }
        
        // If the file doesn't exist, throw an exception
        if (!file_exists($template)){
            throw new \GT\GTException( get_string('template', 'block_gradetracker'), get_string('filenotfound', 'block_gradetracker'), $template, get_string('createfileorchangepath', 'block_gradetracker'));
        }
        
        // Extract any variables into the template
        if (!empty($this->variables)){
            extract($this->variables);
        }
                
        $this->set("this", $this);
        
        flush();
        ob_start();
            include $template;
        $output = ob_get_clean();
        
        $this->output = $output;
        return $this->output;        
        
    }
    
    /**
     * Echo the template file
     */
    public function display()
    {
        echo $this->output;
    }
    
}