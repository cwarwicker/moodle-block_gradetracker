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
 * View logs
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
require_once('../../config.php');
require_once('lib.php');

require_login();

if (!gt_has_capability('block/gradetracker:configure') || !gt_has_capability('block/gradetracker:configure_reporting')) {
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$id = required_param('id', PARAM_INT);

$GT = new \GT\GradeTracker();
$Log = new \GT\Log($id);
$TPL = new \GT\Template();

if ($Log->id) {
    $TPL->set("log", $Log);
}

$TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/config/reporting/log.html' );
$TPL->display();