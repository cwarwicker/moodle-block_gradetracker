<?php
/**
 * User
 *
 * This class deals with Moodle Users, and any methods relating them to the Grade Tracker
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

class OldLog {
    
    // Context's
    const GT_LOG_CONTEXT_GRID = 'grid';
    const GT_LOG_CONTEXT_CONFIG = 'config';
    
    
    // new
    const GT_LOG_DETAILS_VIEWED_STUDENT_GRID = 'viewed student grid';
    const GT_LOG_DETAILS_VIEWED_UNIT_GRID = 'viewed unit grid';
    const GT_LOG_DETAILS_VIEWED_CLASS_GRID = 'viewed class grid';
    // new
    
    
    const GT_LOG_DETAILS_UPDATED_USER_CRIT = 'updated user criterion';
    const GT_LOG_DETAILS_UPDATED_USER_UNIT = 'updated user unit';
    const GT_LOG_DETAILS_UPDATED_USER_ASS = 'updated user assessment';
    const GT_LOG_DETAILS_UPDATED_USER_ATT = 'updated user attribute';
    const GT_LOG_DETAILS_UPDATED_USER_RANGE = 'updated user range';
    const GT_LOG_DETAILS_UPDATED_USER_GRADE = 'updated user grade';
    const GT_LOG_DETAILS_IMPORTED_GRID = 'imported grid datasheet';
    
    const GT_LOG_DETAILS_SAVED_CONFIG = 'saved configuration settings';
    
    public $id = false;
    public $user = false;
    public $attributes = array();
    
    public function __construct($id = false){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_logs", array("id" => $id));
        if ($record)
        {
            
            $props = get_object_vars($record);
            foreach($props as $prop => $val)
            {
                $this->$prop = $val;
            }
            
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
    
   
    
    
    public function getDescription(){
        
        global $CFG;
        
        if (!$this->id || !$this->user || !$this->user->isValid()){
            return false;
        }
                        
        // Minimum load
        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->min();
        
        $str = '?';
                
        switch($this->details)
        {
            
            // Update a user criterion
            case self::GT_LOG_DETAILS_UPDATED_USER_CRIT:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['unitID']) && isset($this->attributes['critID']) && isset($this->attributes['studentID']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $unit = new \GT\Unit($this->attributes['unitID']);
                    $crit = \GT\Criterion::load($this->attributes['critID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    $award = new \GT\CriteriaAward(@$this->attributes['awardID']);
                    
                    // Don't display long comments by default
                    if (isset($this->attributes['comments']) && strlen($this->attributes['comments']) > 120){
                        
                        // Split it
                        $split = array( substr($this->attributes['comments'], 0, 100), substr($this->attributes['comments'], 100) );
                        $split[0] = \gt_html($split[0]) . '<span class="gt_read_less_more_link_'.$this->id.'">... <a href="#" onclick="$(\'#gt_read_more_'.$this->id.'\').toggle();$(\'.gt_read_less_more_link_'.$this->id.'\').toggle();return false;"><small>['.get_string('readmore', 'block_gradetracker').']</small></a></span> ';
                        $split[1] = '<span id="gt_read_more_'.$this->id.'" style="display:none;">' . \gt_html($split[1]) . '</span> ';
                        $split[1] .= '<span class="gt_read_less_more_link_'.$this->id.'" style="display:none;"><a href="#" onclick="$(\'#gt_read_more_'.$this->id.'\').toggle();$(\'.gt_read_less_more_link_'.$this->id.'\').toggle();return false;"><small>['.get_string('readless', 'block_gradetracker').']</small></a></span>';
                        $this->attributes['comments'] = implode("", $split);
                        
                    } elseif (isset($this->attributes['comments'])){
                        $this->attributes['comments'] = \gt_html($this->attributes['comments']);
                    }
                    
                    $comments = (isset($this->attributes['comments'])) ? "<tr><td>".get_string('comments', 'block_gradetracker')."</td><td>".$this->attributes['comments']."</td></tr>" : '';

                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_CRIT', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        "<strong>{$unit->getDisplayName()}</strong>",
                        "<strong>{$crit->getName()}</strong>",
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>{$award->getName()} ({$award->getShortName()})</strong>",
                        $comments
                    );
                        
                }
                
            break;
            
            // Updated a user unit
            case self::GT_LOG_DETAILS_UPDATED_USER_UNIT:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['unitID']) && isset($this->attributes['studentID']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $unit = new \GT\Unit($this->attributes['unitID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    $award = new \GT\UnitAward(@$this->attributes['awardID']);
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_UNIT', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        "<strong>{$unit->getDisplayName()}</strong>",
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>{$award->getName()} ({$award->getShortName()})</strong>"
                    );
                        
                }
                
            break;
            
            // Updated a user assessment
            case self::GT_LOG_DETAILS_UPDATED_USER_ASS:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['assID']) && isset($this->attributes['studentID']) && isset($this->attributes['studentID']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $ass = new \GT\Assessment($this->attributes['assID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    
                    $ass->setQualification($qual);
                    $award = $ass->getGradeByID($this->attributes['grade']);
                    if (!$award){
                        $award = new \GT\CriteriaAward();
                    }
                    $ceta = false;
                    
                    if (isset($this->attributes['ceta'])){
                        $ceta = new \GT\QualificationAward($this->attributes['ceta']);
                    }
                    
                    $cetaStr = ($ceta && $ceta->isValid()) ? $ceta->getName() : '-';
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_ASS', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        "<strong>{$ass->getName()}</strong>",
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>{$award->getName()} ({$award->getShortName()})</strong>",
                        "<strong>{$cetaStr}</strong>"
                    );
                        
                }
                
            break;
            
            case self::GT_LOG_DETAILS_UPDATED_USER_ATT:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['studentID']) && isset($this->attributes['attribute']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    
                    if (isset($this->attributes['unitID'])){
                        $unit = new \GT\Unit($this->attributes['unitID']);
                    }
                    
                    $unitName = (isset($unit)) ? "<tr><td>".get_string('unit', 'block_gradetracker')."</td><td><strong>{$unit->getDisplayName()}</strong></td></tr>" : '';
                    $value = (isset($this->attributes['value'])) ? \gt_html($this->attributes['value']) : '-';
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_ATT', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        $unitName,
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>".\gt_html($this->attributes['attribute'])."</strong>",
                        "<strong>{$value}</strong>"
                    );
                        
                }
                
            break;
            
            case self::GT_LOG_DETAILS_UPDATED_USER_RANGE:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['unitID']) && isset($this->attributes['studentID']) && isset($this->attributes['critID']) && isset($this->attributes['rangeID']) && isset($this->attributes['observationNum']) && isset($this->attributes['value']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $unit = new \GT\Unit($this->attributes['unitID']);
                    $crit = \GT\Criterion::load($this->attributes['critID']);
                    $range = \GT\Criterion::load($this->attributes['rangeID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    
                    $obNum = \gt_html($this->attributes['observationNum']);
                    $award = new \GT\CriteriaAward($this->attributes['value']);
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_RANGE', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        "<strong>{$unit->getDisplayName()}</strong>",
                        "<strong>{$crit->getName()}</strong>",
                        "<strong>{$range->getName()}</strong>",
                        "<strong>{$obNum}</strong>",
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>{$award->getName()} ({$award->getShortName()})</strong>"
                    );
                        
                }
                
                
            break;
            
            case self::GT_LOG_DETAILS_UPDATED_USER_GRADE:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['studentID']) && isset($this->attributes['type']) && isset($this->attributes['grade']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    $stud = new \GT\User($this->attributes['studentID']);
                    
                    $type = gt_html($this->attributes['type']);
                    $grade = new \GT\QualificationAward($this->attributes['grade']);
                    $gradeStr = ($grade->isValid()) ? $grade->getName() : '-';
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_UPDATED_USER_GRADE', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        "<a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a>",
                        "<strong>{$type}</strong>",
                        "<strong>{$gradeStr}</strong>"
                    );
                        
                }
                
                
            break;
            
            case self::GT_LOG_DETAILS_IMPORTED_GRID:
                
                if (isset($this->attributes['qualID']) && isset($this->attributes['type']))
                {
                                                    
                    $qual = new \GT\Qualification($this->attributes['qualID']);
                    
                    if (isset($this->attributes['studentID'])){
                        $stud = new \GT\User($this->attributes['studentID']);
                    } elseif (isset($this->attributes['unitID'])){
                        $unit = new \GT\Unit($this->attributes['unitID']);
                    }
                                  
                    $unitStr = (isset($unit)) ? "<tr><td>".get_string('unit', 'block_gradetracker')."</td><td><strong>{$unit->getDisplayName()}</strong></td></tr>" : '';
                    $studStr = (isset($stud)) ? "<tr><td>".get_string('student', 'block_gradetracker')."</td><td><a href='{$CFG->wwwroot}/blocks/gradetracker/grid.php?type=student&id={$stud->id}&qualID={$qual->getID()}'>{$stud->getDisplayName()}</a></td></tr>" : '';
                    
                    $str = sprintf( get_string('log:GT_LOG_DETAILS_IMPORTED_GRID', 'block_gradetracker'),
                        "<strong>{$qual->getDisplayName()}</strong>",
                        $unitStr,
                        $studStr
                    );
                        
                }
                
            break;
            
            
            
            // Save config settings
            case self::GT_LOG_DETAILS_SAVED_CONFIG:
                                                   
                $str = sprintf( get_string('log:GT_LOG_DETAILS_SAVED_CONFIG', 'block_gradetracker'),
                    gt_html( print_r( \gt_json_decode_array($this->attributes), true) )
                );
                
            break;
            
        }
            
        return $str;
        
    }
    
    
    /**
     * Add a new log to the database
     * @global type $DB
     * @global type $USER
     * @param type $context
     * @param type $details
     * @param type $log
     * @return type
     */
    public function addLog($context, $details, $log){
        
        global $DB, $USER;
        
        $obj = new \stdClass();
        $obj->timestamp = time();
        $obj->userid = $USER->id;
        $obj->userip = getremoteaddr();
        $obj->backtrace = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        $obj->beforejson = (isset($log['before'])) ? $log['before'] : null;
        $obj->afterjson = (isset($log['after'])) ? $log['after'] : null;
        $obj->details = $details;
        $obj->context = $context;
        
        $logID = $DB->insert_record("bcgt_logs", $obj);
        
        // Attributes
        if ($logID && isset($log['attributes']) && $log['attributes'])
        {
            foreach($log['attributes'] as $name => $value)
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
    
    
    public static function search($params){
        
        global $DB;
                        
        // Student - easier to just get their ID here
        if (isset($params['atts']['stud'])){
            $stud = \GT\User::byUsername($params['atts']['stud']);
        }
        
        $sqlParams = array();

        $sql = "SELECT l.id
                FROM {bcgt_logs} l
                INNER JOIN {bcgt_log_attributes} a ON a.logid = l.id 
                INNER JOIN {user} u ON u.id = l.userid
                WHERE l.id > 0 ";

        // Action
        if (isset($params['action'])){
            $sql .= "AND l.details = ? ";
            $sqlParams[] = $params['action'];
        }
        
        // Date
        if (isset($params['timing']) && isset($params['dateStart']) && isset($params['dateEnd'])){
            
            // Just this day
            if ($params['timing'] == 'e'){
                $sql .= "AND l.timestamp >= ? AND l.timestamp <= ? ";
                $sqlParams[] = $params['dateStart'];
                $sqlParams[] = $params['dateEnd'];
            }
            
            // Before this day
            elseif ($params['timing'] == 'b'){
                $sql .= "AND l.timestamp < ? ";
                $sqlParams[] = $params['dateStart'];
            }
            
            // After this day
            elseif ($params['timing'] == 'b'){
                $sql .= "AND l.timestamp > ? ";
                $sqlParams[] = $params['dateEnd'];
            }
            
        }
        
        // User
        if (isset($params['user'])){
            $sql .= "AND u.username = ? ";
            $sqlParams[] = $params['user'];
        }
                
        $sql .= "GROUP BY l.id 
                 ORDER BY l.timestamp DESC";
                
        $records = $DB->get_records_sql($sql, $sqlParams);
        $return = array();
                
        if ($records)
        {
            foreach($records as $record)
            {
                
                $obj = new \GT\Log($record->id);
                
                // QualID
                if (isset($params['atts']['qual'])){
                    if (!isset($obj->attributes['qualID']) || $obj->attributes['qualID'] != $params['atts']['qual']){
                        continue;
                    }
                }
                
                // UnitID
                if (isset($params['atts']['unit'])){
                    if (!isset($obj->attributes['unitID']) || $obj->attributes['unitID'] != $params['atts']['unit']){
                        continue;
                    }
                }
                
                // StudentID
                if (isset($stud) && $stud){
                    if (!isset($obj->attributes['studentID']) || $obj->attributes['studentID'] != $stud->id){
                        continue;
                    }
                }
                
                $return[] = $obj;
                
            }
        }
        
        
        // Limit returned results
        $limit = (isset($params['atts']['limit']) && $params['atts']['limit'] != '') ? $params['atts']['limit'] : false;
        if ($limit && $limit > 0){
            $return = array_slice($return, 0, $limit);
        }
        
        return $return;
        
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
   
}