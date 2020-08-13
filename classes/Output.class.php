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
 * Output class
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class Output {

    public function initAMD_structures_grade() {
        $values = \block_gradetracker\CriteriaAward::getSupportedSpecialVals();
        return \block_gradetracker\CriteriaAward::getSupportedSpecialVals();
    }

    public function initAMD_units() {

        global $VARS;

        $return = array();

        // Unit object is stored in the global $VARS variable for use elsewhere. Such as here.
        if (isset($VARS['GUI'])) {
            $unit = $VARS['GUI'];
        } else {

            $id = optional_param('id', false, PARAM_INT);
            $unit = new \block_gradetracker\Unit\GUI($id);

        }

        // If the unit has been loaded
        if ($unit) {

            $structureID = $unit->getStructureID();
            $Structure = new \block_gradetracker\QualificationStructure($structureID);

            $gradingStructures = $Structure->getCriteriaGradingStructures(true);

            $return['maxNumericPoints'] = \block_gradetracker\Criteria\NumericCriterion::getMaxPoints();
            $return['supportedTypes'] = \block_gradetracker\Criterion::getSupportedTypes();
            $return['gradingTypes'] = \block_gradetracker\CriteriaAward::getSupportedGradingTypes();
            $return['gradingStructures'] = array();
            foreach ($gradingStructures as $grading) {
                $return['gradingStructures'][] = array('id' => $grading->getID(), 'name' => $grading->getName());
            }

        }

        return $return;

    }

    public function initAMD_grid($data) {

        // Unit Grid
        if ($data['type'] === 'unit') {

            // We need to get an array of the criteria and the values, for the Mass Update section
            $data['massUpdate'] = array();

            $GTEXE = \block_gradetracker\Execution::getInstance();
            $GTEXE->min();

            $unit = new Unit($data['id']);
            $criteria = $unit->getHeaderCriteriaNamesFlat();

            foreach ($criteria as $critName) {

                $criterion = $unit->getCriterionByName($critName);
                $critArr = array();

                foreach ($criterion->getPossibleValues() as $award) {
                    $critArr[] = array($award->getName(), $award->getID());
                }

                $data['massUpdate'][$criterion->getID()] = $critArr;

            }

        }

        return $data;

    }

    public static function initAMD($view, $section = null, $data = null) {

        $output = new Output();
        $method = 'initAMD_' . strtolower($view);
        if (!is_null($section)) {
            $method .= '_' . strtolower($section);
        }

        if (method_exists($output, $method)) {
            return array($output->$method($data));
        } else {
            return array();
        }

    }

}