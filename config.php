<?php
/**
 * Plugin Configuration
 *
 * Configure all the various aspects of the plugin
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com> <moodlesupport@bedford.ac.uk>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

require_once '../../config.php';
require_once $CFG->dirroot . '/lib/filelib.php';
require_once $CFG->dirroot.'/lib/coursecatlib.php';
require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

// Need to be logged in to view this page
require_login();

if (!gt_has_capability('block/gradetracker:configure')){
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

$User = new \GT\User($USER->id);

$view = optional_param('view', false, PARAM_TEXT);
$section = optional_param('section', false, PARAM_TEXT);
$page = optional_param('page', false, PARAM_TEXT);
$id = optional_param('id', false, PARAM_INT);
$course = false;

if ($view == 'course' && $id)
{
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

if ($view && !gt_has_capability('block/gradetracker:configure_'.$view))
{
    print_error( get_string('invalidaccess', 'block_gradetracker') );
}

// Default section for views
if ( (!$section || $section == '') && $view && array_key_exists($view, $defaultSections) )
{
    $section = $defaultSections[$view];
}

$GT = new \GT\GradeTracker();
$TPL = new \GT\Template();
$MSGS = false;
$VARS = false;

if (isset($_POST) && !empty($_POST))
{
    $GT->saveConfig($view, $section, $page);
}

// Set up PAGE
$PAGE->set_context( context_course::instance(SITEID) );
$PAGE->set_url($CFG->wwwroot . '/blocks/gradetracker/config.php');
$PAGE->set_heading( get_string('config', 'block_gradetracker') );
$PAGE->set_cacheable(true);
$PAGE->set_pagelayout( $GT->getMoodleThemeLayout() );

// Try and load javascript
if (file_exists($CFG->dirroot . '/blocks/gradetracker/js/config/'.$view.'/'.$section.'/scripts.js')){
    $PAGE->requires->js( '/blocks/gradetracker/js/config/'.$view.'/'.$section.'/scripts.js', true );
    $PAGE->requires->js_init_call("{$view}_{$section}_bindings", null, true);
} elseif (file_exists($CFG->dirroot . '/blocks/gradetracker/js/config/'.$view.'/scripts.js')){
    $PAGE->requires->js( '/blocks/gradetracker/js/config/'.$view.'/scripts.js', true );
    $PAGE->requires->js_init_call("{$view}_bindings", null, true);
}

$GT->loadJavascript();
$GT->loadCSS();
$PAGE->requires->js_init_call("gt_bindings", null, true);

// If course is set, put that into breadcrumb
$PAGE->navbar->add( $GT->getPluginTitle(), null);
$PAGE->navbar->add( get_string('config', 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php', navigation_node::TYPE_CUSTOM);

$title = '';

// Course
if ($course)
{
    $PAGE->navbar->add( $course->fullname, $CFG->wwwroot . '/course/view.php?id='.$course->id, navigation_node::TYPE_CUSTOM);
}

if ($view)
{
    $title = get_string('breadcrumbs:config:'.$view, 'block_gradetracker');
    $viewURL = $view;
    if ($view == 'course'){
        $viewURL = $view . '&id=' . $id;
    } 
    
    $PAGE->navbar->add( get_string('breadcrumbs:config:'.$view, 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php?view='.$viewURL, navigation_node::TYPE_CUSTOM);
    
}

if ($view && $section)
{
    $title = get_string('breadcrumbs:config:'.$view.':'.$section, 'block_gradetracker');
    $viewURL = $view;
    if ($view == 'course'){
        $viewURL = $view . '&id=' . $id;
    } 
    $PAGE->navbar->add( get_string('breadcrumbs:config:'.$view.':'.$section, 'block_gradetracker'), $CFG->wwwroot . '/blocks/gradetracker/config.php?view='.$viewURL.'&section='.$section, navigation_node::TYPE_CUSTOM);
}

if ($view && $section && $page)
{
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


// test event
//$event = \block_gradetracker\event\my_event::create( array(
//    'objectid' => 1,
//    'context' => context_block::instance(1)
//));
//$event->trigger();


echo $OUTPUT->footer();