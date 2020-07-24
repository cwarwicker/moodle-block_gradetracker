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
 * Handle Moodle events, such as course enrolments/unenrolments and what that means for the elbp data
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
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

    array(
        'eventname'   => '\core\event\user_enrolment_updated',
        'callback'    => 'block_gradetracker_observer::gt_auto_modifyenrolment',
        'internal'    => false,
        'priority'    => 9999
    )

);