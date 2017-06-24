<?php

/**
 * Handle Moodle events, such as course enrolments/unenrolments and what that means for the elbp data
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com>
 * 
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 */


defined('MOODLE_INTERNAL') || die();


$observers = array(
    
    array(
        'eventname'   => '\core\event\role_assigned',
        'callback'    => 'block_gradetracker_observer::gt_auto_enrol',
        'internal'    => false,
        'priority'    => 9999,
    ),
    
    array(
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => 'block_gradetracker_observer::gt_auto_unenrol',
        'internal'    => false,
        'priority'    => 9999,
    ),
    
);