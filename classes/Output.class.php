<?php
/**
 * Output
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

class Output {

  public function initAMD_structures_grade(){
    $values = \GT\CriteriaAward::getSupportedSpecialVals();
    return \GT\CriteriaAward::getSupportedSpecialVals();
  }

  public function initAMD_units(){

    global $VARS;

    $return = array();

    // Unit object is stored in the global $VARS variable for use elsewhere. Such as here.
    if (isset($VARS['GUI'])){
      $unit = $VARS['GUI'];
    } else {

      $id = optional_param('id', false, PARAM_INT);
      $unit = new \GT\Unit\GUI($id);

    }

    // If the unit has been loaded
    if ($unit){

      $structureID = $unit->getStructureID();
      $Structure = new \GT\QualificationStructure($structureID);

      $gradingStructures = $Structure->getCriteriaGradingStructures(true);

      $return['maxNumericPoints'] = \GT\Criteria\NumericCriterion::getMaxPoints();
      $return['supportedTypes'] = \GT\Criterion::getSupportedTypes();
      $return['gradingTypes'] = \GT\CriteriaAward::getSupportedGradingTypes();
      $return['gradingStructures'] = array();
      foreach($gradingStructures as $grading){
        $return['gradingStructures'][] = array('id' => $grading->getID(), 'name' => $grading->getName());
      }

    }

    return $return;

  }

  public static function initAMD($view, $section = null){

    $output = new Output();
    $method = 'initAMD_' . strtolower($view);
    if (!is_null($section)){
      $method .= '_' . strtolower($section);
    }

    if (method_exists($output, $method)){
      return array($output->$method());
    } else {
      return array();
    }

  }



}