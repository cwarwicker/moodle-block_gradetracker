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
 * Grade Tracker class
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

if (!defined('BCGT')) {
    define('BCGT', true);
}

require_once($CFG->dirroot . '/local/df_hub/lib.php');

class GradeTracker {

    private $CFG;
    private $DB;
    private $string;
    public $cache;

    const REMOTE_HOST_URL = 'https://github.com/cwarwicker/moodle-block_gradetracker';
    const REMOTE_VERSION_URL = 'https://raw.githubusercontent.com/cwarwicker/moodle-block_gradetracker/master/v.txt';
    const REMOTE_HUB = '';
    const REMOTE_HUB_TOKEN = '';

    const MAJOR_VERSION = 2; // A new major version would be a drastic change to the system, which sees lots of new features added or existing aspects of the system changed in large ways
    const MINOR_VERSION = 0; // A new minor version would be a small number of new features/changes
    const PATCH_VERSION = 0; // A patch would be one or more bug fixes

    /**
     * Construct the block object
     */
    public function __construct() {

        global $CFG, $DB;

        // Set block variables
        $this->CFG = $CFG;
        $this->DB = $DB;
        $this->string = get_string_manager()->load_component_strings('block_gradetracker', $this->CFG->lang, true);

    }

    /**
     * Uninstall the gradetracker plugin
     */
    public function uninstall() {

        global $CFG;

        // Database tables should be handled by Moodle

        // Remove moodledata directory
        $result = \remove_dir(self::dataroot(), true);
        mtrace('Trying to remove gradetracker directory in data root...' . (int)$result, "\n<br>");

    }

