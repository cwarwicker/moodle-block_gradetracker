<?php
/**
 * Feature
 *
 * The class that defines a level of a qualification structure
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

class QualificationStructureLevel {
    
    private $id = false;
    private $name;
    private $icon;
    private $minSubLevels;
    private $maxSubLevels;

    /**
     * Construct the feature object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false, $name = false) {
        
        global $DB;
        
        $record = false;
        
        if ($id){
            $record = $DB->get_record("bcgt_qual_structure_levels", array("id" => $id));
        } elseif ($name){
            $record = $DB->get_record("bcgt_qual_structure_levels", array("name" => $name));
        }
        
        if ($record){

            $this->id = $record->id;
            $this->name = $record->name;
            $this->minSubLevels = $record->minsublevels;
            $this->maxSubLevels = $record->maxsublevels;

        }
        
        return $this;
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getMinSubLevels(){
        return $this->minSubLevels;
    }
    
    public function getMaxSubLevels(){
        return $this->maxSubLevels;
    }
        
    /**
     * Get a qual structure level by its name
     * @global \GT\type $DB
     * @param type $name
     * @return type
     */
    public static function getByName($name)
    {
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qual_structure_levels", array("name" => $name), "id");
        return ($record) ? new \GT\QualificationStructureLevel($record->id) : false;
        
    }
    
}
