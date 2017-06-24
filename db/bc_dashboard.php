<?php

defined('MOODLE_INTERNAL') || die;

/**
 * This file contains the list of all the elements which can be used in bc_dashboard reporting, and their definitions
 * 
 * @copyright 2017 Bedford College
 * @package Bedford College Electronic Learning Blue Print (ELBP)
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

$elements = array(
    
    'avggcse' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/avggcse.php',
        'class' => '\GT\bc_dashboard\avggcse',
    ),
    
    'grade' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/grade.php',
        'class' => '\GT\bc_dashboard\grade',
    ),
    
    'listofquals' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/listofquals.php',
        'class' => '\GT\bc_dashboard\listofquals',
    ),
    
    'numberofqoe' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/numberofqoe.php',
        'class' => '\GT\bc_dashboard\numberofqoe',
    ),
    
    'award' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/award.php',
        'class' => '\GT\bc_dashboard\award',
    ),
    
    'valueadded' => array(
        'file' => '/blocks/gradetracker/classes/bc_dashboard/valueadded.php',
        'class' => '\GT\bc_dashboard\valueadded',
    ),
    
    
);