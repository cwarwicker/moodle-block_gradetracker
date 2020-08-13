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
 * This file is included in the df_hub plugin, to display stats about the gradetracker's usage.
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

// Count Structures
$stats['structures'] = count( \block_gradetracker\QualificationStructure::getAllStructures() );

// Count Quals
$stats['quals'] = \block_gradetracker\Qualification::countQuals();

// Count units
$stats['units'] = \block_gradetracker\Unit::countUnits();

// Count criteria
$stats['criteria'] = \block_gradetracker\Criterion::countCriteria();

// Count Students With Grids
$stats['studwithgrid'] = $DB->count_records("bcgt_user_quals", array("role" => "STUDENT"));

// Count Staff With Grids
$stats['staffwithgrid'] = $DB->count_records("bcgt_user_quals", array("role" => "STAFF"));
