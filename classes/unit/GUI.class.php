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
 * This class handles all the unit interfaces, such as creation forms, editing forms, etc...
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT\Unit;

defined('MOODLE_INTERNAL') or die();

require_once('Unit.class.php');

class GUI extends \GT\Unit {

    private $tpl;

    public function loadTemplate(&$TPL) {
        $this->tpl = $TPL;
    }

    /**
     * Do the relevant template bits required by the new unit form
     */
    public function displayFormNewUnit() {

        // Load the builds based on the structure selected
        $structureID = $this->getStructureID();
        $Structure = new \GT\QualificationStructure($structureID);
        if ($Structure->isValid()) {

            // Levels
            $levels = \GT\Level::getAllStructureLevels($structureID);
            $this->tpl->set("levels", $levels);

            // Unit Grading Structures
            $this->tpl->set("unitGradingStructures", $Structure->getUnitGradingStructures(true));

            // Criteria Grading Structures
            $this->tpl->set("criteriaGradingStructures", $Structure->getCriteriaGradingStructures(true));

            // Criteria letters
            $letters = $Structure->getCriteriaLetters();
            $this->tpl->set("criteriaLetters", $letters);

            // Criteria
            if ($this->getCriteria()) {
                $flatCriteria = $this->loadCriteriaIntoFlatArray();
                $flatCriteria = $this->sortCriteria($flatCriteria, false, true);
                $this->tpl->set("flatCriteria", $flatCriteria);
            }

            // Count up how many of each there currently are
            $lettersCount = array();
            $useCriteria = (isset($flatCriteria)) ? $flatCriteria : false;
            if ($letters) {
                foreach ($letters as $letter) {
                    $lettersCount[$letter] = $this->countCriteriaByLetter($letter, $useCriteria);
                }
            }

            $this->tpl->set("lettersCount", $lettersCount);

        } else {
            $Structure = false;
        }

        $this->tpl->set("Structure", $Structure);
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());

    }

    /**
     *
     * @global type $MSGS
     */
    public function saveFormNewUnit() {

        global $MSGS;

        $submission = array(
            'save_unit' => optional_param('save_unit', false, PARAM_TEXT),
        );

        $settings = array(
            'id' => optional_param('id', false, PARAM_INT),
            'unit_type' => optional_param('unit_type', false, PARAM_INT),
            'unit_level' => optional_param('unit_level', false, PARAM_INT),
            'unit_name' => optional_param('unit_name', false, PARAM_TEXT),
            'unit_number' => optional_param('unit_number', false, PARAM_TEXT),
            'unit_code' => optional_param('unit_code', false, PARAM_TEXT),
            'unit_credits' => optional_param('unit_credits', false, PARAM_TEXT),
            'unit_desc' => optional_param('unit_desc', false, PARAM_TEXT),
            'unit_grading_structure' => optional_param('unit_grading_structure', false, PARAM_INT),
            'unit_criteria' => df_optional_param_array_recursive('unit_criteria', false, PARAM_TEXT),
            'custom_elements' => df_optional_param_array_recursive('custom_elements', false, PARAM_TEXT),
        );

        // Take the data from the form and load it into the qualification object
        $unitID = ($settings['id']) ? $settings['id'] : false;
        $structureID = ($settings['unit_type']) ? $settings['unit_type'] : false;
        $levelID = ($settings['unit_level']) ? $settings['unit_level'] : false;
        $name = ($settings['unit_name']) ? $settings['unit_name'] : '';
        $number = ($settings['unit_number']) ? $settings['unit_number'] : null;
        $code = ($settings['unit_code']) ? $settings['unit_code'] : '';
        $credits = ($settings['unit_credits']) ? $settings['unit_credits'] : null;
        $desc = ($settings['unit_desc']) ? $settings['unit_desc'] : '';
        $grading = ($settings['unit_grading_structure']) ? $settings['unit_grading_structure'] : '';
        $criteria = ($settings['unit_criteria']) ? $settings['unit_criteria'] : false;
        $customElements = ($settings['custom_elements']) ? $settings['custom_elements'] : false;

        // If the ID isn't already set by the valid object
        if (!$this->isValid()) {
            $this->setID($unitID);
            $this->setStructureID($structureID);
        }

        $this->setLevelID($levelID);
        $this->setUnitNumber($number);
        $this->setName($name);
        $this->setCode($code);
        $this->setCredits($credits);
        $this->setDescription($desc);
        $this->setGradingStructureID($grading);
        $this->setCriteriaPostData($criteria);
        $this->loadCustomFormElements();
        $this->setCustomElementValues($customElements);

        // Save the qualification
        if ($submission['save_unit']) {

            if ($this->hasNoErrors() && $this->save()) {
                $MSGS['success'] = get_string('unitsaved', 'block_gradetracker');
            } else {
                $MSGS['errors'] = $this->getErrors();
            }

        }

    }

    public function displayFormSearchUnits() {

        if (isset($this->searchParams)) {
            $this->tpl->set("searchParams", $this->searchParams);
        }
        $this->tpl->set("structures", \GT\QualificationStructure::getAllStructures());
        $this->tpl->set("allLevels", \GT\Level::getAllLevels());

    }

    public function submitFormUnitSearch($deleted = false) {

        global $MSGS;

        $settings = array(
            'unit_type' => optional_param('unit_type', false, PARAM_INT),
            'unit_level' => optional_param('unit_level', false, PARAM_INT),
            'unitNumber' => optional_param('unitNumber', false, PARAM_TEXT),
            'code' => optional_param('code', false, PARAM_TEXT),
            'name' => optional_param('name', false, PARAM_TEXT),
        );

        $structureID = ($settings['unit_type']) ? $settings['unit_type'] : false;
        $levelID = ($settings['unit_level']) ? $settings['unit_level'] : false;
        $unitNumber = ($settings['unitNumber']) ? trim($settings['unitNumber']) : false;
        $code = ($settings['code']) ? trim($settings['code']) : false;
        $name = ($settings['name']) ? trim($settings['name']) : false;

        $this->searchParams = array();
        $this->searchParams['structureID'] = $structureID;
        $this->searchParams['levelID'] = $levelID;
        $this->searchParams['unitNumber'] = $unitNumber;
        $this->searchParams['name'] = $name;
        $this->searchParams['code'] = $code;
        $this->searchParams['deleted'] = ($deleted) ? 1 : 0;

        return self::search($this->searchParams);

    }

}
