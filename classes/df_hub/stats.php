<?php

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

// Count Structures
$stats['structures'] = count( \GT\QualificationStructure::getAllStructures() );

// Count Quals
$stats['quals'] = \GT\Qualification::countQuals();

// Count units
$stats['units'] = \GT\Unit::countUnits();

// Count criteria
$stats['criteria'] = \GT\Criterion::countCriteria();

// Count Students With Grids
$stats['studwithgrid'] = $DB->count_records("bcgt_user_quals", array("role" => "STUDENT"));

// Count Staff With Grids
$stats['staffwithgrid'] = $DB->count_records("bcgt_user_quals", array("role" => "STAFF"));