<?php
/**
 * Log
 *
 * This class allows you to log gradetracker-related information and searching existing logs
 * 
 * @copyright 2017 Bedford College
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

class Log
{
        
    // Context's
    const GT_LOG_CONTEXT_GRID = 'grid';
    const GT_LOG_CONTEXT_CONFIG = 'config';
    
    // Details/Actions
    const GT_LOG_DETAILS_VIEWED_STUDENT_GRID = 'viewed student grid';
    const GT_LOG_DETAILS_VIEWED_UNIT_GRID = 'viewed unit grid';
    const GT_LOG_DETAILS_VIEWED_CLASS_GRID = 'viewed class grid';
    const GT_LOG_DETAILS_UPDATED_USER_CRIT = 'updated user criterion';
    const GT_LOG_DETAILS_UPDATED_USER_UNIT = 'updated user unit';
    const GT_LOG_DETAILS_UPDATED_USER_ASS = 'updated user assessment';
    const GT_LOG_DETAILS_UPDATED_USER_ATT = 'updated user attribute';
    const GT_LOG_DETAILS_UPDATED_USER_RANGE = 'updated user range';
    const GT_LOG_DETAILS_UPDATED_USER_GRADE = 'updated user grade';
    const GT_LOG_DETAILS_AUTO_UPDATED_USER_GRADE = 'automatically calculated user grade';
    const GT_LOG_DETAILS_AUTO_UPDATED_USER_AWARD = 'automatically calculated user award';
    const GT_LOG_DETAILS_IMPORTED_STUDENT_GRID = 'imported student grid datasheet';
    const GT_LOG_DETAILS_IMPORTED_UNIT_GRID = 'imported unit grid datasheet';
    const GT_LOG_DETAILS_IMPORTED_CLASS_GRID = 'imported class grid datasheet';
    
    const GT_LOG_DETAILS_UPDATED_PLUGIN_GENERAL_SETTINGS = 'updated plugin settings - general';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_QUAL_SETTINGS = 'updated plugin settings - qualification';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_UNIT_SETTINGS = 'updated plugin settings - unit';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_CRITERIA_SETTINGS = 'updated plugin settings - criteria';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_GRID_SETTINGS = 'updated plugin settings - grid';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_USER_SETTINGS = 'updated plugin settings - user';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_GRADE_SETTINGS = 'updated plugin settings - grade';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_ASSESSMENTS_SETTINGS = 'updated plugin settings - assessment';
    const GT_LOG_DETAILS_UPDATED_PLUGIN_REPORTING_SETTINGS = 'updated plugin settings - report';
    
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_STRUCTURE = 'updated qualification structure';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_BUILD = 'updated qualification build';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_UNIT_GRADING_STRUCTURE = 'updated unit grading structure';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_CRITERIA_GRADING_STRUCTURE = 'updated criteria grading structure';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_LEVELS = 'updated qualification structure levels';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_SUBTYPES = 'updated qualification structure subtypes';
    const GT_LOG_DETAILS_UPDATED_STRUCTURE_QOE = 'updated quals on entry';
    
    const GT_LOG_DETAILS_CREATED_QUALIFICATION = 'created qualification';
    const GT_LOG_DETAILS_UPDATED_QUALIFICATION = 'updated qualification';
    const GT_LOG_DETAILS_DUPLICATED_QUALIFICATION = 'duplicated qualification';
    const GT_LOG_DETAILS_DELETED_QUALIFICATION = 'deleted qualification';
    const GT_LOG_DETAILS_RESTORED_QUALIFICATION = 'restored qualification';
    
    const GT_LOG_DETAILS_CREATED_UNIT = 'created unit';
    const GT_LOG_DETAILS_UPDATED_UNIT = 'updated unit';
    const GT_LOG_DETAILS_DUPLICATED_UNIT = 'duplicated unit';
    const GT_LOG_DETAILS_DELETED_UNIT = 'deleted unit';
    const GT_LOG_DETAILS_RESTORED_UNIT = 'restored unit';
    
    const GT_LOG_DETAILS_CREATED_ASSESSMENT = 'created assessment';
    const GT_LOG_DETAILS_UPDATED_ASSESSMENT = 'updated assessment';
    const GT_LOG_DETAILS_DELETED_ASSESSMENT = 'deleted assessment';
    
    const GT_LOG_DETAILS_CREATED_MODULE_LINK = 'created module link';
    const GT_LOG_DETAILS_UPDATED_MODULE_LINK = 'updated module link';
    const GT_LOG_DETAILS_DELETED_MODULE_LINK = 'deleted module link';
    
    const GT_LOG_DETAILS_UPDATED_DATA_MAPPINGS = 'updated data transfer mappings';
    const GT_LOG_DETAILS_TRANSFERRED_OLD_SPECS = 'transferred old qualification specifications';
    const GT_LOG_DETAILS_TRANSFERRED_OLD_DATA = 'transferred old student data';
    const GT_LOG_DETAILS_IMPORTED_QOE = 'imported quals on entry data';
    const GT_LOG_DETAILS_IMPORTED_AVGGCSE = 'imported avg gcse data';
    const GT_LOG_DETAILS_IMPORTED_TARGET_GRADES = 'imported target grade data';
    const GT_LOG_DETAILS_IMPORTED_ASP_GRADES = 'imported aspirational grade data';
    const GT_LOG_DETAILS_IMPORTED_CETA_GRADES = 'imported ceta grade data';
    const GT_LOG_DETAILS_IMPORTED_ASSESSMENT_GRADES = 'imported assessment grade data';
    const GT_LOG_DETAILS_IMPORTED_COEFFICIENTS = 'imported weighted coefficients data';
    
    const GT_LOG_DETAILS_UPDATED_COURSE_QUALS = 'updated qualifications on course';
    const GT_LOG_DETAILS_UPDATED_COURSE_USER_QUALS = 'updated user qualifications';
    const GT_LOG_DETAILS_UPDATED_COURSE_USER_UNITS = 'updated user units';
    const GT_LOG_DETAILS_UPDATED_COURSE_ACTIVITY_LINKS = 'updated activity links';
    const GT_LOG_DETAILS_DELETED_COURSE_ACTIVTY_LINK = 'deleted activity link';
    
    // Attribute variable names
    const GT_LOG_ATT_QUALID = 'qualID';
    const GT_LOG_ATT_UNITID = 'unitID';
    const GT_LOG_ATT_ASSID = 'assID';
    const GT_LOG_ATT_CRITID = 'critID';
    const GT_LOG_ATT_RANGEID = 'rangeID';
    const GT_LOG_ATT_COURSEID = 'courseID';
    const GT_LOG_ATT_STUDID = 'studentID';
    
    
    
    // These variables are set automatically and not changed depending on the log
    public $id = false;
    public $timestamp;
    public $userid;
    public $userip;
    public $backtrace;
    
    // These variables are changed depending on the log
    public $beforejson = array(); # Before and After should contain all the key bits which may have changed
    public $afterjson = array(); # Before and After should contain all the key bits which may have changed
    public $details;
    public $context;
    public $attributes = array(); # These are the ones you are going to search on, so should only be IDs, like studentID, qualID, unitID, etc...
    
    // These other dynamic variables, such as a user object from the userid
    public $user;
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            
            $record = $DB->get_record("bcgt_logs", array("id" => $id));
            if ($record)
            {

                $this->id = $record->id;
                $this->timestamp = $record->timestamp;
                $this->userid = $record->userid;
                $this->userip = $record->userip;
                $this->backtrace = json_decode($record->backtrace);
                $this->beforejson = json_decode($record->beforejson);
                $this->afterjson = json_decode($record->afterjson);
                $this->details = $record->details;
                $this->context = $record->context;                

                $atts = $DB->get_records("bcgt_log_attributes", array("logid" => $this->id));
                if ($atts)
                {
                    foreach($atts as $att)
                    {
                        $this->attributes[$att->attributename] = $att->attributevalue;
                    }
                }

                $this->user = new \GT\User($this->userid);

            }
            
        }
        
    }
    
   
    /**
     * Add an attribute to the array
     * @param type $name
     * @param type $value
     * @return \GT\Log
     */
    public function addAttribute($name, $value){
        $this->attributes[$name] = $value;
        return $this;
    }
    
    /**
     * Save the log and its attributes to the database
     * @global type $DB
     * @global type $USER
     * @return type
     */
    public function save(){
        
        global $DB, $USER;
        
        $obj = new \stdClass();
        $obj->timestamp = time();
        $obj->userid = $USER->id;
        $obj->userip = getremoteaddr();
        $obj->backtrace = json_encode( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) );
        $obj->beforejson = ($this->beforejson) ? json_encode($this->beforejson) : null;
        $obj->afterjson = ($this->afterjson) ? json_encode($this->afterjson) : null;
        $obj->details = $this->details;
        $obj->context = $this->context;
        
        $logID = $DB->insert_record("bcgt_logs", $obj);
        
        // Attributes
        if ($logID && $this->attributes)
        {
            foreach($this->attributes as $name => $value)
            {
                if (!is_null($value))
                {
                    $obj = new \stdClass();
                    $obj->logid = $logID;
                    $obj->attributename = $name;
                    $obj->attributevalue = $value;
                    $DB->insert_record("bcgt_log_attributes", $obj);
                }
            }
        }
        
        return $logID;
        
    }
    
    /**
     * Convert the log to a simple array with all the relevant details, so we can either display this on the page, or use it to create a downloadable csv
     * @return type
     */
    public function toCSV(){
        
        $row = array();
        $row['id'] = $this->id;
        $row['time'] = date('d-m-Y, H:i:s', $this->timestamp);
        $row['user'] = ($this->user) ? $this->user->getDisplayName() : '-';
        $row['context'] = $this->context;
        $row['details'] = $this->details;
        
        // Attributes
        $row['attributes'] = array();
        
        foreach($this->attributes as $att => $val)
        {
            $row['attributes'][$att] = $this->getAttributeFromValue($att, $val);
        }
        
        return $row;
        
    }
    
    /**
     * Get the attribute name from its id, e.g. a qual name or a unit name
     * @param type $att
     * @param type $val
     * @return string
     */
    public function getAttributeFromValue($att, $val){
        
        if ($att == self::GT_LOG_ATT_ASSID){
            
            $ass = new \GT\Assessment($val);
            return ($ass->isValid()) ? $ass->getName() : '-';
            
        } elseif ($att == self::GT_LOG_ATT_COURSEID){
            
            $course = new \GT\Course($val);
            return ($course->isValid()) ? \gt_html($course->getName()) : '-';
            
        } elseif ($att == self::GT_LOG_ATT_CRITID){
            
            $crit = \GT\Criterion::load($val);
            return ($crit && $crit->isValid()) ? \gt_html($crit->getName()) : '-';
            
        } elseif ($att == self::GT_LOG_ATT_QUALID){
            
            $qual = new \GT\Qualification($val);
            return ($qual->isValid()) ? $qual->getDisplayName() : '-';
            
        } elseif ($att == self::GT_LOG_ATT_RANGEID){
            
            $crit = \GT\Criterion::load($val);
            return ($crit && $crit->isValid()) ? $crit->getName() : '-';
            
        } elseif ($att == self::GT_LOG_ATT_STUDID){
            
            $student = new \GT\User($val);
            return $student->getDisplayName();
            
        } elseif ($att == self::GT_LOG_ATT_UNITID){
            
            $unit = new \GT\Unit($val);
            return ($unit->isValid()) ? $unit->getDisplayName() : '-';
            
        } else {
            return '-';
        }
        
    }
    
    
    /**
     * Get the most n recent logs
     * @global \GT\type $DB
     * @param type $limit
     * @return \GT\Log
     */
    public static function getRecentLogs($limit = 20){
        
        global $DB;
        
        $return = array();
        
        $records = $DB->get_records("bcgt_logs", array(), "id DESC", "id", 0, $limit);
        if ($records)
        {
            foreach($records as $record)
            {
                $return[] = new \GT\Log($record->id);
            }
        }
        
        return $return;
        
    }
    
    /**
     * Search for logs
     * @global \GT\type $DB
     * @param type $params
     * @return type
     */
    public static function search($params){
        
        global $DB;
        
        $return = array();
        $sqlParams = array();
        
        $sql = array('select' => array(), 'from' => '', 'join' => array(), 'where' => array(), 'order' => '');
        $sql['select'][] = 'l.id';        
        $sql['from'] = '{bcgt_logs} l';
        $sql['join'][] = 'LEFT JOIN {user} u ON u.id = l.userid';
        
        // Attributes
        $i = 1;
        if (isset($params['atts']))
        {
            foreach( $params['atts'] as $att => $val )
            {
                
                if ($att == 'COURSEID' && $val == 'OTHER'){
                    continue;
                }
                
                // if we are using a course shortname, need to convert that to an id
                elseif ($att == 'COURSENAME'){
                    $att = 'COURSEID';
                }
                
                $sql['join'][] = "INNER JOIN {bcgt_log_attributes} la{$i} ON la{$i}.logid = l.id AND la{$i}.attributename = ?";
                $sqlParams[] = $att;
                $i++;
                
            }
        }
        
        $sql['where'][] = 'l.id > 0';
        $sql['order'] = 'l.timestamp DESC, l.id DESC';
        
        
        // Params
        // Log action/details
        if (isset($params['details']) && $params['details'] != ''){
            $sql['where'][] = "l.details = ?";
            $sqlParams[] = $params['details'];
        }
        
        // User who logged the action
        if (isset($params['user']) && trim($params['user']) != ''){
            $sql['where'][] = "u.username = ?";
            $sqlParams[] = trim($params['user']);
        }
        
        // Dates
        if (isset($params['date_from']) && $params['date_from'] != false){
            $sql['where'][] = "l.timestamp >= ?";
            $sqlParams[] = strtotime($params['date_from'] . ' 00:00:00');
        }
        
        if (isset($params['date_to']) && $params['date_to'] != false){
            $sql['where'][] = "l.timestamp <= ?";
            $sqlParams[] = strtotime($params['date_to'] . ' 23:59:59');
        }
        
        // Attributes
        $i = 1;
        if (isset($params['atts']))
        {
            foreach( $params['atts'] as $att => $val )
            {

                // If it's a student, need to get the id
                if ($att == 'STUDENTID'){
                    $usr = \GT\User::byUsername($val);
                    if (!$usr){
                        continue;
                    }
                    $val = $usr->id; 
                }
                
                // If we are using a course shortname, need to skip the courseID of "OTHER"
                elseif ($att == 'COURSEID' && $val == 'OTHER'){
                    continue;
                }
                
                // if we are using a course shortname, need to convert that to an id
                elseif ($att == 'COURSENAME'){
                    $crs = \GT\Course::retrieve('shortname', $val);
                    if (!$crs){
                        continue;
                    }
                    $val = $crs->id;
                }

                $sql['where'][] = "la{$i}.attributevalue = ?";
                $sqlParams[] = $val;
                $i++;

            }
        }
        
        $fullSQL = \gt_convert_to_sql($sql);
                
        $records = $DB->get_records_sql($fullSQL, $sqlParams);
        if ($records)
        {
            foreach($records as $record)
            {
                $log = new \GT\Log($record->id); 
                if ($log->id)
                {
                    $return[$log->id] = $log->toCSV();
                }
            }
        }
        
        return $return;
        
    }
    
    
    
    
}