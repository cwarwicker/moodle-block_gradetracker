<?php
require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

// Moodle sets the content type to text/html, so need to override that here, not before the config.php
// This section copied from userstyles.php
header('Content-Type: text/css', true);
header("X-Content-Type-Options: nosniff"); // for IE
header('Cache-Control: no-cache');

$GT = new \GT\GradeTracker();

$css = $GT->getSetting('custom_css');
$css = trim($css);
if ($css)
{
    echo \gt_html($css);
}