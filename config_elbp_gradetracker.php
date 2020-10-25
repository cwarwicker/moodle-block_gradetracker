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
 * Configure the Gradetracker plugin for the ELBP
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/elbp/lib.php');

// Need to be logged in to view this page
require_login();

$ELBP = block_elbp\ELBP::instantiate();
$DBC = new block_elbp\DB();

$view = optional_param('view', 'main', PARAM_ALPHA);

$access = $ELBP->getCoursePermissions(1);
if (!$access['god']) {
    print_error( get_string('invalidaccess', 'block_elbp') );
}

try {
    $OBJ = \block_elbp\Plugins\Plugin::instaniate("elbp_gradetracker");
} catch (\block_elbp\ELBPException $e) {
    echo $e->getException();
    exit;
}

$TPL = new \block_elbp\Template();
$MSGS['errors'] = '';
$MSGS['success'] = '';

// Has the config form been submitted?
if (isset($_POST) && !empty($_POST)) {
    $OBJ->saveConfig();
    $TPL->set("saved", get_string('saved', 'block_elbp'));
}


// Set up PAGE
$PAGE->set_context( context_course::instance(1) );
$PAGE->set_url($CFG->wwwroot . $OBJ->getPath() . 'config_elbp_gradetracker.php');
$PAGE->set_title( get_string('config', 'block_elbp') );
$PAGE->set_heading( get_string('config', 'block_elbp') );
$PAGE->set_cacheable(true);
$ELBP->loadJavascript();
$ELBP->loadCSS();

// If course is set, put that into breadcrumb
$PAGE->navbar->add( get_string('config', 'block_elbp'), $CFG->wwwroot . $OBJ->getPath() . '/config.php', navigation_node::TYPE_CUSTOM);

echo $OUTPUT->header();


$TPL->set("OBJ", $OBJ);
$TPL->set("view", $view);
$TPL->set("MSGS", $MSGS);
$TPL->set("OUTPUT", $OUTPUT);

try {
    $TPL->load( $CFG->dirroot . $OBJ->getPath() . '/tpl/elbp_gradetracker/config.html' );
    $TPL->display();
} catch (\block_elbp\ELBPException $e) {
    echo $e->getException();
}

echo $OUTPUT->footer();