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
 * AJAX script to update grids
 *
 * @package    block_gradetracker
 * @copyright  2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @author     Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/gradetracker/lib.php');
require_once($CFG->dirroot . '/local/df_hub/lib.php');

// Have they timed out?
if ($USER->id <= 0) {
    exit;
}

$PAGE->set_context( context_system::instance() );
require_login();

// Check the session key before doing anything.
// This is a bit awkward, we we don't want the print_error inside confirm_sesskey() to run, as it'll mess up the AJAX response.
// And if the param is missing, e.g. false, null, etc... it'll try required_param again which will lead to the print_error.
// So the default we are going to set is just the string ' ', as that will not be the session key, but isn't empty, so won't trigger a print_error.
$sesskey = optional_param('sesskey', ' ', PARAM_RAW);
if (!confirm_sesskey($sesskey)) {
    exit;
}

$action = optional_param('action', false, PARAM_TEXT);
$params = df_optional_param_array_recursive('params', false, PARAM_TEXT);

$GT = new \block_gradetracker\GradeTracker();
$TPL = new \block_gradetracker\Template();
$User = new \block_gradetracker\User($USER->id);

// Store entire submitted form in a debugging log (if enabled).
\gt_debug("Called update.php: " . print_r(\df_clean_entire_post(), true));

// If action not defined exit.
if (!$action) {
    exit;
}

