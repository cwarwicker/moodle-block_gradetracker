<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * This file contains the list of all the elements which can be used in bc_dashboard reporting, and their definitions
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

defined('MOODLE_INTERNAL') || die;

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