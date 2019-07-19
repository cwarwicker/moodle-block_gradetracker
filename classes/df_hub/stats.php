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