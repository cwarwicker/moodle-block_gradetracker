<?php
/**
 * This class defines the scheduled task to refresh site registration
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

class refresh_site_registration extends \core\task\scheduled_task
{
        
    /**
     * Get the name of the task
     * @return type
     */
    public function get_name(){
        return get_string('task:refresh_site_registration', 'block_gradetracker');
    }
    
    public function execute() {
        
        mtrace("Attempting to update site registration");
        
        $Site = new \GT\Site();
        $result = $Site->cron();
        mtrace("Result: " . $result);
        
    }
    
}