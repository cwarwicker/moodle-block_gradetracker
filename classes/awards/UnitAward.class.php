<?php

/**
 * UnitAward
 *
 * Class for dealing with Unit Awards
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

class UnitAward {
    
    private $id = false;
    private $gradingStructureID;
    private $name;
    private $shortname;
    private $points;
    private $pointsLower;
    private $pointsUpper;
    
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_unit_awards", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->gradingStructureID = $record->gradingstructureid;
                $this->name = $record->name;
                $this->shortname = $record->shortname;
                $this->points = $record->points;
                $this->pointsLower = $record->pointslower;
                $this->pointsUpper = $record->pointsupper;
                
            }
            
        }
        else
        {
            $this->name = get_string('notattempted', 'block_gradetracker');
            $this->shortname = get_string('na', 'block_gradetracker');
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Since units can only have met awards, just return if it's a valid award, so it must be met
     * @return type
     */
    public function isMet(){
        return ($this->isValid());
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function setID($id){
        $this->id = $id;
        return $this;
    }
    
    public function getGradingStructureID(){
        return $this->gradingStructureID;
    }
    
    public function setGradingStructureID($id){
        $this->gradingStructureID = $id;
        return $this;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function getShortName(){
        return $this->shortname;
    }
    
    public function setShortName($name){
        $this->shortname = trim($name);
        return $this;
    }
    
    public function getPoints(){
        return $this->points;
    }
    
    public function setPoints($points){
        $this->points = $points;
        return $this;
    }
    
    public function getPointsLower(){
        return $this->pointsLower;
    }
    
    public function setPointsLower($points){
        $this->pointsLower = $points;
        return $this;
    }
    
    public function getPointsUpper(){
        return $this->pointsUpper;
    }
    
    public function setPointsUpper($points){
        $this->pointsUpper = $points;
        return $this;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Check the award has no errors
     * @return type
     */
    public function hasNoErrors(){
        
        // Name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:gradestructures:awards:name', 'block_gradetracker');
        }
        
        // Shortname - If not set, set to first letter of name
        if (strlen($this->shortname) == 0){
            $this->shortname = strtoupper( substr($this->name, 0, 1) );
        }
        
        // Points
        if ($this->points == ''){
            $this->errors[] = get_string('errors:gradestructures:awards:points', 'block_gradetracker');
        }
        
        // Lower points
//        if ($this->pointsLower == ''){
//            $this->errors[] = get_string('errors:gradestructures:awards:pointslower', 'block_gradetracker');
//        }
//        
//        // Upper points
//        if ($this->pointsUpper == ''){
//            $this->errors[] = get_string('errors:gradestructures:awards:pointsupper', 'block_gradetracker');
//        }
        
        return (!$this->errors);
        
    }
    
    /**
     * Save the unit award
     * @global type $DB
     * @return boolean
     */
    public function save(){
        
        global $DB;
        
        $obj = new \stdClass();
        
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        
        $obj->gradingstructureid = $this->gradingStructureID;
        $obj->name = $this->name;
        $obj->shortname = $this->shortname;
        $obj->points = $this->points;
        $obj->pointslower = $this->pointsLower;
        $obj->pointsupper = $this->pointsUpper;
        
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_unit_awards", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_unit_awards", $obj);
            $result = $this->id;
        }
        
        if (!$result){
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
        
        
        return true;
        
        
    }
    
    
}
