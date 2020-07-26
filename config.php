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
 * Plugin Configuration
 *
 * Configure all the various aspects of the plugin
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');

// Need to be logged in to view this page
require_login();

if (!gt_has_capability('block/gradetracker:configure')) {
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$User = new \GT\User($USER->id);

$view = optional_param('view', false, PARAM_TEXT);
$section = optional_param('section', false, PARAM_TEXT);
$page = optional_param('page', false, PARAM_TEXT);
$id = optional_param('id', false, PARAM_INT);
$course = false;

if ($view == 'course' && $id) {
    $course = get_course($id);
}

$defaultSections = array(
    'settings' => 'general',
    'structures' => 'qual',
    'quals' => 'overview',
    'units' => 'overview',
    'course' => 'my',
    'data' => 'qoe',
    'assessments' => 'manage',
    'tests' => 'tg',
    'reporting' => 'logs'
);

if ($view && !gt_has_capability('block/gradetracker:configure_'.$view)) {
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

// Default section for views
if ( (!$section || $section == '') && $view && array_key_exists($view, $defaultSections) ) {
    $section = $defaultSections[$view];
}

$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$MSGS = false;
$VARS = false;

// This use of $_POST is just to check if a config form was submitted.
if (isset($_POST) && !empty($_POST)) {
    $GT->saveConfig($view, $section, $page);
}

// Set up PAGE
$PAGE->set_context( context_course::instance(SITEID) );
$PAGE->set_url($CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->set_heading( get_string('config', 'block_gradetracker') );
$PAGE->set_cacheable(true);
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );

$GT->loadJavascript();
$GT->loadCSS();

// Try and load page-specific javascript
if (file_exists($CFG->dirroot . '/blocks/gradetracker/amd/src/config_'.$view.'_'.$section.'.js')) {
    $PAGE->requires->js_call_amd("block_gradetracker/config_{$view}_{$section}", 'init', \GT\Output::initAMD($view, $section));
} else if (file_exists($CFG->dirroot . '/blocks/gradetracker/amd/src/config_'.$view.'.js')) {
    $PAGE->requires->js_call_amd("block_gradetracker/config_{$view}", 'init', \GT\Output::initAMD($view));
}

// If course is set, put that into breadcrumb
$PAGE->navbar->add( $GT->getPluginTitle(), null);
$PAGE->navbar->add( get_string('config', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php', navigation_node::TYPE_CUSTOM);

$title = '';

// Course
if ($course) {
    $PAGE->navbar->add( $course->fullname, $CFG->wwwroot . '/course/view.php?id='.$course->id, navigation_node::TYPE_CUSTOM);
}

if ($view) {
    $title = get_string('breadcrumbs:config:'.$view, 'block_gradetracker');
    $viewURL = $view;
    if ($view == 'course') {
        $viewURL = $view . '&id=' . $id;
    }

    $PAGE->navbar->add( get_string('breadcrumbs:config:'.$view, 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php?view='.$viewURL, navigation_node::TYPE_CUSTOM);

}

if ($view && $section) {
    $title = get_string('breadcrumbs:config:'.$view.':'.$section, 'block_gradetracker');
    $viewURL = $view;
    if ($view == 'course') {
        $viewURL = $view . '&id=' . $id;
    }
    $PAGE->navbar->add( get_string('breadcrumbs:config:'.$view.':'.$section, 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php?view='.$viewURL.'&section='.$section, navigation_node::TYPE_CUSTOM);
}

if ($view && $section && $page) {
    $title = get_string('breadcrumbs:config:'.$view.':'.$section.':'.$page, 'block_gradetracker');
    $PAGE->navbar->add( get_string('breadcrumbs:config:'.$view.':'.$section.':'.$page, 'block_gradetracker'), null);
}

$PAGE->set_title( $SITE->shortname . ': ' . $GT->getPluginTitle() . ': ' . get_string('config', 'block_gradetracker') . ': ' . $title );

echo $OUTPUT->header();

$TPL->set("GT", $GT)
    ->set("view", $view)
    ->set("section", $section)
    ->set("page", $page)
    ->set("id", $id)
    ->set("course", $course)
    ->set("MSGS", $MSGS)
    ->set("User", $User);
$TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/config.html' );
$TPL->display();

echo $OUTPUT->footer();