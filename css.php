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
 * Custom CSS for the gradetracker
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

// Moodle sets the content type to text/html, so need to override that here, not before the config.php
// This section copied from userstyles.php
header('Content-Type: text/css', true);
header("X-Content-Type-Options: nosniff"); // for IE
header('Cache-Control: no-cache');

$GT = new \GT\GradeTracker();

$css = $GT->getSetting('custom_css');
$css = trim($css);
if ($css) {
    echo \gt_html($css);
}