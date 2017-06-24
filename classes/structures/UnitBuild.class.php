<?php
/**
 * UnitBuild
 *
 * This is not a real object, there is no actual UnitBuild
 * 
 * This is instead used as a class to define a static method for getting the attributes for units
 * based on their structureID & levelID combination, which for the sake of consistency we are
 * calling a Unit Build here
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

class UnitBuild {
   
    private $structureID;
    private $levelID;
    
    public function setStructureID($id){
        $this->structureID = $id;
        return $this;
    }
    
    public function setLevelID($id){
        $this->levelID = $id;
        return $this;
    }
    
    /**
     * Get an attribute for this combination
     * @global type $DB
     * @param type $attribute
     * @return boolean
     */
    public function getAttribute($attribute){
        
        global $DB;
        
        if (is_null($this->structureID) || is_null($this->levelID)) return false;
        
        $record = $DB->get_record("bcgt_unit_build_attributes", array("qualstructureid" => $this->structureID, "levelid" => $this->levelID, "attribute" => $attribute));
        return ($record) ? $record->value : false;
        
    }
    
    /**
     * Update an attribute
     * @global \GT\type $DB
     * @param type $attribute
     * @param type $value
     */
    public function updateAttribute($attribute, $value){
        
        global $DB;
                
        $check = $DB->get_record("bcgt_unit_build_attributes", array("qualstructureid" => $this->structureID, "levelid" => $this->levelID, "attribute" => $attribute));
        if ($check)
        {
            $check->value = $value;
            $DB->update_record("bcgt_unit_build_attributes", $check);
        }
        else
        {
            $ins = new \stdClass();
            $ins->qualstructureid = $this->structureID;
            $ins->levelid = $this->levelID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $DB->insert_record("bcgt_unit_build_attributes", $ins);
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
        $records = $DB->get_records_select("bcgt_unit_build_attributes", "qualstructureid = ? AND levelid = ? AND attribute LIKE 'default_%'", array($this->structureID, $this->levelID));
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
     * Load the structure id and level id into the object
     * @param type $structureID
     * @param type $levelID
     * @return \GT\UnitBuild
     */
    public static function load($structureID, $levelID){
        
        $obj = new \GT\UnitBuild();
        $obj->setStructureID($structureID);
        $obj->setLevelID($levelID);
        return $obj;
        
    }
    
}
