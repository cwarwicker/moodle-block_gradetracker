<?php

/**
 * CriteriaAward
 *
 * Class for dealing with Criteria Awards
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

class CriteriaAward {
    
    private static $specialVals = array('LATE', 'WS', 'WNS', 'NO');
    
    private $id = false;
    private $gradingStructureID;
    private $name;
    private $shortname;
    private $img;
    private $specialVal;
    private $points;
    private $pointsLower;
    private $pointsUpper;
    private $met;
    
    private $imgFile;
    private $imgData;
    private $errors = array();
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_criteria_awards", array("id" => $id));
            if ($record)
            {
                
                $this->id = $record->id;
                $this->gradingStructureID = $record->gradingstructureid;
                $this->name = $record->name;
                $this->shortname = $record->shortname;
                $this->img = $record->img;
                $this->specialVal = $record->specialval;
                $this->points = $record->points;
                $this->pointsLower = $record->pointslower;
                $this->pointsUpper = $record->pointsupper;
                $this->met = $record->met;
                
            }
            
        }
        
        // If it's not valid, load in the N/A bits
        if (!$this->id)
        {
            $this->name = get_string('notattempted', 'block_gradetracker');
            $this->shortname = get_string('na', 'block_gradetracker');
            $this->img = false;
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false);
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
    
    public function getGradingStructure(){
        return new \GT\CriteriaAwardStructure($this->getGradingStructureID());
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($name){
        $this->name = trim($name);
        $this->name = ucwords($this->name);
        return $this;
    }
    
    /**
     * Use this as the Name if the award is not valid
     * @param type $name
     */
    public function setDefaultName($name){
        if (!$this->isValid()){
            $this->name = $name;
            $this->shortname = $name;
        }
    }
    
    public function getShortName(){
        return $this->shortname;
    }
    
    public function setShortName($name){
        $this->shortname = trim($name);
        return $this;
    }
    
    public function getImage(){
       
        if (isset($this->iconTmp)){
            return "tmp//" . $this->iconTmp;
        } else {
            return $this->img;
        }
        
    }
    
    public function setImage($img){
        $this->img = $img;
        return $this;
    }
    
    public function getImageFile(){
        return $this->imgFile;
    }
    
    public function setImageFile($file){
        $this->imgFile = $file;
        return $this;
    }
    
    public function getImageData(){
        return $this->imgData;
    }
    
    public function setImageData($data){
        $this->imgData = $data;
        return $this;
    }
       
    /**
     * Get the url for the award's icon
     * @return type
     */
    public function getImageURL(){
        
        global $CFG;
                
        // If we have a tmp file, e.g. if we've got an error so we haven't got as far as saving img properly yet
        if (isset($this->iconTmp)){
            return $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . gt_get_data_path_code( \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp );
        }
        
        
        // Otherwise
        if (!is_null($this->img) && strlen($this->img) > 0 && file_exists( \GT\GradeTracker::dataroot() . '/img/awards/' . $this->gradingStructureID . '/' . $this->img ))
        {
            return $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . gt_get_data_path_code( \GT\GradeTracker::dataroot() . '/img/awards/' . $this->gradingStructureID . '/' . $this->img ) ;
        }
        elseif ($this->img === false)
        {
            return $CFG->wwwroot . '/blocks/gradetracker/pix/symbols/default/na.png';
        }
        else
        {
            return $CFG->wwwroot . '/blocks/gradetracker/pix/no_image.jpg';

        }

    }
    
    /**
     * Get the moodledata path to the actual file
     * @return type
     */
    public function getImagePath(){
        return \GT\GradeTracker::dataroot() . '/img/awards/' . $this->gradingStructureID . '/' . $this->img;
    }
    
    public function getSpecialVal(){
        return $this->specialVal;
    }
    
    public function setSpecialVal($val){
        $this->specialVal = $val;
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
    
    public function getMet(){
        return $this->met;
    }
    
    public function setMet($val){
        $this->met = $val;
        return $this;
    }
    
    public function isMet(){
        return ($this->met == 1);
    }
    
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Check the award has no errors
     * @return type
     */
    public function hasNoErrors(){
        
        global $CFG;
        
        // Name
        if (strlen($this->name) == 0){
            $this->errors[] = get_string('errors:gradestructures:awards:name', 'block_gradetracker');
        }
        
        // Shortname - If not set, set to first letter of name
        if (strlen($this->shortname) == 0){
            $this->errors[] = get_string('errors:gradestructures:awards:shortname', 'block_gradetracker') . ' - ' . $this->name;
        }
        
        // Points
        if ($this->points == '' || !is_numeric($this->points)){
            $this->points = 0;
        }
        
        // Lower points
        if ($this->pointsLower != '' && !is_numeric($this->pointsLower)){
            $this->errors[] = get_string('errors:gradestructures:awards:pointslower', 'block_gradetracker') . ' - ' . $this->name;
        }
        
        // Upper points
        if ($this->pointsUpper != '' && !is_numeric($this->pointsUpper)){
            $this->errors[] = get_string('errors:gradestructures:awards:pointsupper', 'block_gradetracker') . ' - ' . $this->name;
        }
                
        // Check icon is valid image
        if (isset($this->imgFile) && $this->imgFile['size'] > 0)
        {
            $Upload = new \GT\Upload();
            $Upload->setFile($this->imgFile);
            $Upload->setMimeTypes( array('image/png', 'image/jpeg', 'image/bmp', 'image/gif') );
            $Upload->setUploadDir("tmp");
            $result = $Upload->doUpload();
            if ($result['success'] === true){
                $this->iconTmp = $Upload->getFileName();
                \gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp );
            } else {
                $this->errors[] = $result['error'] . ' - ' . $this->name;
            }
                        
        }
        
        // If we are importing XML, the image will be a data string
        elseif (isset($this->imgData) && strlen($this->imgData) > 0)
        {
            
             // Make dataroot tmp directory
            if (!is_dir(\GT\GradeTracker::dataroot() . '/tmp/')){
                $result = mkdir(\GT\GradeTracker::dataroot() . '/tmp/', $CFG->directorypermissions);
            }
            
            $ext = \gt_get_image_ext_from_base64($this->imgData);
            $name = preg_replace("/[^a-z0-9]/i", '', $this->name);
            $this->iconTmp = 'import-icon-' . time() . '-' . $name . '.' . $ext;
            
            // Check if this already exists (it may do, as in GCSE A* will be changed to A by this preg_replace
            // and then it will conflict with the A grade image
            while( file_exists(\GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp) ){
                $this->iconTmp = 'import-icon-' . time() . '-' . $name . '_.' . $ext;
            }
            
            $result = \gt_save_base64_image($this->imgData, \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp);
            if ($result){
                \gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp );
            } else {
                $this->errors[] = get_string('errors:save:file', 'block_gradetracker') . ' - ' . $this->name;
            }
        }
        

        
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
        $obj->specialval = $this->specialVal;
        $obj->points = $this->points;
        $obj->pointslower = $this->pointsLower;
        $obj->pointsupper = $this->pointsUpper;
        $obj->met = $this->met;
                
        // Image
        if (isset($this->iconTmp)){
                        
            // Move from tmp to qual structure directory
            if (\gt_save_file(\GT\GradeTracker::dataroot() . '/tmp/' . $this->iconTmp, 'img/awards/' . $this->gradingStructureID, $this->iconTmp, false)){
                
                $this->img = $this->iconTmp;
                $obj->img = $this->img;
                \gt_create_data_path_code( \GT\GradeTracker::dataroot() . '/img/awards/' . $this->gradingStructureID . '/' . $this->img );
                unset($this->iconTmp);
                
            }
            
        }
        
        if ($this->isValid()){
            $result = $DB->update_record("bcgt_criteria_awards", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_criteria_awards", $obj);
            $result = $this->id;
        }
        
        if (!$result){
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }
        
        
        return true;
        
        
    }
    
    /**
     * Delete the criteria award
     * @global \GT\type $DB
     * @return type
     */
    public function delete(){
        
        global $DB;
        return $DB->delete_records("bcgt_criteria_awards", array("id" => $this->id));        
        
    }
    
    /**
     * Get the array of supported special vals which do things, e.g. LATE, WS, WNS, etc...
     * @return type
     */
    public static function getSupportedSpecialVals(){
        sort(self::$specialVals);
        return self::$specialVals;
    }
    
    /**
     * Get the supported grading types
     * These define what we are actually doing with the grading structure chosen
     * "NORMAL" means we just have a drop-down of values, or checkbox where relevant
     * "DATE" means you have a datepicker and when that is updated it sets the criterion 
     *        to the "met" value of the grading structure. The structure must have ONE met value for this to work.
     * @return array
     */
    public static function getSupportedGradingTypes(){
    
        return array("NORMAL", "DATE");
        
    }
    
    public static function getDistinctNamesNonMet(){
        
        global $DB;
        
        $records = $DB->get_records_sql("SELECT DISTINCT name, shortname FROM {bcgt_criteria_awards} WHERE met = 0 ORDER BY shortname");
        return $records;
        
    }
    
    
}
