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
 * This class handles all the qualification interfaces, such as creation forms, editing forms, adding/removing
 * forms, etc...
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker\Qualification;

defined('MOODLE_INTERNAL') or die();

require_once('Qualification.class.php');

class GUI extends \block_gradetracker\Qualification {

    private $tpl;

    public function loadTemplate(&$TPL) {
        $this->tpl = $TPL;
    }

    /**
     * Save the new qualification/edit qualification form
     * @global type $MSGS
     */
    public function saveFormNewQualification() {

        global $MSGS;

        $submission = array(
            'save_qualification' => optional_param('save_qualification', false, PARAM_TEXT),
        );

        $settings = array(
            'qual_id' => optional_param('qual_id', false, PARAM_INT),
            'qual_type' => optional_param('qual_type', false, PARAM_INT),
            'qual_build' => optional_param('qual_build', false, PARAM_INT),
            'qual_name' => optional_param('qual_name', false, PARAM_TEXT),
            'custom_elements' => df_optional_param_array_recursive('custom_elements', false, PARAM_TEXT),
            'qual_units' => df_optional_param_array_recursive('qual_units', false, PARAM_INT),
            'qual_courses' => df_optional_param_array_recursive('qual_courses', false, PARAM_INT),
        );

        // Take the data from the form and load it into the qualification object
        $qualID = ($settings['qual_id']) ? $settings['qual_id'] : false;
        $structureID = ($settings['qual_type']) ? $settings['qual_type'] : false;
        $buildID = ($settings['qual_build']) ? $settings['qual_build'] : false;
        $name = ($settings['qual_name']) ? $settings['qual_name'] : '';
        $customElements = ($settings['custom_elements']) ? $settings['custom_elements'] : false;
        $unitIDs = ($settings['qual_units']) ? $settings['qual_units'] : false;
        $courseIDs = ($settings['qual_courses']) ? $settings['qual_courses'] : false;

        $this->setStructureID($structureID);
        $this->setID($qualID);
        $this->setBuildID($buildID);
        $this->setName($name);
        $this->setUnitsByID($unitIDs);
        $this->setCoursesByID($courseIDs);
        $this->loadCustomFormElements();
        $this->setCustomElementValues($customElements);

        // Save the qualification
        if ($submission['save_qualification']) {

            if ($this->hasNoErrors() && $this->save()) {
                $MSGS['success'] = get_string('qualsaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $this->getErrors();
            }

        }

    }

    /**
     * Set various variables required in the new/edit qualification form
     * @return string
     */
    public function displayFormNewQualification() {

        global $CFG;

        // Load the builds based on the structure selected
        $structureID = $this->getStructureID();
        $Structure = new \block_gradetracker\QualificationStructure($structureID);
        if ($Structure->isValid()) {
            $builds = \block_gradetracker\QualificationBuild::getAllBuilds($structureID);
            $this->tpl->set("builds", $builds);
        } else {
            $Structure = false;
        }

        $this->tpl->set("Structure", $Structure);
        $this->tpl->set("structures", \block_gradetracker\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \block_gradetracker\Level::getAllLevels());
        $this->tpl->set("allSubTypes", \block_gradetracker\SubType::getAllSubTypes());
        $this->tpl->set("allCats", \core_course_category::make_categories_list());

        // Load units and courses
        $this->getUnits();
        $this->getCourses();

        // Sort the units by level for the select menus
        $Sorter = new \block_gradetracker\Sorter();
        $Sorter->sortUnitsByLevel($this->units);

    }

    /**
     * Display the search qualifications form
     */
    public function displayFormSearchQualifications() {

        $structureID = (isset($this->searchParams['structureID'])) ? $this->searchParams['structureID'] : false;
        $Structure = new \block_gradetracker\QualificationStructure($structureID);
        if ($Structure->isValid()) {
            $builds = \block_gradetracker\QualificationBuild::getAllBuilds($structureID);
            $this->tpl->set("builds", $builds);
        } else {
            $Structure = false;
        }

        $this->tpl->set("Structure", $Structure);

        if (isset($this->searchParams)) {
            $this->tpl->set("searchParams", $this->searchParams);
        }
        $this->tpl->set("structures", \block_gradetracker\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \block_gradetracker\Level::getAllLevels());
        $this->tpl->set("allSubTypes", \block_gradetracker\SubType::getAllSubTypes());

    }

    /**
     * Search for qualifications
     * @global \block_gradetracker\Qualification\type $MSGS
     */
    public function submitFormSearch($deleted = false) {

        global $MSGS;

        $settings = array(
            'qual_type' => optional_param('qual_type', false, PARAM_INT),
            'qual_level' => optional_param('qual_level', false, PARAM_INT),
            'qual_sub_type' => optional_param('qual_sub_type', false, PARAM_INT),
            'qual_name' => optional_param('qual_name', false, PARAM_TEXT),
            'qual_custom_elements' => df_optional_param_array_recursive('qual_custom_elements', false, PARAM_TEXT),
        );

        $structureID = $settings['qual_type'];
        $levelID = $settings['qual_level'];
        $subTypeID = $settings['qual_sub_type'];
        $name = $settings['qual_name'];
        $custom = ($settings['qual_custom_elements']) ? array_filter($settings['qual_custom_elements']) : false;

        $this->searchParams = array();
        $this->searchParams['structureID'] = $structureID;
        $this->searchParams['levelID'] = $levelID;
        $this->searchParams['subTypeID'] = $subTypeID;
        $this->searchParams['name'] = $name;
        $this->searchParams['custom'] = $custom;

        if ($deleted == true) {
            $this->searchParams['deleted'] = 1;
        } else {
            $this->searchParams['deleted'] = 0;
        }

        return self::search($this->searchParams);

    }

}