switch($action) {

    case 'update_student_criterion':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $met = (isset($params['met'])) ? $params['met'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;

        // OPtional range id
        $rangeID = (isset($params['rID']) && $params['rID'] > 0) ? $params['rID'] : false;
        // Optional observation number
        $obNum = (isset($params['obNum']) && $params['obNum'] > 0) ? $params['obNum'] : false;

        // If the date was set, convert that to a unix timestamp
        if ($date) {
            $date = strtotime($date);
        }

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || ($met === false && $value === false && $date === false)) {
            \gt_debug("missing params");
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            \gt_debug("Either qualification or student is invalid");
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            \gt_debug("Could not find unit ID ({$unitID}) on qualification ({$Qualification->getID()} - {$Qualification->getName()})");
            exit;
        }

        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion) {
            \gt_debug("Could not find criterion on unit");
            exit;
        }

        // We can only do a tickbox or DATE if it has ONE met value
        if ($met !== false || $date !== false) {
            $awards = $criterion->getPossibleValues(true);
            if (count($awards) <> 1) {
                \gt_debug("More or Less than 1 'met' criteria award, so cannot do tickbox");
                exit;
            }

            // If we ticked it
            if ($met || $date) {
                $award = reset($awards);
            } else {
                // If we unticked it
                $award = new \block_gradetracker\CriteriaAward(0);
            }

        } else if ($value !== false) {
            // Using a select menu to send a value id instead
            $award = new \block_gradetracker\CriteriaAward($value);
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            \gt_debug("User has no editing access on this qual unit");
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            \gt_debug("Student is not assigned to this qual unit");
            exit;
        }

        // If we have passed through an Observation Number, this is a Ranged criterion we are storing a
        // value against a Observation with
        if ($obNum > 0) {

            $criterion->setCriterionObservationValue($obNum, $rangeID, $award->getID(), $date);

        } else {

            // Otherwise just a normal criterion update
            $criterion->setUserAward($award);

            if ($date !== false) {
                $criterion->setUserAwardDate($date);
            } else if (!$award->isMet()) {

                // If the value is not MET, reset the award date as that's only for MET values
                $criterion->setUserAwardDate(0);

            }

            $criterion->saveUser();

        }

        // Auto calculations - Don't notify events if the unit award changes
        $unit->autoCalculateAwards( false );

        $result = array(
            'awards' => $criterion->jsonResult,
            'unitawards' => $unit->jsonResult,
            'progress' => $unit->unitCal()
        );

        // Merge in the results from the rules
        if (isset($GLOBALS['rule_response']['awards'])) {
            if (!is_array($result['awards'])) {
                $result['awards'] = array();
            }
            $result['awards'] = $result['awards'] + $GLOBALS['rule_response']['awards'];
        }

        if (isset($GLOBALS['rule_response']['unitawards'])) {
            if (!is_array($result['unitawards'])) {
                $result['unitawards'] = array();
            }
            $result['unitawards'] = $result['unitawards'] + $GLOBALS['rule_response']['unitawards'];
        }

        echo json_encode($result);
        exit;

    break;

    case 'update_student_assessment':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assessmentID = (isset($params['assessmentID'])) ? $params['assessmentID'] : false;
        $type = (isset($params['type'])) ? $params['type'] : false;
        $value = (isset($params['value']) && ctype_digit($params['value'])) ? $params['value'] : null;
        $gradingMethod = (isset($params['gradingMethod'])) ? $params['gradingMethod'] : false;

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assessmentID || !in_array($type, array('grade', 'ceta'))) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Do we have the permissions to edit this qual?
        if (!$User->canEditQual($qualID)) {
            exit;
        }

        // Make sure the Assessment is valid
        $Assessment = new \block_gradetracker\Assessment($assessmentID);
        if (!$Assessment->isValid()) {
            exit;
        }

        $award = false;

        // If we're using a grading structure, check the award is valid
        if ($gradingMethod != 'numeric') {
            if ($type == 'grade') {
                $award = new \block_gradetracker\CriteriaAward($value);
                // If we specified an actual value, then check if that value is valid
                // Otherwise we passed through an invalid value in the first place, so must be setting to null
                if ($value > 0 && !$award->isValid()) {
                    exit;
                }
            } else if ($type == 'ceta') {
                $award = new \block_gradetracker\QualificationAward($value);
                // Same as above
                if ($value > 0 && !$award->isValid()) {
                    exit;
                }
            }
        } else if ($gradingMethod == 'numeric') {

            // Check is within min and max of assessment
            $min = $Assessment->getSetting('numeric_grading_min');
            $max = $Assessment->getSetting('numeric_grading_max');

            if (!is_numeric($value) || $value < $min || $value > $max) {
                exit;
            }

        }

        // Do the assessment stuff
        $Assessment->setQualification( $Qualification );
        $Assessment->loadStudent( $studentID );

        if ($type == 'grade') {
            if ($gradingMethod == 'numeric') {
                $Assessment->setUserScore($value);
            } else {
                $Assessment->setUserGrade($award);
            }
        } else if ($type == 'ceta') {
            $Assessment->setUserCeta($award);
        }

        $Assessment->saveUser();

        $result = array();

        echo json_encode($result);
        exit;

    break;

    // Update the custom field of an assessment
    case 'update_student_assessment_custom_field':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assessmentID = (isset($params['assessmentID'])) ? $params['assessmentID'] : false;
        $fieldID = (isset($params['fieldID'])) ? $params['fieldID'] : false;
        $value = (isset($params['value'])) ? trim($params['value']) : null;

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assessmentID || !$fieldID) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Do we have the permissions to edit this qual?
        if (!$User->canEditQual($qualID)) {
            exit;
        }

        // Make sure the Assessment is valid
        $Assessment = new \block_gradetracker\Assessment($assessmentID);
        if (!$Assessment->isValid()) {
            exit;
        }

        $Field = new \block_gradetracker\FormElement($fieldID);
        if (!$Field->isValid()) {
            exit;
        }

        // Do the assessment stuff
        $Assessment->setQualification( $Qualification );
        $Assessment->loadStudent( $studentID );

        $Assessment->setUserCustomFieldValue($fieldID, $value);

        // Save user to update last updated time
        $Assessment->saveUser();

        $result = array();
        echo json_encode($result);
        exit;

    break;

    case 'update_student_unit':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || $value === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            exit;
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            exit;
        }

        $award = new \block_gradetracker\UnitAward($value);
        $unit->setUserAward($award);
        $unit->saveUser();

        // Reload the student's criteria to get any updated values
        $unit->loadStudent( $unit->getStudent() );

        $result = array(
            'awards' => array(),
            'unitawards' => array(),
            'progress' => $unit->unitCal()
        );

        // Merge in the results from the rules
        if (isset($GLOBALS['rule_response']['awards'])) {
            if (!is_array($result['awards'])) {
                $result['awards'] = array();
            }
            $result['awards'] = $result['awards'] + $GLOBALS['rule_response']['awards'];
        }

        if (isset($GLOBALS['rule_response']['unitawards'])) {
            if (!is_array($result['unitawards'])) {
                $result['unitawards'] = array();
            }
            $result['unitawards'] = $result['unitawards'] + $GLOBALS['rule_response']['unitawards'];
        }

        echo json_encode($result);
        exit;

    break;

    // Just the award date, not the value
    case 'update_student_criterion_award_date':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;

        // If the date was set, convert that to a unix timestamp
        if ($date) {
            $date = strtotime($date);
        }

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $date === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            exit;
        }

        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion) {
            exit;
        }


        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            exit;
        }

        $criterion->setUserAwardDate($date);
        $criterion->saveUser();

        $result = array();

        echo json_encode($result);
        exit;

    break;

    // Update the award date of an observation number on a Range
    case 'update_student_range_observation_award_date':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $rangeID = (isset($params['rangeID'])) ? $params['rangeID'] : false;
        $obNum = (isset($params['obNum'])) ? $params['obNum'] : false;
        $date = (isset($params['date'])) ? $params['date'] : false;

        // If the date was set, convert that to a unix timestamp
        if ($date) {
            $date = strtotime($date);
        }

        // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$rangeID || !$obNum || $date === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            exit;
        }

        // Make sure the criterion is valid
        $range = $unit->getCriterion($rangeID);
        if (!$range) {
            exit;
        }


        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            exit;
        }

        // Uses a setting
        $range->setUserObservationAwardDate($obNum, $date);

        $result = array();

        echo json_encode($result);
        exit;

    break;


    case 'update_student_criterion_comments':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;

         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $value === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            exit;
        }

        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion) {
            exit;
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            exit;
        }

        $criterion->setUserComments($value);
        $criterion->saveUser();

        $result = array();

        echo json_encode($result);
        exit;

    break;

    // This one is for when we have a popup for the Criterion because we have levels of sub crit
    case 'update_student_sub_criterion_comments':

        if (isset($params)) {

            foreach ($params as $param) {

                $studentID = (isset($param['studentID'])) ? $param['studentID'] : false;
                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $value = (isset($param['value'])) ? $param['value'] : false;

                 // If any of these are not set, stop
                if (!$studentID || !$qualID || !$unitID || !$critID || $value === false) {
                    exit;
                }

                // Load the UserQualification object and load the specified user into it
                $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
                $Qualification->loadStudent($studentID);

                // Make sure Qual & Student are valid
                if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit) {
                    exit;
                }

                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);
                if (!$criterion) {
                    exit;
                }

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($qualID, $unitID)) {
                    exit;
                }

                // Is the student actually on this qualification and this unit?
                if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
                    exit;
                }

                $criterion->setUserComments($value);
                $criterion->saveUser();

            }

        }

        $result = array();

        echo json_encode($result);
        exit;

    break;

    case 'update_student_assessment_comments':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $assID = (isset($params['assID'])) ? $params['assID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;

         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$assID || $value === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the assessment is valid
        $assessment = $Qualification->getAssessment($assID);
        if (!$assessment) {
            exit;
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditQual($qualID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQual($qualID, "STUDENT")) {
            exit;
        }

        $assessment->setUserComments($value);
        $assessment->saveUser();

        $result = array();

        echo json_encode($result);
        exit;

    break;

    case 'update_student_detail_criterion':

        if (isset($params)) {

            foreach ($params as $param) {

                $studentID = (isset($param['studentID'])) ? $param['studentID'] : false;
                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $type = (isset($param['type'])) ? $param['type'] : false;
                $value = (isset($param['value'])) ? $param['value'] : false;

                 // If any of these are not set, stop
                if (!$studentID || !$qualID || !$unitID || !$critID || !$type || $value === false) {
                    exit;
                }

                // Must be valid type
                if ($type != 'custom_value' && $type != 'comments') {
                    exit;
                }

                // Load the UserQualification object and load the specified user into it
                $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
                $Qualification->loadStudent($studentID);

                // Make sure Qual & Student are valid
                if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit) {
                    exit;
                }

                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);
                if (!$criterion) {
                    exit;
                }

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($qualID, $unitID)) {
                    exit;
                }

                // Is the student actually on this qualification and this unit?
                if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
                    exit;
                }

                if ($type == 'custom_value') {
                    $criterion->setUserCustomValue($value);
                } else if ($type == 'comments') {
                    $criterion->setUserComments($value);
                }

                $criterion->saveUser();

            }

        }

        $result = array();

        echo json_encode($result);
        exit;

    break;

    case 'mass_update_student_detail_criterion':

        if (isset($params)) {

            foreach ($params as $param) {

                $qualID = (isset($param['qualID'])) ? $param['qualID'] : false;
                $unitID = (isset($param['unitID'])) ? $param['unitID'] : false;
                $critID = (isset($param['critID'])) ? $param['critID'] : false;
                $groupID = (isset($param['groupID'])) ? $param['groupID'] : false;
                $courseID = (isset($param['courseID'])) ? $param['courseID'] : false;
                $valueID = (isset($param['valueID'])) ? $param['valueID'] : false;

                // If any of these are not set, stop
                if ( $qualID === false || $unitID === false || $critID === false || $courseID === false || $valueID === false) {
                    exit;
                }

                // Load the UserQualification object and load the specified user into it
                $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);

                // Make sure Qual
                if (!$Qualification->isValid()) {
                    exit;
                }

                // Make sure the unit is valid
                $unit = $Qualification->getUnit($unitID);
                if (!$unit) {
                    exit;
                }

                // Make sure the criterion is valid
                $criterion = $unit->getCriterion($critID);
                if (!$criterion) {
                    exit;
                }

                $students = $unit->getUsers("STUDENT", false, $courseID, $groupID);

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($qualID, $unitID)) {
                    exit;
                }

                $award = new \block_gradetracker\CriteriaAward($valueID);

                foreach ($students as $student) {
                    $Qualification->loadStudent($student);

                    $unit = $Qualification->getUnit($unitID);
                    if (!$unit) {
                        exit;
                    }

                    $criterion = $unit->getCriterion($critID);
                    if (!$criterion) {
                        exit;
                    }

                    if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
                        exit;
                    }

                    $criterion->setUserAward($award);
                    $criterion->saveUser();
                }

            }

        }

        $result = array();

        echo json_encode($result);
        exit;

    break;

    case 'update_student_numeric_point':

        $studentID = (isset($params['studentID'])) ? $params['studentID'] : false;
        $qualID = (isset($params['qualID'])) ? $params['qualID'] : false;
        $unitID = (isset($params['unitID'])) ? $params['unitID'] : false;
        $critID = (isset($params['critID'])) ? $params['critID'] : false;
        $rangeID = (isset($params['rangeID']) && $params['rangeID'] > 0) ? $params['rangeID'] : false;
        $value = (isset($params['value'])) ? $params['value'] : false;

         // If any of these are not set, stop
        if (!$studentID || !$qualID || !$unitID || !$critID || $value === false) {
            exit;
        }

        // Load the UserQualification object and load the specified user into it
        $Qualification = new \block_gradetracker\Qualification\UserQualification($qualID);
        $Qualification->loadStudent($studentID);

        // Make sure Qual & Student are valid
        if (!$Qualification->isValid() || !$Qualification->getStudent() || !$Qualification->getStudent()->isValid()) {
            exit;
        }

        // Make sure the unit is valid
        $unit = $Qualification->getUnit($unitID);
        if (!$unit) {
            exit;
        }

        // Make sure the criterion is valid
        $criterion = $unit->getCriterion($critID);
        if (!$criterion) {
            exit;
        }

        // Do we have the permissions to edit this unit?
        if (!$User->canEditUnit($qualID, $unitID)) {
            exit;
        }

        // Is the student actually on this qualification and this unit?
        if (!$Qualification->getStudent()->isOnQualUnit($qualID, $unitID, "STUDENT")) {
            exit;
        }

        if ($rangeID) {
            $range = $unit->getCriterion($rangeID);
            if (!$range) {
                exit;
            }
        }

        $totalPoints = 0;
        $awardID = 0;
        $parentAwardID = 0;

        // If we are on a Range, we store in the user_range table
        if ($rangeID) {

            $parent = $unit->getCriterion( $criterion->getParentID() );

            $criterion->setRangeCriterionValue($range->getID(), $criterion->getID(), $value);
            $totalPoints = $range->getTotalPoints();

            // Calculate range award
            $awardID = $range->autoCalculateAwardFromConversionChart();

            // Calculate award for overall criteria - checking if all Ranges are completed
            $parentAwardID = $parent->autoCalculateAwardFromRanges();

        } else {

            // Otherwise it's just against the 1 criterion, so we  use the Custom Value to store the points
            $criterion->setUserCustomValue($value);
            $criterion->saveUser();

            // Get the new total points
            $parent = $unit->getCriterion( $criterion->getParentID() );
            if ($parent) {
                $totalPoints = $parent->getTotalPoints();
            }

            // Now calculate the overall criteria
            $awardID = $parent->autoCalculateAwardFromConversionChart();

        }

        $result = array(
            'points' => $totalPoints,
            'awardID' => $awardID,
            'awardCriterion' => (($rangeID) ? $rangeID : $parent->getID()),
            'parentAwardID' => $parentAwardID,
            'parentCriterion' => $parent->getID()
        );

        echo json_encode($result);
        exit;

    break;

    case 'update_user_grade':

        $student = new \block_gradetracker\User($params['sID']);
        if (!$student->isValid()) {
            exit;
        }

        // Should be valid type of grade
        if ($params['type'] != 'target' && $params['type'] != 'aspirational' && $params['type'] != 'ceta') {
            exit;
        }

        // Do we have the permission to do this?
        if ($params['type'] == 'target' && !$User->hasUserCapability('block/gradetracker:edit_target_grades', $student->id, $params['qID'])) {
            exit;
        } else if ($params['type'] == 'aspirational' && !$User->hasUserCapability('block/gradetracker:edit_aspirational_grades', $student->id, $params['qID'])) {
            exit;
        } else if ($params['type'] == 'ceta' && !$User->hasUserCapability('block/gradetracker:edit_ceta_grades', $student->id, $params['qID'])) {
            exit;
        }

        $award = new \block_gradetracker\QualificationAward($params['awardID']);

        // Set target grade
        $student->setUserGrade($params['type'], $params['awardID'], array('qualID' => $params['qID']));

        $grade = (!is_null($award->getName())) ? $award->getName() : '';

        $result = array(
            'gradeID' => $award->getID(),
            'grade' => $grade
        );

        echo json_encode($result);
        exit;

    break;

    case 'update_user_attribute':

        if (!isset($params['attribute']) || !isset($params['type']) || !isset($params['value']) || !isset($params['studentID'])) {
            exit;
        }

        $student = new \block_gradetracker\User($params['studentID']);
        if (!$student->isValid()) {
            exit;
        }

        switch($params['type']) {

            // User unit attribute
            case 'unit':

                // need unitID and qualID to make sure they are actually on the unit somewhere
                if (!isset($params['unitID']) || !isset($params['qualID'])) {
                    exit;
                }

                // Do we have the permissions to edit this unit?
                if (!$User->canEditUnit($params['qualID'], $params['unitID'])) {
                    exit;
                }

                // Get qual and load student into it
                $qual = new \block_gradetracker\Qualification\UserQualification($params['qualID']);
                if (!$qual->isValid() || !$qual->loadStudent($params['studentID'])) {
                    exit;
                }

                // Get unit from qual
                $unit = $qual->getUnit($params['unitID']);
                if (!$unit) {
                    exit;
                }

                // Check user is on unit
                if (!$student->isOnQualUnit($qual->getID(), $unit->getID(), "STUDENT")) {
                    exit;
                }

                $value = trim($params['value']);
                if ($value == '') {
                    $value = null;
                }

                // Update the attribute, don't use the qualID though, units are qual independant
                $unit->updateAttribute($params['attribute'], $value, $student->id);

                $result = array();

                echo json_encode($result);
                exit;

            break;
        }

        exit;

    break;

    case 'set_debugging':

        if (!\is_siteadmin()) {
            exit;
        }

        $value = (isset($params['value'])) ? $params['value'] : false;
        if ($value) {
            $_SESSION['gt_debug'] = true;
        } else {
            unset($_SESSION['gt_debug']);
        }

        echo 'OK';

        exit;

    break;

    case 'clear_debugging':

        if (!\is_siteadmin()) {
            exit;
        }

        $file = \block_gradetracker\GradeTracker::dataroot() . "/debug/{$USER->id}.txt";
        if (file_exists($file)) {
            file_put_contents($file, '');
        }

        echo 'OK';

        exit;

    break;

    case 'register_site':

        if (!\is_siteadmin()) {
            exit;
        }

        $Site = new \block_gradetracker\Site();
        $Site->load($params);
        $result = $Site->submit();

        echo $result;
        exit;

    break;

}
