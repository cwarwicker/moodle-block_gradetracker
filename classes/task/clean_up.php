<?php
/**
 * This class defines the scheduled task to do clean up procedures
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

namespace block_gradetracker\task;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

class clean_up extends \core\task\scheduled_task
{
        
    /**
     * Get the name of the task
     * @return type
     */
    public function get_name(){
        return get_string('task:clean_up', 'block_gradetracker');
    }
    
    /**
     * Execute the clean up
     * @global type $DB
     */
    public function execute() {
        
        global $DB;
                
        $GT = new \GT\GradeTracker();
        
        // Clean up logs from the db
        $keepFor = $GT->getSetting('keep_logs_for');
        if (is_int($keepFor) && $keepFor > 0)
        {
            
            $ago = strtotime("-{$keepFor} days");
            mtrace("Deleting all logs created before " . date('d-m-Y, H:i', $ago));

            // Delete all logs and their attributes, that were created before that timestamp
            $logs = $DB->get_records_select("bcgt_logs", "timestamp < ?", array($ago));
            
            mtrace("Found " . count($logs) . " logs to delete");
            
            foreach($logs as $log)
            {
                $DB->delete_records("bcgt_logs", array("id" => $log->id));
                $DB->delete_records("bcgt_log_attributes", array("logid" => $log->id));
            }
                        
        }
        else
        {
            mtrace("'keep_logs_for' set to never delete logs");
        }
        
        
        // Clean up tmp files
        mtrace("Cleaning up tmp files from [dataroot]/gradetracker");
        $result = \GT\GradeTracker::gc();
        mtrace("Deleted {$result} tmp files");
                                
    }
    
}