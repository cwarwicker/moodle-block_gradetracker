<?php
/**
 * SubType
 *
 * The class for Qualification Sub Types, e.g. Diploma, Certificate, Award, etc...
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

class SubType {
    
    private $id = false;
    private $name;
    private $shortname;
    private $deleted = 0;
    
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_qual_subtypes", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->name = $record->name;
                $this->shortname = $record->shortname;
                $this->deleted = $record->deleted;
                
            }
            
        }
        
    }
    
    /**
     * Is it a valid DB record?
     * @return type
     */
    public function isValid(){
        return ($this->id !== false);
    }
    
    /**
     * Get the subtype id
     * @return type
     */
    public function getID(){
        return $this->id;
    }
    
    /**
     * Get the subtype name
     * @return type
     */
    public function getName(){
        return \gt_html($this->name);
    }
    
    /**
     * Get the subtype shortname
     * @return type
     */
    public function getShortName(){
        return \gt_html($this->shortname);
    }
    
    /**
     * Get the deleted value
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
        $this->shortname = trim($name);
        return $this;
    }
    
    public function setDeleted($val){
        $this->deleted = $val;
        return $this;
    }
    
    public function countQualifications(){
        
        global $DB;
        $results = $DB->get_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
        $count = 0;
        foreach ($results as $result) {
            $count += $DB->count_records("bcgt_qualifications", array("buildid" => $result->id, "deleted" => 0));
        }
        return $count;
    }
    
    
    public function countQualificationBuilds(){
        global $DB;
        return $DB->count_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
    }
    
    public function delete(){
        
        global $DB;
        
        $this->deleted = 1;
        $this->save();
        
        $qual_builds = $DB->get_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
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
            $obj->shortname = $this->shortname;
            $obj->deleted = $this->deleted;
            return $DB->update_record("bcgt_qual_subtypes", $obj);
            
        } else {
            
            $obj = new \stdClass();
            $obj->name = $this->name;
            $obj->shortname = $this->shortname;
            $obj->deleted = 0;
            $this->id = $DB->insert_record("bcgt_qual_subtypes", $obj);
            return $this->id;
            
        }
        
    }
    
    /**
     * Check to make sure it has no errors
     * @return type
     */
    public function hasNoErrors(){
        
        if (empty($this->name)){
            $this->errors[] = get_string('errors:qualsubtype:name', 'block_gradetracker');
        }
        
        if (empty($this->shortname)){
            $this->errors[] = get_string('errors:qualsubtype:shortname', 'block_gradetracker');
        }
        
        return (!$this->errors);
        
    }
    
    public function loadPostData(){
        
        $name = $_POST['subtype_name'];
        $shortName = $_POST['subtype_shortname'];
        $deleted = 0;
        
        if (isset($_POST['subtype_id'])){
            $this->setID($_POST['subtype_id']);
        }
        
        if (isset($_POST['subtype_deleted'])){
            $deleted = $_POST['subtype_deleted'];
        }
        
        $this->setName($name);
        $this->setShortName($shortName);
        $this->setDeleted($deleted);
        
    }
    
    /**
     * Get all the defined subtypes
     * @global type $DB
     * @return type
     */
    public static function getAllSubTypes(){
        
        global $DB;
        
        $records = $DB->get_records("bcgt_qual_subtypes", array("deleted" => 0), "name ASC", "id");
        
        $return = array();
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\SubType($record->id);
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
        
        $record = $DB->get_record("bcgt_qual_subtypes", array("name" => $name, "deleted" => 0));
        return ($record) ? new \GT\SubType($record->id) : false;
        
    }
    
    
    
}
