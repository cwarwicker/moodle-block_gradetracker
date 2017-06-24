<?php
/**
 * Grade Tracker
 *
 * The overall class for the block itself
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

namespace GT;

if(!defined('BCGT')) define('BCGT', true);

class GradeTracker
{
    
    private $CFG;
    private $DB;
    private $string;
    public $cache;
    
    const REMOTE_HOST_URL = 'http://moodleportal.bedford.ac.uk';
    const REMOTE_VERSION_URL = 'http://moodleportal.bedford.ac.uk/gt_version.txt';
    const REMOTE_HUB = 'http://moodleportal.bedford.ac.uk/webservice/rest/server.php';
    const REMOTE_HUB_TOKEN = '6454383ec602e4d8448f7226f361ff31';
    
    const MAJOR_VERSION = 1; // A new major version would be a drastic change to the system, which sees lots of new features added or existing aspects of the system changed in large ways
    const MINOR_VERSION = 1; // A new minor version would be a small number of new features/changes
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

        $this->cache = \cache::make('block_gradetracker', 'settings');        
        if ($this->cache->get('settings') === false){
            $settings = \gt_get_overriden_settings();
            if ($settings){
                $this->cache->set('settings', \gt_get_overriden_settings());
            }
        }
        
    }
    
    /**
     * Uninstall the gradetracker plugin
     */
    public function uninstall()
    {
        
        global $CFG;
        
        // Database tables should be handled by Moodle
        
        // Remove moodledata directory
        $result = \remove_dir(\GT\GradeTracker::dataroot(), true);
        mtrace('Trying to remove gradetracker directory in data root...' . (int)$result, "\n<br>");
        
        
        
    }
    
    /**
     * Install the gradetracker plugin
     * @global \GT\type $CFG
     * @global \GT\type $DB
     */
    public function install()
    {
        
        global $CFG, $DB;

        // Make dataroot directory
        if (!is_dir(\GT\GradeTracker::dataroot())){
            $result = mkdir(\GT\GradeTracker::dataroot(), $CFG->directorypermissions);
            mtrace('Trying to create gradetracker directory in data root...' . (int)$result, "\n<br>");
        }

        // Make dataroot tmp directory
        if (!is_dir(\GT\GradeTracker::dataroot() . '/tmp/')){
            $result = mkdir(\GT\GradeTracker::dataroot() . '/tmp/', $CFG->directorypermissions);
            mtrace('Trying to create gradetracker tmp directory in data root...' . (int)$result, "\n<br>");
        }

        // ======================== Install data ======================== //

        
        // Hard-Coded Installs
        // 
        // Qualification Structure Features
        $features = \GT\QualificationStructure::_features();
        if ($features){
            foreach($features as $feature){

                $check = $DB->get_record("bcgt_qual_structure_features", array("name" => $feature));
                if (!$check){

                    $obj = new \stdClass();
                    $obj->name = $feature;
                    $result = $DB->insert_record("bcgt_qual_structure_features", $obj);
                    mtrace('Trying to insert qual_structure_feature ' . $feature . '...' . (int)$result, "\n<br>");

                }

            }
        }


        // Qualification Structure Levels
        $levels = \GT\QualificationStructure::_levels();
        if ($levels){
            foreach($levels as $level => $minMax){

                $check = $DB->get_record("bcgt_qual_structure_levels", array("name" => $level));
                if ($check){
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
        $levels = json_decode( file_get_contents($installDir.'/structures/level/levels.json') );
        if ($levels)
        {
            
            foreach($levels as $level)
            {
                
                $check = $DB->get_record("bcgt_qual_levels", array("name" => $level->name, "deleted" => 0));
                if ($check)
                {
                    $check->shortname = $level->shortname;
                    $check->ordernum = $level->ordernum;
                    $result = $DB->update_record("bcgt_qual_levels", $check);
                }
                else
                {
                    $obj = new \stdClass();
                    $obj->name = $level->name;
                    $obj->shortname = $level->shortname;
                    $obj->ordernum = $level->ordernum;
                    $result = $DB->insert_record("bcgt_qual_levels", $obj);
                }
                
                mtrace('Trying to insert/update qual_level ' . $level->name . '...' . (int)$result, "\n<br>");
                
            }            
            
        }
        
        
        
        
        // Install Qualification SubTypes (e.g. Diploma, Certificate, Award, etc...(
        $subtypes = json_decode( file_get_contents($installDir.'/structures/subtype/subtypes.json') );
        if ($subtypes)
        {
            
            foreach($subtypes as $subtype)
            {
                
                $check = $DB->get_record("bcgt_qual_subtypes", array("name" => $subtype->name, "deleted" => 0));
                if ($check)
                {
                    $check->shortname = $subtype->shortname;
                    $result = $DB->update_record("bcgt_qual_subtypes", $check);
                }
                else
                {
                    $obj = new \stdClass();
                    $obj->name = $subtype->name;
                    $obj->shortname = $subtype->shortname;
                    $result = $DB->insert_record("bcgt_qual_subtypes", $obj);
                }
                
                mtrace('Trying to insert/update qual_subtype ' . $subtype->name . '...' . (int)$result, "\n<br>");
                
            }            
            
        }
        
        
        
        // Install support for Mods activity links (e.g. assign, turnitintooltwo)
        $mods = json_decode( file_get_contents($installDir.'/mods/mods.json') );
        if ($mods)
        {
            
            foreach($mods as $mod)
            {
                
                // First need to check if this mod is installed
                $result = false;
                $moduleRecord = $DB->get_record("modules", array("name" => $mod->mod));
                if ($moduleRecord)
                {
                
                    $check = $DB->get_record("bcgt_mods", array("modid" => $moduleRecord->id, "deleted" => 0));
                    if ($check)
                    {
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
                    }
                    else
                    {
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
        
        
        
        
        // Install Qualification Structures (e.g. BTEC, A Level, etc...)
        // This includes the Unit grading structures and the Criteria grading structures for this
        $files = scandir($installDir . '/structures/qual');
        $files = array_filter($files, function($f) use ($installDir){
            $info = pathinfo($installDir.'/structures/qual/' . $f);
            return (!is_dir($installDir.'/structures/qual/' . $f) && isset($info['extension']) && ($info['extension'] == 'xml' || $info['extension'] == 'zip'));
        });
                
        if ($files)
        {
            foreach($files as $file)
            {
                $output = \GT\QualificationStructure::importXML($installDir.'/structures/qual/'.$file);
                mtrace( $output['output'], "\n<br>" );
                if ($output['errors'])
                {
                    if (is_array($output['errors'][0]))
                    {
                        foreach($output['errors'] as $fileNum => $errors)
                        {
                            mtrace('Errors ('.$output['files'][$fileNum].'): ' . implode('\n<br>', $errors), "\n<br>");
                        }
                    } 
                    else
                    {
                        mtrace('Errors: ' . implode('\n<br>', $output['errors']), "\n<br>");
                    }
                }
            }
        }
        
        
        
        
        
        // Install Qualification Builds
        // This includes the Qual grading structures and any assessment grading structures defined
        // just for this build. As well as any default settings for this build.
        $files = scandir($installDir . '/structures/build');
        $files = array_filter($files, function($f) use ($installDir){
            $info = pathinfo( $installDir . '/structures/build/' . $f );
            return (!is_dir($installDir.'/structures/build/' . $f) && isset($info['extension']) && ($info['extension'] == 'xml' || $info['extension'] == 'zip'));
        });
        
        if ($files)
        {
            foreach($files as $file)
            {
                $output = \GT\QualificationBuild::importXML($installDir.'/structures/build/'.$file, true);
                mtrace( $output['output'] , "\n<br>");
                if ($output['errors'])
                {
                    if (is_array($output['errors'][0]))
                    {
                        foreach($output['errors'] as $fileNum => $errors)
                        {
                            mtrace('Errors ('.$output['files'][$fileNum].'): ' . implode('\n<br>', $errors), "\n<br>");
                        }
                    } 
                    else
                    {
                        mtrace('Errors: ' . implode('\n<br>', $output['errors']), "\n<br>");
                    }
                }
            }
        }
        
        
        
       
        
        // Quals On Entry Types
        $types = json_decode( file_get_contents($installDir.'/qoe/types.json') );
        if ($types)
        {
            foreach($types as $type)
            {
                $check = $DB->get_record("bcgt_qoe_types", array("name" => $type->name));
                if ($check)
                {
                    $check->lvl = $type->lvl;
                    $check->weighting = $type->weighting;
                    $result = $DB->update_record("bcgt_qoe_types", $check);
                }
                else
                {
                    $obj = new \stdClass();
                    $obj->name = $type->name;
                    $obj->lvl = $type->lvl;
                    $obj->weighting = $type->weighting;
                    $result = $DB->insert_record("bcgt_qoe_types", $obj);
                }
                
                mtrace('Trying to insert/update qoe_type ' . $type->name . '...' . (int)$result, "\n<br>");
                
            }            
            
        }

        
        
        
        // Quals On Entry Grades
        $grades = json_decode( file_get_contents($installDir.'/qoe/grades.json') );
        if ($grades)
        {
            foreach($grades as $grade)
            {
                $type = $DB->get_record("bcgt_qoe_types", array("name" => $grade->type));
                if ($type)
                {
                    $check = $DB->get_record("bcgt_qoe_grades", array("qoeid" => $type->id, "grade" => $grade->grade));
                    if ($check)
                    {
                        $check->points = $grade->points;
                        $check->weighting = $grade->weighting;
                        $result = $DB->update_record("bcgt_qoe_types", $check);
                    }
                    else
                    {
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
        
        
        
        
        
        // ======================== Insert Default Settings ======================== //
        
        // General Settings
        \GT\Setting::updateSetting('plugin_title', 'Grade Tracker');
        \GT\Setting::updateSetting('theme_layout', 'base');
        \GT\Setting::updateSetting('use_gt_jqueryui', '1');      
        \GT\Setting::updateSetting('student_role_shortnames', 'student');
        \GT\Setting::updateSetting('staff_role_shortnames', 'editingteacher,teacher');
        \GT\Setting::updateSetting('course_name_format', '[%sn%] %fn%');
        \GT\Setting::updateSetting('use_auto_enrol_quals', '1');
        \GT\Setting::updateSetting('use_auto_enrol_units', '1');
        \GT\Setting::updateSetting('use_auto_unenrol_quals', '1');
        \GT\Setting::updateSetting('use_auto_unenrol_units', '1');
        
        // Qual Settings
        
            // Weighting Coefficients
            \GT\Setting::updateSetting('qual_weighting_percentiles', '9');
            \GT\Setting::updateSetting('weighting_percentile_color_1', '#c21014');
            \GT\Setting::updateSetting('weighting_percentile_color_2', '#f21318');
            \GT\Setting::updateSetting('weighting_percentile_color_3', '#ee6063');
            \GT\Setting::updateSetting('weighting_percentile_color_4', '#6e6e6e');
            \GT\Setting::updateSetting('weighting_percentile_color_5', '#252525');
            \GT\Setting::updateSetting('weighting_percentile_color_6', '#6e6e6e');
            \GT\Setting::updateSetting('weighting_percentile_color_7', '#72a9fc');
            \GT\Setting::updateSetting('weighting_percentile_color_8', '#2047ff');
            \GT\Setting::updateSetting('weighting_percentile_color_9', '#123798');
            \GT\Setting::updateSetting('weighting_percentile_percentage_1', '100');
            \GT\Setting::updateSetting('weighting_percentile_percentage_2', '90');
            \GT\Setting::updateSetting('weighting_percentile_percentage_3', '75');
            \GT\Setting::updateSetting('weighting_percentile_percentage_4', '60');
            \GT\Setting::updateSetting('weighting_percentile_percentage_5', '40');
            \GT\Setting::updateSetting('weighting_percentile_percentage_6', '25');
            \GT\Setting::updateSetting('weighting_percentile_percentage_7', '10');
            \GT\Setting::updateSetting('weighting_percentile_percentage_8', '0');
            \GT\Setting::updateSetting('weighting_percentile_percentage_9', '1');
            \GT\Setting::updateSetting('default_weighting_percentile', '3');

            // Weighting Constants
            \GT\Setting::updateSetting('weighting_constants_enabled', '0'); # Should enable this if they want it
            \GT\Setting::updateSetting('weighting_direction', 'UP');
            
        // Unit Settings
            
            
        // Criteria Settings
            
            
        // Grid Settings
        \GT\Setting::updateSetting('enable_grid_logs', '0');
        \GT\Setting::updateSetting('grid_fixed_links', '0');
        \GT\Setting::updateSetting('assessment_grid_show_quals_one_page', '1');
        \GT\Setting::updateSetting('unit_grid_paging', '15');
        \GT\Setting::updateSetting('class_grid_paging', '15');
        
        // User Settings
        \GT\Setting::updateSetting('student_columns', 'username,name');

        // Grade Settings
        \GT\Setting::updateSetting('pred_grade_min_units', '3');
        \GT\Setting::updateSetting('asp_grade_diff', '1');
        \GT\Setting::updateSetting('weighted_target_method', 'ucas');
        \GT\Setting::updateSetting('weighted_target_direction', 'UP');
        
        // Assessment Settings
        \GT\Setting::updateSetting('use_assessments_comments', '1');     
                             
        
        mtrace('Inserted default configuration settings', "\n<br>");
        

    }
    
    /**
     * Get the a.b.c version number of the plugin
     * @return type
     */
    public function getPluginVersion()
    {
        return self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION;
    }
    
    /**
     * Get the block version as of the version.php file
     */
    public function getBlockVersion()
    {
        global $DB;
        $block = $DB->get_record("config_plugins", array("plugin" => "block_gradetracker", "name" => "version"));
        return ($block) ? $block->value : false;
    }
    
     /**
     * Print out a message if there are new updates
     * @return string
     */
    public function printVersionCheck($full = false, $return = false){
        
        global $CFG;
        
        $remote = @file_get_contents(self::REMOTE_VERSION_URL);
        if (!$remote){
            return \gt_error_alert_box(get_string('unabletocheckforupdates', 'block_gradetracker'));
        }
        
        $remote = json_decode(trim($remote));
        if (!$remote || is_null($remote)){
            return \gt_error_alert_box(get_string('unabletocheckforupdates', 'block_gradetracker'));
        }
                
        $result = version_compare($this->getPluginVersion(), $remote->version, '<');
        if ($result){
            $img = (file_exists($CFG->dirroot . '/blocks/gradetracker/pix/update_'.$remote->update.'.png')) ? $CFG->wwwroot . '/blocks/gradetracker/pix/update_'.$remote->update.'.png' : $CFG->wwwroot . '/blocks/gradetracker/pix/update_general.png';
            $link = (isset($remote->file) && $remote->file != '') ? $remote->file : self::REMOTE_HOST_URL;
            if ($full){
                return "<span class='gt_update_notification_full_{$remote->update}'>".get_string('newversionavailable', 'block_gradetracker').": {$remote->version} [".\get_string('versionupdatetype_'.$remote->update, 'block_gradetracker')."]</span>";
            } else {
                return "&nbsp;&nbsp;&nbsp;&nbsp;<span class='gt_update_notification'><a href='{$link}'><img src='{$img}' alt='update' title='".get_string('newversionavailable', 'block_gradetracker').": {$remote->version} [".\get_string('versionupdatetype_'.$remote->update, 'block_gradetracker')."]' /></a></span>";
            }
        }
        
        elseif ($return){
            return $return;
        }
        
    }
    
    /**
     * Get the title of the plugin
     */
    public function getPluginTitle()
    {
        $setting = \GT\Setting::getSetting("plugin_title");
        return ($setting) ? format_text($setting, FORMAT_PLAIN) : get_string('pluginname', 'block_gradetracker');
    }
    
    /**
     * Get the chosen theme
     * @return type
     */
    public function getTheme()
    {
        $setting = \GT\Setting::getSetting("theme");
        return ($setting) ? $setting : 'default';
    }
    
    /**
     * Get the theme layout setting for the full page views. Or "login" by default if undefined.
     * @return type
     */
    public function getMoodleThemeLayout()
    {
        
        $setting = \GT\Setting::getSetting("theme_layout");
        return ($setting) ? $setting : 'base';
        
    }
    
    /**
     * get the selected categories to use in reporting
     * @return type
     */
    public function getReportingCategories(){
        
        $setting = \GT\Setting::getSetting("reporting_categories");
        $cats = explode(",", $setting);
        
        $return = array();
        foreach($cats as $catID)
        {
            $return[$catID] = \coursecat::get($catID)->name;
        }
        
        // Order by name
        asort($return);
        
        return $return;
        
    }
    
       
    /**
     * Get the URL of the institution logo image
     * @return boolean
     */
    public function getInstitutionLogoUrl()
    {
        
        global $CFG;
        
        $setting = \GT\Setting::getSetting("institution_logo");
        if ($setting)
        {
            $code = gt_get_data_path_code($CFG->dataroot . '/gradetracker/img/' . $setting);
            if ($code)
            {
                return $CFG->wwwroot . '/blocks/gradetracker/download.php?f=' . $code;
            }
        }
        
        return false;
        
    }
    
    /**
     * Get the course name format
     * @return type
     */
    public function getCourseNameFormat()
    {
        $setting = \GT\Setting::getSetting("course_name_format");
        return ($setting && strlen($setting) > 0) ? ($setting) : "[%sn%] %fn%";
    }
    
    /**
     * Get an array of role shortnames for roles we want to be able to use to link students to a qualification
     * @return array
     */
    public function getStudentRoles()
    {
        
        $return = array();
        $roles = \GT\Setting::getSetting("student_role_shortnames");
        
        if ($roles)
        {
            $shortnames = explode(",", $roles);
            if ($shortnames)
            {
                foreach($shortnames as $shortname)
                {
                    $role = gt_get_role_by_shortname( trim($shortname) );
                    if ($role)
                    {
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
    public function getStaffRoles()
    {
        
        $return = array();
        $roles = \GT\Setting::getSetting("staff_role_shortnames");
        
        if ($roles)
        {
            
            $shortnames = explode(",", $roles);
            if ($shortnames)
            {
                foreach($shortnames as $shortname)
                {
                    $role = gt_get_role_by_shortname( trim($shortname) );
                    if ($role)
                    {
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
    public function getStudentGridNavigation(){
        
        $setting = $this->getSetting('student_grid_navigation');
        return json_decode($setting);
        
    }
    
    /**
     * Get the navigation links for the unit grid
     * @return type
     */
    public function getUnitGridNavigation(){
        
        $setting = $this->getSetting('unit_grid_navigation');
        return json_decode($setting);
        
    }
    
    /**
     * Get the navigation links for the class grid
     * @return type
     */
    public function getClassGridNavigation(){
        
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
    public function updateSetting($setting, $value, $userID = null)
    {
        return \GT\Setting::updateSetting($setting, $value, $userID);
    }
    
    public function getSetting($setting, $userID = null)
    {
        return \GT\Setting::getSetting($setting, $userID);
    }
    
    /**
     * For a given user, find all the contexts they are assigned to
     * @param int $userID If null, will use the user id of the current user logged in
     */
    public function getUserContexts($userID = null)
    {
     
        global $DB, $USER;
        
        // Stop if no user logged in
        if (!$USER) return false;

        // If no user specified, use you instead
        if (is_null($userID)) $userID = $USER->id;
        
        $contexts = array();
        
        $records = $DB->get_records_sql("SELECT DISTINCT c.*
                                         FROM {role_assignments} r
                                         INNER JOIN {context} c ON c.id = r.contextid
                                         WHERE r.userid = ?", array($userID));
        
        // If they are enroled on any course, check the context of those courses
        if ($records)
        {
            foreach($records as $record)
            {
                $info = get_context_info_array($record->id);
                if (isset($info[0]) && $info[0])
                {
                    $contexts[] = $info[0];
                }
            }
        }
        // Otherwise, just check against the system context, incase they are admin with no actual enrolments
        else
        {
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
    public function displayConfig($view, $section = false, $page = false)
    {
        
        global $CFG, $PAGE, $MSGS, $VARS, $USER;
                
        if (!$view) return false;
        
        $User = new \GT\User($USER->id);
        $id = optional_param('id', false, PARAM_INT);
        
        if (isset($VARS['TPL'])){
            $TPL = $VARS['TPL'];
        } else {
            $TPL = new \GT\Template();
        }
                
        $TPL->set("GT", $this)
            ->set("MSGS", $MSGS)
            ->set("section", $section)
            ->set("User", $User);
                
        try {
            
            switch ($view)
            {
                
                case 'overview':
                    
                    $Site = new \GT\Site();
                    $TPL->set("Site", $Site);
                    
                    // Qual Structures
                    $structures = \GT\QualificationStructure::getAllStructures();
                    $TPL->set("structures", $structures);
                    
                    // Stats
                    $TPL->set("countUsers", \GT\User::countUsers());
                    $TPL->set("countCourses", \GT\Course::countCourses());
                    
                    // Logs
                    $TPL->set("logs", \GT\Log::getRecentLogs(15));
                    
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
                    
                    $this->displayConfigUnit($TPL, $VARS, $section, $page, $id);
                    
                break;
                
                case 'course': 
                    
                    $this->displayConfigCourse($TPL, $section, $page, $id);
                    
                break;
                
                case 'data':
                    
                    $this->displayConfigData($TPL, $section, $page);
                    
                break;
            
                case 'assessments':
                    
                    switch($section)
                    {   
                    
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

            
            if ($section && $page)
            {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'/'.$section.'/'.$page.'.html';
            }
            elseif ($section)
            {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'/'.$section.'.html';
            }
            else
            {
                $file = $this->CFG->dirroot . '/blocks/gradetracker/tpl/config/'.$view.'.html';
            }
            
            $TPL->load($file);
            $TPL->display();
            
        } catch (\GT\GTException $e){
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
    public function displayConfigSettings($TPL, $VARS, $PAGE, $section, $page, $id)
    {
        if ($section == 'general')
        {

            // Get the possible layouts of the theme
            ksort($PAGE->theme->layouts);
            $TPL->set("layouts", $PAGE->theme->layouts);

        }

        elseif ($section == 'qual')
        {

            // Qual builds
            $TPL->set("builds", \GT\QualificationBuild::getAllBuilds());

            if ($page == 'coefficients')
            {
                $TPL->set("percentiles", \GT\Setting::getSetting('qual_weighting_percentiles'));
                $TPL->set("qualifications", \GT\Qualification::getAllQualifications());
            }

        }

        elseif ($section == 'user')
        {
            $cols = $this->getSetting('student_columns');
            $TPL->set("cols", explode(",", $cols));
        }

        elseif ($section == 'assessments')
        {
            $fields = \GT\Assessment::getCustomFormFields();
            $TPL->set("fields", $fields);
            $TPL->set("cntCustomFormElements", 0);
        }
        
        elseif ($section == 'reporting')
        {
            $TPL->set("categories", \coursecat::make_categories_list());
            $TPL->set("reportingCats", $this->getReportingCategories());
            $TPL->set("structures", \GT\QualificationStructure::getAllStructures());
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
    public function displayConfigStructures($TPL, $VARS, $section, &$page, $id)
    {
        $Structure = false;
                    
        // Qualification Structures
        if ($section == 'qual')
        {

            if ( $page == 'new' || $page == 'edit' )
            {

                $page = 'new';
                $Structure = new \GT\QualificationStructure($id);

                // If we've submitted post data, that object will be in VARS to use instead of a blank object
                if (isset($VARS['qualStructure']))
                {
                    $Structure = $VARS['qualStructure'];
                }

            }

            elseif ($page == 'delete')
            {
                $Structure = new \GT\QualificationStructure($id);
            }

            // Overview page 
            else
            {
                $TPL->set("qualStructures", \GT\QualificationStructure::getAllStructures());
            }

            // Existing structures
            $TPL->set("possibleLevels", \GT\QualificationStructure::getPossibleStructureLevels());
            $TPL->set("possibleFeatures", \GT\QualificationStructure::getPossibleStructureFeatures());
            $TPL->set("Structure", $Structure);
            $TPL->set("cntCustomFormElements", 0);
            $TPL->set("cntRules", 0);

        }

        // Qualification Builds
        elseif ($section == 'builds')
        {

            $TPL->set("builds", \GT\QualificationBuild::getAllBuilds());

            $Build = new \GT\QualificationBuild($id);

            if ($page == 'new' || $page == 'edit')
            {

                $page = 'new';

                $TPL->set("qualStructures", \GT\QualificationStructure::getAllStructures());
                $TPL->set("qualLevels", \GT\Level::getAllLevels());
                $TPL->set("qualSubTypes", \GT\SubType::getAllSubTypes());

                if (isset($VARS['qualBuild']))
                {
                    $Build = $VARS['qualBuild'];
                }

            }

            elseif ($page == 'defaults')
            {

                $TPL->set("Structure", new \GT\QualificationStructure($Build->getStructureID()));

            }

            $TPL->set("Build", $Build);
            $TPL->set("cntAwards", 0);

        }

        // Grading structures
        elseif ($section == 'grade')
        {

            $type = optional_param('type', false, PARAM_TEXT);

            // If the build is specified we are creating grading structures for the build instead of the qual structure
            // In which case, we can load the QualStructure from the Build before hand
            $buildID = optional_param('build', false, PARAM_INT);

            // If Build specified
            if ($buildID)
            {
                $id = false; // Reset the QualStructure id to false
                $build = new \GT\QualificationBuild($buildID);
                if ($build->isValid())
                {
                    $id = $build->getStructureID();
                    $TPL->set("Build", $build);
                }
            }

            // View the editing page
            if ($page == 'edit' && $id){
                $Structure = new \GT\QualificationStructure($id);
                $TPL->set("Structure", $Structure);
            } 

            elseif ($page == 'new_unit' && $id){

                $Structure = new \GT\QualificationStructure($id);
                $TPL->set("Structure", $Structure);

                if (isset($VARS['UnitGradingStructure'])){
                    $TPL->set("UnitAwardStructure", $VARS['UnitGradingStructure']);
                } else {
                    $TPL->set("UnitAwardStructure", new \GT\UnitAwardStructure());
                }

            }

            elseif ($page == 'new_criteria' && $id)
            {

                $Structure = new \GT\QualificationStructure($id);
                $TPL->set("Structure", $Structure);

                if (isset($VARS['CriteriaAwardStructure'])){
                    $TPL->set("CriteriaAwardStructure", $VARS['CriteriaAwardStructure']);
                } else {
                    $TPL->set("CriteriaAwardStructure", new \GT\CriteriaAwardStructure());
                }

            }

            elseif ($page == 'edit_unit' && $id){

                $page = 'new_unit';

                if (isset($VARS['UnitGradingStructure'])){
                    $UnitAwardStructure = $VARS['UnitGradingStructure'];
                } else {
                    $UnitAwardStructure = new \GT\UnitAwardStructure($id);
                }

                $TPL->set("UnitAwardStructure", $UnitAwardStructure);

                if ($UnitAwardStructure->isValid()){
                    $Structure = new \GT\QualificationStructure($UnitAwardStructure->getQualStructureID());
                    $TPL->set("Structure", $Structure);
                    $TPL->set("buildLevels", $Structure->getAllBuildLevels());
                    $TPL->set("builds", $Structure->getAllBuilds());
                }

            }

            elseif ($page == 'edit_criteria' && $id)
            {

                $page = 'new_criteria';

                if (isset($VARS['CriteriaAwardStructure'])){
                    $CriteriaAwardStructure = $VARS['CriteriaAwardStructure'];
                } else {
                    $CriteriaAwardStructure = new \GT\CriteriaAwardStructure($id);
                }

                $TPL->set("CriteriaAwardStructure", $CriteriaAwardStructure);

                if ($CriteriaAwardStructure->isValid()){
                    
                    // Qual Structure
                    $TPL->set("Structure", new \GT\QualificationStructure($CriteriaAwardStructure->getQualStructureID()));
                    
                    // Qual Build
                    if ($CriteriaAwardStructure->getQualBuildID()){
                        $TPL->set("Build", new \GT\QualificationBuild($CriteriaAwardStructure->getQualBuildID()));
                    }
                    
                }


            }

            elseif ($page == 'delete_unit' && $id){

                $UnitAwardStructure = new \GT\UnitAwardStructure($id);
                $TPL->set("UnitAwardStructure", $UnitAwardStructure);

            }

            elseif ($page == 'delete_criteria' && $id)
            {

                $CriteriaAwardStructure = new \GT\CriteriaAwardStructure($id);
                $TPL->set("CriteriaAwardStructure", $CriteriaAwardStructure);

            }

            // Build Or Structure to be used to select the grading structures and not duplicate code
            if (isset($build) && $build && $build->isValid()){
                $TPL->set("Object", $build);
            } else {
                $TPL->set("Object", $Structure);
            }

            $TPL->set("qualStructures", \GT\QualificationStructure::getAllStructures());
            $TPL->set("type", $type);
            $TPL->set("cntAwards", 0);

        }

        elseif ($section == 'levels')
        {

            $levels = \GT\Level::getAllLevels();
            $TPL->set("levels", $levels);

            if ($page == 'new' || $page == 'edit')
            {

                $Level = new \GT\Level($id);
                $page = 'new';
                $TPL->set("Level", $Level);

            }

            elseif ($page == 'delete')
            {
                $Level = new \GT\Level($id);
                $TPL->set("Level", $Level);
            }

        }

        elseif ($section == 'subtypes')
        {

            $subTypes = \GT\SubType::getAllSubTypes();
            $TPL->set("subTypes", $subTypes);

            if ($page == 'new' || $page == 'edit')
            {

                $page = 'new';

                if (isset($VARS['SubType'])){
                    $subType = $VARS['SubType'];
                } else {
                    $subType = new \GT\SubType($id);
                }

                $TPL->set("SubType", $subType);

            }

            elseif ($page == 'delete')
            {
                $subType = new \GT\SubType($id);
                $TPL->set("SubType", $subType);
            }

        }

        elseif ($section == 'qoe')
        {

            $TPL->set("allSubjects", \GT\QualOnEntry::getAllSubjects());
            $TPL->set("allTypes", \GT\QualOnEntry::getAllTypes());
            $TPL->set("allGrades", \GT\QualOnEntry::getAllGrades());

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
    public function displayConfigQuals($TPL, $VARS, &$section, $page, $id)
    {
        // Edit is just used for aesthetic purposes, it's the "new" form
        if ($section == 'edit') $section = 'new';
        
        // Overview
        if ($section == 'overview'){
            
            $stats = array();
            $stats['active'] = \GT\Statistics::getQualifications('active');
            $stats['inactive'] = \GT\Statistics::getQualifications('inactive');
            $stats['correctcredits'] = \GT\Statistics::getQualificationsByCredits('correct');
            $stats['incorrectcredits'] = \GT\Statistics::getQualificationsByCredits('incorrect');
            
            $TPL->set("stats", $stats);
            
        }

        // New Qualification form
        elseif ($section == 'new'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Qualification\GUI($id);
            }
            
            $GUI->loadTemplate($TPL);
            $GUI->displayFormNewQualification();
            $TPL->set("Qualification", $GUI);

        }

        elseif ($section == 'search'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Qualification\GUI($id);
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormSearchQualifications();
            $TPL->set("Qualification", $GUI);

        }
        elseif ($section == 'delete'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Qualification\GUI($id);
            }
            $qualification= new \GT\Qualification($id);
            $TPL->set("qualification", $qualification);
            $GUI->loadTemplate($TPL);                      
        }
        elseif ($section == 'copy'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Qualification\GUI($id);
            }
            $copyQual= new \GT\Qualification($id);
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
    public function displayConfigUnit($TPL, $VARS, &$section, $page, $id)
    {
        
        // Edit and New use same template, so set it to "new" if it's "edit"
        if ($section == 'edit') $section = 'new';
                    
        // Overview
        if ($section == 'overview'){
            
            $stats = array();
            $stats['active'] = \GT\Statistics::getUnits('active');
            $stats['inactive'] = \GT\Statistics::getUnits('inactive');
            
            $TPL->set("stats", $stats);
            
        }
        
        elseif ($section == 'new'){
            
            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Unit\GUI($id);
            }
            
            $GUI->loadTemplate($TPL);
            $GUI->displayFormNewUnit();
            $TPL->set("Unit", $GUI);

        }
        elseif ($section == 'search'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Unit\GUI($id);
            }

            $GUI->loadTemplate($TPL);
            $GUI->displayFormSearchUnits();                        
        }
        elseif ($section == 'delete'){

            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Unit\GUI($id);
            }
            $unit = new \GT\Unit($id);
            $TPL->set("unit", $unit);
            $GUI->loadTemplate($TPL);                      
        }
        elseif ($section == 'copy')
        {
            if (isset($VARS['GUI'])){
                $GUI = $VARS['GUI'];
            } else {
                $GUI = new \GT\Unit\GUI($id);
            }
            $copyUnit = new \GT\Unit($id);
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
    public function displayConfigCourse($TPL, $section, $page, $id)
    {
        
        global $USER, $PAGE;
        
        $User = new \GT\User($USER->id);
        
        if ($section == 'overview')
        {
            
        }
        
        elseif ($section == 'search')
        {
            $TPL->set("categories", \coursecat::make_categories_list());
        }
        
        elseif ($section == 'my')
        {
            $TPL->set("courses", $User->getCourses("STAFF"));
        }
        
        else
        {
            $course = new \GT\Course($id);
            $TPL->set("Course", $course);
            if (!$course->isValid()){
                print_error('invalidcourse', 'block_gradetracker');
            }

            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($id, "STAFF") && !$User->hasCapability('block/gradetracker:edit_all_courses')){
                print_error('invalidaccess', 'block_gradetracker');
            }
            
            // Optional Parent course ID
            $pID = optional_param('pID', false, PARAM_INT);
            $TPL->set("pID", $pID);

            if ($section == 'quals'){

                if (!$User->hasCapability('block/gradetracker:edit_course_quals')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $TPL->set("allStructures", \GT\QualificationStructure::getAllStructures());
                $TPL->set("allLevels", \GT\Level::getAllLevels());
                $TPL->set("allSubTypes", \GT\SubType::getAllSubTypes());
                $QualPicker = new \GT\FormElement();
                $QualPicker->setType('QUALPICKER');
                $QualPicker->setValue( $course->getCourseQualifications() );
                $TPL->set("QualPicker", $QualPicker);

            } elseif ($section == 'userquals'){

                $TPL->set("staff", $course->getStaff());
                $TPL->set("courseQuals", $course->getCourseQualifications(true));


            } elseif ($section == 'userunits'){
            
                global $GTEXE;
                $GTEXE = \GT\Execution::getInstance();
                $GTEXE->QUAL_STRUCTURE_MIN_LOAD = true;
                $GTEXE->QUAL_BUILD_MIN_LOAD = true;
                $GTEXE->QUAL_MIN_LOAD = true;
                $GTEXE->UNIT_MIN_LOAD = true;
                $GTEXE->STUDENT_LOAD_LEVEL = \GT\Execution::STUD_LOAD_LEVEL_UNIT;
                
            } elseif ($section == 'activities'){

                // Check permissions
                if (!$User->hasCapability('block/gradetracker:edit_course_activity_refs')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $modLinks = \GT\ModuleLink::getEnabledModLinks();
                $courseQuals = $course->getCourseQualifications(true);
                $TPL->set("courseQuals", $courseQuals);
                $TPL->set("modLinks", $modLinks);
                $TPL->set("activities", $course->getSupportedActivities());
                

                if ($page == 'add'){

                    // Bring javascript in
                    $PAGE->requires->js( '/blocks/gradetracker/js/mod.js' , false );
                    $PAGE->requires->js_init_call("gt_mod_hook_bindings", null, true);

                    $cmID = optional_param('cmid', false, PARAM_INT);
                    $qID = optional_param('qualid', false, PARAM_INT);
                    $uID = optional_param('unitid', false, PARAM_INT);

                    // If cmID is valid, that means we clicked on an assignment and we want to add units to it
                    if ($cmID)
                    {

                        if (isset($_POST['coursemoduleid'])){
                            $cmID = $_POST['coursemoduleid'];
                        }
                        $TPL->set("cmID", $cmID);

                        $moduleActivity = \GT\ModuleLink::getModuleLinkFromCourseModule($cmID);
                        $TPL->set("moduleActivity", $moduleActivity);

                        $unitsLinked = array();
                        if ($courseQuals){
                            foreach($courseQuals as $courseQual){
                                $unitsLinked[$courseQual->getID()] = \GT\Activity::getUnitsLinkedToCourseModule($cmID, $courseQual->getID(), true);
                            }
                        }

                        $TPL->set("unitsLinked", $unitsLinked);
                        $TPL->set("viewBy", "cm");

                    }

                    // If qual & unit are valid, we clicked on a unit and want to add assignments to it
                    else
                    {

                        $qual = new \GT\Qualification($qID);
                        $unit = $qual->getUnit($uID);
                        if (!$unit){
                            print_error( 'invalidunit', 'block_gradetracker');
                        }
                        $criteria = $unit->sortCriteria(false, true);

                        $qualUnitActivities = \GT\ModuleLink::getModulesOnUnit($qual->getID(), $unit->getID(), $course->id);

                        $TPL->set("qual", $qual);
                        $TPL->set("unit", $unit);
                        $TPL->set("criteria", $criteria);
                        $TPL->set("unitActivities", $qualUnitActivities);
                        $TPL->set("viewBy", "unit");

                    }
                }
                
                elseif ($page == 'delete') {
                    
                    // Check permissions
                    if (!$User->hasCapability('block/gradetracker:delete_course_activity_refs')){
                        print_error('invalidaccess', 'block_gradetracker');
                    }
                    
                    $cmID = optional_param('cmid', false, PARAM_INT);
                    $qID = optional_param('qualid', false, PARAM_INT);
                    $uID = optional_param('unitid', false, PARAM_INT);
                    $part = optional_param('part', null, PARAM_INT);
                    
                    $moduleLink = \GT\ModuleLink::getModuleLinkFromCourseModule($cmID);
                    
                    $qual = new \GT\Qualification($qID);
                    if ($uID){
                        $unit = new \GT\Unit($uID);
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
    public function displayConfigData($TPL, $section, $page)
    {
        switch($section)
        {

            case 'tg':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateTargetGradesCSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleTargetGradesCSV($reload));
                $TPL->set("allQuals", \GT\Qualification::getAllQualifications());

            break;

            case 'qoe':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateQoECSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleQoECSV($reload));


            break;
        
            case 'avggcse':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateAvgGCSECSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleAvgGCSECSV($reload));


            break;
        
            case 'aspg':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateAspirationalGradesCSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleAspirationalGradesCSV($reload));

            break;
        
            case 'ceta':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateCetaGradesCSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleCetaGradesCSV($reload));

            break;
        
            case 'wcoe':

                $reload = optional_param('reload', false, PARAM_BOOL);

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateWCoeCSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleWCoeCSV($reload));

            break;
        
            case 'ass':

                $reload = optional_param('reload', false, PARAM_BOOL);
                $assessments = \GT\Assessment::getAllAssessments();

                $TPL->set("templateFile", \GT\CSV\Template::generateTemplateAssGradesCSV($reload));
                $TPL->set("exampleFile", \GT\CSV\Example::generateExampleAssGradesCSV($reload));
                $TPL->set("assessments", $assessments);


            break;
        
            case 'block_bcgt':
                
                $oldGT = new \GT\OldGradeTrackerSystem();
                $allStructures = \GT\QualificationStructure::getAllStructures();
                $allQuals = \GT\Qualification::getAllQualifications();
                
                if ($page == 'data'){
                    $newQuals = \GT\OldGradeTrackerSystem::getNewMappedQualifications();
                    $TPL->set("newQuals", $newQuals);
                }
                
                $TPL->set("oldGT", $oldGT);
                $TPL->set("allStructures", $allStructures);
                $TPL->set("allQuals", $allQuals);
                
                                
            break;

        }
        
    }
    
    /**
     * Display configuration assessments manage
     * @param type $TPL
     * @param type $page
     * @param type $User
     */
    public function displayConfigAssessmentsModules($TPL, $page, $User)
    {
        
        global $VARS;
        
        // Check permissions
        if (!$User->hasCapability('block/gradetracker:edit_activity_settings')){
            print_error('invalidaccess', 'block_gradetracker');
        }
        
        if ($page == 'edit' || $page == 'delete'){
                                
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:'.$page.'_course_activity_refs')){
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Mod = (isset($VARS['Mod'])) ? $VARS['Mod'] : new \GT\ModuleLink($id);
            $TPL->set("Mod", $Mod);
            $TPL->set("allMods", \GT\ModuleLink::getAllInstalledMods());

        } 

        $TPL->set("mods", \GT\ModuleLink::getEnabledModLinks());
    }
    
    
    /**
     * Display configuration assessments manage
     * @param type $TPL
     * @param type $page
     * @param type $User
     */
    public function displayConfigAssessmentsManage($TPL, $page, $User)
    {
        
        global $VARS;
        
        if ($page == 'edit'){
                                
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_assessments')){
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = (isset($VARS['Assessment'])) ? $VARS['Assessment'] : new \GT\Assessment($id);
            $TPL->set("Assessment", $Assessment);
            $TPL->set("allTypes", \GT\Assessment::getAllTypes());
            
            $quals = $Assessment->getQuals();

            $QualPicker = new \GT\FormElement();
            $QualPicker->setType('QUALPICKER');
            $QualPicker->setValue( $quals );
            $TPL->set("QualPicker", $QualPicker);

            $formFields = \GT\Assessment::getCustomFormFields();
            $TPL->set("formFields", $formFields);
            
            
            // Get distinct list of qualification structures attached to this assessment
            $qualStructures = array();
            if ($quals)
            {
                foreach($quals as $qualID)
                {
                    $qualStructure = \GT\Qualification::getStructureFromQualID($qualID);
                    if ($qualStructure)
                    {
                        $qualStructures[$qualStructure->getID()] = $qualStructure;
                    }
                }
            }
            $TPL->set("qualStructuresArray", $qualStructures);            
            
            // Get distinct list of qualification builds attached to this assessment
            $qualBuilds = array();
            if ($quals)
            {
                foreach($quals as $qualID)
                {
                    $qualBuild = \GT\Qualification::getBuildFromQualID($qualID);
                    if ($qualBuild)
                    {
                        $qualBuilds[$qualBuild->getID()] = $qualBuild;
                    }
                }
            }
            $TPL->set("qualBuildsArray", $qualBuilds); 
            

        } elseif ($page == 'delete'){

            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_assessments')){
                print_error('invalidaccess', 'block_gradetracker');
            }

            $id = optional_param('id', false, PARAM_INT);
            $Assessment = (isset($VARS['Assessment'])) ? $VARS['Assessment'] : new \GT\Assessment($id);
            $TPL->set("Assessment", $Assessment);

        } else {

            $TPL->set("allAssessments", \GT\Assessment::getAllAssessments());

        }
    }
    
    
    /**
     * Display configuration reporting
     * @param type $TPL
     */
    public function displayConfigTestsTG($TPL)
    {
        $reload = optional_param('reload', false, PARAM_BOOL);
                            
        $TPL->set("templateFile", \GT\CSV\Template::generateTemplateTargetGradesCSV($reload));
        $TPL->set("exampleFile", \GT\CSV\Example::generateExampleTargetGradesCSV($reload));
        $TPL->set("allQuals", \GT\Qualification::getAllQualifications());
    }
    
    
    /**
     * Display configuration reporting
     * @param type $TPL
     * @param type $page
     * @param type $id
     */
    public function displayConfigReporting($TPL, $section, $page, $id)
    {
        global $DB, $MSGS;
        
        // Pre-Built reports
        if ($section == 'reports')
        {
            
            // Check permissions
            if (!\gt_has_capability('block/gradetracker:run_built_report')){
                print_error('invalidaccess');
            }
            
            // Criteria Progress report
            if ($page == 'critprog')
            {
                
                $Report = new \GT\Reports\CriteriaProgressReport();
                $structures = \GT\QualificationStructure::getStructuresBySetting('custom_dashboard_view', 'view-criteria-short');
                
                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("awardNames", \GT\CriteriaAward::getDistinctNamesNonMet());
                $TPL->set("Report", $Report);

            }
            
            elseif ($page == 'passprog')
            {
                
                $Report = new \GT\Reports\PassCriteriaProgressReport();
                $structures = \GT\QualificationStructure::getStructuresBySetting('reporting_pass_criteria_method', true);
                
                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("awardNames", \GT\CriteriaAward::getDistinctNamesNonMet());
                $TPL->set("Report", $Report);
                
            }
            
            elseif ($page == 'passsummary')
            {
                
                $Report = new \GT\Reports\PassCriteriaSummaryReport();
                $structures = \GT\QualificationStructure::getStructuresBySetting('reporting_pass_criteria_method', true);
                
                $TPL->set("structures", $structures);
                $TPL->set("categories", $this->getReportingCategories());
                $TPL->set("Report", $Report);
                $TPL->set("awardNames", \GT\CriteriaAward::getDistinctNamesNonMet());
                
            }
            
        }
        
        elseif ($section == 'logs')
        {
         
            $GTEXE = \GT\Execution::getInstance();
            $GTEXE->min();
            
            $TPL->set("reflectionClass", new \ReflectionClass("\GT\Log"));
            $TPL->set("allQuals", \GT\Qualification::getAllQualifications(true));
            $TPL->set("allUnits", \GT\Unit::getAllUnits(false));
            $TPL->set("allCourses", \GT\Course::getAllCoursesWithQuals());
            
        }
        
    }
    
    
    
    
    /**
     * Save configuration settings
     * @param type $view
     * @param type $section
     */
    public function saveConfig($view, $section, $page)
    {
        
        global $DB, $CFG, $MSGS, $VARS;
        
        switch($view)
        {
            
            case 'settings':
                
                // If the save was successful, log it
                if ($this->saveConfigSettings($section, $page)){
                    
                    // ------------ Logging Info
                    $detail = 'GT_LOG_DETAILS_UPDATED_PLUGIN_'.strtoupper($section).'_SETTINGS';
                    $Log = new \GT\Log();
                    $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
                    $Log->details = constant('\GT\Log::' . $detail);
                    unset($_POST['submitconfig']);
                    $Log->afterjson = $_POST;
                    $Log->save();
                    // ------------ Logging Info
                    
                }
                
            break;
        
        
            // Structures
            case 'structures':
                
                // Qual structure or Grade structure
                switch($section)
                {
                    
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
                
                switch($section)
                {
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
                    case 'block_bcgt':
                        $this->saveConfigDataBlockBCGT();
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
                switch($section)
                {
                    case 'logs':
                        $this->saveConfigReportingLogs();
                    break;
                }
            break;
        
            
        }
        
                
    }
    
    /**
     * Save the Quals on Entry forms
     * @global \GT\type $CFG
     * @global \GT\type $MSGS
     */
    private function saveConfigQOE()
    {
        
        global $CFG, $MSGS;
        
        // Subjects
        if (isset($_POST['save_subjects']))
        {
            
            $idArray = array();
            if (isset($_POST['ids']))
            {
                
                for ($i = 0; $i < count($_POST['ids']); $i++)
                {
                    
                    $id = trim($_POST['ids'][$i]);
                    $name = trim($_POST['names'][$i]);
                    if (empty($name)) continue;
                    
                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;
                    
                    // Save the record
                    $result = \GT\QualOnEntry::saveSubject($id, $name);
                    if (is_numeric($result)){
                        $idArray[] = $result;
                    }
                    
                }
                
            }
            
            // Remove any we didn't save this time, as we must have deleted them on the form
            \GT\QualOnEntry::deleteSubjectsNotSaved($idArray);
            $MSGS['success'] = get_string('qoesubjectssaved', 'block_gradetracker');
            
        }
        
        // Types (qualifications)
        elseif (isset($_POST['save_types']))
        {
            
            $idArray = array();
            if (isset($_POST['ids']))
            {
             
                for ($i = 0; $i < count($_POST['ids']); $i++)
                {
                    
                    $id = trim($_POST['ids'][$i]);
                    $name = trim($_POST['names'][$i]);
                    $lvl = trim($_POST['levels'][$i]);
                    $weight = trim($_POST['weightings'][$i]);
                    if (empty($name)) continue;
                    
                    // Set default weight to 1 if not valid
                    if ($weight == '' || $weight < 0) $weight = 1;
                    
                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;
                    
                    // Save the record
                    $result = \GT\QualOnEntry::saveType($id, $name, $lvl, $weight);
                    if (is_numeric($result)){
                        $idArray[] = $result;
                    }
                    
                }
                
            }
            
            // Remove any we didn't save this time, as we must have deleted them on the form
            \GT\QualOnEntry::deleteTypesNotSaved($idArray);
            $MSGS['success'] = get_string('qoetypessaved', 'block_gradetracker');
            
        }
        
        // Grades
        elseif (isset($_POST['save_grades']))
        {
            
            $idArray = array();
            if (isset($_POST['ids']))
            {
             
                for ($i = 0; $i < count($_POST['ids']); $i++)
                {
                    
                    $id = trim($_POST['ids'][$i]);
                    $type = trim($_POST['types'][$i]);
                    $name = trim($_POST['grades'][$i]);
                    $points = trim($_POST['points'][$i]);
                    $weight = trim($_POST['weightings'][$i]);
                    if (empty($name)) continue;
                    
                    // Set default weight to 1 if not valid
                    if ($weight == '' || $weight < 0) $weight = 1;
                    
                    // Append to idArray so we can delete ones we haven't saved
                    $idArray[] = $id;
                    
                    // Save the record
                    $result = \GT\QualOnEntry::saveGrade($id, $type, $name, $points, $weight);
                    if (is_numeric($result)){
                        $idArray[] = $result;
                    }
                    
                }
                
            }
            
            // Remove any we didn't save this time, as we must have deleted them on the form
            \GT\QualOnEntry::deleteGradesNotSaved($idArray);
            $MSGS['success'] = get_string('qoegradessaved', 'block_gradetracker');
            
        }
        
        
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QOE;
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
        
    }
    
    /**
     * Save assessment configuration
     * @global \GT\type $CFG
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @global type $USER
     * @param type $section
     * @param type $page
     */
    private function saveConfigAssessments($section, $page)
    {
        
        global $CFG, $MSGS, $VARS, $USER;
        
        $User = new \GT\User($USER->id);
        
        // New/Edit module link
        if ($section == 'modules' && $page == 'edit')
        {
            
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_activity_settings')){
                print_error('invalidaccess', 'block_gradetracker');
            }
            
            $id = optional_param('id', false, PARAM_INT);
            $Module = new \GT\ModuleLink($id);
            $Module->loadPostData();
            
            $valid = ($Module->isValid());
                     
            if ( $Module->hasNoErrors() && $Module->save() ){
                
                $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');
                
                // Log variables
                $detail = ($valid) ? \GT\Log::GT_LOG_DETAILS_UPDATED_MODULE_LINK : \GT\Log::GT_LOG_DETAILS_CREATED_MODULE_LINK;
                $attributes = array('id' => $Module->getID());
                
            } else {
                $MSGS['errors'] = $Module->getErrors();
            }
            
            $VARS['Mod'] = $Module;
            
        }
        
        elseif ($section == 'modules' && $page == 'delete')
        {
            
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_course_activity_refs')){
                print_error('invalidaccess', 'block_gradetracker');
            }
            
            $id = optional_param('id', false, PARAM_INT);
            $Module = new \GT\ModuleLink($id);
            
            if (isset($_POST['run_away'])){
                redirect( $CFG->wwwroot . '/blocks/gradetracker/config.php?view=assessments&section=modules' );
            } elseif (isset($_POST['confirm_delete_mod_link'])){
                
                $Module->delete();
                $MSGS['success'] = get_string('modlinking:deleted', 'block_gradetracker');      
                
                 // Log variables
                $detail = \GT\Log::GT_LOG_DETAILS_DELETED_MODULE_LINK;
                $attributes = array('id' => $Module->getID());
                
            }
            
            $VARS['Mod'] = $Module;
            
        }
        
        elseif ($section == 'manage' && $page == 'edit')
        {
            
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:edit_assessments')){
                print_error('invalidaccess', 'block_gradetracker');
            }
            
            $id = optional_param('id', false, PARAM_INT);
            $Assessment = new \GT\Assessment($id);            
            $Assessment->loadPostData();
            
            $valid = ($Assessment->isValid());
            
            if ($Assessment->hasNoErrors()){
                
                $Assessment->save();
                $MSGS['success'] = get_string('assessmentsaved', 'block_gradetracker');
                
                // Log variables
                $detail = ($valid) ? \GT\Log::GT_LOG_DETAILS_UPDATED_ASSESSMENT : \GT\Log::GT_LOG_DETAILS_CREATED_ASSESSMENT;
                $attributes = array(\GT\Log::GT_LOG_ATT_ASSID => $Assessment->getID());
                
            } else {
                $MSGS['errors'] = $Assessment->getErrors();
            }
            
            $VARS['Assessment'] = $Assessment;
            
        }
        
        elseif ($section == 'manage' && $page == 'delete')
        {
            
            // Check permissions
            if (!$User->hasCapability('block/gradetracker:delete_assessments')){
                print_error('invalidaccess', 'block_gradetracker');
            }
            
            $id = optional_param('id', false, PARAM_INT);
            $Assessment = new \GT\Assessment($id);
            
            if (isset($_POST['run_away'])){
                redirect( $CFG->wwwroot . '/blocks/gradetracker/config.php?view=assessments&section=manage' );
            } elseif (isset($_POST['confirm_delete_assessment'])){
                
                $Assessment->delete();
                $MSGS['success'] = get_string('assessment:deleted', 'block_gradetracker');      
                
                // Log variables
                $detail = \GT\Log::GT_LOG_DETAILS_DELETED_ASSESSMENT;
                $attributes = array(\GT\Log::GT_LOG_ATT_ASSID => $Assessment->getID());
                
            }
            
            $VARS['Assessment'] = $Assessment;
            
        }
        
        
        
        if (isset($detail)){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = $_POST;
            $Log->attributes = $attributes;
            $Log->save();
            // ------------ Logging Info
        }
        
        
    }
    
    /**
     * Submit the QoE import
     * @global \GT\type $MSGS
     */
    private function saveConfigDataQOE()
    {
        
        global $MSGS;
        
        
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportQualsOnEntry();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:qoe:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }
        
    }
    
    /**
     * Submit the AVG GCSE import
     * @global \GT\type $MSGS
     */
    private function saveConfigDataAvgGCSE()
    {
        
        global $MSGS;
        
        
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportAvgGCSE();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }
        
    }
    
    /**
     * Submit the TG import
     * @global \GT\type $MSGS
     */
    private function saveConfigDataTargetGrades()
    {
        
        global $MSGS;
                
        // Minimum load - we don't need the criteria
        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->min();
        $GTEXE->STUDENT_LOAD_LEVEL = \GT\Execution::STUD_LOAD_LEVEL_UNIT;
        
        if (isset($_POST['submit_calculate']) && isset($_POST['tg_input']))
        {
            
            $student_counter = [0, 0];
            $output = '';
                
            if (isset($_POST['options'])){
                
                $tg_options = $_POST['options'];
                $tg_added_qualID = $_POST['tg_input'];
                                
                foreach($tg_added_qualID as $qualid){
                    
                    $qual = new \GT\Qualification\UserQualification($qualid);
                    $students_on_qual = $qual->getUsers('student');
                    $student_counter[0] += 1;
                                        
                    foreach($students_on_qual as $student){
                                               
                        $qual->clearStudent();
                        
                        $student_counter[1] += 1;
                        
                        // Calculate target grades
                        if (isset($tg_options['calc_tg'])){
                            
                            $tg = $student->calculateTargetGrade($qualid);
                                                        
                            if ($tg) $tg = $tg->getName();
                            else $tg = '-';
                            
                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedtargetgrade', 'block_gradetracker') . ': ' . $tg . '<br>';
                        }
                        
                        // Calculate weighted target grades
                        if (isset($tg_options['calc_wtg'])){
                            
                            $tg = $student->calculateWeightedTargetGrade($qualid);
                            
                            if ($tg) $tg = $tg->getName();
                            else $tg = '-';
                            
                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedweightedtargetgrade', 'block_gradetracker') . ': ' . $tg . '<br>';
                        }
                        
                        // Calculate avg gcse scores
                        if (isset($tg_options['calc_avg'])){
                            
                            $avg = $student->calculateAverageGCSEScore($qualid);
                            if (!$avg){
                                $avg = '-';
                            }
                            
                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedavggcse', 'block_gradetracker') . ': ' . $avg . '<br>';
                        }
                        
                        // Calculate aspirational grades
                        if (isset($tg_options['calc_asp'])){
                            
                            $asp = $student->calculateAspirationalGrade($qualid);
                            
                            if ($asp) $asp = $asp->getName();
                            else $asp = '-';
                            
                            $output .= $qual->getDisplayName() . ' - ' . $student->getDisplayName() . ' - ' . get_string('calculatedaspgrade', 'block_gradetracker') . ': ' . $asp . '<br>';
                        }
                        
                        // Calculate predicted grades
                        if (isset($tg_options['calc_pred']))
                        {
                                                       
                            // For this we need to load the student into the qual
                            $qual->loadStudent($student);
                            $qual->calculatePredictedAwards();
                            
                            $pred = $qual->getUserPredictedOrFinalAward();
                            $type = $pred[0];
                            if ($pred[1]) $grade = $pred[1]->getName();
                            else $grade = '-';
                            
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
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportTargetGrades();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }        
                
    }
    
    private function saveConfigDataAspirationalGrades()
    {
        
        global $MSGS;
        
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportAspirationalGrades();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }        
        
    }
    
    private function saveConfigDataCetaGrades()
    {
        
        global $MSGS;
        
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportCETAGrades();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }        
        
    }
    
    private function saveConfigDataAssessmentGrades()
    {
        
        global $MSGS;
                
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            
            // Check we chose an assessment
            if (!isset($_POST['assID']) || !$_POST['assID']){
                $MSGS['errors'] = get_string('errors:import:ass:id', 'block_gradetracker');
                return false;
            }
            
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportAssessmentGrades($_POST['assID']);
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
        }  
                
    }
    
    
    private function saveConfigDataWeightingCoefficients()
    {
        
        global $MSGS;
        
        // Check if file was submitted
        if (isset($_FILES['file']) && !$_FILES['file']['error'])
        {
            $import = new \GT\DataImport($_FILES['file']);
            $import->runImportWCoe();
            
            if ($import->getErrors()){
                $MSGS['errors'] = $import->getErrors();
            } else {
                $MSGS['success'] = sprintf( get_string('import:tg:processed', 'block_gradetracker'), $import->errCnt );
                $MSGS['output'] = $import->getOutput();
            }
            
            
        }        
        
    }
    
    /**
     * Transfer data from old GT to new GT
     * @global \GT\type $MSGS
     */
    private function saveConfigDataBlockBCGT()
    {
                     
        global $MSGS, $TPL, $VARS;
        
        $oldGT = new \GT\OldGradeTrackerSystem();
        
        if (isset($_POST['save_mappings']))
        {
            $oldGT->saveStructureMappings();
            $MSGS['success'] = get_string('blockbcdbdata:datamapping:saved', 'block_gradetracker');
            $detail = \GT\Log::GT_LOG_DETAILS_UPDATED_DATA_MAPPINGS;
        }
                
        // Transfer only specifications
        elseif (isset($_POST['submit_transfer_specs']) && !empty($_POST['submit_transfer_specs']))
        {
            $output = $oldGT->handleDataTransfer('specs', $_POST['block_bcgt_quals']);
            $MSGS['output'] = $output;
            
            if (!isset($MSGS['errors'])){
                $detail = \GT\Log::GT_LOG_DETAILS_TRANSFERRED_OLD_SPECS;
            }
            
        }
        
        // Import bespoke qualification specification
        elseif (isset($_POST['submit_import_bespoke'])){
            
            $qualID = (isset($_POST['qual'])) ? $_POST['qual'] : false;
            $file = (isset($_FILES['spec'])) ? $_FILES['spec'] : false;
                        
            $DataImport = new \GT\DataImport($file);
            $DataImport->checkFileOldQualSpec($qualID);

            if ($DataImport->getErrors()){
                $MSGS['bespoke_errors'] = $DataImport->getErrors();
            } else {
                $MSGS['bespoke_output'] = $DataImport->getOutput();
            }
                        
        }
        
        elseif (isset($_POST['confirm_import_bespoke']) && isset($_POST['tmp_file']) && isset($_POST['qualID'])){
            
            $qualID = $_POST['qualID'];
            $tmpFile = $_POST['tmp_file'];
            
            $DataImport = new \GT\DataImport($tmpFile);
            $DataImport->runImportOldQualSpec($qualID);
            
            if ($DataImport->getErrors()){
                $MSGS['bespoke_errors'] = $DataImport->getErrors();
            } else {
                $MSGS['bespoke_output'] = $DataImport->getOutput();
                $detail = \GT\Log::GT_LOG_DETAILS_TRANSFERRED_OLD_SPECS;
            }
            
        }
                
        
        // Transfer user Data
        elseif (isset($_POST['submit_data_next'])){
            
            $newStage = 1;
            $stage = isset($_POST['stage']) ? $_POST['stage'] : false;
                        
            if ($stage){
                
                // Stage 1 - Choose Qualifications
                if ($stage == 1 || $stage == 2 || $stage == 3)
                {
                        
                    // Make sure at least 1 qualification has been selected
                    $quals = array();
                    $qualIDs = isset($_POST['quals']) ? $_POST['quals'] : false;
                    if ($qualIDs)
                    {
                        foreach($qualIDs as $qualID)
                        {
                            $obj = new \GT\Qualification($qualID);
                            if ($obj->isValid() && !$obj->isDeleted())
                            {
                                $quals[$obj->getID()] = $obj;
                            }
                        }
                    }

                    if ($quals){
                        $TPL->set("qualsSelected", $quals);
                        $newStage = 2;
                    }
                                                
                }
                
                // Stage 2 - Choose Students
                if ($stage == 2 || $stage == 3)
                {
                    
                    $students = array();
                    $studentIDs = isset($_POST['students']) ? $_POST['students'] : false;
                    
                    if ($studentIDs && $quals)
                    {
                        foreach($quals as $qual)
                        {
                            $qualStudents = $studentIDs[$qual->getID()];
                            $students[$qual->getID()] = array();
                            if ($qualStudents)
                            {
                                foreach($qualStudents as $studentID)
                                {
                                    $obj = new \GT\User($studentID);
                                    if ($obj->isValid())
                                    {
                                        $students[$qual->getID()][$studentID] = $obj;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Set to stage 3
                    if ($students){
                        $TPL->set("studentsSelected", $students);
                        $newStage = 3;
                    }
                    
                }
                
                
                // Stage 3 - Choose units
                if ($stage == 3)
                {
                 
                    $numCheckBox = 0;
                    
                    $units = array();
                    $unitIDs = isset($_POST['units']) ? $_POST['units'] : false;
                    
                    if ($unitIDs && $quals && $students)
                    {
                        foreach($quals as $qual)
                        {
                            $qualUnits = $unitIDs[$qual->getID()];
                            $units[$qual->getID()] = array();
                            if ($qualUnits)
                            {
                                foreach($qualUnits as $unitID)
                                {
                                    $obj = $qual->getUnit($unitID);
                                    if ($obj && $obj->isValid() && !$obj->isDeleted())
                                    {
                                        $units[$qual->getID()][$unitID] = $obj;
                                        $numCheckBox += count($students[$qual->getID()]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $max = ini_get('max_input_vars');
                    if ($numCheckBox >= $max){
                        print_error( 'errors:max_input_vars', 'block_gradetracker' );
                    }
                    
                    // Set to stage 4
                    if ($units){
                        $TPL->set("unitsSelected", $units);
                        $newStage = 4;
                    }
                    
                }
                
                
            }
                        
            $TPL->set("stage", $newStage);
            $TPL->set("oldGT", $oldGT);
            $VARS['TPL'] = $TPL;
                        
        }
        
        
        // Confirm user data transfer
        elseif (isset($_POST['confirm_data_transfer'])){
            
            $data = (isset($_POST['data'])) ? $_POST['data'] : false;
            $output = $oldGT->handleDataTransfer('data', $data);
            $MSGS['output'] = $output;
            
            if (!isset($MSGS['errors'])){
                $detail = \GT\Log::GT_LOG_DETAILS_TRANSFERRED_OLD_DATA;
            }
            
        }
        
        
        
        if (isset($detail)){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
        
    }
    
    /**
     * Save the configuration from the course settings
     * @global type $DB
     * @global \GT\type $CFG
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @param type $section
     * @param type $page
     * @return boolean
     */
    private function saveConfigCourse($section, $page)
    {
        
        global $DB, $CFG, $MSGS, $VARS, $USER;
        
        $User = new \GT\User($USER->id);
        
        // Delete activity link
        if (isset($_POST['confirm_delete_activity_link']))
        {

            $cmID = optional_param('cmid', false, PARAM_INT);
            $qID = optional_param('qualid', false, PARAM_INT);
            $uID = optional_param('unitid', false, PARAM_INT);
            $part = optional_param('part', null, PARAM_INT);
            
            // Get the course this coursemodule is on
            $Course = \GT\Activity::getCourseFromCourseModule($cmID);
            if (!$Course || !$Course->isValid()){
                print_error('invalidcourse', 'block_gradetracker');
            }
            
            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($Course->id, "STAFF") && !\gt_has_capability('block/gradetracker:edit_all_courses')){
                print_error('invalidaccess', 'block_gradetracker');
            }
                
            $links = \GT\Activity::findLinks($cmID, $part, $qID, $uID);
            if ($links)
            {
                foreach($links as $link)
                {
                    $link->remove();
                }
            }

            $MSGS['success'] = get_string('modlinking:deleted', 'block_gradetracker');
            
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_DELETED_COURSE_ACTIVTY_LINK;
            $Log->addAttribute(\GT\Log::GT_LOG_ATT_COURSEID, $Course->id)
                ->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $qID)
                ->addAttribute(\GT\Log::GT_LOG_ATT_UNITID, $uID);
            $Log->save();
            // ------------ Logging Info

        }
        
        
        
        if ($section != 'search')
        {
            $id = optional_param('id', false, PARAM_INT);
            $course = new \GT\Course($id);
            if (!$course->isValid()){
                print_error('invalidcourse', 'block_gradetracker');
            }
            
            // Check if the user is on this course, otherwise they shouldn't be able to edit anything
            if (!$User->isOnCourse($id, "STAFF") && !\gt_has_capability('block/gradetracker:edit_all_courses')){
                print_error('invalidaccess', 'block_gradetracker');
            }

            if ($section == 'quals'){

                $course->saveFormCourseQuals();

            } elseif ($section == 'userquals'){

                $course->saveFormUserQuals();

            } elseif ($section == 'userunits'){

                $course->saveFormUserUnits();

            } elseif ($section == 'activities'){

                // Check permissions
                if (!$User->hasCapability('block/gradetracker:edit_course_activity_refs')){
                    print_error('invalidaccess', 'block_gradetracker');
                }
                
                $cmID = optional_param('cmid', false, PARAM_INT);
                $qID = optional_param('qualid', false, PARAM_INT);
                $uID = optional_param('unitid', false, PARAM_INT);
                $part = optional_param('part', null, PARAM_INT);
                
                if ($page == 'add')
                {

                    // By course module
                    if ($cmID)
                    {

                        require_once $CFG->dirroot . '/blocks/gradetracker/hook.php';
                        \gt_mod_hook_process($_POST['coursemoduleid'], $course);
                        $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');

                    }
                    elseif ($qID && $uID)
                    {

                        $qualID = $_POST['qualid'];
                        $unitID = $_POST['unitid'];
                        $course = new \GT\Course($_POST['courseID']);
                        $linkedCriteria = (isset($_POST['gt_criteria'])) ? $_POST['gt_criteria'] : false;
                        $criteriaArray = array();

                        // If there are criteria we want to link, process them
                        if ($linkedCriteria)
                        {

                            foreach($linkedCriteria as $courseModID => $criteria)
                            {

                                $criteriaArray[$courseModID] = array();

                                foreach($criteria as $critID => $value)
                                {

                                    if ( (is_numeric($value) && $value > 0) || !is_numeric($value) )
                                    {

                                        $activity = new \GT\Activity();
                                        $activity->setCourseModuleID($courseModID);
                                        $activity->setQualID($qualID);
                                        $activity->setUnitID($unitID);
                                        $activity->setCritID($critID);

                                        // If the value is an int > 0, then it must be a partID
                                        if (is_numeric($value) && $value > 0){
                                            $activity->setPartID($value);
                                        }

                                        $activity->create();
                                        $criteriaArray[$courseModID][] = $critID;

                                    }

                                }

                            }

                        }

                        // Now remove any that are currently linked to this qual unit that were not submitted in the form
                        \GT\Activity::removeNonSubmittedLinksOnUnit($qualID, $unitID, $course->id, $criteriaArray);

                        $MSGS['success'] = get_string('modlinking:saved', 'block_gradetracker');
                        
                        // ------------ Logging Info
                        $Log = new \GT\Log();
                        $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
                        $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_COURSE_ACTIVITY_LINKS;
                        $Log->afterjson = $linkedCriteria;
                        $Log->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $qualID)
                            ->addAttribute(\GT\Log::GT_LOG_ATT_UNITID, $unitID);
                        $Log->save();
                        // ------------ Logging Info

                    }
                
                }
                                
            }
        }
        elseif ($section == 'search')
        {
            $courses = \GT\Course::search( array(
                "name" => $_POST['coursename'],
                "catID" => $_POST['coursecats']
            ) );
            
            $TPL = new \GT\Template();
            $TPL->set("courses", $courses);
            $VARS['TPL'] = $TPL;
        }
    }
    
    private function saveConfigUnits($section, $page)
    {
        
        global $MSGS, $VARS;
                
        $id = optional_param('id', false, PARAM_INT);
        
        if ($section == 'edit') $section = 'new';
        
        // change deleted field in 1 
        if (isset($_POST['delete_unit']) && gt_has_capability('block/gradetracker:delete_restore_units'))
        {
            $unit = new \GT\Unit($id);
            $unit->delete();
            $detail = \GT\Log::GT_LOG_DETAILS_DELETED_UNIT;
            $MSGS['success'] = get_string('unitdeleted', 'block_gradetracker');
        }
        
        // copy unit
        elseif (isset($_POST['copy_unit']))
        {
            $unit = new \GT\Unit($id);
            $unit->copyUnit();
            $detail = \GT\Log::GT_LOG_DETAILS_DUPLICATED_UNIT;
        } 
        
        // New Qualification submission
        elseif ($section == 'new' && !isset($_POST['restoreUnit']))
        {
            
            $TPL = new \GT\Template();
            $unit = new \GT\Unit\GUI($id);
                        
            $valid = ($unit->isValid());
            
            $unit->loadTemplate($TPL);
            $unit->saveFormNewUnit();
            
            if (isset($MSGS['success'])){
                $detail = ($valid) ? \GT\Log::GT_LOG_DETAILS_UPDATED_UNIT : \GT\Log::GT_LOG_DETAILS_CREATED_UNIT;
            }
            
            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $unit;
                        
        }
        elseif ($section == 'search' && isset($_POST['submit_search']))
        {   
            $TPL = new \GT\Template();
            $unit = new \GT\Unit\GUI($id);
            $unit->loadTemplate($TPL);
            
            $results = $unit->submitFormUnitSearch();
            $TPL->set("results", $results);
            
            $deletedresults = $unit->submitFormUnitSearch(true);
            $TPL->set("deletedresults", $deletedresults);
            
            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $unit;
            
        }
        
        // Restore the deleted unit
        elseif ((isset($_POST['restoreUnit_x']) || isset($_POST['restoreUnit'])) && gt_has_capability('block/gradetracker:delete_restore_units'))
        {
            $unit = new \GT\Unit($id);
            $unit->restore();
            $detail = \GT\Log::GT_LOG_DETAILS_RESTORED_UNIT;
            $MSGS['success'] = get_string('unitrestored', 'block_gradetracker');
        }
        
                
        if (!isset($MSGS['errors']) && isset($detail)){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = $_POST;
            $Log->addAttribute(\GT\Log::GT_LOG_ATT_UNITID, $unit->getID());
            $Log->save();
            // ------------ Logging info
        }
        
        
    }
    
    private function saveConfigQuals($section, $page)
    {
        
        global $MSGS, $VARS;
        
        $id = optional_param('id', false, PARAM_INT);
        
        if ($section == 'edit') $section = 'new';
        
        if (isset($_POST['delete_qual']) && gt_has_capability('block/gradetracker:delete_restore_quals'))
        {
            $qual = new \GT\Qualification($id);
            $qual->delete();
            $MSGS['success'] = get_string('qualdeleted', 'block_gradetracker');
            $detail = \GT\Log::GT_LOG_DETAILS_DELETED_QUALIFICATION;
        }
        
        elseif( isset($_POST['copy_qual']) ){
            $qual = new \GT\Qualification($id);
            $qual->copyQual();
            $detail = \GT\Log::GT_LOG_DETAILS_DUPLICATED_QUALIFICATION;
        }
        
        // New Qualification submission
        elseif ($section == 'new' && !isset($_POST['restoreQual']))
        {
                        
            $TPL = new \GT\Template();
            $qual = new \GT\Qualification\GUI($id);
            
            $valid = ($qual->isValid());
            
            $qual->loadTemplate($TPL);
            $qual->saveFormNewQualification();
            
            if (isset($MSGS['success'])){
                $detail = ($valid) ? \GT\Log::GT_LOG_DETAILS_UPDATED_QUALIFICATION :  \GT\Log::GT_LOG_DETAILS_CREATED_QUALIFICATION ;
            }
            
            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $qual;
        }
        
        elseif ($section == 'search' && isset($_POST['submit_search']))
        {
            
            $TPL = new \GT\Template();
            $qual = new \GT\Qualification\GUI($id);
            $qual->loadTemplate($TPL);
            
            $results = $qual->submitFormSearch();
            $TPL->set("results", $results);
            
            $deletedresults = $qual->submitFormSearch(true);
            $TPL->set("deletedresults", $deletedresults);
            
            $VARS['TPL'] = $TPL;
            $VARS['GUI'] = $qual;
            
        }
        
        // Restore deleted qual
        if ((isset($_POST['restoreQual_x']) || isset($_POST['restoreQual'])) && gt_has_capability('block/gradetracker:delete_restore_quals'))
        {
            $qual = new \GT\Qualification($id);
            $qual->restore();
            $detail = \GT\Log::GT_LOG_DETAILS_RESTORED_QUALIFICATION;
            $MSGS['success'] = get_string('qualrestored', 'block_gradetracker');
        }
        
        
        if (!isset($MSGS['errors']) && isset($detail)){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = $detail;
            $Log->afterjson = $_POST;
            $Log->addAttribute(\GT\Log::GT_LOG_ATT_QUALID, $qual->getID());
            $Log->save();
            // ------------ Logging info
        }
        
        
    }
    
    /**
     * Save the config from grading structures
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @param type $page
     */
    private function saveConfigGradingStructures($page)
    {
        
        global $MSGS, $VARS;
        
        // If we are saving an existing one, there will be a hidden input called grading_qual_structure_id
        // which will have the QualStructureID and the "id" field in the URL will be the gradingStructureID
        // Otherwise if it's a new one, the "id" field in the URL will be the QualStructureID
        $hiddenStructureID = optional_param('grading_qual_structure_id', false, PARAM_INT);
        if ($hiddenStructureID){
            $structureID = $hiddenStructureID;
            $gradingStructureID = optional_param('id', false, PARAM_INT);  
        } else {
            $structureID = optional_param('id', false, PARAM_INT);
        }   
        
        $buildID = optional_param('build', false, PARAM_INT);
        $QualStructure = new \GT\QualificationStructure($structureID);
        $QualBuild = new \GT\QualificationBuild($buildID);
        
                
        if ( ($page == 'new_unit' || $page == 'edit_unit') && isset($_POST['submit_unit_grading_structure']))
        {
            
            $type = 'unit';
            
            // Does this qual structure have units enabled?
            if (!$QualStructure->isLevelEnabled("Units")){
                $MSGS['errors'] = sprintf( get_string('unitsfeaturenotenabled', 'block_gradetracker'), $QualStructure->getName() );
                return false;
            }
            
            // If we've locked it down, we can't save anything new
//            if ($QualStructure->getUnitGradingLockedTo()){
//                $MSGS['errors'] = sprintf( get_string('gradingstructurelocked', 'block_gradetracker'), $QualStructure->getName() );
//                return false;
//            }
            
            $UnitGradingStructure = new \GT\UnitAwardStructure();
            $UnitGradingStructure->loadPostData();
            
            if ($UnitGradingStructure->hasNoErrors() && $UnitGradingStructure->save())
            {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $UnitGradingStructure->getErrors();
            }
            
            $VARS['UnitGradingStructure'] = $UnitGradingStructure;
            
        }
        
        elseif ( ($page == 'new_criteria' || $page == 'edit_criteria') && isset($_POST['submit_crit_grading_structure']) )
        {
            
            $type = 'criteria';
            $CriteriaAwardStructure = new \GT\CriteriaAwardStructure();
            $CriteriaAwardStructure->loadPostData();
            
            if ($CriteriaAwardStructure->hasNoErrors() && $CriteriaAwardStructure->save())
            {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $CriteriaAwardStructure->getErrors();
            }
            
            $VARS['CriteriaAwardStructure'] = $CriteriaAwardStructure;
                        
        }
        
        elseif (isset($_POST['delete_unit_grading_structure']))
        {
            
            $type = 'unit';
            $UnitGradingStructure = new \GT\UnitAwardStructure($_POST['grading_structure_id']);
            if ($UnitGradingStructure->isValid())
            {
                
                $UnitGradingStructure->delete();
                $MSGS['success'] = get_string('gradingstructuredeleted', 'block_gradetracker');
                
            }
            
        }
        
        elseif (isset($_POST['delete_crit_grading_structure']))
        {
            
            $type = 'criteria';
            $CriteriaGradingStructure = new \GT\CriteriaAwardStructure($_POST['grading_structure_id']);
            if ($CriteriaGradingStructure->isValid())
            {
                
                $CriteriaGradingStructure->delete();
                $MSGS['success'] = get_string('gradingstructuredeleted', 'block_gradetracker');
                
            }
            
        }
        
        elseif (isset($_POST['enable_unit_grading_structure_x'], $_POST['enable_unit_grading_structure_y']))
        {
                        
            $type = 'unit';
            $UnitGradingStructure = new \GT\UnitAwardStructure($_POST['grading_structure_id']);
            if ($UnitGradingStructure->isValid())
            {
                $UnitGradingStructure->toggleEnabled();
            }
            
        }
        
        elseif (isset($_POST['enable_crit_grading_structure_x'], $_POST['enable_crit_grading_structure_y']))
        {
            
            $type = 'criteria';
            $CriteriaGradingStructure = new \GT\CriteriaAwardStructure($_POST['grading_structure_id']);
            if ($CriteriaGradingStructure->isValid())
            {
                
                $CriteriaGradingStructure->toggleEnabled();
                
            }
            
        }
        
        elseif (isset($_POST['set_grading_structure_assessments_x'], $_POST['set_grading_structure_assessments_y']))
        {
            
            $type = 'criteria';
            $gradingStructureID = $_POST['grading_structure_id'];
            $gradingStructure = new \GT\CriteriaAwardStructure($gradingStructureID);
            
            if (!$gradingStructure->isValid()){
                $MSGS['errors'] = get_string('invalidgradingstructure', 'block_gradetracker');
                return false;
            }
            
            
            // If it is already set to this, unset it
            if ($gradingStructure->isUsedInAssessments()){
                $gradingStructure->setIsUsedForAssessments(0);
            } else {                
                // Then set this one
                $gradingStructure->setIsUsedForAssessments(1);
            }
            
            $gradingStructure->save();
            
            // Enable it if it's not enabled
            if (!$gradingStructure->isEnabled()){
                $gradingStructure->toggleEnabled();
            }
            
        }
        
        elseif (isset($_POST['export_unit_x'], $_POST['export_unit_y']))
        {
            
            $id = $_POST['grading_structure_id'];
            $gradingStructure = $QualStructure->getSingleStructure($id, $QualStructure->getUnitGradingStructures(false));

            if ($QualStructure->isValid() && $gradingStructure)
            {
                $XML = $QualStructure->exportUnitXML($id);
                
                $name = preg_replace("/[^a-z0-9]/i", "", $gradingStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_grading_unit_structure_'.$name.'.xml');
                header('Content-type: text/xml');
                
                echo $XML->asXML();
                exit;
                
            }
            
        }
        
        elseif (isset($_POST['export_criteria_x'], $_POST['export_criteria_y']))
        {
            
            $id = $_POST['grading_structure_id'];
            
            $Object = ($QualBuild && $QualBuild->isValid()) ? $QualBuild : $QualStructure;
            $gradingStructure = $Object->getSingleStructure($id, $Object->getCriteriaGradingStructures(false));

            if ($Object->isValid() && $gradingStructure)
            {
                
                $XML = $QualStructure->exportCriteriaXML($id);
                
                $name = preg_replace("/[^a-z0-9]/i", "", $gradingStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_grading_criteria_structure_'.$name.'.xml');
                header('Content-type: text/xml');
                
                echo $XML->asXML();
                exit;
                
            }
            
        }
        
        elseif (isset($_POST['import_qual_structure_unit']) && !empty($_FILES['file']))
        {
            
            $type = 'unit';
            $result = \GT\QualificationStructure::importUnitXML($_FILES['file']['tmp_name'], $structureID);
            if ($result['result'])
            {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $result['errors'];
            }
            
            $MSGS['import_output'] = $result['output'];
            
        }
        
        
        elseif (isset($_POST['import_qual_structure_criteria']) && !empty($_FILES['file']))
        {
            
            $type = 'criteria';
            
            // Do it by qual build
            if ($QualBuild && $QualBuild->isValid()){
                $result = \GT\QualificationStructure::importCriteriaXML($_FILES['file']['tmp_name'], false, $QualBuild->getID());
            } else {
                // Do it by qual structure
                $result = \GT\QualificationStructure::importCriteriaXML($_FILES['file']['tmp_name'], $structureID);
            }
            
            if ($result['result'])
            {
                $MSGS['success'] = get_string('gradingstructuresaved', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $result['errors'];
            }
            
            $MSGS['import_output'] = $result['output'];
            
        }
        
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = constant('\GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_'.strtoupper($type).'_GRADING_STRUCTURE');
            unset($_POST['submitconfig']);
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
        
    }
    
    /**
     * Save the config from the QUal Build pages
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @param type $page
     */
    private function saveConfigQualBuilds($page)
    {
        
        global $MSGS, $VARS;
        
        // Are we editing a build?
        if ($page == 'new' || $page == 'edit')
        {
            
            $QualBuild = new \GT\QualificationBuild();
            $QualBuild->loadPostData();
            
            if ($QualBuild->hasNoErrors() && $QualBuild->save())
            {
                $MSGS['success'] = get_string('buildsaved', 'block_gradetracker');
                $QualBuild = new \GT\QualificationBuild();
            }
            else
            {
                $MSGS['errors'] = $QualBuild->getErrors();
            }
            
            $VARS['qualBuild'] = $QualBuild;
            
        }
        
        // Save awards for this build
        elseif ($page == 'awards' && isset($_POST['submit_qual_build_awards']))
        {
            
            $id = $_POST['build_id'];
            $build = new \GT\QualificationBuild($id);
            if ($build->isValid())
            {
                
                $build->loadAwardPostData();
                                
                if ($build->hasNoErrors() && $build->save())
                {
                    $MSGS['success'] = get_string('gradessaved', 'block_gradetracker');
                }
                else
                {
                    $MSGS['errors'] = $build->getErrors();
                }
                
                $VARS['qualBuild'] = $build;
                
            }
            
        }
        
        // Save defaults for this build
        elseif ($page == 'defaults' && isset($_POST['submit_qual_build_defaults']))
        {
            
            $id = $_POST['build_id'];
            $build = new \GT\QualificationBuild($id);
            if ($build->isValid())
            {
                
                $build->saveDefaults( ((isset($_POST['custom'])) ? $_POST['custom'] : false), ((isset($_POST['build'])) ? $_POST['build'] : false) );
                $MSGS['success'] = get_string('defaultssaved', 'block_gradetracker');
                
            }
            
        }
        
        // Delete build
        elseif (isset($_POST['delete_build']))
        {
            
            $id = $_POST['build_id'];
            $build = new \GT\QualificationBuild($id);
            if ($build->isValid())
            {
                
                $build->delete();
                $MSGS['success'] = get_string('builddeleted', 'block_gradetracker');
                
            }
            
        }
        
        // Export build
        elseif (isset($_POST['export_build_x'], $_POST['export_build_y']))
        {
            
            $id = $_POST['build_id'];
            $build = new \GT\QualificationBuild($id);
            if ($build->isValid())
            {
                
                $XML = $build->exportXML();
                                
                $name = preg_replace("/[^a-z0-9]/i", "", $build->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_qual_build_'.$name.'.xml');
                header('Content-type: text/xml');
                
                echo $XML->asXML();
                exit;
                
            }
            
        }
        
        // Mass export build
        elseif (isset($_POST['mass_export_build_x'], $_POST['mass_export_build_y']))
        {
            
            // Clear tmp files
            \GT\GradeTracker::gc();
            
            $path = \GT\GradeTracker::dataroot() . "/tmp";
            
            $builds = \GT\QualificationBuild::getAllBuilds();
            foreach ($builds as $build){
                $build = new \GT\QualificationBuild($build->getID());
                if ($build->isValid() && isset($_POST['build_id_' . $build->getID()]))
                {
                    $XML = $build->exportXML();
                }
            }
            
            $zip = new \ZipArchive;

            $tmp_file = tempnam($path, 'gt_');
            $zip->open($tmp_file, \ZipArchive::CREATE);

            # loop through each file
            foreach (glob($path . "/*.xml") as $file){
                $download_file = file_get_contents($file);
                $zip->addFromString(basename($file),$download_file);
            }

            $zip->close();

            header('Content-disposition: attachment; filename=qualbuilds.zip');
            header('Content-type: application/zip');
            readfile($tmp_file);
            
            // Clear newly created tmp files
            \GT\GradeTracker::gc();
            
            exit;
        }
        
        // Import build
        elseif (isset($_POST['import_qual_build']))
        {
            
            $result = \GT\QualificationBuild::importXML($_FILES['file']['tmp_name']);
            if ($result['result'])
            {
                $MSGS['success'] = get_string('qualbuildimported', 'block_gradetracker');
                unset($MSGS['errors']);
            }
            else
            {
                $MSGS['errors'] = $result['errors'];
            }
            
            $MSGS['import_output'] = $result['output'];
            
        }
                
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_BUILD;
            unset($_POST['submitconfig']);
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
        
    }
    
    /**
     * Save qual levels
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @param type $page
     */
    private function saveConfigQualLevels($page)
    {
        
        global $MSGS, $VARS;
        
        // Are we editing a build?
        if ($page == 'new' || $page == 'edit')
        {
            
            $Level = new \GT\Level();
            $Level->loadPostData();
            
            if ($Level->hasNoErrors() && $Level->save())
            {
                $MSGS['success'] = get_string('levelsaved', 'block_gradetracker');
                $Level = new \GT\Level();
            }
            else
            {
                $MSGS['errors'] = $Level->getErrors();
            }
            
            $VARS['Level'] = $Level;
            
        }
        
        elseif (isset($_POST['delete_level']))
        {
            
            $id = $_POST['level_id'];
            $level = new \GT\Level($id);
            if ($level->isValid())
            {
                $level->delete();    
                $MSGS['success'] = get_string('leveldeleted', 'block_gradetracker');
            }
            
        }
        
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_LEVELS;
            unset($_POST['submit']);
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
    }
    
    /**
     * Save sub types
     * @global \GT\type $MSGS
     * @global \GT\type $VARS
     * @param type $page
     */
    private function saveConfigQualSubTypes($page)
    {
        
        global $MSGS, $VARS;
        
        // Are we editing a build?
        if ($page == 'new' || $page == 'edit')
        {
            
            $SubType = new \GT\SubType();
            $SubType->loadPostData();
            
            if ($SubType->hasNoErrors() && $SubType->save())
            {
                $MSGS['success'] = get_string('subtypesaved', 'block_gradetracker');
                $SubType = new \GT\SubType();
            }
            else
            {
                $MSGS['errors'] = $SubType->getErrors();
            }
            
            $VARS['SubType'] = $SubType;
            
        }
        
        elseif (isset($_POST['delete_subtype']))
        {
            
            $id = $_POST['subtype_id'];
            $SubType = new \GT\SubType($id);
            if ($SubType->isValid())
            {
                $SubType->delete();    
                $MSGS['success'] = get_string('subtypedeleted', 'block_gradetracker');
            }
            
        }
        
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_SUBTYPES;
            unset($_POST['submit']);
            $Log->afterjson = $_POST;
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
    private function saveConfigQualStructures($page)
    {
        
        global $MSGS, $VARS;
                
        // Are we editing a structure?
        if ($page == 'edit' || $page == 'new')
        {

            $QualStructure = new \GT\QualificationStructure();
            $QualStructure->loadPostData();
            
            // If no errors, save it
            if ($QualStructure->hasNoErrors() && $QualStructure->save()){

                $MSGS['success'] = get_string('structuresaved', 'block_gradetracker');

            } else {

                $MSGS['errors'] = $QualStructure->getErrors();

            }

            $VARS['qualStructure'] = $QualStructure;

        }

        // Are we enabling/disabling the structure
        elseif (isset($_POST['enable_structure_x'], $_POST['enable_structure_y']))
        {
            
            $structureID = $_POST['structure_id'];
            $QualStructure = new \GT\QualificationStructure($structureID);
            if ($QualStructure->isValid())
            {
                $QualStructure->toggleEnabled();
            }
            
        }
        
        // Are we deleting it the structure?
        elseif (isset($_POST['delete_structure']))
        {
            
            $structureID = $_POST['structure_id'];
            $QualStructure = new \GT\QualificationStructure($structureID);
            if ($QualStructure->isValid())
            {
                
                $QualStructure->delete();
                $MSGS['success'] = $QualStructure->getName() . " : " . get_string('deleted', 'block_gradetracker');
                
            }
            
        }
        
        // Are we duplicating the Structure?
        elseif (isset($_POST['copy_structure_x'], $_POST['copy_structure_y']))
        {
            
            $structureID = $_POST['structure_id'];
            $QualStructure = new \GT\QualificationStructure($structureID);
            if ($QualStructure->isValid())
            {
                $QualStructure->duplicate();
            }
            
        }
        
        // Are we exporting a structure
        elseif (isset($_POST['export_structure_x'], $_POST['export_structure_y']))
        {
            
            $structureID = $_POST['structure_id'];
            $QualStructure = new \GT\QualificationStructure($structureID);
            if ($QualStructure->isValid())
            {
                
                $XML = $QualStructure->exportXML();
                
                $name = preg_replace("/[^a-z0-9]/i", "", $QualStructure->getName());
                $name = str_replace(" ", "_", $name);

                header('Content-disposition: attachment; filename=gt_qual_structure_'.$name.'.xml');
                header('Content-type: text/xml');
                
                echo $XML->asXML();
                exit;
                
            }
            
        }
        
        elseif (isset($_POST['import_qual_structure']) && !empty($_FILES['file']))
        {
            $result = \GT\QualificationStructure::importXML($_FILES['file']['tmp_name']);
            if ($result['result'])
            {
                $MSGS['success'] = get_string('structureimported', 'block_gradetracker');
            }
            else
            {
                $MSGS['errors'] = $result['errors'];
            }
            
            $MSGS['import_output'] = $result['output'];
            
        }
        
        
        if (!isset($MSGS['errors'])){
            // ------------ Logging Info
            $Log = new \GT\Log();
            $Log->context = \GT\Log::GT_LOG_CONTEXT_CONFIG;
            $Log->details = \GT\Log::GT_LOG_DETAILS_UPDATED_STRUCTURE_QUAL_STRUCTURE;
            unset($_POST['submitconfig']);
            $Log->afterjson = $_POST;
            $Log->save();
            // ------------ Logging Info
        }
        
    }
    
    /**
     * Save the plugin settings
     */
    private function saveConfigSettings($section, $page)
    {
        
        global $CFG, $MSGS;
        
        $settings = $_POST;
        
        // Qual Weighting - Constants
        if ($section == 'qual' && $page == 'constants' && isset($settings['submit_constants']))
        {
            
            // Enable/Disable
            \GT\Setting::updateSetting('weighting_constants_enabled', $settings['weighting_constants_enabled']);
            
            
            // The qual build constants
            if (isset($settings['constants'])){
                
                foreach($settings['constants'] as $buildID => $constant){
                    
                    $build = new \GT\QualificationBuild($buildID);
                    if ($build->isValid()){
                        
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
                       
        }
        
        elseif ($section == 'qual' && $page == 'coefficients' && (isset($settings['submitconfig']) || isset($settings['submit_build_coefficients']) || isset($settings['submit_qual_coefficients'])))
        {
            
            // General config
            if (isset($settings['submitconfig']))
            {
                
                // Number of percentiles to use
                \GT\Setting::updateSetting('qual_weighting_percentiles', $settings['qual_weighting_percentiles']);
                
                // Colours
                if(isset($settings['percentile_colours']) && $settings['percentile_colours'])
                {
                    foreach($settings['percentile_colours'] as $percentile => $colour)
                    {
                        \GT\Setting::updateSetting('weighting_percentile_color_' . $percentile, $colour);
                    }
                }
                
                // Percentages
                if (isset($settings['percentile_percents']) && $settings['percentile_percents'])
                {
                    foreach($settings['percentile_percents'] as $percentile => $percent)
                    {
                        \GT\Setting::updateSetting('weighting_percentile_percentage_'.$percentile, $percent);
                    }
                }
                
                // Default
                if (isset($settings['default_percentile'])){
                    \GT\Setting::updateSetting('default_weighting_percentile', $settings['default_percentile']);
                }
                
                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;
                                
            }
                        
            // Qualification Build Coefficients
            else if (isset($settings['submit_build_coefficients']))
            {
                                
                // Build coefficients
                if ($settings['build_coefficient'])
                {
                    foreach($settings['build_coefficient'] as $buildID => $coefficients)
                    {
                        if ($coefficients)
                        {
                            foreach($coefficients as $percentile => $coefficient)
                            {
                                \GT\Setting::updateSetting('build_coefficient_' . $buildID . '_' . $percentile, $coefficient);
                            }
                        }
                    }
                }
                
                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;
                
            }
            
            // Qualification Coefficients
            elseif (isset($settings['submit_qual_coefficients']))
            {
                
                if ($settings['qual_coefficients'])
                {
                    foreach($settings['qual_coefficients'] as $qualID => $coefficients)
                    {
                        
                        $qual = new \GT\Qualification($qualID);
                        
                        if ($qual && $coefficients)
                        {
                            foreach($coefficients as $percentile => $coefficient)
                            {
                                $qual->updateAttribute('coefficient_' . $percentile, $coefficient);
                            }
                        }
                        
                    }
                }
                
                $MSGS['success'] = get_string('settingsupdated', 'block_gradetracker');
                return true;
                
            }
            
        }
        
                                
        elseif (isset($settings['submitconfig']))
        {
           
            // Remove so doesn't get put into lbp_settings
            unset($settings['submitconfig']);
            
            // Checkboxes need int values
            if ($section == 'general'){
                $settings['use_theme_jquery'] = (isset($settings['use_theme_jquery'])) ? '1' : '0';
            }
            
            // Grid settings
            if ($section == 'grid'){
                $settings['enable_grid_logs'] = (isset($settings['enable_grid_logs'])) ? '1' : '0';
                $settings['assessment_grid_show_quals_one_page'] = (isset($settings['assessment_grid_show_quals_one_page'])) ? '1' : '0';
            }
            
            // Assesment settings
            if ($section == 'assessments'){
                
                $settings['use_assessments_comments'] = (isset($settings['use_assessments_comments'])) ? '1' : '0';
                
                // Form fields
                $elementIDs = array();
                                
                if (isset($_POST['custom_form_fields_names']))
                {
                    foreach ($_POST['custom_form_fields_names'] as $key => $name)
                    {

                        $params = new \stdClass();
                        $params->id = (isset($_POST['custom_form_fields_ids'][$key])) ? $_POST['custom_form_fields_ids'][$key] : false;
                        $params->name = $name;
                        $params->form = 'assessment_grid';
                        $params->type = (isset($_POST['custom_form_fields_types'][$key])) ? $_POST['custom_form_fields_types'][$key] : false;
                        $params->options = (isset($_POST['custom_form_fields_options'][$key]) && !empty($_POST['custom_form_fields_options'][$key])) ? $_POST['custom_form_fields_options'][$key] : false;
                        $params->validation = array();
                        $element = \GT\FormElement::create($params);
                        $element->save();
                        $elementIDs[] = $element->getID();

                    }
                }
                
                $settings['assessment_grid_custom_form_elements'] = implode(",", $elementIDs);
                
            }
            
            //$settings['use_custom_templating'] = (isset($settings['use_custom_templating'])) ? '1' : '0';
            
            // Navigation links are in serperate arrays for name and URL
            if ($section == 'grid'){
                
                if (isset($settings['student_grid_nav'])){

                    $studNav = array();

                    foreach($settings['student_grid_nav'] as $i => $array){

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)){

                            if ($sub){

                                foreach($sub as $s){

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)){
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
                if (isset($settings['unit_grid_nav'])){

                    $unitNav = array();

                    foreach($settings['unit_grid_nav'] as $i => $array){

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)){

                            if ($sub){

                                foreach($sub as $s){

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)){
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
                if (isset($settings['class_grid_nav'])){

                    $classNav = array();

                    foreach($settings['class_grid_nav'] as $i => $array){

                        $name = trim($array['name']);
                        $url = trim($array['url']);
                        $sub = isset($array['sub']) ? $array['sub'] : false;
                        $subObj = array();

                        if (strlen($name)){

                            if ($sub){

                                foreach($sub as $s){

                                    $subName = trim($s['name']);
                                    $subUrl = trim($s['url']);

                                    if (strlen($subName)){
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
            
            }
            
            
            // Reporting section
            if ($section == 'reporting'){
                                                
                // Criteria Progress report - weighted criteria scores
                $allStructures = \GT\QualificationStructure::getAllStructures();
                if ($allStructures){
                    foreach($allStructures as $structure){
                        
                        $array = array();
                        
                        if (isset($settings['crit_weight_scores'][$structure->getID()])){
                            
                            $elements = $settings['crit_weight_scores'][$structure->getID()];
                                
                            if (isset($elements['letter'])){
                                foreach($elements['letter'] as $key => $val){
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
                if ($allStructures){
                    foreach($allStructures as $structure){
                        
                        $method = (isset($settings['pass_prog_method'][$structure->getID()]) && $settings['pass_prog_method'][$structure->getID()] != 'disable') ? $settings['pass_prog_method'][$structure->getID()] : null;
                        $structure->updateSetting('reporting_pass_criteria_method', $method);
                        
                        if ($method == 'byletter'){
                            $value = $settings['pass_prog_by_letter'][$structure->getID()];
                        } elseif ($method == 'bygradestructure'){
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
                
                
            }

                        
            // Loop through settings and save them
            foreach( (array)$settings as $setting => $value ){
                if (is_array($value)){
                    $this->updateSetting($setting, implode(",", $value));
                } else {
                    $this->updateSetting($setting, $value);
                }
            }
            
            
            
            
            // Files
            if (isset($_FILES['institution_logo']) && $_FILES['institution_logo']['size'] > 0)
            {
                $mime = \gt_get_file_mime_type($_FILES['institution_logo']['tmp_name']);
                if (\gt_mime_type_is_image($mime))
                {
                    
                    // Save the file
                    \gt_create_data_directory('img');
                    
                    if (\gt_save_file($_FILES['institution_logo']['tmp_name'], 'img', $_FILES['institution_logo']['name']))
                    {
                        
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
    
    
    private function saveConfigTestsAveGCSE($section)
    {
        
        global $VARS;
                
        if (isset($_POST['gt_avggcsescore']) && isset($_POST['qualid']) && $_POST['qualid'] > 0)
        {
            
            $avggcsescore = $_POST['gt_avggcsescore'];
            $qual_id = $_POST['qualid'];
            $qual = new \GT\Qualification($qual_id);
            $qual_build = $qual->getBuild();
            $award = $qual_build->getAwardByAvgGCSEScore($avggcsescore);
            $awards = $qual_build->getAwards('desc');
               
            $TPL = new \GT\Template();
            $TPL->set("single_award", $award);
            $TPL->set("awards", $awards);
            $TPL->set("avggcsescore", $avggcsescore);
            $TPL->set("qualification", $qual);
            $VARS['TPL'] = $TPL;
        }
        
        
    }
    
    
    private function saveConfigReportingLogs(){
        
        global $TPL, $VARS;
        
        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->min();
        $GTEXE->UNIT_NO_SORT = false;
                
        if (isset($_POST['search']))
        {
            
            $params = array();
            $params['details'] = (isset($_POST['log_details']) && $_POST['log_details'] != '') ? $_POST['log_details'] : false;
            $params['user'] = (isset($_POST['log_user']) && trim($_POST['log_user']) != '') ? trim($_POST['log_user']) : false;
            $params['date_from'] = (isset($_POST['log_date_from']) && trim($_POST['log_date_from']) != '') ? trim($_POST['log_date_from']) : false;
            $params['date_to'] = (isset($_POST['log_date_to']) && trim($_POST['log_date_to']) != '') ? trim($_POST['log_date_to']) : false;
            
            // Attributes
            foreach($_POST['log_attribute'] as $key => $val){
                if (trim($val) != ''){
                    $params['atts'][$key] = trim($val);
                }
            }
                        
            $results = \GT\Log::search($params);
            $TPL->set("results", $results);
            $TPL->set("search", $params);
            
            // If we searched for a qual, get the list of units & assessments on the qual to populate the dropdowns
            if (isset($params['atts']['QUALID'])){
                
                $qual = new \GT\Qualification($params['atts']['QUALID']);
                $TPL->set("useUnits", $qual->getUnits());
                $TPL->set("useAss", $qual->getAssessments());
                       
            }
            
            if (isset($params['atts']['UNITID'])){
                
                $unit = new \GT\Unit($params['atts']['UNITID']);
                $TPL->set("useCriteria", $unit->sortCriteria(false, true));
                
            }
            
            $VARS['TPL'] = $TPL;
            
        }
        
        
    }
    
    
    /**
     * Get the URL of one of the icons from the pix/icons directory
     * @global \GT\type $CFG
     * @param type $icon
     * @return type
     */
    public function icon($icon){
        
        global $CFG;
        
        $file = $CFG->dirroot . '/blocks/gradetracker/pix/icons/'.$icon.'.png';
        if (file_exists($file)){
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
    public function loadJavascript( $external = false )
    {
        
        global $CFG, $PAGE;
        
        $output = "";
                
        
        $scripts = array(
            '/blocks/gradetracker/js/lib/gvs/gridviewscroll.min.js',
            '/blocks/gradetracker/js/lib/jquery-taphold/taphold.js',
            '/blocks/gradetracker/js/lib/jquery-slimmenu/jquery.slimmenu.min.js',
            '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery-bc-popup.js',
//            '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery.easing.min.js', # Shouldn't need this, as jQuery UI included
            '/blocks/gradetracker/js/lib/jquery-bc-notify/jquery-bc-notify.js',
            '/blocks/gradetracker/js/lib/tablesorter/jquery.tablesorter.js',
            '/blocks/gradetracker/js/lib/misc/fw.js',
            '/blocks/gradetracker/js/lib/misc/spk.js',
            '/blocks/gradetracker/js/scripts.js'
        );
        
        
        
        // jQuery
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
               
        
        // If loaded from external, we want to echo out these with script tags, otherwise use the Moodle requires->js
        foreach($scripts as $script)
        {
            if ($external)
            {
                $output .= "<script src='{$script}' type='text/javascript'></script>";
            }
            else
            {
                $PAGE->requires->js( $script );
            }
        }
        
                
        return $output;        
        
    }
    
    /**
     * Load any extra CSS scripts required
     */
    public function loadCSS( $external = false, $from = false )
    {
        
        global $PAGE;
        
        $output = "";
        
        $styles = array(
            new \moodle_url('http://fonts.googleapis.com/css?family=Poiret+One'),
//            '/blocks/gradetracker/js/jquery/css/ui-lightness/jquery-ui.min.css',
//            '/blocks/gradetracker/js/jquery/css/ui-lightness/theme.css',
            '/blocks/gradetracker/js/jquery/css/base.css',
            '/blocks/gradetracker/js/lib/jquery-slimmenu/slimmenu.css',
            '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery-bc-popup.css',
            '/blocks/gradetracker/js/lib/jquery-bc-notify/jquery-bc-notify.css',
            '/blocks/gradetracker/css.php'
        );
        
        // Outside of main moodle, it won't have loaded the main style sheet
        if ($from == 'portal'){
            $styles[] = '/blocks/gradetracker/styles.css';
        }
        
        foreach($styles as $style)
        {
            if ($external)
            {
                $output .= "<link rel='stylesheet' href='{$style}' />";
            }
            else
            {
                $PAGE->requires->css( $style );
            }
        }
        
        return $output;

    }
   
    
    
    /**
     * Return the dataroot directory
     * @global \GT\type $CFG
     * @return type
     */
    public static function dataroot(){
     
        global $CFG;
        return $CFG->dataroot . '/gradetracker';
        
    }
    
    /**
     * Clear tmp files
     * @param type $dir
     */
    public static function gc($dir = false, $cnt = 0){
        
        $tmp = self::dataroot() . '/tmp';
        $path = ($dir) ? $dir : $tmp;
        
        foreach( glob($path . '/*') as $file )
        {
            if (is_dir($file)){
                self::gc($file, $cnt);
            } elseif ($file != "." && $file != "..") {
                unlink($file);
                $cnt++;
            }
        }
        
        if ($path != $tmp){
            rmdir($path);
        }
        
        return $cnt;
        
    }
    
    
}