    /**
     * Install the gradetracker plugin
     * @global \block_gradetracker\type $CFG
     * @global \block_gradetracker\type $DB
     */
    public function install() {

        global $CFG, $DB;

        // Make dataroot directory
        if (!is_dir(self::dataroot())) {
            $result = mkdir(self::dataroot(), $CFG->directorypermissions);
            mtrace('Trying to create gradetracker directory in data root...' . (int)$result, "\n<br>");
        }

        // Make dataroot tmp directory
        if (!is_dir(self::dataroot() . '/tmp/')) {
            $result = mkdir(self::dataroot() . '/tmp/', $CFG->directorypermissions);
            mtrace('Trying to create gradetracker tmp directory in data root...' . (int)$result, "\n<br>");
        }

        // ======================== Install data ======================== //

        // Hard-Coded Installs
        //
        // Qualification Structure Features
        $features = \block_gradetracker\QualificationStructure::_features();
        if ($features) {
            foreach ($features as $feature) {

                $check = $DB->get_record("bcgt_qual_structure_features", array("name" => $feature));
                if (!$check) {

                    $obj = new \stdClass();
                    $obj->name = $feature;
                    $result = $DB->insert_record("bcgt_qual_structure_features", $obj);
                    mtrace('Trying to insert qual_structure_feature ' . $feature . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // Qualification Structure Levels
        $levels = \block_gradetracker\QualificationStructure::_levels();
        if ($levels) {
            foreach ($levels as $level => $minMax) {

                $check = $DB->get_record("bcgt_qual_structure_levels", array("name" => $level));
                if ($check) {
                    $check->minsublevels = $minMax[0];
                    $check->maxsublevels = $minMax[1];
                    $result = $DB->update_record("bcgt_qual_structure_levels", $check);
                } else {
                    $obj = new \stdClass();
                    $obj->name = $level;
                    $obj->minsublevels = $minMax[0];
                    $obj->maxsublevels = $minMax[1];
                    $result = $DB->insert_record("bcgt_qual_structure_levels", $obj);
                }

                mtrace('Trying to insert/update qual_structure_level ' . $level . '...' . (int)$result, "\n<br>");

            }
        }

        // Configurable Installs
        //
        // Install directory
        $installDir = $CFG->dirroot . '/blocks/gradetracker/db/install';

        // Install Qualification Levels (e.g. Level 1, Level 2, etc...(
        if (file_exists($installDir.'/structures/level/levels.json')) {
            $levels = json_decode( file_get_contents($installDir.'/structures/level/levels.json') );
            if ($levels) {

                foreach ($levels as $level) {

                    $check = $DB->get_record("bcgt_qual_levels", array("name" => $level->name, "deleted" => 0));
                    if ($check) {
                        $check->shortname = $level->shortname;
                        $check->ordernum = $level->ordernum;
                        $result = $DB->update_record("bcgt_qual_levels", $check);
                    } else {
                        $obj = new \stdClass();
                        $obj->name = $level->name;
                        $obj->shortname = $level->shortname;
                        $obj->ordernum = $level->ordernum;
                        $result = $DB->insert_record("bcgt_qual_levels", $obj);
                    }

                    mtrace('Trying to insert/update qual_level ' . $level->name . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // Install Qualification SubTypes (e.g. Diploma, Certificate, Award, etc...(
        if (file_exists($installDir.'/structures/subtype/subtypes.json')) {
            $subtypes = json_decode( file_get_contents($installDir.'/structures/subtype/subtypes.json') );
            if ($subtypes) {

                foreach ($subtypes as $subtype) {

                    $check = $DB->get_record("bcgt_qual_subtypes", array("name" => $subtype->name, "deleted" => 0));
                    if ($check) {
                        $check->shortname = $subtype->shortname;
                        $result = $DB->update_record("bcgt_qual_subtypes", $check);
                    } else {
                        $obj = new \stdClass();
                        $obj->name = $subtype->name;
                        $obj->shortname = $subtype->shortname;
                        $result = $DB->insert_record("bcgt_qual_subtypes", $obj);
                    }

                    mtrace('Trying to insert/update qual_subtype ' . $subtype->name . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // Install support for Mods activity links (e.g. assign, turnitintooltwo)
        if (file_exists($installDir.'/mods/mods.json')) {
            $mods = json_decode( file_get_contents($installDir.'/mods/mods.json') );
            if ($mods) {

                foreach ($mods as $mod) {

                    // First need to check if this mod is installed
                    $result = false;
                    $moduleRecord = $DB->get_record("modules", array("name" => $mod->mod));
                    if ($moduleRecord) {

                        $check = $DB->get_record("bcgt_mods", array("modid" => $moduleRecord->id, "deleted" => 0));
                        if ($check) {
                            $check->modtable = $mod->modtable;
                            $check->modcoursecol = $mod->modcoursecol;
                            $check->modstartcol = $mod->modstartcol;
                            $check->modduecol = $mod->modduecol;
                            $check->modtitlecol = $mod->modtitlecol;
                            $check->submissiontable = $mod->submissiontable;
                            $check->submissionusercol = $mod->submissionusercol;
                            $check->submissiondatecol = $mod->submissiondatecol;
                            $check->submissionmodcol = $mod->submissionmodcol;
                            $check->auto = $mod->auto;
                            $check->enabled = $mod->enabled;
                            $check->deleted = $mod->deleted;
                            $check->submissionstatuscol = $mod->submissionstatuscol;
                            $check->submissionstatusval = $mod->submissionstatusval;
                            $check->parttable = $mod->parttable;
                            $check->partmodcol = $mod->partmodcol;
                            $check->parttitlecol = $mod->parttitlecol;
                            $check->submissionpartcol = $mod->submissionpartcol;
                            $result = $DB->update_record("bcgt_mods", $check);
                        } else {
                            $obj = new \stdClass();
                            $obj->modid = $moduleRecord->id;
                            $obj->modtable = $mod->modtable;
                            $obj->modcoursecol = $mod->modcoursecol;
                            $obj->modstartcol = $mod->modstartcol;
                            $obj->modduecol = $mod->modduecol;
                            $obj->modtitlecol = $mod->modtitlecol;
                            $obj->submissiontable = $mod->submissiontable;
                            $obj->submissionusercol = $mod->submissionusercol;
                            $obj->submissiondatecol = $mod->submissiondatecol;
                            $obj->submissionmodcol = $mod->submissionmodcol;
                            $obj->auto = $mod->auto;
                            $obj->enabled = $mod->enabled;
                            $obj->deleted = $mod->deleted;
                            $obj->submissionstatuscol = $mod->submissionstatuscol;
                            $obj->submissionstatusval = $mod->submissionstatusval;
                            $obj->parttable = $mod->parttable;
                            $obj->partmodcol = $mod->partmodcol;
                            $obj->parttitlecol = $mod->parttitlecol;
                            $obj->submissionpartcol = $mod->submissionpartcol;
                            $result = $DB->insert_record("bcgt_mods", $obj);
                        }

                    }

                    mtrace('Trying to insert/update bcgt_mods ' . $mod->mod . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // Install Qualification Structures (e.g. BTEC, A Level, etc...)
        // This includes the Unit grading structures and the Criteria grading structures for this
        $files = scandir($installDir . '/structures/qual');
        $files = array_filter($files, function($f) use ($installDir) {
            $info = pathinfo($installDir.'/structures/qual/' . $f);
            return (!is_dir($installDir.'/structures/qual/' . $f) && isset($info['extension']) && ($info['extension'] == 'xml' || $info['extension'] == 'zip'));
        });

        if ($files) {
            foreach ($files as $file) {
                $output = \block_gradetracker\QualificationStructure::importXML($installDir.'/structures/qual/'.$file);
                mtrace( $output['output'], "\n<br>" );
                if ($output['errors']) {
                    if (is_array($output['errors'][0])) {
                        foreach ($output['errors'] as $fileNum => $errors) {
                            mtrace('Errors ('.$output['files'][$fileNum].'): ' . implode('\n<br>', $errors), "\n<br>");
                        }
                    } else {
                        mtrace('Errors: ' . implode('\n<br>', $output['errors']), "\n<br>");
                    }
                }
            }
        }

        // Install Qualification Builds
        // This includes the Qual grading structures and any assessment grading structures defined
        // just for this build. As well as any default settings for this build.
        $files = scandir($installDir . '/structures/build');
        $files = array_filter($files, function($f) use ($installDir) {
            $info = pathinfo( $installDir . '/structures/build/' . $f );
            return (!is_dir($installDir.'/structures/build/' . $f) && isset($info['extension']) && ($info['extension'] == 'xml' || $info['extension'] == 'zip'));
        });

        if ($files) {
            foreach ($files as $file) {
                $output = \block_gradetracker\QualificationBuild::importXML($installDir.'/structures/build/'.$file, true);
                mtrace( $output['output'] , "\n<br>");
                if ($output['errors']) {
                    if (is_array($output['errors'][0])) {
                        foreach ($output['errors'] as $fileNum => $errors) {
                            mtrace('Errors ('.$output['files'][$fileNum].'): ' . implode('\n<br>', $errors), "\n<br>");
                        }
                    } else {
                        mtrace('Errors: ' . implode('\n<br>', $output['errors']), "\n<br>");
                    }
                }
            }
        }

        // Quals On Entry Types
        if (file_exists($installDir.'/qoe/types.json')) {
            $types = json_decode( file_get_contents($installDir.'/qoe/types.json') );
            if ($types) {
                foreach ($types as $type) {
                    $check = $DB->get_record("bcgt_qoe_types", array("name" => $type->name));
                    if ($check) {
                        $check->lvl = $type->lvl;
                        $check->weighting = $type->weighting;
                        $result = $DB->update_record("bcgt_qoe_types", $check);
                    } else {
                        $obj = new \stdClass();
                        $obj->name = $type->name;
                        $obj->lvl = $type->lvl;
                        $obj->weighting = $type->weighting;
                        $result = $DB->insert_record("bcgt_qoe_types", $obj);
                    }

                    mtrace('Trying to insert/update qoe_type ' . $type->name . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // Quals On Entry Grades
        if (file_exists($installDir.'/qoe/grades.json')) {
            $grades = json_decode( file_get_contents($installDir.'/qoe/grades.json') );
            if ($grades) {
                foreach ($grades as $grade) {
                    $type = $DB->get_record("bcgt_qoe_types", array("name" => $grade->type));
                    if ($type) {
                        $check = $DB->get_record("bcgt_qoe_grades", array("qoeid" => $type->id, "grade" => $grade->grade));
                        if ($check) {
                            $check->points = $grade->points;
                            $check->weighting = $grade->weighting;
                            $result = $DB->update_record("bcgt_qoe_types", $check);
                        } else {
                            $obj = new \stdClass();
                            $obj->qoeid = $type->id;
                            $obj->grade = $grade->grade;
                            $obj->points = $grade->points;
                            $obj->weighting = $grade->weighting;
                            $result = $DB->insert_record("bcgt_qoe_grades", $obj);
                        }
                    }

                    mtrace('Trying to insert/update qoe_grade ' . $grade->grade . '...' . (int)$result, "\n<br>");

                }

            }
        }

        // ======================== Insert Default Settings ======================== //

        // General Settings
        \block_gradetracker\Setting::updateSetting('plugin_title', 'Grade Tracker');
        \block_gradetracker\Setting::updateSetting('theme_layout', 'base');
        \block_gradetracker\Setting::updateSetting('use_gt_jqueryui', '1');
        \block_gradetracker\Setting::updateSetting('student_role_shortnames', 'student');
        \block_gradetracker\Setting::updateSetting('staff_role_shortnames', 'editingteacher,teacher');
        \block_gradetracker\Setting::updateSetting('course_name_format', '[%sn%] %fn%');
        \block_gradetracker\Setting::updateSetting('use_auto_enrol_quals', '1');
        \block_gradetracker\Setting::updateSetting('use_auto_enrol_units', '1');
        \block_gradetracker\Setting::updateSetting('use_auto_unenrol_quals', '1');
        \block_gradetracker\Setting::updateSetting('use_auto_unenrol_units', '1');

        // Qual Settings

        // Weighting Coefficients
        \block_gradetracker\Setting::updateSetting('qual_weighting_percentiles', '9');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_1', '#c21014');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_2', '#f21318');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_3', '#ee6063');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_4', '#6e6e6e');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_5', '#252525');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_6', '#6e6e6e');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_7', '#72a9fc');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_8', '#2047ff');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_9', '#123798');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_1', '100');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_2', '90');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_3', '75');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_4', '60');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_5', '40');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_6', '25');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_7', '10');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_8', '0');
        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_9', '1');
        \block_gradetracker\Setting::updateSetting('default_weighting_percentile', '3');

        // Weighting Constants
        \block_gradetracker\Setting::updateSetting('weighting_constants_enabled', '0'); # Should enable this if they want it
        \block_gradetracker\Setting::updateSetting('weighting_direction', 'UP');

        // Unit Settings

        // Criteria Settings

        // Grid Settings
        \block_gradetracker\Setting::updateSetting('enable_grid_logs', '0');
        \block_gradetracker\Setting::updateSetting('grid_fixed_links', '0');
        \block_gradetracker\Setting::updateSetting('assessment_grid_show_quals_one_page', '1');
        \block_gradetracker\Setting::updateSetting('unit_grid_paging', '15');
        \block_gradetracker\Setting::updateSetting('class_grid_paging', '15');

        // User Settings
        \block_gradetracker\Setting::updateSetting('student_columns', 'username,name');

        // Grade Settings
        \block_gradetracker\Setting::updateSetting('pred_grade_min_units', '3');
        \block_gradetracker\Setting::updateSetting('asp_grade_diff', '1');
        \block_gradetracker\Setting::updateSetting('weighted_target_method', 'ucas');
        \block_gradetracker\Setting::updateSetting('weighted_target_direction', 'UP');

        // Assessment Settings
        \block_gradetracker\Setting::updateSetting('use_assessments_comments', '1');

        mtrace('Inserted default configuration settings', "\n<br>");

    }

    /**
     * Get the a.b.c version number of the plugin
     * @return type
     */
    public function getPluginVersion() {
        return self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION;
    }

    /**
     * Get the block version as of the version.php file
     */
    public function getBlockVersion() {
        global $DB;
        $block = $DB->get_record("config_plugins", array("plugin" => "block_gradetracker", "name" => "version"));
        return ($block) ? $block->value : false;
    }

    /**
     * Print out a message if there are new updates
     * @return string
     */
    public function printVersionCheck($full = false, $return = false) {

        global $CFG, $OUTPUT;

        $remote = @file_get_contents(self::REMOTE_VERSION_URL);
        if (!$remote) {
            return \gt_error_alert_box(get_string('unabletocheckforupdates', 'block_gradetracker'));
        }

        $remote = json_decode(trim($remote));
        if (!$remote || is_null($remote)) {
            return \gt_error_alert_box(get_string('unabletocheckforupdates', 'block_gradetracker'));
        }

        $result = version_compare($this->getPluginVersion(), $remote->version, '<');
        if ($result) {
            $img = (file_exists($CFG->dirroot . '/blocks/gradetracker/pix/update_'.$remote->update.'.png')) ? $CFG->wwwroot . '/blocks/gradetracker/pix/update_'.$remote->update.'.png' : $CFG->wwwroot . '/blocks/gradetracker/pix/update_general.png';
            $link = (isset($remote->file) && $remote->file != '') ? $remote->file : self::REMOTE_HOST_URL;
            if ($full) {
                return "<span class='gt_update_notification_full_{$remote->update}'>".get_string('newversionavailable', 'block_gradetracker').": {$remote->version} [".\get_string('versionupdatetype_'.$remote->update, 'block_gradetracker')."]</span> <a href='{$link}'><img src='".\gt_image_url('t/download')."' alt='download' /></a>";
            } else {
                return "&nbsp;&nbsp;&nbsp;&nbsp;<span class='gt_update_notification'><a href='{$link}'><img src='{$img}' alt='update' title='".get_string('newversionavailable', 'block_gradetracker').": {$remote->version} [".\get_string('versionupdatetype_'.$remote->update, 'block_gradetracker')."]' /></a></span>";
            }
        } else if ($return) {
            return $return;
        }

    }

    /**
     * Get the title of the plugin
     */
    public function getPluginTitle() {
        $setting = \block_gradetracker\Setting::getSetting("plugin_title");
        return ($setting) ? format_text($setting, FORMAT_PLAIN) : get_string('pluginname', 'block_gradetracker');
    }

    /**
     * Get the chosen theme
     * @return type
     */
    public function getTheme() {
        $setting = \block_gradetracker\Setting::getSetting("theme");
        return ($setting) ? $setting : 'default';
    }

    /**
     * Get the theme layout setting for the full page views. Or "login" by default if undefined.
     * @return type
     */
    public function getMoodleThemeLayout() {

        $setting = \block_gradetracker\Setting::getSetting("theme_layout");
        return ($setting) ? $setting : 'base';

    }

    /**
     * get the selected categories to use in reporting
     * @return type
     */
    public function getReportingCategories() {

        $setting = \block_gradetracker\Setting::getSetting("reporting_categories");
        $cats = explode(",", $setting);

        $return = array();
        foreach ($cats as $catID) {
            $return[$catID] = \core_course_category::get($catID)->name;
        }

        // Order by name
        asort($return);

        return $return;

    }


    /**
     * Get the URL of the institution logo image
     * @return boolean
     */
    public function getInstitutionLogoUrl() {

        global $CFG;

        $setting = \block_gradetracker\Setting::getSetting("institution_logo");
        if ($setting) {
            $code = gt_get_data_path_code($CFG->dataroot . '/gradetracker/img/' . $setting);
            if ($code) {
                return $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . $code;
            }
        }

        return false;

    }

    /**
     * Get the course name format
     * @return type
     */
    public function getCourseNameFormat() {
        $setting = \block_gradetracker\Setting::getSetting("course_name_format");
        return ($setting && strlen($setting) > 0) ? ($setting) : "[%sn%] %fn%";
    }

    /**
     * Get an array of role shortnames for roles we want to be able to use to link students to a qualification
     * @return array
     */
    public function getStudentRoles() {

        $return = array();
        $roles = \block_gradetracker\Setting::getSetting("student_role_shortnames");

        if ($roles) {
            $shortnames = explode(",", $roles);
            if ($shortnames) {
                foreach ($shortnames as $shortname) {
                    $role = gt_get_role_by_shortname( trim($shortname) );
                    if ($role) {
                        $return[] = $role->shortname;
                    }
                }
            }

        }

        return $return;

    }

    /**
     * Get an array of role shortnames for roles we want to be able to use to link staff members to qualifications
     * @return array
     */
    public function getStaffRoles() {

        $return = array();
        $roles = \block_gradetracker\Setting::getSetting("staff_role_shortnames");

        if ($roles) {

            $shortnames = explode(",", $roles);
            if ($shortnames) {
                foreach ($shortnames as $shortname) {
                    $role = gt_get_role_by_shortname( trim($shortname) );
                    if ($role) {
                        $return[] = $role->shortname;
                    }
                }
            }

        }

        return $return;

    }

    /**
     * Get the navigation links for the student grid
     * @return type
     */
    public function getStudentGridNavigation() {

        $setting = $this->getSetting('student_grid_navigation');
        return json_decode($setting);

    }

    /**
     * Get the navigation links for the unit grid
     * @return type
     */
    public function getUnitGridNavigation() {

        $setting = $this->getSetting('unit_grid_navigation');
        return json_decode($setting);

    }

    /**
     * Get the navigation links for the class grid
     * @return type
     */
    public function getClassGridNavigation() {

        $setting = $this->getSetting('class_grid_navigation');
        return json_decode($setting);

    }

    /**
     * Update a plugin setting
     * @param type $setting
     * @param type $value
     * @param type $userID
     * @return type
     */
    public function updateSetting($setting, $value, $userID = null) {
        return \block_gradetracker\Setting::updateSetting($setting, $value, $userID);
    }

    public function getSetting($setting, $userID = null) {
        return \block_gradetracker\Setting::getSetting($setting, $userID);
    }

    /**
     * For a given user, find all the contexts they are assigned to
     * @param int $userID If null, will use the user id of the current user logged in
     */
    public function getUserContexts($userID = null) {

        global $DB, $USER;

        // Stop if no user logged in
        if (!$USER) {
            return false;
        }

        // If no user specified, use you instead
        if (is_null($userID)) {
            $userID = $USER->id;
        }

        $contexts = array();

        $records = $DB->get_records_sql("SELECT DISTINCT c.*
                                         FROM {role_assignments} r
                                         INNER JOIN {context} c ON c.id = r.contextid
                                         WHERE r.userid = ?", array($userID));

        // If they are enroled on any course, check the context of those courses
        if ($records) {
            foreach ($records as $record) {
                $info = get_context_info_array($record->id);
                if (isset($info[0]) && $info[0]) {
                    $contexts[] = $info[0];
                }
            }
        } else {
            $contexts[] = \context_system::instance();
        }

        return $contexts;

    }


    /**
     * Display a config form/page
     * This method is too big, it should be broken down into methods for each view, like the saveConfig methods
     * @param type $view
     * @param type $section
     * @param type $page
     */
    public function displayConfig($view, $section = false, $page = false) {

        global $CFG, $PAGE, $MSGS, $VARS, $USER;

        if (!$view) {
            return false;
        }

        $User = new \block_gradetracker\User($USER->id);
        $id = optional_param('id', false, PARAM_INT);

        if (isset($VARS['TPL'])) {
            $TPL = $VARS['TPL'];
        } else {
            $TPL = new \block_gradetracker\Template();
        }

        $TPL->set("GT", $this)
            ->set("MSGS", $MSGS)
            ->set("section", $section)
            ->set("User", $User);

        try {

            switch ($view) {

                case 'overview':

                    // Require hub
                    require_once($CFG->dirroot . '/local/df_hub/lib.php');

                    $site = new \local_df_hub\site();
                    $TPL->set("site", $site);

                    // Qual Structures
                    $structures = \block_gradetracker\QualificationStructure::getAllStructures();
                    $TPL->set("structures", $structures);

                    // Stats
                    $TPL->set("countUsers", \block_gradetracker\User::countUsers());
                    $TPL->set("countCourses", \block_gradetracker\Course::countCourses());

                    // Logs
                    $TPL->set("logs", \block_gradetracker\Log::getRecentLogs(15));

                    // Scheduled Task
                    $TPL->set("task", \core\task\manager::get_scheduled_task('\block_gradetracker\task\refresh_site_registration'));

                    break;

                case 'settings':

                    $this->displayConfigSettings($TPL, $VARS, $PAGE, $section, $page, $id);

                    break;

                case 'structures':

                    $this->displayConfigStructures($TPL, $VARS, $section, $page, $id);

                    break;

                case 'quals':

                    $this->displayConfigQuals($TPL, $VARS, $section, $page, $id);

                    break;

                case 'units':

                    $this->displayConfigUnit($TPL, $section, $page, $id);

                    break;

                case 'course':

                    $this->displayConfigCourse($TPL, $section, $page, $id);

                    break;

                case 'data':

                    $this->displayConfigData($TPL, $section, $page);

                    break;

                case 'assessments':

                    switch ($section) {

                        case 'modules':

                            $this->displayConfigAssessmentsModules($TPL, $page, $User);

                            break;

                        case 'manage':

                            $this->displayConfigAssessmentsManage($TPL, $page, $User);

                            break;

                    }

                    break;

                case 'tests':

                    $this->displayConfigTestsTG($TPL);

                    break;

                case 'reporting':

                    $this->displayConfigReporting($TPL, $section, $page, $id);

                    break;
            }

            if ($section && $page) {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'/'.$section.'/'.$page.'.html';
            } else if ($section) {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'/'.$section.'.html';
            } else {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'.html';
            }

            $TPL->load($file);
            $TPL->display();

        } catch (\block_gradetracker\GTException $e) {
            echo $e->getException();
        }

    }


    /**
     * Display configuration settings
     * @param type $TPL
     * @param type $VARS
     * @param type $PAGE
     * @param type $section
     * @param type $page
     * @param type $id
     */
    public function displayConfigSettings($TPL, $VARS, $PAGE, $section, $page, $id) {
        if ($section == 'general') {

            // Get the possible layouts of the theme
            ksort($PAGE->theme->layouts);
            $TPL->set("layouts", $PAGE->theme->layouts);

        } else if ($section == 'qual') {

            // Qual builds
            $TPL->set("builds", \block_gradetracker\QualificationBuild::getAllBuilds());

            if ($page == 'coefficients') {
                $TPL->set("percentiles", \block_gradetracker\Setting::getSetting('qual_weighting_percentiles'));
                $TPL->set("qualifications", \block_gradetracker\Qualification::getAllQualifications());
            }

        } else if ($section == 'user') {
            $cols = $this->getSetting('student_columns');
            $TPL->set("cols", explode(",", $cols));
        } else if ($section == 'assessments') {
            $fields = \block_gradetracker\Assessment::getCustomFormFields();
            $TPL->set("fields", $fields);
            $TPL->set("cntCustomFormElements", 0);
        } else if ($section == 'reporting') {
            $TPL->set("categories", \core_course_category::make_categories_list());
            $TPL->set("reportingCats", $this->getReportingCategories());
            $TPL->set("structures", \block_gradetracker\QualificationStructure::getAllStructures());
        }

    }


    /**
     * Display configuration structures
     * @param type $TPL
     * @param type $VARS
     * @param type $section
     * @param type $page
     * @param type $id
     */
    public function displayConfigStructures($TPL, $VARS, $section, &$page, $id) {
        $Structure = false;

        // Qualification Structures
        if ($section == 'qual') {

            if ( $page == 'new' || $page == 'edit' ) {

                $page = 'new';
                $Structure = new \block_gradetracker\QualificationStructure($id);

                // If we've submitted post data, that object will be in VARS to use instead of a blank object
                if (isset($VARS['qualStructure'])) {
                    $Structure = $VARS['qualStructure'];
                }

            } else if ($page == 'delete') {
                $Structure = new \block_gradetracker\QualificationStructure($id);
            } else {
                // Overview page.
                $TPL->set("qualStructures", \block_gradetracker\QualificationStructure::getAllStructures());
            }

            // Existing structures
            $TPL->set("possibleLevels", \block_gradetracker\QualificationStructure::getPossibleStructureLevels());
            $TPL->set("possibleFeatures", \block_gradetracker\QualificationStructure::getPossibleStructureFeatures());
            $TPL->set("Structure", $Structure);
            $TPL->set("cntCustomFormElements", 0);
            $TPL->set("cntRules", 0);

        } else if ($section == 'builds') {

            $TPL->set("builds", \block_gradetracker\QualificationBuild::getAllBuilds());

            $Build = new \block_gradetracker\QualificationBuild($id);

            if ($page == 'new' || $page == 'edit') {

                $page = 'new';

                $TPL->set("qualStructures", \block_gradetracker\QualificationStructure::getAllStructures());
                $TPL->set("qualLevels", \block_gradetracker\Level::getAllLevels());
                $TPL->set("qualSubTypes", \block_gradetracker\SubType::getAllSubTypes());

                if (isset($VARS['qualBuild'])) {
                    $Build = $VARS['qualBuild'];
                }

            } else if ($page == 'defaults') {

                $TPL->set("Structure", new \block_gradetracker\QualificationStructure($Build->getStructureID()));

            }

            $TPL->set("Build", $Build);
            $TPL->set("cntAwards", 0);

        } else if ($section == 'grade') {

            // Grading Structures.

            $type = optional_param('type', false, PARAM_TEXT);

            // If the build is specified we are creating grading structures for the build instead of the qual structure
            // In which case, we can load the QualStructure from the Build before hand
            $buildID = optional_param('build', false, PARAM_INT);

            // If Build specified
            if ($buildID) {
                $id = false; // Reset the QualStructure id to false
                $build = new \block_gradetracker\QualificationBuild($buildID);
                if ($build->isValid()) {
                    $id = $build->getStructureID();
                    $TPL->set("Build", $build);
                }
            }

            // View the editing page
            if ($page == 'edit' && $id) {
                $Structure = new \block_gradetracker\QualificationStructure($id);
                $TPL->set("Structure", $Structure);
            } else if ($page == 'new_unit' && $id) {

                $Structure = new \block_gradetracker\QualificationStructure($id);
                $TPL->set("Structure", $Structure);

                if (isset($VARS['UnitGradingStructure'])) {
                    $TPL->set("UnitAwardStructure", $VARS['UnitGradingStructure']);
                } else {
                    $TPL->set("UnitAwardStructure", new \block_gradetracker\UnitAwardStructure());
                }

            } else if ($page == 'new_criteria' && $id) {

                $Structure = new \block_gradetracker\QualificationStructure($id);
                $TPL->set("Structure", $Structure);

                if (isset($VARS['CriteriaAwardStructure'])) {
                    $TPL->set("CriteriaAwardStructure", $VARS['CriteriaAwardStructure']);
                } else {
                    $TPL->set("CriteriaAwardStructure", new \block_gradetracker\CriteriaAwardStructure());
                }

            } else if ($page == 'edit_unit' && $id) {

                $page = 'new_unit';

                if (isset($VARS['UnitGradingStructure'])) {
                    $UnitAwardStructure = $VARS['UnitGradingStructure'];
                } else {
                    $UnitAwardStructure = new \block_gradetracker\UnitAwardStructure($id);
                }

                $TPL->set("UnitAwardStructure", $UnitAwardStructure);

                if ($UnitAwardStructure->isValid()) {
                    $Structure = new \block_gradetracker\QualificationStructure($UnitAwardStructure->getQualStructureID());
                    $TPL->set("Structure", $Structure);
                    $TPL->set("buildLevels", $Structure->getAllBuildLevels());
                    $TPL->set("builds", $Structure->getAllBuilds());
                }

            } else if ($page == 'edit_criteria' && $id) {

                $page = 'new_criteria';

                if (isset($VARS['CriteriaAwardStructure'])) {
                    $CriteriaAwardStructure = $VARS['CriteriaAwardStructure'];
                } else {
                    $CriteriaAwardStructure = new \block_gradetracker\CriteriaAwardStructure($id);
                }

                $TPL->set("CriteriaAwardStructure", $CriteriaAwardStructure);

                if ($CriteriaAwardStructure->isValid()) {

                    // Qual Structure
                    $TPL->set("Structure", new \block_gradetracker\QualificationStructure($CriteriaAwardStructure->getQualStructureID()));

                    // Qual Build
                    if ($CriteriaAwardStructure->getQualBuildID()) {
                        $TPL->set("Build", new \block_gradetracker\QualificationBuild($CriteriaAwardStructure->getQualBuildID()));
                    }

                }

            } else if ($page == 'delete_unit' && $id) {

                $UnitAwardStructure = new \block_gradetracker\UnitAwardStructure($id);
                $TPL->set("UnitAwardStructure", $UnitAwardStructure);

            } else if ($page == 'delete_criteria' && $id) {

                $CriteriaAwardStructure = new \block_gradetracker\CriteriaAwardStructure($id);
                $TPL->set("CriteriaAwardStructure", $CriteriaAwardStructure);

            }

            // Build Or Structure to be used to select the grading structures and not duplicate code
            if (isset($build) && $build && $build->isValid()) {
                $TPL->set("Object", $build);
            } else {
                $TPL->set("Object", $Structure);
            }

            $TPL->set("qualStructures", \block_gradetracker\QualificationStructure::getAllStructures());
            $TPL->set("type", $type);
            $TPL->set("cntAwards", 0);

        } else if ($section == 'levels') {

            $levels = \block_gradetracker\Level::getAllLevels();
            $TPL->set("levels", $levels);

            if ($page == 'new' || $page == 'edit') {

                $Level = new \block_gradetracker\Level($id);
                $page = 'new';
                $TPL->set("Level", $Level);

            } else if ($page == 'delete') {
                $Level = new \block_gradetracker\Level($id);
                $TPL->set("Level", $Level);
            }

        } else if ($section == 'subtypes') {

            $subTypes = \block_gradetracker\SubType::getAllSubTypes();
            $TPL->set("subTypes", $subTypes);

            if ($page == 'new' || $page == 'edit') {

                $page = 'new';

                if (isset($VARS['SubType'])) {
                    $subType = $VARS['SubType'];
                } else {
                    $subType = new \block_gradetracker\SubType($id);
                }

                $TPL->set("SubType", $subType);

            } else if ($page == 'delete') {
                $subType = new \block_gradetracker\SubType($id);
                $TPL->set("SubType", $subType);
            }

        } else if ($section == 'qoe') {

            $TPL->set("allSubjects", \block_gradetracker\QualOnEntry::getAllSubjects());
            $TPL->set("allTypes", \block_gradetracker\QualOnEntry::getAllTypes());
            $TPL->set("allGrades", \block_gradetracker\QualOnEntry::getAllGrades());

        }
    }


    /**
     * Display configuration quals
     * @param type $TPL
     * @param type $VARS
     * @param type $section
     * @param type $page
     * @param type $id
     */
    public function displayConfigQuals($TPL, $VARS, &$section, $page, $id) {
        // Edit is just used for aesthetic purposes, it's the "new" form
        if ($section == 'edit') {
            $section = 'new';
        }

        // Overview
        if ($section == 'overview') {

            $stats = array();
            $stats['active'] = \block_gradetracker\Statistics::getQualifications('active');
            $stats['inactive'] = \block_gradetracker\Statistics::getQualifications('inactive');
            $stats['correctcredits'] = \block_gradetracker\Statistics::getQualificationsByCredits('correct');
            $stats['incorrectcredits'] = \block_gradetracker\Statistics::getQualificationsByCredits('incorrect');

            $TPL->set("stats", $stats);

        } else if ($section == 'new') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Qualification\GUI($id);
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormNewQualification();
            $TPL->set("Qualification", $GUI);

        } else if ($section == 'search') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Qualification\GUI($id);
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormSearchQualifications();
            $TPL->set("Qualification", $GUI);

        } else if ($section == 'delete') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Qualification\GUI($id);
            }
            $qualification = new \block_gradetracker\Qualification($id);
            $TPL->set("qualification", $qualification);
            $GUI->loadTemplate($TPL);
        } else if ($section == 'copy') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Qualification\GUI($id);
            }
            $copyQual = new \block_gradetracker\Qualification($id);
            $TPL->set("copyQual", $copyQual);
            $GUI->loadTemplate($TPL);
        }
    }


    /**
     * Display configuration unit
     * @param type $TPL
     * @param type $VARS
     * @param type $section
     * @param type $page
     * @param type $id
     */
    public function displayConfigUnit($TPL, &$section, $page, $id) {

        global $VARS;

        // Edit and New use same template, so set it to "new" if it's "edit"
        if ($section == 'edit') {
            $section = 'new';
        }

        // Overview
        if ($section == 'overview') {

            $stats = array();
            $stats['active'] = \block_gradetracker\Statistics::getUnits('active');
            $stats['inactive'] = \block_gradetracker\Statistics::getUnits('inactive');

            $TPL->set("stats", $stats);

        } else if ($section == 'new') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Unit\GUI($id);
                $VARS['GUI'] = $GUI;
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormNewUnit();
            $TPL->set("Unit", $GUI);

        } else if ($section == 'search') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Unit\GUI($id);
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormSearchUnits();
        } else if ($section == 'delete') {

            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Unit\GUI($id);
            }
            $unit = new \block_gradetracker\Unit($id);
            $TPL->set("unit", $unit);
            $GUI->loadTemplate($TPL);
        } else if ($section == 'copy') {
            if (isset($VARS['GUI'])) {
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \block_gradetracker\Unit\GUI($id);
            }
            $copyUnit = new \block_gradetracker\Unit($id);
            $TPL->set("copyUnit", $copyUnit);
            $GUI->loadTemplate($TPL);
        }
    }


    /**
     * Display configuration course
     * @param type $TPL
     * @param type $section
     * @param type $page
     * @param type $id
     */
    public function displayConfigCourse($TPL, $section, $page, $id) {

        global $USER, $PAGE;

        $User = new \block_gradetracker\User($USER->id);

        if ($section == 'search') {
            $TPL->set("categories", \core_course_category::make_categories_list());
        } else if ($section == 'my') {
            $TPL->set("courses", $User->getCourses("STAFF"));
        } else {
            $course = new \block_gradetracker\Course($id);
            $TPL->set("Course", $course);
            if (!$course->isValid()) {
                print_error('invalidcourse', 'block_gradetracker');
            }

            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($id, "STAFF") && !$User->hasCapability('block/gradetracker:edit_all_courses')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            // Optional Parent course ID
            $pID = optional_param('pID', false, PARAM_INT);
            $TPL->set("pID", $pID);

            if ($section == 'quals') {

                if (!$User->hasCapability('block/gradetracker:edit_course_quals')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                $TPL->set("allStructures", \block_gradetracker\QualificationStructure::getAllStructures());
                $TPL->set("allLevels", \block_gradetracker\Level::getAllLevels());
                $TPL->set("allSubTypes", \block_gradetracker\SubType::getAllSubTypes());
                $QualPicker = new \block_gradetracker\FormElement();
                $QualPicker->setType('QUALPICKER');
                $QualPicker->setValue( $course->getCourseQualifications() );
                $TPL->set("QualPicker", $QualPicker);

            } else if ($section == 'userquals') {

                $TPL->set("staff", $course->getStaff());
                $TPL->set("courseQuals", $course->getCourseQualifications(true));

            } else if ($section == 'userunits') {

                global $GTEXE;
                $GTEXE = \block_gradetracker\Execution::getInstance();
                $GTEXE->QUAL_STRUCTURE_MIN_LOAD = true;
                $GTEXE->QUAL_BUILD_MIN_LOAD = true;
                $GTEXE->QUAL_MIN_LOAD = true;
                $GTEXE->UNIT_MIN_LOAD = true;
                $GTEXE->STUDENT_LOAD_LEVEL = \block_gradetracker\Execution::STUD_LOAD_LEVEL_UNIT;

            } else if ($section == 'activities') {

                // Check permissions
                if (!$User->hasCapability('block/gradetracker:edit_course_activity_refs')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                $modLinks = \block_gradetracker\ModuleLink::getEnabledModLinks();
                $courseQuals = $course->getCourseQualifications(true);
                $TPL->set("courseQuals", $courseQuals);
                $TPL->set("modLinks", $modLinks);
                $TPL->set("activities", $course->getSupportedActivities());

                if ($page == 'add') {

                    $cmID = optional_param('cmid', false, PARAM_INT);
                    $qID = optional_param('qualid', false, PARAM_INT);
                    $uID = optional_param('unitid', false, PARAM_INT);
                    $submittedcmID = optional_param('coursemoduleid', false, PARAM_INT);

                    // If cmID is valid, that means we clicked on an assignment and we want to add units to it
                    if ($cmID) {

                        if ($submittedcmID) {
                            $cmID = $submittedcmID;
                        }
                        $TPL->set("cmID", $cmID);

                        $moduleActivity = \block_gradetracker\ModuleLink::getModuleLinkFromCourseModule($cmID);
                        $TPL->set("moduleActivity", $moduleActivity);

                        $unitsLinked = array();
                        if ($courseQuals) {
                            foreach ($courseQuals as $courseQual) {
                                $unitsLinked[$courseQual->getID()] = \block_gradetracker\Activity::getUnitsLinkedToCourseModule($cmID, $courseQual->getID(), true);
                            }
                        }

                        $TPL->set("unitsLinked", $unitsLinked);
                        $TPL->set("viewBy", "cm");

                    } else {
                        // If qual & unit are valid, we clicked on a unit and want to add assignments to it
                        $qual = new \block_gradetracker\Qualification($qID);
                        $unit = $qual->getUnit($uID);
                        if (!$unit) {
                            print_error( 'invalidunit', 'block_gradetracker');
                        }
                        $criteria = $unit->sortCriteria(false, true);

                        $qualUnitActivities = \block_gradetracker\ModuleLink::getModulesOnUnit($qual->getID(), $unit->getID(), $course->id);

                        $TPL->set("qual", $qual);
                        $TPL->set("unit", $unit);
                        $TPL->set("criteria", $criteria);
                        $TPL->set("unitActivities", $qualUnitActivities);
                        $TPL->set("viewBy", "unit");

                    }
                } else if ($page == 'delete') {

                    // Check permissions
                    if (!$User->hasCapability('block/gradetracker:delete_course_activity_refs')) {
                        print_error('invalidaccess', 'block_gradetracker');
                    }

                    $cmID = optional_param('cmid', false, PARAM_INT);
                    $qID = optional_param('qualid', false, PARAM_INT);
                    $uID = optional_param('unitid', false, PARAM_INT);
                    $part = optional_param('part', null, PARAM_INT);

                    $moduleLink = \block_gradetracker\ModuleLink::getModuleLinkFromCourseModule($cmID);

                    $qual = new \block_gradetracker\Qualification($qID);
                    if ($uID) {
                        $unit = new \block_gradetracker\Unit($uID);
                        $unitArray = array( $unit );
                        $TPL->set("unit", $unit);
                    } else {
                        $unitArray = $moduleLink->getUnitsOnModule($qual->getID());
                    }

                    $TPL->set("qual", $qual);
                    $TPL->set("unitArray", $unitArray);
                    $TPL->set("part", $part);
                    $TPL->set("cmID", $cmID);
                    $TPL->set("moduleLink", $moduleLink);

                }

            }
        }

    }
    /**
     * Display configuration assessments manage
     * @param type $TPL
     * @param type $section
     */
    public function displayConfigData($TPL, $section, $page) {
        switch ($section) {

            case 'tg':

                $reload = optional_param('reload', false, PARAM_BOOL);
                $quals = \block_gradetracker\Qualification::getAllQualifications();

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateTargetGradesCSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleTargetGradesCSV($reload));

                $QualPicker = new \block_gradetracker\FormElement();
                $QualPicker->setType('QUALPICKER');
                $TPL->set("QualPicker", $QualPicker);

                break;

            case 'qoe':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateQoECSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleQoECSV($reload));

                break;

            case 'avggcse':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateAvgGCSECSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleAvgGCSECSV($reload));

                break;

            case 'aspg':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateAspirationalGradesCSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleAspirationalGradesCSV($reload));

                break;

            case 'ceta':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateCetaGradesCSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleCetaGradesCSV($reload));

                break;

            case 'wcoe':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateWCoeCSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleWCoeCSV($reload));

                break;

            case 'ass':

                $reload = optional_param('reload', false, PARAM_BOOL);
                $assessments = \block_gradetracker\Assessment::getAllAssessments();

                $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateAssGradesCSV($reload));
                $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleAssGradesCSV($reload));
                $TPL->set("assessments", $assessments);

                break;

        }

    }

    /**
     * Display configuration assessments manage
     * @param type $TPL
     * @param type $page
     * @param type $User
     */
    public function displayConfigAssessmentsModules($TPL, $page, $User) {

        global $VARS;

        // Check permissions
        if (!$User->hasCapability('block/gradetracker:edit_activity_settings')) {
            print_error('invalidaccess', 'block_gradetracker');
        }

        if ($page == 'edit' || $page == 'delete') {

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:'.$page.'_course_activity_refs')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Mod = (isset($VARS['Mod'])) ? $VARS['Mod'] : new \block_gradetracker\ModuleLink($id);
            $TPL->set("Mod", $Mod);
            $TPL->set("allMods", \block_gradetracker\ModuleLink::getAllInstalledMods());

        }

        $TPL->set("mods", \block_gradetracker\ModuleLink::getEnabledModLinks());
    }


    /**
     * Display configuration assessments manage
     * @param type $TPL
     * @param type $page
     * @param type $User
     */
    public function displayConfigAssessmentsManage($TPL, $page, $User) {

        global $VARS;

        if ($page == 'edit') {

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_assessments')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = (isset($VARS['Assessment'])) ? $VARS['Assessment'] : new \block_gradetracker\Assessment($id);
            $TPL->set("Assessment", $Assessment);
            $TPL->set("allTypes", \block_gradetracker\Assessment::getAllTypes());

            $quals = $Assessment->getQuals();

            $QualPicker = new \block_gradetracker\FormElement();
            $QualPicker->setType('QUALPICKER');
            $QualPicker->setValue( $quals );
            $TPL->set("QualPicker", $QualPicker);

            $formFields = \block_gradetracker\Assessment::getCustomFormFields();
            $TPL->set("formFields", $formFields);

            // Get distinct list of qualification structures attached to this assessment
            $qualStructures = array();
            if ($quals) {
                foreach ($quals as $qualID) {
                    $qualStructure = \block_gradetracker\Qualification::getStructureFromQualID($qualID);
                    if ($qualStructure) {
                        $qualStructures[$qualStructure->getID()] = $qualStructure;
                    }
                }
            }
            $TPL->set("qualStructuresArray", $qualStructures);

            // Get distinct list of qualification builds attached to this assessment
            $qualBuilds = array();
            if ($quals) {
                foreach ($quals as $qualID) {
                    $qualBuild = \block_gradetracker\Qualification::getBuildFromQualID($qualID);
                    if ($qualBuild) {
                        $qualBuilds[$qualBuild->getID()] = $qualBuild;
                    }
                }
            }
            $TPL->set("qualBuildsArray", $qualBuilds);

        } else if ($page == 'delete') {

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_assessments')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = (isset($VARS['Assessment'])) ? $VARS['Assessment'] : new \block_gradetracker\Assessment($id);
            $TPL->set("Assessment", $Assessment);

        } else {

            $TPL->set("allAssessments", \block_gradetracker\Assessment::getAllAssessments());

        }
    }


    /**
     * Display configuration reporting
     * @param type $TPL
     */
    public function displayConfigTestsTG($TPL) {
        $reload = optional_param('reload', false, PARAM_BOOL);

        $TPL->set("templateFile", \block_gradetracker\CSV\Template::generateTemplateTargetGradesCSV($reload));
        $TPL->set("exampleFile", \block_gradetracker\CSV\Example::generateExampleTargetGradesCSV($reload));
        $TPL->set("allQuals", \block_gradetracker\Qualification::getAllQualifications());
    }


    /**
     * Display configuration reporting
     * @param type $TPL
     * @param type $page
     * @param type $id
     */
    public function displayConfigReporting($TPL, $section, $page, $id) {
        global $DB, $MSGS;

        // Pre-Built reports
        if ($section == 'reports') {

            // Check permissions
            if (!\gt_has_capability('block/gradetracker:run_built_report')) {
                print_error('invalidaccess');
            }

            // Criteria Progress report
            if ($page == 'critprog') {

                $Report = new \block_gradetracker\Reports\CriteriaProgressReport();
                $structures = \block_gradetracker\QualificationStructure::getStructuresBySetting('custom_dashboard_view', 'view-criteria-short');

                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("awardNames", \block_gradetracker\CriteriaAward::getDistinctNamesNonMet());
                $TPL->set("Report", $Report);

            } else if ($page == 'passprog') {

                $Report = new \block_gradetracker\Reports\PassCriteriaProgressReport();
                $structures = \block_gradetracker\QualificationStructure::getStructuresBySetting('reporting_pass_criteria_method', true);

                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("awardNames", \block_gradetracker\CriteriaAward::getDistinctNamesNonMet());
                $TPL->set("Report", $Report);

            } else if ($page == 'passsummary') {

                $Report = new \block_gradetracker\Reports\PassCriteriaSummaryReport();
                $structures = \block_gradetracker\QualificationStructure::getStructuresBySetting('reporting_pass_criteria_method', true);

                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("Report", $Report);
                $TPL->set("awardNames", \block_gradetracker\CriteriaAward::getDistinctNamesNonMet());

            }

        } else if ($section == 'logs') {

            $GTEXE = \block_gradetracker\Execution::getInstance();
            $GTEXE->min();

            $TPL->set("reflectionClass", new \ReflectionClass("\block_gradetracker\Log"));
            $TPL->set("allQuals", \block_gradetracker\Qualification::getAllQualifications(true));
            $TPL->set("allUnits", \block_gradetracker\Unit::getAllUnits(false));
            $TPL->set("allCourses", \block_gradetracker\Course::getAllCoursesWithQuals());

        }

    }




    /**
     * Save configuration settings
     * @param type $view
     * @param type $section
     */
    public function saveConfig($view, $section, $page) {

        global $DB, $CFG, $MSGS, $VARS;

        // All submitted configuration forms are routed through this method. So we can just check for the sesskey here.
        // Even if the data is supplied via $_GET, this method won't be called unless $_POST is populated with something (See: config.php).
        require_sesskey();

        switch ($view) {

            case 'settings':

                // If the save was successful, log it
                if ($this->saveConfigSettings($section, $page)) {

                    // ------------ Logging Info
                    $detail = 'GT_LOG_DETAILS_UPDATED_PLUGIN_'.strtoupper($section).'_SETTINGS';
                    $Log = new \block_gradetracker\Log();
                    $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
                    $Log->details = constant('\block_gradetracker\Log::' . $detail);
                    $Log->afterjson = \df_clean_entire_post();
                    $Log->save();
                    // ------------ Logging Info

                }

                break;

            // Structures
            case 'structures':

                // Qual structure or Grade structure
                switch ($section) {

                    case 'qual':
                        $this->saveConfigQualStructures($page);
                        break;

                    case 'builds':
                        $this->saveConfigQualBuilds($page);
                        break;

                    case 'grade':
                        $this->saveConfigGradingStructures($page);
                        break;

                    case 'levels':
                        $this->saveConfigQualLevels($page);
                        break;

                    case 'subtypes':
                        $this->saveConfigQualSubTypes($page);
                        break;

                    case 'qoe':
                        $this->saveConfigQOE();
                        break;

                }

                break;

            case 'quals':
                $this->saveConfigQuals($section, $page);
                break;

            case 'units':
                $this->saveConfigUnits($section, $page);
                break;

            case 'course':
                $this->saveConfigCourse($section, $page);
                break;

            case 'data':

                switch ($section) {
                    case 'tg':
                        $this->saveConfigDataTargetGrades();
                        break;
                    case 'qoe':
                        $this->saveConfigDataQOE();
                        break;
                    case 'avggcse':
                        $this->saveConfigDataAvgGCSE();
                        break;
                    case 'aspg':
                        $this->saveConfigDataAspirationalGrades();
                        break;
                    case 'ceta':
                        $this->saveConfigDataCetaGrades();
                        break;
                    case 'wcoe':
                        $this->saveConfigDataWeightingCoefficients();
                        break;
                    case 'ass':
                        $this->saveConfigDataAssessmentGrades();
                        break;
                }

                break;

            case 'assessments':
                $this->saveConfigAssessments($section, $page);
                break;

            case 'tests':
                $this->saveConfigTestsAveGCSE($section);
                break;

            case 'reporting':
                switch ($section) {
                    case 'logs':
                        $this->saveConfigReportingLogs();
                        break;
                }
                break;

        }

    }

    /**
     * Save the Quals on Entry forms
     * @global \block_gradetracker\type $CFG
     * @global \block_gradetracker\type $MSGS
     */
    private function saveConfigQOE() {

        global $CFG, $MSGS;

        $submission = array(
            'save_subjects' => optional_param('save_subjects', false, PARAM_TEXT),
            'save_types' => optional_param('save_types', false, PARAM_TEXT),
            'save_grades' => optional_param('save_grades', false, PARAM_TEXT),
        );

        $settings = array(
            'ids' => df_optional_param_array_recursive('ids', false, PARAM_INT),
            'names' => df_optional_param_array_recursive('names', false, PARAM_TEXT),
            'levels' => df_optional_param_array_recursive('levels', false, PARAM_TEXT),
            'weightings' => df_optional_param_array_recursive('weightings', false, PARAM_TEXT),
            'types' => df_optional_param_array_recursive('types', false, PARAM_TEXT),
            'grades' => df_optional_param_array_recursive('grades', false, PARAM_TEXT),
            'points' => df_optional_param_array_recursive('points', false, PARAM_TEXT),
        );

        // Subjects
        if ($submission['save_subjects']) {

            $idArray = array();
            if ($settings['ids']) {

                for ($i = 0; $i < count($settings['ids']); $i++) {

                    $id = trim($settings['ids'][$i]);
                    $name = trim($settings['names'][$i]);
                    if (empty($name)) {
                        continue;
                    }

                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;

                    // Save the record
                    $result = \block_gradetracker\QualOnEntry::saveSubject($id, $name);
                    if (is_numeric($result)) {
                        $idArray[] = $result;
                    }

                }

            }

            // Remove any we didn't save this time, as we must have deleted them on the form
            \block_gradetracker\QualOnEntry::deleteSubjectsNotSaved($idArray);
            $MSGS['success'] = get_string('qoesubjectssaved', 'block_gradetracker');

        } else if ($submission['save_types']) {

            $idArray = array();
            if ($settings['ids']) {

                for ($i = 0; $i < count($settings['ids']); $i++) {

                    $id = trim($settings['ids'][$i]);
                    $name = trim($settings['names'][$i]);
                    $lvl = trim($settings['levels'][$i]);
                    $weight = trim($settings['weightings'][$i]);
                    if (empty($name)) {
                        continue;
                    }

                    // Set default weight to 0 if not valid
                    if ($weight == '') {
                        $weight = 0;
                    }

                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;

                    // Save the record
                    $result = \block_gradetracker\QualOnEntry::saveType($id, $name, $lvl, $weight);
                    if (is_numeric($result)) {
                        $idArray[] = $result;
                    }

                }

            }

            // Remove any we didn't save this time, as we must have deleted them on the form
            \block_gradetracker\QualOnEntry::deleteTypesNotSaved($idArray);
            $MSGS['success'] = get_string('qoetypessaved', 'block_gradetracker');

        } else if ($submission['save_grades']) {

            $idArray = array();
            if ($settings['ids']) {

                for ($i = 0; $i < count($settings['ids']); $i++) {

                    $id = trim($settings['ids'][$i]);
                    $type = trim($settings['types'][$i]);
                    $name = trim($settings['grades'][$i]);
                    $points = trim($settings['points'][$i]);
                    $weight = trim($settings['weightings'][$i]);
                    if (empty($name)) {
                        continue;
                    }

                    // Set default weight to 1 if not valid
                    if ($weight == '' || $weight < 0) {
                        $weight = 1;
                    }

                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;

                    // Save the record
                    $result = \block_gradetracker\QualOnEntry::saveGrade($id, $type, $name, $points, $weight);
                    if (is_numeric($result)) {
                        $idArray[] = $result;
                    }

                }

            }

            // Remove any we didn't save this time, as we must have deleted them on the form
            \block_gradetracker\QualOnEntry::deleteGradesNotSaved($idArray);
            $MSGS['success'] = get_string('qoegradessaved', 'block_gradetracker');

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QOE;
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save assessment configuration
     * @global \block_gradetracker\type $CFG
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @global type $USER
     * @param type $section
     * @param type $page
     */
    private function saveConfigAssessments($section, $page) {

        global $CFG, $MSGS, $VARS, $USER;

        $User = new \block_gradetracker\User($USER->id);

        // New/Edit module link
        if ($section == 'modules' && $page == 'edit') {

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_activity_settings')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Module = new \block_gradetracker\ModuleLink($id);
            $Module->loadPostData();

            $valid = ($Module->isValid());

            if ( $Module->hasNoErrors() && $Module->save() ) {

                $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');

                // Log variables
                $detail = ($valid) ? \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_MODULE_LINK : \block_gradetracker\Log::GT_LOG_DETAILS_CREATED_MODULE_LINK;
                $attributes = array('id' => $Module->getID());

            } else {
                $MSGS['errors'] = $Module->getErrors();
            }

            $VARS['Mod'] = $Module;

        } else if ($section == 'modules' && $page == 'delete') {

            $submission = array(
                'confirm_delete_mod_link' => optional_param('confirm_delete_mod_link', false, PARAM_TEXT),
                'run_away' => optional_param('run_away', false, PARAM_TEXT),
            );

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_course_activity_refs')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Module = new \block_gradetracker\ModuleLink($id);

            if ($submission['run_away']) {
                redirect( $CFG->wwwroot . '/blocks/gradetracker/config.php?view=assessments&section=modules' );
            } else if ($submission['confirm_delete_mod_link']) {

                $Module->delete();
                $MSGS['success'] = get_string('modlinking:deleted', 'block_gradetracker');

                // Log variables
                $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DELETED_MODULE_LINK;
                $attributes = array('id' => $Module->getID());

            }

            $VARS['Mod'] = $Module;

        } else if ($section == 'manage' && $page == 'edit') {

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_assessments')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = new \block_gradetracker\Assessment($id);
            $Assessment->loadPostData();

            $valid = ($Assessment->isValid());

            if ($Assessment->hasNoErrors()) {

                $Assessment->save();
                $MSGS['success'] = get_string('assessmentsaved', 'block_gradetracker');

                // Log variables
                $detail = ($valid) ? \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_ASSESSMENT : \block_gradetracker\Log::GT_LOG_DETAILS_CREATED_ASSESSMENT;
                $attributes = array(\block_gradetracker\Log::GT_LOG_ATT_ASSID => $Assessment->getID());

            } else {
                $MSGS['errors'] = $Assessment->getErrors();
            }

            $VARS['Assessment'] = $Assessment;

        } else if ($section == 'manage' && $page == 'delete') {

            $submission = array(
                'confirm_delete_assessment' => optional_param('confirm_delete_assessment', false, PARAM_TEXT),
                'run_away' => optional_param('run_away', false, PARAM_TEXT),
            );

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_assessments')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = new \block_gradetracker\Assessment($id);

            if ($submission['run_away']) {
                redirect( $CFG->wwwroot . '/blocks/gradetracker/config.php?view=assessments&section=manage' );
            } else if ($submission['confirm_delete_assessment']) {

                $Assessment->delete();
                $MSGS['success'] = get_string('assessment:deleted', 'block_gradetracker');

                // Log variables
                $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DELETED_ASSESSMENT;
                $attributes = array(\block_gradetracker\Log::GT_LOG_ATT_ASSID => $Assessment->getID());

            }

            $VARS['Assessment'] = $Assessment;

        }

        if (isset($detail)) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = \df_clean_entire_post();
            $Log->attributes = $attributes;
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Submit the QoE import
     * @global \block_gradetracker\type $MSGS
     */
    private function saveConfigDataQOE() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportQualsOnEntry();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:qoe:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }

    /**
     * Submit the AVG GCSE import
     * @global \block_gradetracker\type $MSGS
     */
    private function saveConfigDataAvgGCSE() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportAvgGCSE();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }

    /**
     * Submit the TG import
     * @global \block_gradetracker\type $MSGS
     */
    private function saveConfigDataTargetGrades() {

        global $MSGS;

        // Minimum load - we don't need the criteria
        $GTEXE = \block_gradetracker\Execution::getInstance();
        $GTEXE->min();
        $GTEXE->STUDENT_LOAD_LEVEL = \block_gradetracker\Execution::STUD_LOAD_LEVEL_UNIT;

        $submission = array(
            'submit_calculate' => optional_param('submit_calculate', false, PARAM_TEXT),
        );

        $settings = array(
            'quals' => df_optional_param_array_recursive('quals', false, PARAM_INT),
            'options' => df_optional_param_array_recursive('options', false, PARAM_TEXT),
        );

        if ($submission['submit_calculate'] && $settings['quals']) {

            $student_counter = [0, 0];
            $output = '';

            if ($settings['options']) {

                $tg_options = $settings['options'];
                $tg_added_qualID = $settings['quals'];

                foreach ($tg_added_qualID as $qualid) {

                    $qual = new \block_gradetracker\Qualification\UserQualification($qualid);
                    $students_on_qual = $qual->getUsers('student');
                    $student_counter[0] += 1;

                    foreach ($students_on_qual as $student) {

                        $qual->clearStudent();

                        $student_counter[1] += 1;

                        // Calculate target grades
                        if (isset($tg_options['calc_tg'])) {

                            $tg = $student->calculateTargetGrade($qualid);

                            if ($tg) {
                                $tg = $tg->getName();
                            } else {
                                $tg = '-';
                            }

                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedtargetgrade', 'block_gradetracker') . ': ' . $tg . '<br>';
                        }

                        // Calculate weighted target grades
                        if (isset($tg_options['calc_wtg'])) {

                            $tg = $student->calculateWeightedTargetGrade($qualid);

                            if ($tg) {
                                $tg = $tg->getName();
                            } else {
                                $tg = '-';
                            }

                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedweightedtargetgrade', 'block_gradetracker') . ': ' . $tg . '<br>';
                        }

                        // Calculate avg gcse scores
                        if (isset($tg_options['calc_avg'])) {

                            $avg = $student->calculateAverageGCSEScore($qualid);
                            if (!$avg) {
                                $avg = '-';
                            }

                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedavggcse', 'block_gradetracker') . ': ' . $avg . '<br>';
                        }

                        // Calculate aspirational grades
                        if (isset($tg_options['calc_asp'])) {

                            $asp = $student->calculateAspirationalGrade($qualid);

                            if ($asp) {
                                $asp = $asp->getName();
                            } else {
                                $asp = '-';
                            }

                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedaspgrade', 'block_gradetracker') . ': ' . $asp . '<br>';
                        }

                        // Calculate predicted grades
                        if (isset($tg_options['calc_pred'])) {

                            // For this we need to load the student into the qual
                            $qual->loadStudent($student);
                            $qual->calculatePredictedAwards();

                            $pred = $qual->getUserPredictedOrFinalAward();
                            $type = $pred[0];
                            if ($pred[1]) {
                                $grade = $pred[1]->getName();
                            } else {
                                $grade = '-';
                            }

                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedpredictedgrade', 'block_gradetracker') . ': ' . $grade . ' ('.$type.')<br>';

                        }

                    }

                    $output .= '<br>';

                }

            }

            $MSGS['tg_added'] = sprintf( get_string('config:data:studentsadded', 'block_gradetracker'), $student_counter[1], $student_counter[0]);
            $MSGS['calc_output'] = $output;

        }

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportTargetGrades();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }

    private function saveConfigDataAspirationalGrades() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportAspirationalGrades();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }

    private function saveConfigDataCetaGrades() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportCETAGrades();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }

    private function saveConfigDataAssessmentGrades() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {

            $settings = array(
                'assID' => optional_param('assID', false, PARAM_INT),
            );


            // Check we chose an assessment
            if (!$settings['assID']) {
                $MSGS['errors'] = get_string('errors:import:ass:id', 'block_gradetracker');
                return false;
            }

            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportAssessmentGrades($settings['assID']);

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }


    private function saveConfigDataWeightingCoefficients() {

        global $MSGS;

        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error']) {
            $import = new \block_gradetracker\DataImport($_FILES['file']);
            $import->runImportWCoe();

            if ($import->getErrors()) {
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }

        }

    }



    /**
     * Save the configuration from the course settings
     * @global type $DB
     * @global \block_gradetracker\type $CFG
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @param type $section
     * @param type $page
     * @return boolean
     */
    private function saveConfigCourse($section, $page) {

        global $DB, $CFG, $MSGS, $VARS, $USER;

        $User = new \block_gradetracker\User($USER->id);

        $submission = array(
            'confirm_delete_activity_link' => optional_param('confirm_delete_activity_link', false, PARAM_TEXT),
        );

        $settings = array(
            'coursemoduleid' => optional_param('coursemoduleid', false, PARAM_INT),
            'qualid' => optional_param('qualid', false, PARAM_INT),
            'unitid' => optional_param('unitid', false, PARAM_INT),
            'courseID' => optional_param('courseID', false, PARAM_INT),
            'coursename' => optional_param('coursename', false, PARAM_TEXT),
            'coursecats' => optional_param('coursecats', false, PARAM_TEXT),
            'gt_criteria' => df_optional_param_array_recursive('gt_criteria', false, PARAM_TEXT),
        );

        // Delete activity link
        if ($submission['confirm_delete_activity_link']) {

            $cmID = optional_param('cmid', false, PARAM_INT);
            $qID = optional_param('qualid', false, PARAM_INT);
            $uID = optional_param('unitid', false, PARAM_INT);
            $part = optional_param('part', null, PARAM_INT);

            // Get the course this coursemodule is on
            $Course = \block_gradetracker\Activity::getCourseFromCourseModule($cmID);
            if (!$Course || !$Course->isValid()) {
                print_error('invalidcourse', 'block_gradetracker');
            }

            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($Course->id, "STAFF") && !\gt_has_capability('block/gradetracker:edit_all_courses')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            $links = \block_gradetracker\Activity::findLinks($cmID, $part, $qID, $uID);
            if ($links) {
                foreach ($links as $link) {
                    $link->remove();
                }
            }

            $MSGS['success'] = get_string('modlinking:deleted', 'block_gradetracker');

            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_DELETED_COURSE_ACTIVTY_LINK;
            $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_COURSEID, $Course->id)
                ->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_QUALID, $qID)
                ->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_UNITID, $uID);
            $Log->save();
            // ------------ Logging Info

        }

        if ($section != 'search') {
            $id = optional_param('id', false, PARAM_INT);
            $course = new \block_gradetracker\Course($id);
            if (!$course->isValid()) {
                print_error('invalidcourse', 'block_gradetracker');
            }

            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($id, "STAFF") && !\gt_has_capability('block/gradetracker:edit_all_courses')) {
                print_error('invalidaccess', 'block_gradetracker');
            }

            if ($section == 'quals') {

                $course->saveFormCourseQuals();

            } else if ($section == 'userquals') {

                $course->saveFormUserQuals();

            } else if ($section == 'userunits') {

                $course->saveFormUserUnits();

            } else if ($section == 'activities') {

                // Check permissions
                if (!$User->hasCapability('block/gradetracker:edit_course_activity_refs')) {
                    print_error('invalidaccess', 'block_gradetracker');
                }

                $cmID = optional_param('cmid', false, PARAM_INT);
                $qID = optional_param('qualid', false, PARAM_INT);
                $uID = optional_param('unitid', false, PARAM_INT);
                $part = optional_param('part', null, PARAM_INT);

                if ($page == 'add') {

                    // By course module
                    if ($cmID) {

                        require_once($CFG->dirroot . '/blocks/gradetracker/hook.php');
                        \gt_mod_hook_process($settings['coursemoduleid'], $course);
                        $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');

                    } else if ($qID && $uID) {

                        $qualID = $settings['qualid'];
                        $unitID = $settings['unitid'];
                        $course = new \block_gradetracker\Course($settings['courseID']);
                        $linkedCriteria = $settings['gt_criteria'];
                        $criteriaArray = array();

                        // If there are criteria we want to link, process them
                        if ($linkedCriteria) {

                            foreach ($linkedCriteria as $courseModID => $criteria) {

                                $criteriaArray[$courseModID] = array();

                                foreach ($criteria as $critID => $value) {

                                    if ( (is_numeric($value) && $value > 0) || !is_numeric($value) ) {

                                        $activity = new \block_gradetracker\Activity();
                                        $activity->setCourseModuleID($courseModID);
                                        $activity->setQualID($qualID);
                                        $activity->setUnitID($unitID);
                                        $activity->setCritID($critID);

                                        // If the value is an int > 0, then it must be a partID
                                        if (is_numeric($value) && $value > 0) {
                                            $activity->setPartID($value);
                                        }

                                        $activity->create();
                                        $criteriaArray[$courseModID][] = $critID;

                                    }

                                }

                            }

                        }

                        // Now remove any that are currently linked to this qual unit that were not submitted in the form
                        \block_gradetracker\Activity::removeNonSubmittedLinksOnUnit($qualID, $unitID, $course->id, $criteriaArray);

                        $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');

                        // ------------ Logging Info
                        $Log = new \block_gradetracker\Log();
                        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
                        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_COURSE_ACTIVITY_LINKS;
                        $Log->afterjson = $linkedCriteria;
                        $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_QUALID, $qualID)
                            ->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_UNITID, $unitID);
                        $Log->save();
                        // ------------ Logging Info

                    }

                }

            }
        } else if ($section == 'search') {
            $courses = \block_gradetracker\Course::search( array(
                "name" => $settings['coursename'],
                "catID" => $settings['coursecats']
            ) );

            $TPL = new \block_gradetracker\Template();
            $TPL->set("courses", $courses);
            $VARS['TPL'] = $TPL;
        }
    }

    private function saveConfigUnits($section, $page) {

        global $MSGS, $VARS;

        $id = optional_param('id', false, PARAM_INT);

        if ($section == 'edit') {
            $section = 'new';
        }

        $submission = array(
            'delete_unit' => optional_param('delete_unit', false, PARAM_TEXT),
            'copy_unit' => optional_param('copy_unit', false, PARAM_TEXT),
            'restoreUnit_x' => optional_param('restoreUnit_x', false, PARAM_TEXT),
            'submit_import_unit' => optional_param('submit_import_unit', false, PARAM_TEXT),
            'submit_search' => optional_param('submit_search', false, PARAM_TEXT),
        );

        // change deleted field in 1
        if ($submission['delete_unit'] && gt_has_capability('block/gradetracker:delete_restore_units')) {
            $unit = new \block_gradetracker\Unit($id);
            $unit->delete();
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DELETED_UNIT;
            $MSGS['success'] = get_string('unitdeleted', 'block_gradetracker');
        } else if ($submission['copy_unit']) {
            $unit = new \block_gradetracker\Unit($id);
            $unit->copyUnit();
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DUPLICATED_UNIT;
        } else if ($section == 'new' && !$submission['restoreUnit_x']) {

            $TPL = new \block_gradetracker\Template();
            $unit = new \block_gradetracker\Unit\GUI($id);

            $valid = ($unit->isValid());

            $unit->loadTemplate($TPL);
            $unit->saveFormNewUnit();

            if (isset($MSGS['success'])) {
                $detail = ($valid) ? \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_UNIT : \block_gradetracker\Log::GT_LOG_DETAILS_CREATED_UNIT;
            }

            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $unit;

        } else if ($section == 'search' && $submission['submit_search']) {
            $TPL = new \block_gradetracker\Template();
            $unit = new \block_gradetracker\Unit\GUI($id);
            $unit->loadTemplate($TPL);

            $results = $unit->submitFormUnitSearch();
            $TPL->set("results", $results);

            $deletedresults = $unit->submitFormUnitSearch(true);
            $TPL->set("deletedresults", $deletedresults);

            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $unit;

        } else if ($submission['restoreUnit_x'] && gt_has_capability('block/gradetracker:delete_restore_units')) {
            $unit = new \block_gradetracker\Unit($id);
            $unit->restore();
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_RESTORED_UNIT;
            $MSGS['success'] = get_string('unitrestored', 'block_gradetracker');
        } else if ($submission['submit_import_unit']) {

            $result = \block_gradetracker\Unit::importXML($_FILES['file']['tmp_name']);
            if ($result['result']) {
                $MSGS['success'] = get_string('unitsimported', 'block_gradetracker');
                unset($MSGS['errors']);
            } else {
                $MSGS['errors'] = $result['errors'];
            }

            $MSGS['import_output'] = $result['output'];

        }

        if (!isset($MSGS['errors']) && isset($detail)) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = \df_clean_entire_post();
            $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_UNITID, $unit->getID());
            $Log->save();
            // ------------ Logging info
        }

    }

    private function saveConfigQuals($section, $page) {

        global $MSGS, $VARS;

        $id = optional_param('id', false, PARAM_INT);

        if ($section == 'edit') {
            $section = 'new';
        }

        $submission = array(
            'delete_qual' => optional_param('delete_qual', false, PARAM_TEXT),
            'copy_qual' => optional_param('copy_qual', false, PARAM_TEXT),
            'restoreQual_x' => optional_param('restoreQual_x', false, PARAM_TEXT),
            'submit_search' => optional_param('submit_search', false, PARAM_TEXT),
        );

        if ($submission['delete_qual'] && gt_has_capability('block/gradetracker:delete_restore_quals')) {
            $qual = new \block_gradetracker\Qualification($id);
            $qual->delete();
            $MSGS['success'] = get_string('qualdeleted', 'block_gradetracker');
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DELETED_QUALIFICATION;
        } else if ( $submission['copy_qual'] ) {
            $qual = new \block_gradetracker\Qualification($id);
            $qual->copyQual();
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_DUPLICATED_QUALIFICATION;
        } else if ($section == 'new' && !$submission['restoreQual_x']) {

            $TPL = new \block_gradetracker\Template();
            $qual = new \block_gradetracker\Qualification\GUI($id);

            $valid = ($qual->isValid());

            $qual->loadTemplate($TPL);
            $qual->saveFormNewQualification();

            if (isset($MSGS['success'])) {
                $detail = ($valid) ? \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_QUALIFICATION : \block_gradetracker\Log::GT_LOG_DETAILS_CREATED_QUALIFICATION;
            }

            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $qual;
        } else if ($section == 'search' && $submission['submit_search']) {

            $TPL = new \block_gradetracker\Template();
            $qual = new \block_gradetracker\Qualification\GUI($id);
            $qual->loadTemplate($TPL);

            $results = $qual->submitFormSearch();
            $TPL->set("results", $results);

            $deletedresults = $qual->submitFormSearch(true);
            $TPL->set("deletedresults", $deletedresults);

            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $qual;

        }

        // Restore deleted qual
        if ($submission['restoreQual_x'] && gt_has_capability('block/gradetracker:delete_restore_quals')) {
            $qual = new \block_gradetracker\Qualification($id);
            $qual->restore();
            $detail = \block_gradetracker\Log::GT_LOG_DETAILS_RESTORED_QUALIFICATION;
            $MSGS['success'] = get_string('qualrestored', 'block_gradetracker');
        }

        if (!isset($MSGS['errors']) && isset($detail)) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = \df_clean_entire_post();
            $Log->addAttribute(\block_gradetracker\Log::GT_LOG_ATT_QUALID, $qual->getID());
            $Log->save();
            // ------------ Logging info
        }

    }

    /**
     * Save the config from grading structures
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @param type $page
     */
    private function saveConfigGradingStructures($page) {

        global $MSGS, $VARS;

        // If we are saving an existing one, there will be a hidden input called grading_qual_structure_id
        // which will have the QualStructureID and the "id" field in the URL will be the gradingStructureID
        // Otherwise if it's a new one, the "id" field in the URL will be the QualStructureID
        $hiddenStructureID = optional_param('grading_qual_structure_id', false, PARAM_INT);
        if ($hiddenStructureID) {
            $structureID = $hiddenStructureID;
            $gradingStructureID = optional_param('id', false, PARAM_INT);
        } else {
            $structureID = optional_param('id', false, PARAM_INT);
        }

        $buildID = optional_param('build', false, PARAM_INT);
        $QualStructure = new \block_gradetracker\QualificationStructure($structureID);
        $QualBuild = new \block_gradetracker\QualificationBuild($buildID);

        $submission = array(
            'submit_unit_grading_structure' => optional_param('submit_unit_grading_structure', false, PARAM_TEXT),
            'submit_crit_grading_structure' => optional_param('submit_crit_grading_structure', false, PARAM_TEXT),
            'delete_unit_grading_structure' => optional_param('delete_unit_grading_structure', false, PARAM_TEXT),
            'delete_crit_grading_structure' => optional_param('delete_crit_grading_structure', false, PARAM_TEXT),
            'enable_unit_grading_structure_x' => optional_param('enable_unit_grading_structure_x', false, PARAM_TEXT),
            'enable_crit_grading_structure_x' => optional_param('enable_crit_grading_structure_x', false, PARAM_TEXT),
            'set_grading_structure_assessments_x' => optional_param('set_grading_structure_assessments_x', false, PARAM_TEXT),
            'export_unit_x' => optional_param('export_unit_x', false, PARAM_TEXT),
            'export_criteria_x' => optional_param('export_criteria_x', false, PARAM_TEXT),
            'import_qual_structure_unit' => optional_param('import_qual_structure_unit', false, PARAM_TEXT),
            'import_qual_structure_criteria' => optional_param('import_qual_structure_criteria', false, PARAM_TEXT),
        );

        $settings = array(
            'grading_structure_id' => optional_param('grading_structure_id', false, PARAM_INT),
        );

        if ( ($page == 'new_unit' || $page == 'edit_unit') && $submission['submit_unit_grading_structure']) {

            $type = 'unit';

            // Does this qual structure have units enabled?
            if (!$QualStructure->isLevelEnabled("Units")) {
                $MSGS['errors'] = sprintf( get_string('unitsfeaturenotenabled', 'block_gradetracker'), $QualStructure->getName() );
                return false;
            }

            $UnitGradingStructure = new \block_gradetracker\UnitAwardStructure();
            $UnitGradingStructure->loadPostData();

            if ($UnitGradingStructure->hasNoErrors() && $UnitGradingStructure->save()) {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $UnitGradingStructure->getErrors();
            }

            $VARS['UnitGradingStructure'] = $UnitGradingStructure;

        } else if ( ($page == 'new_criteria' || $page == 'edit_criteria') && $submission['submit_crit_grading_structure']) {

            $type = 'criteria';
            $CriteriaAwardStructure = new \block_gradetracker\CriteriaAwardStructure();
            $CriteriaAwardStructure->loadPostData();

            if ($CriteriaAwardStructure->hasNoErrors() && $CriteriaAwardStructure->save()) {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $CriteriaAwardStructure->getErrors();
            }

            $VARS['CriteriaAwardStructure'] = $CriteriaAwardStructure;

        } else if ($submission['delete_unit_grading_structure']) {

            $type = 'unit';
            $UnitGradingStructure = new \block_gradetracker\UnitAwardStructure($settings['grading_structure_id']);
            if ($UnitGradingStructure->isValid()) {

                $UnitGradingStructure->delete();
                $MSGS['success'] = get_string('gradingstructuredeleted', 'block_gradetracker');

            }

        } else if ($submission['delete_crit_grading_structure']) {

            $type = 'criteria';
            $CriteriaGradingStructure = new \block_gradetracker\CriteriaAwardStructure($settings['grading_structure_id']);
            if ($CriteriaGradingStructure->isValid()) {

                $CriteriaGradingStructure->delete();
                $MSGS['success'] = get_string('gradingstructuredeleted', 'block_gradetracker');

            }

        } else if ($submission['enable_unit_grading_structure_x']) {

            $type = 'unit';
            $UnitGradingStructure = new \block_gradetracker\UnitAwardStructure($settings['grading_structure_id']);
            if ($UnitGradingStructure->isValid()) {
                $UnitGradingStructure->toggleEnabled();
            }

        } else if ($submission['enable_crit_grading_structure_x']) {

            $type = 'criteria';
            $CriteriaGradingStructure = new \block_gradetracker\CriteriaAwardStructure($settings['grading_structure_id']);
            if ($CriteriaGradingStructure->isValid()) {

                $CriteriaGradingStructure->toggleEnabled();

            }

        } else if ($submission['set_grading_structure_assessments_x']) {

            $type = 'criteria';
            $gradingStructureID = $settings['grading_structure_id'];
            $gradingStructure = new \block_gradetracker\CriteriaAwardStructure($gradingStructureID);

            if (!$gradingStructure->isValid()) {
                $MSGS['errors'] = get_string('invalidgradingstructure', 'block_gradetracker');
                return false;
            }

            // If it is already set to this, unset it
            if ($gradingStructure->isUsedInAssessments()) {
                $gradingStructure->setIsUsedForAssessments(0);
            } else {
                // Then set this one
                $gradingStructure->setIsUsedForAssessments(1);
            }

            $gradingStructure->save();

            // Enable it if it's not enabled
            if (!$gradingStructure->isEnabled()) {
                $gradingStructure->toggleEnabled();
            }

        } else if ($submission['export_unit_x']) {

            $id = $settings['grading_structure_id'];
            $gradingStructure = $QualStructure->getSingleStructure($id, $QualStructure->getUnitGradingStructures(false));

            if ($QualStructure->isValid() && $gradingStructure) {
                $XML = $QualStructure->exportUnitXML($id);

                $name = preg_replace("/[^a-z0-9]/i", "", $gradingStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_grading_unit_structure_'.$name.'.xml');
                header('Content-type: text/xml');

                echo $XML->asXML();
                exit;

            }

        } else if ($submission['export_criteria_x']) {

            $id = $settings['grading_structure_id'];

            $Object = ($QualBuild && $QualBuild->isValid()) ? $QualBuild : $QualStructure;
            $gradingStructure = $Object->getSingleStructure($id, $Object->getCriteriaGradingStructures(false));

            if ($Object->isValid() && $gradingStructure) {

                $XML = $QualStructure->exportCriteriaXML($id);

                $name = preg_replace("/[^a-z0-9]/i", "", $gradingStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_grading_criteria_structure_'.$name.'.xml');
                header('Content-type: text/xml');

                echo $XML->asXML();
                exit;

            }

        } else if ($submission['import_qual_structure_unit'] && !empty($_FILES['file'])) {

            $type = 'unit';
            $result = \block_gradetracker\QualificationStructure::importUnitXML($_FILES['file']['tmp_name'], $structureID);
            if ($result['result']) {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $result['errors'];
            }

            $MSGS['import_output'] = $result['output'];

        } else if ($submission['import_qual_structure_criteria'] && !empty($_FILES['file'])) {

            $type = 'criteria';

            // Do it by qual build
            if ($QualBuild && $QualBuild->isValid()) {
                $result = \block_gradetracker\QualificationStructure::importCriteriaXML($_FILES['file']['tmp_name'], false, $QualBuild->getID());
            } else {
                // Do it by qual structure
                $result = \block_gradetracker\QualificationStructure::importCriteriaXML($_FILES['file']['tmp_name'], $structureID);
            }

            if ($result['result']) {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $result['errors'];
            }

            $MSGS['import_output'] = $result['output'];

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = constant('\block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_'.strtoupper($type).'_GRADING_STRUCTURE');
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save the config from the QUal Build pages
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @param type $page
     */
    private function saveConfigQualBuilds($page) {

        global $MSGS, $VARS;

        $submission = array(
            'submit_qual_build_awards' => optional_param('submit_qual_build_awards', false, PARAM_TEXT),
            'submit_qual_build_defaults' => optional_param('submit_qual_build_defaults', false, PARAM_TEXT),
            'delete_build' => optional_param('delete_build', false, PARAM_TEXT),
            'export_build_x' => optional_param('export_build_x', false, PARAM_TEXT),
            'mass_export_build_x' => optional_param('mass_export_build_x', false, PARAM_TEXT),
            'import_qual_build' => optional_param('import_qual_build', false, PARAM_TEXT),
        );

        $settings = array(
            'build_id' => optional_param('build_id', false, PARAM_INT),
            'build' => df_optional_param_array_recursive('build', false, PARAM_TEXT),
            'custom' => df_optional_param_array_recursive('custom', false, PARAM_TEXT),
        );

        // Are we editing a build?
        if ($page == 'new' || $page == 'edit') {

            $QualBuild = new \block_gradetracker\QualificationBuild();
            $QualBuild->loadPostData();

            if ($QualBuild->hasNoErrors() && $QualBuild->save()) {
                $MSGS['success'] = get_string('buildsaved', 'block_gradetracker');
                $QualBuild = new \block_gradetracker\QualificationBuild();
            } else {
                $MSGS['errors'] = $QualBuild->getErrors();
            }

            $VARS['qualBuild'] = $QualBuild;

        } else if ($page == 'awards' && $submission['submit_qual_build_awards']) {

            $id = $settings['build_id'];
            $build = new \block_gradetracker\QualificationBuild($id);
            if ($build->isValid()) {

                $build->loadAwardPostData();

                if ($build->hasNoErrors() && $build->save()) {
                    $MSGS['success'] = get_string('gradessaved', 'block_gradetracker');
                } else {
                    $MSGS['errors'] = $build->getErrors();
                }

                $VARS['qualBuild'] = $build;

            }

        } else if ($page == 'defaults' && $submission['submit_qual_build_defaults']) {

            $id = $settings['build_id'];
            $build = new \block_gradetracker\QualificationBuild($id);
            if ($build->isValid()) {

                $build->saveDefaults( (($settings['custom']) ? $settings['custom'] : false), (($settings['build']) ? $settings['build'] : false) );
                $MSGS['success'] = get_string('defaultssaved', 'block_gradetracker');

            }

        } else if ($submission['delete_build']) {

            $id = $settings['build_id'];
            $build = new \block_gradetracker\QualificationBuild($id);
            if ($build->isValid()) {

                $build->delete();
                $MSGS['success'] = get_string('builddeleted', 'block_gradetracker');

            }

        } else if ($submission['export_build_x']) {

            $id = $settings['build_id'];
            $build = new \block_gradetracker\QualificationBuild($id);
            if ($build->isValid()) {

                $XML = $build->exportXML();

                $name = preg_replace("/[^a-z0-9]/i", "", $build->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_qual_build_'.$name.'.xml');
                header('Content-type: text/xml');

                echo $XML->asXML();
                exit;

            }

        } else if ($submission['mass_export_build_x']) {

            // Clear tmp files
            self::gc();

            $path = self::dataroot() . "/tmp";

            $builds = \block_gradetracker\QualificationBuild::getAllBuilds();
            foreach ($builds as $build) {
                $build = new \block_gradetracker\QualificationBuild($build->getID());
                $settings['build_id' . $build->getID()] = optional_param('build_id' . $build->getID(), false, PARAM_INT);
                if ($build->isValid() && $settings['build_id_' . $build->getID()]) {
                    $XML = $build->exportXML();
                }
            }

            $zip = new \ZipArchive;

            $tmp_file = tempnam($path, 'gt_');
            $zip->open($tmp_file, \ZipArchive::CREATE);

            # loop through each file
            foreach (glob($path . "/*.xml") as $file) {
                $download_file = file_get_contents($file);
                $zip->addFromString(basename($file), $download_file);
            }

            $zip->close();

            header('Content-disposition: attachment; filename=qualbuilds.zip');
            header('Content-type: application/zip');
            readfile($tmp_file);

            // Clear newly created tmp files
            self::gc();

            exit;
        } else if ($submission['import_qual_build']) {

            $result = \block_gradetracker\QualificationBuild::importXML($_FILES['file']['tmp_name']);
            if ($result['result']) {
                $MSGS['success'] = get_string('qualbuildimported', 'block_gradetracker');
                unset($MSGS['errors']);
            } else {
                $MSGS['errors'] = $result['errors'];
            }

            $MSGS['import_output'] = $result['output'];

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_BUILD;
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save qual levels
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @param type $page
     */
    private function saveConfigQualLevels($page) {

        global $MSGS, $VARS;

        $submission = array(
            'delete_level' => optional_param('delete_level', false, PARAM_TEXT),
        );

        $settings = array(
            'level_id' => optional_param('level_id', false, PARAM_INT),
        );

        // Are we editing a build?
        if ($page == 'new' || $page == 'edit') {

            $Level = new \block_gradetracker\Level();
            $Level->loadPostData();

            if ($Level->hasNoErrors() && $Level->save()) {
                $MSGS['success'] = get_string('levelsaved', 'block_gradetracker');
                $Level = new \block_gradetracker\Level();
            } else {
                $MSGS['errors'] = $Level->getErrors();
            }

            $VARS['Level'] = $Level;

        } else if ($submission['delete_level']) {

            $id = $settings['level_id'];
            $level = new \block_gradetracker\Level($id);
            if ($level->isValid()) {
                $level->delete();
                $MSGS['success'] = get_string('leveldeleted', 'block_gradetracker');
            }

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_LEVELS;
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save sub types
     * @global \block_gradetracker\type $MSGS
     * @global \block_gradetracker\type $VARS
     * @param type $page
     */
    private function saveConfigQualSubTypes($page) {

        global $MSGS, $VARS;

        $submission = array(
            'delete_subtype' => optional_param('delete_subtype', false, PARAM_TEXT),
        );

        $settings = array(
            'subtype_id' => optional_param('subtype_id', false, PARAM_INT),
        );

        // Are we editing a build?
        if ($page == 'new' || $page == 'edit') {

            $SubType = new \block_gradetracker\SubType();
            $SubType->loadPostData();

            if ($SubType->hasNoErrors() && $SubType->save()) {
                $MSGS['success'] = get_string('subtypesaved', 'block_gradetracker');
                $SubType = new \block_gradetracker\SubType();
            } else {
                $MSGS['errors'] = $SubType->getErrors();
            }

            $VARS['SubType'] = $SubType;

        } else if ($submission['delete_subtype']) {

            $id = $settings['subtype_id'];
            $SubType = new \block_gradetracker\SubType($id);
            if ($SubType->isValid()) {
                $SubType->delete();
                $MSGS['success'] = get_string('subtypedeleted', 'block_gradetracker');
            }

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_SUBTYPES;
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save configuration forms from the Qual Structures pages
     * @global type $MSGS
     * @global type $VARS
     * @param type $page
     */
    private function saveConfigQualStructures($page) {

        global $MSGS, $VARS;

        $submission = array(
            'enable_structure_x' => optional_param('enable_structure_x', false, PARAM_TEXT),
            'delete_structure' => optional_param('delete_structure', false, PARAM_TEXT),
            'copy_structure_x' => optional_param('copy_structure_x', false, PARAM_TEXT),
            'export_structure_x' => optional_param('export_structure_x', false, PARAM_TEXT),
            'import_qual_structure' => optional_param('import_qual_structure', false, PARAM_TEXT),
        );

        $settings = array(
            'structure_id' => optional_param('structure_id', false, PARAM_INT),
        );

        // Are we editing a structure?
        if ($page == 'edit' || $page == 'new') {

            $QualStructure = new \block_gradetracker\QualificationStructure();
            $QualStructure->loadPostData();

            // If no errors, save it
            if ($QualStructure->hasNoErrors() && $QualStructure->save()) {

                $MSGS['success'] = get_string('structuresaved', 'block_gradetracker');

            } else {

                $MSGS['errors'] = $QualStructure->getErrors();

            }

            $VARS['qualStructure'] = $QualStructure;

        } else if ($submission['enable_structure_x']) {

            $structureID = $settings['structure_id'];
            $QualStructure = new \block_gradetracker\QualificationStructure($structureID);
            if ($QualStructure->isValid()) {
                $QualStructure->toggleEnabled();
            }

        } else if ($submission['delete_structure']) {

            $structureID = $settings['structure_id'];
            $QualStructure = new \block_gradetracker\QualificationStructure($structureID);
            if ($QualStructure->isValid()) {

                $QualStructure->delete();
                $MSGS['success'] = $QualStructure->getName() . " : " . get_string('deleted', 'block_gradetracker');

            }

        } else if ($submission['copy_structure_x']) {

            $structureID = $settings['structure_id'];
            $QualStructure = new \block_gradetracker\QualificationStructure($structureID);
            if ($QualStructure->isValid()) {
                $QualStructure->duplicate();
            }

        } else if ($submission['export_structure_x']) {

            $structureID = $settings['structure_id'];
            $QualStructure = new \block_gradetracker\QualificationStructure($structureID);
            if ($QualStructure->isValid()) {

                $XML = $QualStructure->exportXML();

                $name = preg_replace("/[^a-z0-9]/i", "", $QualStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_qual_structure_'.$name.'.xml');
                header('Content-type: text/xml');

                echo $XML->asXML();
                exit;

            }

        } else if ($submission['import_qual_structure'] && !empty($_FILES['file'])) {
            $result = \block_gradetracker\QualificationStructure::importXML($_FILES['file']['tmp_name']);
            if ($result['result']) {
                $MSGS['success'] = get_string('structureimported', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $result['errors'];
            }

            $MSGS['import_output'] = $result['output'];

        }

        if (!isset($MSGS['errors'])) {
            // ------------ Logging Info
            $Log = new \block_gradetracker\Log();
            $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_STRUCTURE;
            $Log->afterjson = \df_clean_entire_post();
            $Log->save();
            // ------------ Logging Info
        }

    }

    /**
     * Save the plugin settings
     */
    private function saveConfigSettings($section, $page) {

        global $CFG, $MSGS;

        // All the possible forms which can be sunmitted.
        $submission = array(
            'submitconfig' => optional_param('submitconfig', false, PARAM_TEXT),
            'submit_build_coefficients' => optional_param('submit_build_coefficients', false, PARAM_TEXT),
            'submit_qual_coefficients' => optional_param('submit_qual_coefficients', false, PARAM_TEXT),
            'submit_constants' => optional_param('submit_constants', false, PARAM_TEXT),
        );

        $settings = array();

        // Qual Weighting - Constants
        if ($section == 'qual' && $page == 'constants' && $submission['submit_constants']) {

            $settings['weighting_constants_enabled'] = optional_param('weighting_constants_enabled', false, PARAM_TEXT);
            $settings['constants'] = df_optional_param_array_recursive('constants', false, PARAM_TEXT);
            $settings['multipliers'] = df_optional_param_array_recursive('multipliers', false, PARAM_TEXT);

            // Enable/Disable
            \block_gradetracker\Setting::updateSetting('weighting_constants_enabled', $settings['weighting_constants_enabled']);

            // The qual build constants
            if (isset($settings['constants'])) {

                foreach ($settings['constants'] as $buildID => $constant) {

                    $build = new \block_gradetracker\QualificationBuild($buildID);
                    if ($build->isValid()) {

                        // Get the multiplier as well
                        $multiplier = (isset($settings['multipliers'][$buildID])) ? $settings['multipliers'][$buildID] : false;

                        // Set the attributes
                        $build->updateAttribute('build_default_weighting_constant', $constant);
                        $build->updateAttribute('build_default_weighting_multiplier', $multiplier);

                    }

                }

            }

            $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
            return true;

        } else if ($section == 'qual' && $page == 'coefficients' && ($submission['submitconfig'] || $submission['submit_build_coefficients'] || $submission['submit_qual_coefficients'])) {

                $settings['qual_weighting_percentiles'] = optional_param('qual_weighting_percentiles', false, PARAM_TEXT);
                $settings['default_percentile'] = optional_param('default_percentile', false, PARAM_TEXT);
                $settings['percentile_colours'] = df_optional_param_array_recursive('percentile_colours', false, PARAM_TEXT);
                $settings['percentile_percents'] = df_optional_param_array_recursive('percentile_percents', false, PARAM_TEXT);
                $settings['build_coefficient'] = df_optional_param_array_recursive('build_coefficient', false, PARAM_TEXT);
                $settings['qual_coefficients'] = df_optional_param_array_recursive('qual_coefficients', false, PARAM_TEXT);

            // General config
            if ($submission['submitconfig']) {

                // Number of percentiles to use
                \block_gradetracker\Setting::updateSetting('qual_weighting_percentiles', $settings['qual_weighting_percentiles']);

                // Colours
                if (isset($settings['percentile_colours']) && $settings['percentile_colours']) {
                    foreach ($settings['percentile_colours'] as $percentile => $colour) {
                        \block_gradetracker\Setting::updateSetting('weighting_percentile_color_' . $percentile, $colour);
                    }
                }

                // Percentages
                if (isset($settings['percentile_percents']) && $settings['percentile_percents']) {
                    foreach ($settings['percentile_percents'] as $percentile => $percent) {
                        \block_gradetracker\Setting::updateSetting('weighting_percentile_percentage_'.$percentile, $percent);
                    }
                }

                // Default.
                if (isset($settings['default_percentile'])) {
                    \block_gradetracker\Setting::updateSetting('default_weighting_percentile', $settings['default_percentile']);
                }

                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;

            } else if ($submission['submit_build_coefficients']) {

                // Build coefficients
                if ($settings['build_coefficient']) {
                    foreach ($settings['build_coefficient'] as $buildID => $coefficients) {
                        if ($coefficients) {
                            foreach ($coefficients as $percentile => $coefficient) {
                                \block_gradetracker\Setting::updateSetting('build_coefficient_' . $buildID . '_' . $percentile, $coefficient);
                            }
                        }
                    }
                }

                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;

            } else if ($submission['submit_qual_coefficients']) {

                if ($settings['qual_coefficients']) {
                    foreach ($settings['qual_coefficients'] as $qualID => $coefficients) {

                        $qual = new \block_gradetracker\Qualification($qualID);

                        if ($qual && $coefficients) {
                            foreach ($coefficients as $percentile => $coefficient) {
                                $qual->updateAttribute('coefficient_' . $percentile, $coefficient);
                            }
                        }

                    }
                }

                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;

            }

        } else if ($submission['submitconfig']) {

            // General plugin settings.

            // Checkboxes need int values
            if ($section == 'general') {

                $settings['plugin_title'] = optional_param('plugin_title', false, PARAM_TEXT);
                $settings['theme_layout'] = optional_param('theme_layout', false, PARAM_TEXT);
                $settings['student_role_shortnames'] = optional_param('student_role_shortnames', false, PARAM_TEXT);
                $settings['staff_role_shortnames'] = optional_param('staff_role_shortnames', false, PARAM_TEXT);
                $settings['course_name_format'] = optional_param('course_name_format', false, PARAM_TEXT);
                $settings['use_auto_enrol_quals'] = optional_param('use_auto_enrol_quals', 0, PARAM_INT);
                $settings['use_auto_enrol_units'] = optional_param('use_auto_enrol_units', 0, PARAM_INT);
                $settings['use_auto_unenrol_quals'] = optional_param('use_auto_unenrol_quals', 0, PARAM_INT);
                $settings['use_auto_unenrol_units'] = optional_param('use_auto_unenrol_units', 0, PARAM_INT);
                $settings['custom_css'] = optional_param('custom_css', false, PARAM_TEXT);
                $settings['keep_logs_for'] = optional_param('keep_logs_for', false, PARAM_INT);

            } else if ($section == 'criteria') {

                $settings['numeric_criteria_max_points'] = optional_param('numeric_criteria_max_points', false, PARAM_INT);

            } else if ($section == 'grid') {

                $settings['grid_fixed_links'] = optional_param('grid_fixed_links', 0, PARAM_INT);
                $settings['enable_grid_logs'] = optional_param('enable_grid_logs', 0, PARAM_INT);
                $settings['assessment_grid_show_quals_one_page'] = optional_param('assessment_grid_show_quals_one_page', 0, PARAM_INT);
                $settings['student_grid_show_ucas'] = optional_param('student_grid_show_ucas', 0, PARAM_INT);

            } else if ($section == 'assessments') {

                $settings['use_assessments_comments'] = optional_param('use_assessments_comments', 0, PARAM_INT);
                $settings['custom_form_fields_names'] = df_optional_param_array_recursive('custom_form_fields_names', false, PARAM_TEXT);
                $settings['custom_form_fields_ids'] = df_optional_param_array_recursive('custom_form_fields_ids', false, PARAM_TEXT);
                $settings['custom_form_fields_types'] = df_optional_param_array_recursive('custom_form_fields_types', false, PARAM_TEXT);
                $settings['custom_form_fields_options'] = df_optional_param_array_recursive('custom_form_fields_options', false, PARAM_TEXT);

                // Form fields
                $elementIDs = array();

                if (isset($settings['custom_form_fields_names'])) {
                    foreach ($settings['custom_form_fields_names'] as $key => $name) {

                        $params = new \stdClass();
                        $params->id = (isset($settings['custom_form_fields_ids'][$key])) ? $settings['custom_form_fields_ids'][$key] : false;
                        $params->name = $name;
                        $params->form = 'assessment_grid';
                        $params->type = (isset($settings['custom_form_fields_types'][$key])) ? $settings['custom_form_fields_types'][$key] : false;
                        $params->options = (isset($settings['custom_form_fields_options'][$key]) && !empty($settings['custom_form_fields_options'][$key])) ? $settings['custom_form_fields_options'][$key] : false;
                        $params->validation = array();
                        $element = \block_gradetracker\FormElement::create($params);
                        $element->save();
                        $elementIDs[] = $element->getID();

                    }
                }

                unset($settings['custom_form_fields_names']);
                unset($settings['custom_form_fields_ids']);
                unset($settings['custom_form_fields_types']);
                unset($settings['custom_form_fields_options']);

                $settings['assessment_grid_custom_form_elements'] = implode(",", $elementIDs);

            }

            // Navigation links are in serperate arrays for name and URL
            if ($section == 'grid') {

                $settings['student_grid_nav'] = df_optional_param_array_recursive('student_grid_nav', false, PARAM_TEXT);
                $settings['unit_grid_nav'] = df_optional_param_array_recursive('unit_grid_nav', false, PARAM_TEXT);
                $settings['class_grid_nav'] = df_optional_param_array_recursive('class_grid_nav', false, PARAM_TEXT);

                if (isset($settings['student_grid_nav'])) {

                    $studNav = array();

                    foreach ($settings['student_grid_nav'] as $i => $array) {

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)) {

                            if ($sub) {

                                foreach ($sub as $s) {

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)) {
                                        $subObj[] = array("name" => $subName, "url" => $subUrl);
                                    }

                                }

                            }

                            $studNav[] = array("name" => $name, "url" => $url, "sub" => $subObj);

                        }

                    }

                    unset($settings['student_grid_nav']);
                    $settings['student_grid_navigation'] = json_encode($studNav);

                } else {
                    $settings['student_grid_navigation'] = '';
                }

                // Unit grid navigation
                if (isset($settings['unit_grid_nav'])) {

                    $unitNav = array();

                    foreach ($settings['unit_grid_nav'] as $i => $array) {

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)) {

                            if ($sub) {

                                foreach ($sub as $s) {

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)) {
                                        $subObj[] = array("name" => $subName, "url" => $subUrl);
                                    }

                                }

                            }

                            $unitNav[] = array("name" => $name, "url" => $url, "sub" => $subObj);

                        }

                    }

                    unset($settings['unit_grid_nav']);
                    $settings['unit_grid_navigation'] = json_encode($unitNav);

                } else {
                    $settings['unit_grid_navigation'] = '';
                }

                // Class grid navigation
                if (isset($settings['class_grid_nav'])) {

                    $classNav = array();

                    foreach ($settings['class_grid_nav'] as $i => $array) {

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)) {

                            if ($sub) {

                                foreach ($sub as $s) {

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)) {
                                        $subObj[] = array("name" => $subName, "url" => $subUrl);
                                    }

                                }

                            }

                            $classNav[] = array("name" => $name, "url" => $url, "sub" => $subObj);

                        }

                    }

                    unset($settings['class_grid_nav']);
                    $settings['class_grid_navigation'] = json_encode($classNav);

                } else {
                    $settings['class_grid_navigation'] = '';
                }

            } else if ($section == 'reporting') {

                $settings['reporting_categories'] = df_optional_param_array_recursive('reporting_categories', false, PARAM_TEXT);
                $settings['crit_weight_scores'] = df_optional_param_array_recursive('crit_weight_scores', false, PARAM_TEXT);
                $settings['pass_prog_method'] = df_optional_param_array_recursive('pass_prog_method', false, PARAM_TEXT);
                $settings['pass_prog_by_letter'] = df_optional_param_array_recursive('pass_prog_by_letter', false, PARAM_TEXT);
                $settings['pass_prog_by_grade_structure'] = df_optional_param_array_recursive('pass_prog_by_grade_structure', false, PARAM_TEXT);

                // Criteria Progress report - weighted criteria scores
                $allStructures = \block_gradetracker\QualificationStructure::getAllStructures();
                if ($allStructures) {
                    foreach ($allStructures as $structure) {

                        $array = array();

                        if (isset($settings['crit_weight_scores'][$structure->getID()])) {

                            $elements = $settings['crit_weight_scores'][$structure->getID()];

                            if (isset($elements['letter'])) {
                                foreach ($elements['letter'] as $key => $val) {
                                    $array[] = array('letter' => $val, 'score' => $elements['score'][$key]);
                                }
                            }

                        }

                        $value = ($array) ? json_encode($array) : null;
                        $structure->updateSetting('reporting_short_criteria_weighted_scores', $value);

                    }
                }

                unset($settings['crit_weight_scores']);

                // Pass Criteria Progress report
                if ($allStructures) {
                    foreach ($allStructures as $structure) {

                        $method = (isset($settings['pass_prog_method'][$structure->getID()]) && $settings['pass_prog_method'][$structure->getID()] != 'disable') ? $settings['pass_prog_method'][$structure->getID()] : null;
                        $structure->updateSetting('reporting_pass_criteria_method', $method);

                        if ($method == 'byletter') {
                            $value = $settings['pass_prog_by_letter'][$structure->getID()];
                        } else if ($method == 'bygradestructure') {
                            $value = (isset($settings['pass_prog_by_grade_structure'][$structure->getID()])) ? $settings['pass_prog_by_grade_structure'][$structure->getID()] : null;
                            $value = (is_array($value)) ? implode(",", $value) : $value;
                        } else {
                            $value = null;
                        }

                        $structure->updateSetting('reporting_pass_criteria_method_value', $value);

                    }
                }

                unset($settings['pass_prog_method']);
                unset($settings['pass_prog_by_letter']);
                unset($settings['pass_prog_by_grade_structure']);

            } else if ($section == 'user') {
                $settings['student_columns'] = df_optional_param_array_recursive('student_columns', false, PARAM_TEXT);
            } else if ($section == 'grade') {
                $settings['pred_grade_min_units'] = optional_param('pred_grade_min_units', false, PARAM_TEXT);
                $settings['asp_grade_diff'] = optional_param('asp_grade_diff', false, PARAM_TEXT);
                $settings['weighted_target_method'] = optional_param('weighted_target_method', false, PARAM_TEXT);
                $settings['weighted_target_direction'] = optional_param('weighted_target_direction', false, PARAM_TEXT);
            }

            // Loop through settings and save them
            foreach ((array)$settings as $setting => $value) {
                if (is_array($value)) {
                    $this->updateSetting($setting, implode(",", $value));
                } else {
                    $this->updateSetting($setting, $value);
                }
            }

            // Files
            if (isset($_FILES['institution_logo']) && $_FILES['institution_logo']['size'] > 0) {
                $mime = \gt_get_file_mime_type($_FILES['institution_logo']['tmp_name']);
                if (\gt_mime_type_is_image($mime)) {

                    // Save the file
                    \gt_create_data_directory('img');

                    if (\gt_save_file($_FILES['institution_logo']['tmp_name'], 'img', $_FILES['institution_logo']['name'])) {

                        // Create data path code for it
                        \gt_create_data_path_code($CFG->dataroot . '/gradetracker/img/' . $_FILES['institution_logo']['name']);

                        // Save setting
                        $this->updateSetting("institution_logo", $_FILES['institution_logo']['name']);

                    }
                }
            }

            $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
            return true;

        }

        return false;

    }


    private function saveConfigTestsAveGCSE($section) {

        global $VARS;

        $settings = array(
            'gt_avggcsescore' => optional_param('gt_avggcsescore', false, PARAM_TEXT),
            'qualid' => optional_param('qualid', false, PARAM_INT),
        );

        if ($settings['gt_avggcsescore'] && $settings['qualid'] && $settings['qualid'] > 0) {

            $avggcsescore = $settings['gt_avggcsescore'];
            $qual_id = $settings['qualid'];
            $qual = new \block_gradetracker\Qualification($qual_id);
            $qual_build = $qual->getBuild();
            $award = $qual_build->getAwardByAvgGCSEScore($avggcsescore);
            $awards = $qual_build->getAwards('desc');

            $TPL = new \block_gradetracker\Template();
            $TPL->set("single_award", $award);
            $TPL->set("awards", $awards);
            $TPL->set("avggcsescore", $avggcsescore);
            $TPL->set("qualification", $qual);
            $VARS['TPL'] = $TPL;
        }

    }


    private function saveConfigReportingLogs() {

        global $TPL, $VARS;

        $GTEXE = \block_gradetracker\Execution::getInstance();
        $GTEXE->min();
        $GTEXE->UNIT_NO_SORT = false;

        $submission = array(
            'search' => optional_param('search', false, PARAM_TEXT),
        );

        $settings = array(
            'log_details' => optional_param('log_details', false, PARAM_TEXT),
            'log_user' => optional_param('log_user', false, PARAM_TEXT),
            'log_date_from' => optional_param('log_date_from', false, PARAM_TEXT),
            'log_date_to' => optional_param('log_date_to', false, PARAM_TEXT),
            'log_attribute' => df_optional_param_array_recursive('log_attribute', array(), PARAM_TEXT),
        );

        if ($submission['search']) {

            $params = array();
            $params['details'] = ($settings['log_details'] && $settings['log_details'] != '') ? $settings['log_details'] : false;
            $params['user'] = ($settings['log_user'] && trim($settings['log_user']) != '') ? trim($settings['log_user']) : false;
            $params['date_from'] = ($settings['log_date_from'] && trim($settings['log_date_from']) != '') ? trim($settings['log_date_from']) : false;
            $params['date_to'] = ($settings['log_date_to'] && trim($settings['log_date_to']) != '') ? trim($settings['log_date_to']) : false;

            // Attributes
            foreach ($settings['log_attribute'] as $key => $val) {
                if (trim($val) != '') {
                    $params['atts'][$key] = trim($val);
                }
            }

            $results = \block_gradetracker\Log::search($params);
            $TPL->set("results", $results);
            $TPL->set("search", $params);

            // If we searched for a qual, get the list of units & assessments on the qual to populate the dropdowns
            if (isset($params['atts']['QUALID'])) {

                $qual = new \block_gradetracker\Qualification($params['atts']['QUALID']);
                $TPL->set("useUnits", $qual->getUnits());
                $TPL->set("useAss", $qual->getAssessments());

            }

            if (isset($params['atts']['UNITID'])) {

                $unit = new \block_gradetracker\Unit($params['atts']['UNITID']);
                $TPL->set("useCriteria", $unit->sortCriteria(false, true));

            }

            $VARS['TPL'] = $TPL;

        }

    }


    /**
     * Get the URL of one of the icons from the pix/icons directory
     * @global \block_gradetracker\type $CFG
     * @param type $icon
     * @return type
     */
    public function icon($icon) {

        global $CFG;

        $file = $CFG->dirroot . '/blocks/gradetracker/pix/icons/'.$icon.'.png';
        if (file_exists($file)) {
            return str_replace($CFG->dirroot, $CFG->wwwroot, $file);
        } else {
            return $CFG->wwwroot . '/blocks/gradetracker/pix/no_image.jpg';
        }

    }

    /**
     * Load any extra javascript files required
     * @global type $CFG
     * @global type $PAGE
     * @return string
     */
    public function loadJavascript( $external = false ) {

        global $PAGE;

        $PAGE->requires->js_call_amd("block_gradetracker/scripts", 'init');

    }

    /**
     * Load any extra CSS scripts required
     */
    public function loadCSS( $external = false, $from = false ) {

        global $PAGE;

        $output = "";

        $styles = array(
            new \moodle_url('http://fonts.googleapis.com/css?family=Poiret+One'),
            '/blocks/gradetracker/css/jquery-ui/base.css',
            '/blocks/gradetracker/js/lib/jquery-slimmenu/slimmenu.css',
            '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery-bc-popup.css',
            '/blocks/gradetracker/js/lib/jquery-bc-notify/jquery-bc-notify.css',
            '/blocks/gradetracker/css.php'
        );

        // Outside of main moodle, it won't have loaded the main style sheet
        if ($from == 'portal') {
            $styles[] = '/blocks/gradetracker/styles.css';
        }

        // Loop through required styles
        foreach ($styles as $style) {
            if ($external) {
                $output .= "<link rel='stylesheet' href='{$style}' />";
            } else {
                $PAGE->requires->css( $style );
            }
        }

        return $output;

    }



    /**
     * Return the dataroot directory
     * @global \block_gradetracker\type $CFG
     * @return type
     */
    public static function dataroot() {

        global $CFG;
        return $CFG->dataroot . '/gradetracker';

    }

    /**
     * Clear tmp files
     * @param type $dir
     */
    public static function gc($dir = false, $cnt = 0) {

        $tmp = self::dataroot() . '/tmp';
        $path = ($dir) ? $dir : $tmp;

        foreach (glob($path . '/*') as $file) {
            if (is_dir($file)) {
                self::gc($file, $cnt);
            } else if ($file != "." && $file != "..") {
                unlink($file);
                $cnt++;
            }
        }

        if ($path != $tmp) {
            rmdir($path);
        }

        return $cnt;

    }


}

