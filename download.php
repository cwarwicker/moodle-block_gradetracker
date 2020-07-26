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
 * Download a Gradetracker file.
 *
 * Used for uploaded grid icons.
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/blocks/gradetracker/GradeTracker.class.php');

// No require login here, as Parent Portal users need to be able to access the grid icons as well.
// TODO: Add external session checking here, like in some other files.

$f = required_param('f', PARAM_TEXT);

$record = $DB->get_record("bcgt_file_codes", array("code" => $f));
if ($record) {
    $record->path = \GT\GradeTracker::dataroot() . DIRECTORY_SEPARATOR . $record->path;
}

if (!$record || !file_exists($record->path)) {
    print_error( get_string('filenotfound', 'block_gradetracker') );
    exit;
}

\send_file($record->path, basename($record->path));
exit;