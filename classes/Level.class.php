<?php
/**
 * Level
 *
 * The class for Qualification Levels, e.g. Level 1, Level 2, Level 3, etc...
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

class Level {
    
    private $id = false;
    private $name;
    private $shortName;
    private $ordernum;
    private $deleted = 0;
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_levels", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->name = $record->name;
                $this->shortName = $record->shortname;
                $this->ordernum = $record->ordernum;
                $this->deleted = $record->deleted;
                
            }
            
        }
        
    }
    
    /**
     * Is it a valid record from the DB?
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function isDeleted(){
        return ($this->deleted == 1);
    }
    
    /**
     * Get the id of the level
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Get the name of the level
     * @return type
     */
    public function getName(){
        return \gt_html($this->name);
    }
    
    /**
     * Get the short name of the level
     * @return type
     */
    public function getShortName(){
        return \gt_html($this->shortName);
    }
    
    /**
     * Get the order number of the level
     * @return type
     */
    public function getOrderNumber(){
        return $this->ordernum;
    }
    
    /**
     * Get the value of deleted
     * @return type
     */
    public function getDeleted(){
        return $this->deleted;
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    
    public function setID($id){
        $this->id = $id;
        return $this;
    }

    public function setName($name){
        $this->name = trim($name);
        return $this;
    }
    
    public function setShortName($name){
        $this->shortName = trim($name);
        return $this;
    }
    
    public function setOrderNum($num){
        $this->ordernum = trim($num);
        return $this;
    }
    
    public function setDeleted($val){
        $this->deleted = $val;
        return $this;
    }
    
    public function countQualifications(){
        
        // todo check if conn wants quals or qual_builds
        global $DB;
        $results = $DB->get_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
        $count = 0;
        foreach ($results as $result) {
            $count += $DB->count_records("bcgt_qualifications", array("buildid" => $result->id, "deleted" => 0));
        }
        return $count;
    }
    
    
    public function countQualificationBuilds(){
        global $DB;
        return $DB->count_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
    }
    
    
    public function delete(){
        
        global $DB;
        
        $this->deleted = 1;
        $this->save();
        
        $qual_builds = $DB->get_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
        foreach ($qual_builds as $build) {
            $qual_build = new \GT\QualificationBuild($build->id);
            $qual_build->delete();
        }
        
        
        return true;
        
    }
    
    /**
     * Save the level
     * @global type $DB
     * @return type
     */
    public function save(){
        
        global $DB;
        
        if ($this->isValid()){
            
            $obj = new \stdClass();
            $obj->id = $this->id;
            $obj->name = $this->name;
            $obj->shortname = $this->shortName;
            $obj->ordernum = $this->ordernum;
            $obj->deleted = $this->deleted;
            return $DB->update_record("bcgt_qual_levels", $obj);
            
        } else {
            
            $obj = new \stdClass();
            $obj->name = $this->name;
            $obj->shortname = $this->shortName;
            $obj->ordernum = $this->ordernum;
            $obj->deleted = 0;
            $this->id = $DB->insert_record("bcgt_qual_levels", $obj);
            return $this->id;
            
        }
        
    }
    
    /**
     * Check to make sure it has no errors
     * @return type
     */
    public function hasNoErrors(){
        
        if (empty($this->name)){
            $this->errors[] = get_string('errors:quallevels:name', 'block_gradetracker');
        }
        
        if (empty($this->shortName)){
            $this->errors[] = get_string('errors:quallevels:shortname', 'block_gradetracker');
        }
        
        if (!is_numeric($this->ordernum)){
            $this->errors[] = get_string('errors:quallevels:order', 'block_gradetracker');
        }
        
        return (!$this->errors);
        
    }
    
    public function loadPostData(){
        
        $name = $_POST['level_name'];
        $shortName = $_POST['level_shortname'];
        $order = $_POST['level_order'];
        $deleted = 0;
        
        if (isset($_POST['level_id'])){
            $this->setID($_POST['level_id']);
        }
        
        if (isset($_POST['level_deleted'])){
            $deleted = $_POST['level_deleted'];
        }
        
        $this->setName($name);
        $this->setShortName($shortName);
        $this->setOrderNum($order);
        $this->setDeleted($deleted);
        
    }
    
    
    
    /**
     * Get all the possible levels for a structure, based on the builds available
     * @global \GT\type $DB
     * @param type $structureID
     * @return \GT\Level
     */
    public static function getAllStructureLevels($structureID){
        
        global $DB;
        
        $return = array();
        $records = $DB->get_records_sql("SELECT DISTINCT levelid
                                         FROM {bcgt_qual_builds}
                                         WHERE structureid = ? AND deleted = 0", array($structureID));
        
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\Level($record->levelid);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get all the defined levels
     * @global type $DB
     * @return type
     */
    public static function getAllLevels(){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_qual_levels", array("deleted" => 0), "ordernum ASC, name ASC", "id");
        
        $return = array();
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\Level($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Find a level by its name
     * @global \GT\type $DB
     * @param type $name
     * @return type
     */
    public static function findByName($name){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_qual_levels", array("name" => $name, "deleted" => 0));
        return ($record) ? new \GT\Level($record->id) : false;
        
    }
       
    
}
