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
 * This class handles all the user qualification data and functionality, such as user units, user
 * criteria, target grades, etc...
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker\Qualification;

use local_df_hub\excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

defined('MOODLE_INTERNAL') or die();

require_once('Qualification.class.php');

class UserQualification extends \block_gradetracker\Qualification {

    protected $student = false;
    protected $course = false;

    protected $userAwards = array();

    /**
     * Get the student
     * @return type
     */
    public function getStudent() {
        return $this->student;
    }

    /**
     * Get the ID of the loaded student
     * @return type
     */
    public function getStudentID() {

        $student = $this->getStudent();
        return ($student) ? $student->id : false;

    }

    /**
     * Clear any loaded student
     */
    public function clearStudent() {

        $this->student = false;
        $this->clearStudentAwards();

        if ($this->units) {
            foreach ($this->units as $unit) {
                $unit->clearStudent();
            }
        }

    }

    public function clearStudentAwards() {
        $this->userAwards = array();
    }

    /**
     * Load student into the UserQualification object
     * @param mixed $studentID Can be an ID or a \block_gradetracker\User object
     */
    public function loadStudent($studentID) {

        // Clear first
        $this->clearStudent();

        // Might be a User object we passed in
        if ($studentID instanceof \block_gradetracker\User) {

            if ($studentID->isValid()) {
                $this->student = $studentID;
            }

        } else {

            // Or might be just an ID
            $user = new \block_gradetracker\User($studentID);
            if ($user->isValid()) {
                $this->student = $user;
            }

        }

        // load student into units
        if ($this->student && $this->units) {
            foreach ($this->units as $unit) {
                $unit->loadStudent($this->student);
            }
        }

        return $this->student;

    }

    /**
     * Load a course into the object
     * @param type $course
     */
    public function loadCourse($course) {

        // Might be \block_gradetracker\Course object
        if ($course instanceof \block_gradetracker\Course) {

            if ($course->isValid()) {
                $this->course = $course;
            }

        } else if ($course instanceof \stdClass) {
            $this->course = $course;
        } else if (is_numeric($course)) {

            $course = new \block_gradetracker\Course($course);
            if ($course->isValid()) {
                $this->course = $course;
            }

        }

    }

    public function loadUserAwards() {

        global $DB;

        $records = $DB->get_records("bcgt_user_qual_awards", array("userid" => $this->student->id, "qualid" => $this->id));
        if ($records) {
            foreach ($records as $record) {
                $award = new \block_gradetracker\QualificationAward($record->awardid, $record->type);
                if ($award->isValid()) {
                    $this->userAwards[$record->type] = $award;
                }
            }
        }

    }

    public function getUserAwards() {
        if (!$this->userAwards) {
            $this->loadUserAwards();
        }
        return $this->userAwards;
    }

    public function getUserUnitCredits() {

        if (!$this->student) {
            return false;
        }

        $units = $this->getUnits();

        $totalActiveCredits = 0;

        foreach ($units as $unit) {
            if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {
                $totalActiveCredits += $unit->getCredits();
            }
        }

        return $totalActiveCredits;

    }

    /**
     * Get either the final or predicted avg award, depending on which they have (or neither)
     * @return boolean
     */
    public function getUserPredictedOrFinalAward() {

        $final = $this->getUserAward('final');
        if ($final) {
            return array('final', $final);
        }

        $predicted = $this->getUserAward('average');
        if ($predicted) {
            return array('predicted', $predicted);
        }

        return false;

    }

    public function getUserAward($type) {
        if (!$this->userAwards) {
            $this->loadUserAwards();
        }
        return (array_key_exists($type, $this->userAwards)) ? $this->userAwards[$type] : false;
    }

    public function getUserDefaultAward() {
        $award = $this->getUserAward('final');

        if ($award) {
            return $award;
        } else {
            return $this->getUserAward('average');
        }
    }

    /**
     * Get the name of the award the user has, of this type
     * @param type $type
     * @return type
     */
    public function getUserAwardName($type) {

        $award = $this->getUserAward($type);
        return ($award && $award->isValid()) ? $award->getName() : get_string('na', 'block_gradetracker');

    }

    /**
     * Get the UCAS points of the award the user has, of this type
     * @param type $type
     * @return type
     */
    public function getUserAwardUCAS($type) {

        $award = $this->getUserAward($type);
        return ($award && $award->isValid() && $award->getUCAS() > 0) ? $award->getUCAS() : get_string('na', 'block_gradetracker');

    }


    /**
     * Get the weighting percentile for the current FA grade
     * @param \block_gradetracker\Assessment $assessment The assessment to use
     * @param type $from This will normally be false, but in the summary at the top of the grid it will pass through 'summary'. Eventually this gets the "current" assessment, but only if it is enabled in the summary
     * @return mixed
     */
    public function getUserAssessmentWeightingPercentile(\block_gradetracker\Assessment $assessment = null, $from = false) {

        // Defaults
        $assessmentUCAS = false;
        $targetUCAS = false;

        // Get the user's current assessment grade
        if ($assessment) {
            $assessmentGrade = $assessment->getUserGrade();
        } else {
            $assessment = $this->getUserLatestAssessment($from, true);
            $assessmentGrade = ($assessment) ? $assessment->getUsergrade() : false;
        }

        // Find the Qualification Award with the same name as this Assessment grade and get the UCAS points of it
        if ($assessmentGrade) {
            $assessmentAward = \block_gradetracker\QualificationAward::findAwardByName($this->getBuildID(), $assessmentGrade->getName());
            if ($assessmentAward) {
                $assessmentUCAS = $assessmentAward->getUcas();
            }
        }

        // Get the user's target grade and then get the UCAS points of it (This is the Target, not the Weighted target)
        $targetGrade = $this->getStudent()->getUserGrade('target', array('qualID' => $this->id), false, true);
        if ($targetGrade) {
            $targetUCAS = $targetGrade->getUcas();
        }

        $assName = ($assessment) ? $assessment->getName() : '';
        \gt_debug("Calculating Assessment Weighing Percentile for {$this->name} ({$assName})");
        \gt_debug("Assessment UCAS Points: {$assessmentUCAS}");
        \gt_debug("Target Grade UCAS Points: {$targetUCAS}");

        // If we have both UCAS points from Qualification Award (based on grade) and Target Grade
        if ($assessmentUCAS && $targetUCAS) {

            // Get the multiplier for this build
            $multiplier = $this->getBuild()->getAttribute('build_default_weighting_multiplier');

            $QualWeighting = new \block_gradetracker\QualificationWeighting();
            return $QualWeighting->calculateWeightingPercentile($targetUCAS, $assessmentUCAS, $multiplier, $this->id);

        }

        return false;

    }

    /**
     * Get the weighting percentile for the current FA grade
     */
    public function getUserAssessmentCetaWeightingPercentile(\block_gradetracker\Assessment $assessment = null, $from = false) {

        // Does this qualification use cetas?
        if (!$this->isFeatureEnabledByName('cetagrades')) {
            return false;
        }

        // Defaults
        $assessmentUCAS = false;
        $targetUCAS = false;

        // Get the user's current assessment grade
        if ($assessment) {
            $cetaGrade = $assessment->getUserCeta();
        } else {
            // need to do something with the $from variable, for the summary
            $assessment = $this->getUserLatestAssessment($from, false, true);
            $cetaGrade = ($assessment) ? $assessment->getUserCeta() : false;
        }

        if ($cetaGrade) {
            $assessmentUCAS = $cetaGrade->getUcas();
        }

        // Get the user's target grade
        $targetGrade = $this->getStudent()->getUserGrade('target', array('qualID' => $this->id), false, true);
        if ($targetGrade) {
            $targetUCAS = $targetGrade->getUcas();
        }

        if ($assessmentUCAS && $targetUCAS) {

            // Get the multiplier for this build
            $multiplier = $this->getBuild()->getAttribute('build_default_weighting_multiplier');

            $QualWeighting = new \block_gradetracker\QualificationWeighting();
            return $QualWeighting->calculateWeightingPercentile($targetUCAS, $assessmentUCAS, $multiplier, $this->id);

        }

        return false;

    }

    /**
     * Load the units on this qualification - This time loading them as UserUnit objects
     * @global \block_gradetracker\type $DB
     */
    public function loadUnits() {

        global $DB;

        $GTEXE = \block_gradetracker\Execution::getInstance();

        $this->units = array();
        $qualUnits = $DB->get_records("bcgt_qual_units", array("qualid" => $this->id));
        if ($qualUnits) {
            foreach ($qualUnits as $qualUnit) {
                $unit = new \block_gradetracker\Unit\UserUnit($qualUnit->unitid);
                if ($unit->isValid()) {
                    $unit->setQualID($this->id);
                    $unit->setQualStructureID($this->getStructureID());
                    $unit->loadStudent( $this->student );
                    $this->units[$unit->getID()] = $unit;
                }
            }
        }

        // Default sort
        if (!isset($GTEXE->UNIT_NO_SORT) || !$GTEXE->UNIT_NO_SORT) {
            $this->sortUnits();
        }

    }

    /**
     * Get just one unit
     * @global \block_gradetracker\type $DB
     * @param type $unitID
     * @return \block_gradetracker\Unit|boolean
     */
    public function getOneUnit($unitID) {

        global $DB;

        if (!$this->units) {
            $this->units = array();
        }

        // If it's already been retrieved, return that
        if (array_key_exists($unitID, $this->units)) {
            return $this->units[$unitID];
        }

        // Otherwise get it out of the database and load it into the units array
        $qualUnit = $DB->get_record("bcgt_qual_units", array("qualid" => $this->id, "unitid" => $unitID));
        if ($qualUnit) {

            $unit = new \block_gradetracker\Unit\UserUnit($qualUnit->unitid);
            if ($unit->isValid()) {
                $unit->setQualID($this->id);
                $unit->setQualStructureID($this->getStructureID());
                $unit->loadStudent( $this->student );
                $this->units[$unit->getID()] = $unit;
                return $unit;
            }

        }

        // Otherwise return false
        return false;

    }

    /**
     * Count the number of users on this qualification
     * @param type $role
     * @return type
     */
    public function countUsers($role = "STUDENT") {

        $users = $this->getUsers($role);
        return count($users);

    }

     /**
      * Count up the number of credits on the units on this qualification
      * @return type
      */
    public function countUnitCredits() {

        if (!$this->student) {
            return false;
        }

        $total = 0;

        if (!$this->units) {
            $this->loadUnits();
        }

        // Loop units
        if ($this->units) {
            foreach ($this->units as $unit) {
                if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {
                    $total += $unit->getCredits();
                }
            }
        }

        return $total;

    }


    /**
     * Count number of Units student is on
     * @return type
     */
    public function countUnits() {

        if (!$this->student) {
            return false;
        }

        $numberofunits = 0;

        if (!$this->units) {
            $this->loadUnits();
        }

        if ($this->units) {
            foreach ($this->units as $unit) {
                if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {
                    $numberofunits++;
                }
            }
        }

        return $numberofunits;
    }


    /**
     * Count number of Awarded units Student is on
     * @return type
     */
    public function countUnitAwards() {

        if (!$this->student) {
            return false;
        }

        $numberofunitawards = 0;

        if (!$this->units) {
            $this->loadUnits();
        }

        // Loop units
        if ($this->units) {
            foreach ($this->units as $unit) {
                if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {

                    $award = $unit->getUserAward();
                    if ($award && $award->isValid()) {
                        $numberofunitawards++;
                    }
                }
            }
        }

        return $numberofunitawards;
    }

    /**
     * Count Possible Awards for Student
     * Parameter is name of Award
     * @return type
     */
    public function countNumberOfAwards($awardname) {

        if (!$this->student) {
            return false;
        }

        $numberofawards = 0;

        if (!$this->units) {
            $this->loadUnits();
        }

        // Loop units
        if ($this->units) {
            foreach ($this->units as $unit) {
                if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {
                    $award = $unit->getUserAward();
                    if ($award && $awardname == $award->getName()) {
                        $numberofawards++;
                    }
                }
            }
        }

        return $numberofawards;
    }

    public function countCriteriaAwards($name) {

        $units = $this->getUnits();
        $numberofawards = 0;
        $numberofcriteria = 0;
        if ($units) {
            foreach ($units as $u) {
                $criteria = $u->getCriterionByName($name);
                if ($criteria) {
                    $numberofcriteria++;
                    $award = $criteria->getUserAward();
                    if ($award && $award->isValid() && $award->isMet()) {
                        $numberofawards++;
                    }
                }
            }
        }
        return $numberofawards."/".$numberofcriteria;
    }

    public function countUniqueCriteriaAwards($name) {

        $units = $this->getUnits();
        $uniqueAwards = 0;
        $uniqueCriteria = 0;

        if ($units) {
            foreach ($units as $u) {
                $criteria = $u->getCriteria();
                if ($criteria) {
                    foreach ($criteria as $c) {
                        if (substr($c->getName(), 0 , 1) == $name) {
                            $uniqueCriteria++;
                            $award = $c->getUserAward();
                            if ($award && $award->isValid() && $award->isMet()) {
                                $uniqueAwards++;
                            }
                        }
                    }
                }
            }
        }

        return $uniqueAwards."/".$uniqueCriteria;
    }


    /**
     * Get a specific assessment on this qualfication and load the student in
     * @param type $assessmentID
     * @return boolean
     */
    public function getUserAssessment($assessmentID) {

        if (!$this->student) {
            return false;
        }

        $assessment = $this->getAssessment($assessmentID);
        if (!$assessment) {
            return false;
        }

        $assessment->loadStudent( $this->student->id );
        return $assessment;

    }


    /**
     * Get the current assessment with the student's data loaded in
     * @return boolean
     */
    public function getUserCurrentAssessment($from = false, $cetaEnabledOnly = false) {

        if (!$this->student) {
            return false;
        }

        $assessment = $this->getCurrentAssessment($from, $cetaEnabledOnly);

        if (!$assessment) {
            return false;
        }

        $assessment->loadStudent($this->student->id);
        return $assessment;

    }

    /**
     * Get the grade of the user's current assessment
     * @return type
     */
    public function getUserCurrentAssessmentGrade($from = false) {
        $assessment = $this->getUserCurrentAssessment($from);
        return ($assessment) ? $assessment->getUserGrade() : false;
    }

    /**
     * Get the ceta of the user's current assessment
     * @return type
     */
    public function getUserCurrentAssessmentCeta($from = false, $cetaEnabledOnly = false) {
        $assessment = $this->getUserCurrentAssessment($from, $cetaEnabledOnly);
        return ($assessment) ? $assessment->getUserCeta() : false;
    }

    /**
     * Get the latest assessment on this qualification, loaded with the student's data
     * @global \block_gradetracker\type $DB
     * @param bool $from We might be coming from the summary, and only want to include those enabled for summary
     * @return boolean
     */
    public function getUserLatestAssessment($from = false, $withGrade = false, $withCeta = false) {

        if (!$this->student) {
            return false;
        }

        $assessments = $this->getAssessments();
        if (!$assessments) {
            return false;
        }

        // Reverse the order
        $assessments = array_reverse($assessments, true);

        foreach ($assessments as $assessment) {

            // If we are loading from the summary, we want only assessments who are enabled in the summary
            if ($from == 'summary' && !$assessment->isSummaryEnabled()) {
                continue;
            }

            // If we are checking if it has a grade award
            if ($withGrade) {
                $grade = $assessment->getUserGrade();
                if (!$grade->isValid()) {
                    continue;
                }
            }

            // If we are checking if it has a ceta award
            if ($withCeta) {
                $ceta = $assessment->getUserCeta();
                if (!$ceta->isValid()) {
                    continue;
                }
            }

            return $assessment;

        }

        return false;

    }


    /**
     * Get the latest awarded ceta on this student's qualification
     * @global \block_gradetracker\type $DB
     * @return boolean
     */
    public function getUserLatestAssessmentCetaWithAward() {

        global $DB;

        // Is there a student loaded in?
        if (!$this->student) {
            return false;
        }

        // Does this qualification use cetas?
        if (!$this->isFeatureEnabledByName('cetagrades')) {
            return false;
        }

        // Force refresh - there is a weird problem where the wrong qual is on the assessments, but can't work it out
        $this->loadAssessments();

        $assessments = $this->getAssessments();
        if (!$assessments) {
            return false;
        }

        // Reverse the order of the assessments, so they are in descending date order
        $assessments = \array_reverse($assessments, true);
        foreach ($assessments as $assessment) {
            $ceta = $assessment->getUserCeta();
            if ($ceta->isValid()) {
                return $ceta;
            }
        }

        return false;

    }

     /**
      * Get the latest grade on this student's qualification
      * @global \block_gradetracker\type $DB
      * @return boolean
      */
    public function getUserLatestAssessmentGradeWithAward() {

        if (!$this->student) {
            return false;
        }

        // Force refresh - there is a weird problem where the wrong qual is on the assessments, but can't work it out
        $this->loadAssessments();

        $assessments = $this->getAssessments();
        if (!$assessments) {
            return false;
        }

        // Reverse the order of the assessments, so they are in descending date order
        $assessments = array_reverse($assessments, true);
        foreach ($assessments as $assessment) {
            $grade = $assessment->getUserGrade();
            if ($grade->isValid()) {
                return $grade;
            }
        }

        return false;

    }

    /**
     * Get the points associated with a unit award on this qual
     * @global \block_gradetracker\type $DB
     * @param type $awardID
     * @return type
     */
    private function getUnitAwardPoints($awardID = null) {

        global $DB;

        // If we passed through an awardID we are looking specifically against this award
        if ($awardID) {

            // First check for points against the Qual Build
            $records = $DB->get_record("bcgt_unit_award_points", array("awardid" => $awardID, "qualstructureid" => $this->structureID, "qualbuildid" => $this->getBuild()->getID()));
            if (!$records) {
                // Then against the level
                $records = $DB->get_record("bcgt_unit_award_points", array("awardid" => $awardID, "qualstructureid" => $this->structureID, "levelid" => $this->getBuild()->getLevelID()));
            }

        } else {
            // Otherwise we are looking for any on this Build or Level
            // First check for points against the Qual Build
            $records = $DB->get_records_select("bcgt_unit_award_points", "qualstructureid = ? AND qualbuildid = ? AND points > 0", array($this->structureID, $this->getBuild()->getID()));
            if (!$records) {
                // Then against the level
                $records = $DB->get_records_select("bcgt_unit_award_points", "qualstructureid = ? AND levelid = ? AND points > 0", array($this->structureID, $this->getBuild()->getLevelID()));
            }
        }

        return $records;

    }

    /**
     * Calculate the student's predicted awards for this qual
     * @global \block_gradetracker\type $DB
     * @return boolean
     */
    public function calculatePredictedAwards() {

        global $DB;

        \gt_debug("---------------------------------------------------------------");

        if (!$this->student) {
            \gt_debug("Error: No student");
            return false;
        }

        \gt_debug("Calculating Predicted Awards for " . $this->student->getDisplayName() . " on - " . $this->getDisplayName());

        if (!$this->units) {
            $this->loadUnits();
        }

        if (!$this->units) {
            \gt_debug("Error: No units");
            return false;
        }

        // Make sure we have this feature enabled
        if (!$this->isFeatureEnabledByName('predictedgrades') && !$this->isFeatureEnabledByName('predictedminmaxgrades')) {
            \gt_debug("Error: Predicted Grades feature not enabled");
            return false;
        }

        $min = $this->getSystemSetting('pred_grade_min_units');
        if (!$min || !is_numeric($min) || $min < 1) {
            $min = 1; // Set to default of 1 if problem
        }

        \gt_debug("Minimum Required Unit Awards: {$min}");

        // If this qualification structure is using the complicated BTEC-like system
        $possibleUnitAwardPoints = $this->getUnitAwardPoints(null);
        if ($possibleUnitAwardPoints) {

            \gt_debug("Unit Award Points exist for this structure, so we are using the complex BTEC-like calculations");

            // Set some default variable values
            $pointsPerCredit = $this->getBuild()->getPointsPerCredit();
            if (!is_numeric($pointsPerCredit) || $pointsPerCredit < 1) {
                $pointsPerCredit = 1;
            }

            $defaultCredits = $this->getDefaultCredits();

            usort($possibleUnitAwardPoints, function($a, $b) {
                return ($b->points < $a->points);
            });
            $minUnitAwardPoints = reset($possibleUnitAwardPoints);

            usort($possibleUnitAwardPoints, function($a, $b) {
                return ($b->points > $a->points);
            });
            $maxUnitAwardPoints = reset($possibleUnitAwardPoints);

            $totalUnits = 0;
            $totalUnitsAwarded = 0;
            $totalUnitCredits = 0;
            $totalCreditsAwarded = 0;
            $totalUnitPoints = 0;
            $unitPointsNoMin = 0;
            $unitPointsNoMax = 0;
            $predictedAverageGrade = false;

            \gt_debug("DefaultCredits for this Qual Build: {$defaultCredits}");
            \gt_debug("PointsPerCredit for this Qual Build: {$pointsPerCredit}");
            \gt_debug("MinPoints: {$minUnitAwardPoints->points}");
            \gt_debug("MaxPoints: {$maxUnitAwardPoints->points}");

            // Loop through the units
            if ($this->units) {

                foreach ($this->units as $unit) {

                    if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {

                        $totalUnits++;
                        \gt_debug("");
                        \gt_debug($unit->getDisplayName() . " ({$unit->getCredits()})");
                        $totalUnitCredits += $unit->getCredits();
                        \gt_debug("Incrementing Total Unit Credits: {$totalUnitCredits}");

                        $award = $unit->getUserAward();

                        // Unit has been awarded to student and we haven't reached the default credits yet
                        if ($award && $award->isMet() && $totalCreditsAwarded < $defaultCredits) {

                            \gt_debug("Unit is awarded and we haven't reached the credits limit yet");
                            $totalUnitsAwarded++;
                            $totalCreditsAwarded += $unit->getCredits();
                            \gt_debug("Incrementing total credits awarded: [{$totalCreditsAwarded}]");
                            $unitAwardPoints = $this->getUnitAwardPoints($award->getID());
                            if ($unitAwardPoints) {
                                \gt_debug("Unit Award Points: {$unitAwardPoints->points}");
                                $totalUnitPoints += $unitAwardPoints->points * ( $unit->getCredits() / $pointsPerCredit );
                                \gt_debug("Incrementing total unit award points: (({$totalUnitPoints}))");
                            } else {
                                \gt_debug("WARNING: Could not find Unit Award Points for awardID {$award->getID()} and levelID {$unit->getLevelID()}");
                            }

                        } else if ( ( $totalUnitCredits < $defaultCredits ) || ( $totalUnitCredits == $defaultCredits && $totalCreditsAwarded < $defaultCredits ) ) {

                            \gt_debug("Either unit not awarded, or we've gone over the expected amount of credits, so adjusting the min/max points");
                            // Adjust min & max points accordingly
                            $unitPointsNoMin += $minUnitAwardPoints->points * ( $unit->getCredits() / $pointsPerCredit );
                            $unitPointsNoMax += $maxUnitAwardPoints->points * ( $unit->getCredits() / $pointsPerCredit );
                            \gt_debug("unitPointsNoMin: {$unitPointsNoMin}");
                            \gt_debug("unitPointsNoMax: {$unitPointsNoMax}");

                        }

                    }

                }

            }

            // Calculate average & overall points
            $averagePoints = (float)@($totalUnitPoints / @( $totalCreditsAwarded / $pointsPerCredit ) );
            $overallPoints = (float)@($averagePoints * @( $defaultCredits / $pointsPerCredit ) );

            \gt_debug("********************************");
            \gt_debug("Average Points: {$averagePoints}");
            \gt_debug("Overall Points: {$overallPoints}");
            \gt_debug("Total Credits on Qual: {$totalUnitCredits}");
            \gt_debug("Total Credits Awarded: {$totalCreditsAwarded}");
            \gt_debug("Total Unit Points: {$totalUnitPoints}");

            // If there are less credits than expected, adjust the min & max awards
            $additionalMinPoints = 0;
            $additionalMaxPoints = 0;

            if ($totalUnitCredits < $defaultCredits) {

                $fewerCredits = $defaultCredits - $totalUnitCredits;
                $additionalMinPoints = $minUnitAwardPoints->points * @( $fewerCredits / $pointsPerCredit );
                $additionalMaxPoints = $maxUnitAwardPoints->points * @( $fewerCredits / $pointsPerCredit );

            }

            // Delete existing final award
            if ($totalUnitsAwarded < $totalUnits) {
                \gt_debug("Not all units awarded, so deleting any existing Final award");
                $this->deleteUserAward("final");
            }

            // Predicted Average
            if ($this->isFeatureEnabledByName('predictedgrades') && $totalUnitsAwarded >= $min) {
                $predictedAverageGrade = $this->getAwardByPoints($overallPoints);

                // If they have completed all the units, this becomes the final award instead of average
                if ($predictedAverageGrade) {
                    $this->saveUserAward($predictedAverageGrade, "average");
                    if ($totalCreditsAwarded == $totalUnitCredits) {
                        $this->saveUserAward($predictedAverageGrade, "final");
                    }
                } else {
                    // Reset to blank
                    $blank = new \block_gradetracker\QualificationAward();
                    $this->saveUserAward($blank, "average");
                    \gt_debug("Could not find predicted grade by points ({$overallPoints})");
                }

            } else if ($this->isFeatureEnabledByName('predictedgrades')) {
                $blank = new \block_gradetracker\QualificationAward();
                $this->saveUserAward($blank, "average");
            }

            \gt_debug("Predicted Avg/Final Grade: " . print_r($predictedAverageGrade, true));

            // Predicted Min/Max
            if ($this->isFeatureEnabledByName('predictedminmaxgrades') && $totalUnitsAwarded >= $min) {

                $overallMinPoints = $totalUnitPoints + $unitPointsNoMin + $additionalMinPoints;
                $overallMaxPoints = $totalUnitPoints + $unitPointsNoMax + $additionalMaxPoints;

                \gt_debug("Overall MinPoints: {$overallMinPoints}");
                \gt_debug("Overall MaxPoints: {$overallMaxPoints}");

                $predictedMinGrade = $this->getAwardByPoints($overallMinPoints);
                $predictedMaxGrade = $this->getAwardByPoints($overallMaxPoints);

                \gt_debug("Predicted Min Grade: " . print_r($predictedMinGrade, true));
                \gt_debug("Predicted Max Grade: " . print_r($predictedMaxGrade, true));

                if ($predictedMinGrade) {
                    $this->saveUserAward($predictedMinGrade, "min");
                }

                if ($predictedMaxGrade) {
                    $this->saveUserAward($predictedMaxGrade, "max");
                }

            } else {
                $this->deleteUserAward("min");
                $this->deleteUserAward("max");
            }

        } else {

            // Otherwise we are just using a simple points average like we do with unit awards
            \gt_debug("Simple average calculation");

            $possibleAwards = $this->getBuild()->getAwards();
            $possibleAwardArray = array();

            // Check if at least one of the awards is using point ranges
            foreach ($possibleAwards as $possibleAward) {
                if ($possibleAward->getPointsLower() > 0 || $possibleAward->getPointsUpper() > 0) {
                    $possibleAwardArray[] = $possibleAward;
                }
            }

            if (!$possibleAwardArray) {
                \gt_debug("Error: No awards found with lower or upper scores");
                return false;
            }

            $Sorter = new \block_gradetracker\Sorter();
            $Sorter->sortQualAwards($possibleAwardArray, 'asc');

            $minRank = $this->getBuild()->getMinRank();
            $maxRank = $this->getBuild()->getMaxRank();

            \gt_debug("Min Rank: {$minRank}");
            \gt_debug("Max Rank: {$maxRank}");

            $unitMaxPointArray = array();

            // Check all the units to see if at least one has a grading structure with the same
            // max points, otherwise we cannot do an auto calculation
            foreach ($this->units as $unit) {
                $unitGradingStructure = $unit->getGradingStructure();
                $unitMaxPointArray[$unit->getID()] = $unitGradingStructure->getMaxPoints();
            }

            // If none have a max points of the same as the parent, we cannot proceed
            if (!in_array($maxRank, $unitMaxPointArray)) {
                \gt_debug("Error: No units have a grading structure with a Max Points that matches the Qual Grading Structure. So auto calculations are not possible");
                return false;
            }

            $totalUnits = 0;
            $totalAwardableUnits = 0;
            $totalUnitsAwarded = 0;
            $totalUnitsWithAward = 0; // Same as above, but used for different thing
            $pointsArray = array();
            $unitsNotAwarded = array();

            foreach ($this->units as $unit) {

                if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {

                    \gt_debug($unit->getName());

                    $totalUnits++;

                    $userAward = $unit->getUserAward();
                    if ($userAward && $userAward->isMet()) {
                        $totalUnitsWithAward++;
                    }

                    $unitPossibleAwards = $unit->getGradingStructure()->getAwards();

                    // If this only has one possible award (e.g. Pass) but the parent has multiple
                    // (e.g. PMD) then don't include this in the calculations as it will throw it off
                    if (count($unitPossibleAwards) == 1 && count($possibleAwardArray) > 1) {
                        \gt_debug("Skip: Unit only has one possible award, so excluding from calculations");
                        continue;
                    }

                    // If this doesn't have any awards with a points score above 0, skip it as well
                    if ($unit->getGradingStructure()->getMaxPoints() == 0) {
                        \gt_debug("Skip: Unit has no awards with a points score above 0, so excluding from calculations");
                        continue;
                    }

                    $totalAwardableUnits++;

                    if ($userAward && $userAward->isMet()) {

                        $totalUnitsAwarded++;
                        $points = $userAward->getPoints();

                        \gt_debug("Unit has award, with {$points} points");

                        // If the max points of this is different to that of the parent, adjust it up or down
                        // to ensure calculation is accurate
                        $unitMaxPoints = $unitMaxPointArray[$unit->getID()];
                        if ($unitMaxPoints <> $maxRank) {

                            \gt_debug("Award structure point range differs from qualification, so adjusting the points either up or down to compensate");
                            // Get the difference between the max and min of the parent's structure
                            $diff = $maxRank - $minRank;
                            $steps = count($unitPossibleAwards) - 1;
                            $fraction = $diff / $steps;

                            // Are we adjusting from a larger scale to a smaller scale, or the other way?
                            if ( count($unitPossibleAwards) > count($possibleAwardArray) ) {
                                $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'down');
                            } else if ( count($unitPossibleAwards) < count($possibleAwardArray) ) {
                                $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'up');
                            } else {
                                $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray);
                            }

                            $points = $adjusted[$points];
                            \gt_debug("Adjusted award points to: {$points}");

                        }

                        $pointsArray[$unit->getID()] = $points;

                    } else {

                        $unitsNotAwarded[] = $unit;

                    }

                }

            }

            \gt_debug("Total calculable units awarded: {$totalUnitsAwarded}");
            \gt_debug("Unit award points array: " . print_r($pointsArray, true));

            // Average award
            if ($totalUnitsAwarded == 0) {
                $averagePoints = 0;
            } else {
                $averagePoints = round( array_sum($pointsArray) / $totalUnitsAwarded, 2);
            }

            \gt_debug("Average Points: {$averagePoints}");
            $averageAward = $this->getAwardByPoints($averagePoints);
            \gt_debug("Average Award: " . print_r($averageAward, true));

            // Compare the total units which have been awarded against the total which could be (are calculable)
            if ($totalUnitsAwarded < $totalAwardableUnits) {

                \gt_debug("Not all units have been awarded yet, so calculating the min/max awards");
                $adjustedMinPointsTotal = 0;
                $adjustedMaxPointsTotal = 0;

                foreach ($unitsNotAwarded as $unitNotAwarded) {

                    // THe average award we don't need to adjust as it will always come back to this scale
                    // So we just assume this score for the rest of the units

                    \gt_debug($unitNotAwarded->getName());

                    $minPointsUnitNotAwarded = $unitNotAwarded->getGradingStructure()->getMinPoints();
                    $maxPointsUnitNotAwarded = $unitNotAwarded->getGradingStructure()->getMaxPoints();

                    $unitNotAwardedPossibleAwards = $unitNotAwarded->getGradingStructure()->getAwards();

                    $newMin = $minPointsUnitNotAwarded;
                    $newMax = $maxPointsUnitNotAwarded;

                    // If this only has one possible award (e.g. Pass) but the parent has multiple
                    // (e.g. PMD) then don't include this in the calculations as it will throw it off
                    if (count($unitNotAwardedPossibleAwards) == 1 && count($possibleAwardArray) > 1) {
                        \gt_debug("Skip: Unit only has 1 possible award");
                        continue;
                    }

                    // If this doesn't have any awards with a points score above 0, skip it as well
                    if ($maxPointsUnitNotAwarded == 0) {
                        \gt_debug("Skip: Unit has no awards with points above 0");
                        continue;
                    }

                    // Convert these points
                    if ($maxPointsUnitNotAwarded <> $maxRank) {

                        // Get the difference between the max and min of the parent's structure
                        \gt_debug("Award structures differ, so adjusting points up/down to compensate");
                        $diff = $maxRank - $minRank;
                        $steps = count($unitNotAwardedPossibleAwards) - 1;
                        $fraction = $diff / $steps;

                        // Are we adjusting from a larger scale to a smaller scale, or the other way?
                        if ( count($unitNotAwardedPossibleAwards) > count($possibleAwardArray) ) {
                            $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'down');
                        } else if ( count($unitNotAwardedPossibleAwards) < count($possibleAwardArray) ) {
                            $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray, 'up');
                        } else {
                            $adjusted = $unit->getGradingStructure()->adjustPointsByFraction($fraction, $possibleAwardArray);
                        }

                        $newMin = $adjusted[$minPointsUnitNotAwarded];
                        $newMax = $adjusted[$maxPointsUnitNotAwarded];

                        \gt_debug("Adjusted minPoints: {$newMin}, maxPoints: {$newMax}");

                    }

                    $adjustedMinPointsTotal += $newMin;
                    $adjustedMaxPointsTotal += $newMax;

                }

                $minPoints = round( ( array_sum($pointsArray) + $adjustedMinPointsTotal ) / $totalAwardableUnits, 2);
                $maxPoints = round( ( array_sum($pointsArray) + $adjustedMaxPointsTotal ) / $totalAwardableUnits, 2);

                \gt_debug("Min Points: {$minPoints}, Max Points: {$maxPoints}");

                $minAward = $this->getAwardByPoints($minPoints);
                $maxAward = $this->getAwardByPoints($maxPoints);

            } else {
                $minAward = $averageAward;
                $maxAward = $averageAward;
            }

            \gt_debug("Min Award: " . print_r($minAward, true));
            \gt_debug("Max Award: " . print_r($maxAward, true));

            // Delete existing final award
            if ($totalUnitsWithAward < $totalUnits) {
                \gt_debug("Not all units awarded, so deleting any existing Final award");
                $this->deleteUserAward("final");
            }

            // Predicted Average/Final
            if ($this->isFeatureEnabledByName('predictedgrades') && $totalUnitsWithAward >= $min && $averageAward) {

                \gt_debug("Saving average/final award");

                // If they have completed all the units, this becomes the final award instead of average
                $this->saveUserAward($averageAward, "average");
                if ($totalUnitsWithAward == $totalUnits) {
                    $this->saveUserAward($averageAward, "final");
                }

            } else if ($this->isFeatureEnabledByName('predictedgrades')) {
                \gt_debug("Total units awarded ({$totalUnitsWithAward}) does not meet the minimum no. required ({$min}). So resetting predicted awards.");
                $blank = new \gt\QualificationAward();

                $this->saveUserAward($blank, "average");
                if ($totalUnitsWithAward == $totalUnits) {
                    $this->saveUserAward($blank, "final");
                }
            }

            // Predicted Min & Max
            if ($this->isFeatureEnabledByName('predictedminmaxgrades') && $totalUnitsWithAward >= $min && $minAward && $maxAward) {
                \gt_debug("Saving min/max awards");
                $this->saveUserAward($minAward, "min");
                $this->saveUserAward($maxAward, "max");
            } else if ($this->isFeatureEnabledByName('predictedminmaxgrades')) {
                \gt_debug("Deleting min/max awards");
                $blank = new \gt\QualificationAward();

                $this->saveUserAward($blank, "min");
                $this->saveUserAward($blank, "max");

            }
        }

        // Reload user awards
        $this->loadUserAwards();

        \gt_debug("---------------------------------------------------------------");

    }

    /**
     * Get a qual award by a points score
     * @return type
     */
    private function getAwardByPoints($points) {
        return $this->getBuild()->getAwardByPoints($points);
    }

    /**
     * Delete a user award
     * @global \block_gradetracker\type $DB
     * @param type $type
     * @return boolean
     */
    private function deleteUserAward($type) {

        global $DB;

        if (!$this->student) {
            return false;
        }

        $DB->delete_records("bcgt_user_qual_awards", array("userid" => $this->student->id, "qualid" => $this->id, "type" => $type));

    }

    /**
     * Save a user qual award
     * @global \block_gradetracker\type $DB
     * @param \block_gradetracker\QualificationAward $award
     * @param type $type
     * @return boolean
     */
    private function saveUserAward(\block_gradetracker\QualificationAward $award, $type) {

        global $DB;

        if (!$this->student) {
            return false;
        }

        $check = $DB->get_record("bcgt_user_qual_awards", array("userid" => $this->student->id, "qualid" => $this->id, "type" => $type));
        if ($check) {
            $check->awardid = $award->getID();
            $result = $DB->update_record("bcgt_user_qual_awards", $check);
        } else {
            $ins = new \stdClass();
            $ins->userid = $this->student->id;
            $ins->qualid = $this->id;
            $ins->awardid = $award->getID();
            $ins->type = $type;
            $result = $DB->insert_record("bcgt_user_qual_awards", $ins);
        }

        if ($result) {
            $this->userAwards[$type] = $award;
        }

        // ------------ Logging
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_AUTO_UPDATED_USER_AWARD;
        $Log->afterjson = array(
            'type' => $type,
            'gradeID' => ($award) ? $award->getID() : null,
            'grade' => ($award) ? $award->getName() : null,
        );
        $Log->attributes = array(
                \block_gradetracker\Log::GT_LOG_ATT_QUALID => $this->id,
                \block_gradetracker\Log::GT_LOG_ATT_STUDID => $this->student->id
            );
        $Log->save();
        // ------------ Logging

        return $result;

    }

    /**
     * Get the student grid
     * @global \block_gradetracker\Qualification\type $CFG
     * @global \block_gradetracker\User $User
     * @global \block_gradetracker\Qualification\type $USER
     * @param type $method
     * @param \block_gradetracker\Template $params
     * @return boolean
     */
    public function getStudentGrid( $method, $params ) {

        global $CFG, $User;

        $ass = optional_param('ass', false, PARAM_INT);
        $GT = new \block_gradetracker\GradeTracker();

        $isExternalUser = false;
        $external = (isset($params['external']) && $params['external']) ? true : false;
        $externalSession = (isset($params['extSsn'])) ? $params['extSsn'] : false;

        if (!isset($params['TPL'])) {
            $params['TPL'] = new \block_gradetracker\Template();
        }

        if (!isset($User)) {
            $userID = \gt_get_external_gt_user_id($params['extSsn']);
            $User = new \block_gradetracker\User($userID);
        }

        // Are we using external access?
        if ($external && $externalSession) {
            if (\gt_validate_external_session($params['extSsn'], $params['student']->id)) {
                $isExternalUser = true;
            }
        }

        // Can we see this person's grid?
        // 1) Are we the student ourselves, looking at our own grid?
        // OR
        // 2) Are we on one of the same courses as the student, with the capability view_student_grids?
        // 3) And are we ticked onto the qualification?
        // 4) And is the student ticked onto the qualification?
        $isTheStudent = (!$isExternalUser && $User->isValid() && $User->id == $params['student']->id);

        // First check is to see if they have view_student_grids capability OR they are the student themselves instead
        if (!$isExternalUser && !$User->hasUserCapability('block/gradetracker:view_student_grids', $params['student']->id, $this->getID()) && !$isTheStudent) {
            print_error('invalidaccess', 'block_gradetracker');
        }

        // Next check is to see if the logged in user is a STAFF on the qualification, OR they have the view_all_quals capability OR they are the student
        if (!$isExternalUser && !$User->isOnQual($this->id, "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals') && !$isTheStudent) {
            print_error('invalidaccess', 'block_gradetracker');
        }

        // Final check is to see if the student is actually on this qual
        if (!$params['student']->isOnQual($this->id, "STUDENT")) {
            print_error('invalidrecord', 'block_gradetracker');
        }

        // If the qualification has no units, display the assessment grid instead
        $QualStructure = new \block_gradetracker\QualificationStructure( $this->getStructureID() );

        // Is disabled
        if (!$QualStructure->isEnabled()) {
            print_error('structureisdisabled', 'block_gradetracker');
        }

        if ( !$QualStructure->isLevelEnabled("Units") || $ass == 1 ) {
            $gridFile = 'assessment_grid';
            $assessmentView = true;
        } else {
            $gridFile = 'grid';
            $assessmentView = false;
        }

        // If the qualification we are looking at has the force_single_page setting, we don't want to
        // show any others with it
        if ($GT->getSetting('assessment_grid_show_quals_one_page') == 1 && $QualStructure->getSetting('force_single_page') == 1) {
            $allQualifications = array( $this->id => $this );
        } else {
            // Get all the qualifications to display in the coefficient table
            $allQualifications = $params['student']->getQualifications("STUDENT");
        }

        // Check if we are using qual weightings
        $hasWeightings = false;
        if ($this->getBuild()->hasQualWeightings()) {

            $hasWeightings = true;
            $params['TPL']->set("weightingPercentiles", \block_gradetracker\Setting::getSetting('qual_weighting_percentiles'));

            // Order them by name
            $Sorter = new \block_gradetracker\Sorter();
            $Sorter->sortQualificationsByName($allQualifications);

            // Then move the chosen qual to the top so that is first
            \gt_array_element_to_top($allQualifications, $this->id);

        }

        // Load the student
        $this->loadStudent($params['student']);

        // All qualifications being viewed
        $params['TPL']->set("allQualifications", $allQualifications);

        // Navigation links
        $params['TPL']->set("links", $GT->getStudentGridNavigation());

        // Values for grid key
        $params['TPL']->set("allPossibleValues", $this->getAllPossibleValues());

        // Main variables
        $params['TPL']->set("Student", $params['student']);
        $params['TPL']->set("Qualification", $this);
        $params['TPL']->set("QualStructure", $QualStructure);

        // Target Grade possible values
        $params['TPL']->set("targetGradeAwards", $this->getBuild()->getAwards());

        // Weighting variable
        $params['TPL']->set("hasWeightings", $hasWeightings);

        // Capabilities
        $params['TPL']->set("canSeeValueAdded", \gt_has_capability('block/gradetracker:see_value_added'));
        $params['TPL']->set("canSeeBothTargets", \gt_has_capability('block/gradetracker:see_both_target_weighted_target_grades'));
        $params['TPL']->set("canSeeTargetGrade", \gt_has_capability('block/gradetracker:see_target_grade'));
        $params['TPL']->set("canSeeWeightedTargetGrade", \gt_has_capability('block/gradetracker:see_weighted_target_grade'));

        // Other Options/Settings
        $params['TPL']->set("gridFile", $gridFile);
        $params['TPL']->set("assessmentView", $assessmentView);
        $params['TPL']->set('showUCAS', \block_gradetracker\Setting::getSetting('student_grid_show_ucas'));

        if (isset($params['print']) && $params['print']) {
            $params['TPL']->set("print", true);
        }

        // Which method are we using?
        if ($method == 'TPL') {
            // TPL was altered by object reference, no need to return anything
            return true;
        } else if ($method == 'return') {

            $GT = new \block_gradetracker\GradeTracker();

            $params['TPL']->set("GT", $GT)
                ->set("User", $User)
                ->set("access", $params['access'])
                ->set("gridFile", $gridFile)
                ->set("assessmentView", $assessmentView)
                ->set("courseID", $params['courseID'])
                ->set("external", true)
                ->set("extSsn", $externalSession);

            try {
                $params['TPL']->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/student.html' );
                return $params['TPL']->getOutput();
            } catch (\block_gradetracker\GTException $e) {
                return $e->getException();
            }

        }

    }


    /**
     * Get the data for the student grid
     * Can be called externally, e.g. Parent Portal, ELBP, etc...
     * @global \block_gradetracker\Qualification\type $CFG
     * @global \block_gradetracker\Qualification\type $USER
     * @param type $params
     * @return type
     */
    public function getStudentGridData($params) {

        global $CFG, $User;

        // Prepare all the variables we will need in the templates
        $GT = new \block_gradetracker\GradeTracker();
        $TPL = new \block_gradetracker\Template();

        $isExternalUser = false;
        $external = (isset($params['external']) && $params['external']) ? true : false;
        $externalSession = (isset($params['extSsn'])) ? $params['extSsn'] : false;

        // If object passed through, get the id from it as the studentID
        if (isset($params['student'])) {
            $params['studentID'] = $params['student']->id;
        }

        if (!isset($User)) {
            $userID = \gt_get_external_gt_user_id($params['extSsn']);
            $User = new \block_gradetracker\User($userID);
        }

        // Are we using external access?
        if ($external && $externalSession) {
            if (\gt_validate_external_session($params['extSsn'], $params['studentID'])) {
                $isExternalUser = true;
            }
        }

        // Force access to "view" in case they tried to go to edit but don't have permission
        if (!\gt_has_capability('block/gradetracker:edit_student_grids')) {
            $params['access'] = 'v';
        }

        $isTheStudent = (!$isExternalUser && $User->isValid() && $User->id == $params['studentID']);

        // First check is to see if they have view_student_grids capability OR they are the student themselves instead
        if (!$isExternalUser && !$User->hasUserCapability('block/gradetracker:view_student_grids', $params['studentID'], $this->getID()) && !$isTheStudent) {
            return \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') );
        }

        // Load student from object or id
        if (isset($params['student'])) {
            $this->loadStudent($params['student']);
        } else {
            $this->loadStudent($params['studentID']);
        }

        $Student = $this->getStudent();
        if (!$Student) {
             return \gt_error_alert_box( get_string('invaliduser', 'block_gradetracker') );
        }

        // Next check is to see if the logged in user is a STAFF on the qualification, OR they have the view_all_quals capability OR they are the student
        if (!$isExternalUser && !$User->isOnQual($this->id, "STAFF") && !\gt_has_capability('block/gradetracker:view_all_quals') && !$isTheStudent) {
             return \gt_error_alert_box( get_string('invalidaccess', 'block_gradetracker') );
        }

        // Final check is to see if the student is actually on this qual
        if (!$Student->isOnQual($this->id, "STUDENT")) {
             return \gt_error_alert_box( get_string('invalidrecord', 'block_gradetracker') );
        }

        $QualStructure = new \block_gradetracker\QualificationStructure($this->getStructureID());
        if (!$QualStructure->isValid()) {
             return \gt_error_alert_box( get_string('invalidqual', 'block_gradetracker') );
        }

        $print = (isset($params['print']) && $params['print']) ? $params['print'] : false;

        $TPL->set("Student", $Student);
        $TPL->set("Qualification", $this);
        $TPL->set("QualStructure", $QualStructure);
        $TPL->set("User", $User);
        $TPL->set("GT", $GT);
        $TPL->set("params", $params);
        $TPL->set("external", $external);
        $TPL->set("print", $print);

        $file = 'grid';

        // Assessment view
        if (!$QualStructure->isLevelEnabled("Units") || ($params['assessmentView'] == 1 && $this->getAssessments()) ) {

            $file = 'assessment_grid';

            // If we want to show them all on one page, get all their qualifications
            if ($GT->getSetting('assessment_grid_show_quals_one_page') == 1 && $QualStructure->getSetting('force_single_page') <> 1) {

                $Qualifications = $Student->getQualifications("STUDENT");

                // Filter out ones who want to be on their own page, not included in list
                $Qualifications = array_filter($Qualifications, function($obj) {
                    return ($obj->getStructure()->getSetting('force_single_page') != 1);
                });

                // Order them by name
                $Sorter = new \block_gradetracker\Sorter();
                $Sorter->sortQualificationsByName($Qualifications);

                // Then move the chosen qual to the top so that is first
                \gt_array_element_to_top($Qualifications, $this->id);

            } else {

                $Qualifications = array($this);

            }

            $canSeeWeightings = false;
            $hasWeightings = false;
            if ($this->getBuild()->hasQualWeightings()) {

                $hasWeightings = true;
                $canSeeWeightings = \gt_has_capability('block/gradetracker:see_weighting_percentiles');

                $TPL->set("weightingPercentiles", \block_gradetracker\Setting::getSetting('qual_weighting_percentiles'));

            }

            $allAssessments = \block_gradetracker\Assessment::getAllAssessmentsOnQuals($Qualifications);

            $TPL->set("Qualifications", $Qualifications);
            $TPL->set("allAssessments", $allAssessments);
            $TPL->set("hasWeightings", $hasWeightings);
            $TPL->set("canSeeWeightings", $canSeeWeightings);

            // Assessments may have different colspans, e.g. if they have CETA enabled or have any custom fields
            $defaultColspan = 0;
            if ($GT->getSetting('use_assessments_comments') == 1 && !$print) {
                $defaultColspan++;
            }

            $customFieldsArray = array();
            $colspanArray = array();
            if ($allAssessments) {

                foreach ($allAssessments as $ass) {

                    $colspan = $defaultColspan;

                    // Does the assessment have CETA enabled?
                    if ($this->isFeatureEnabledByName('cetagrades') && $ass->isCetaEnabled()) {
                        $colspan++;
                    }

                    // Does this assessment have any custom fields on it?
                    $fields = $ass->getEnabledCustomFormFields();
                    $customFieldsArray[$ass->getID()] = $fields;

                    $colspan += count($fields);

                    // Does it have a grading method?
                    if ($ass->getSetting('grading_method') != 'none') {
                        $colspan++;
                    }

                    $colspanArray[$ass->getID()] = $colspan;

                }

            }

            $TPL->set("colspanArray", $colspanArray);
            $TPL->set("defaultColspan", $defaultColspan);
            $TPL->set("customFieldsArray", $customFieldsArray);

        }

        try {
             $TPL->load( $CFG->dirroot . '/blocks/gradetracker/tpl/grids/student/'.$file.'.html' );
             return $TPL->getOutput();
        } catch (\block_gradetracker\GTException $e) {
             return $e->getException();
        }

    }

    /**
     * Import a class grid
     * @global \block_gradetracker\Qualification\type $CFG
     * @global \block_gradetracker\Qualification\type $MSGS
     * @param string $file
     * @return boolean
     */
    public function importClass($file = false) {

        global $CFG, $MSGS;

        $settings = array(
            'qualID' => optional_param('qualID', false, PARAM_INT),
            'now' => optional_param('now', false, PARAM_INT),
            'ass' => optional_param('ass', false, PARAM_INT),
            'studs' => df_optional_param_array_recursive('studs', false, PARAM_TEXT),
            'unit_students' => df_optional_param_array_recursive('unit_students', false, PARAM_TEXT),
        );

        $assessmentView = $settings['ass'];

        // ------------ Logging Info
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_IMPORTED_CLASS_GRID;
        $Log->attributes = array(
                \block_gradetracker\Log::GT_LOG_ATT_QUALID => $this->id
            );

        $Log->save();
        // ------------ Logging Info

        if (!$file) {

            if (!$settings['qualID'] || !$settings['now']) {
                print_error('errors:missingparams', 'block_gradetracker');
            }

            $file = \block_gradetracker\GradeTracker::dataroot() . '/tmp/C_' . $settings['qualID'] . '_' . $settings['now'] . '.xlsx';

        }

        // Open with PHPExcel reader
        try {
            $objPHPExcel = IOFactory::load($file);
        } catch (Exception $e) {
            print_error($e->getMessage());
            return false;
        }

        $output = "";
        $output .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $file ) . '<br>';

        $cntSheets = $objPHPExcel->getSheetCount();
        $cnt = 0;

        $studentArray = array();

        if ($assessmentView) {

            $objPHPExcel->setActiveSheetIndex(0);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $commentsWorkSheet = $objPHPExcel->getSheet(1);

            $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $objWorksheet->getTitle() ) . '<br>';
            $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $commentsWorkSheet->getTitle() ) . '<br>';

            $lastCol = $objWorksheet->getHighestColumn();
            $lastCol++;
            $lastRow = $objWorksheet->getHighestRow();

            // Checkboxes
            $studFilter = ($settings['studs']) ? $settings['studs'] : array();

            // Get array of assessment IDs from column headers
            $assessmentsArray = array();

            for ($col = 'C'; $col != $lastCol; $col++) {

                $colValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();

                // If the cell is not empty
                if ($colValue != '') {

                    preg_match("/^\[([0-9]+)\]/", $colValue, $matches);
                    $id = (isset($matches[1])) ? $matches[1] : false;

                    // If format of column is valid and we got an ID out of it
                    if ($id) {
                        $assessmentsArray[$id] = array('id' => $id, 'name' => $colValue, 'colspan' => 1, 'startingCell' => $col);
                    }

                } else if ($assessmentsArray) {

                    // Else if it's blank, it must be merged with a previous cell, so increment colspan
                    end($assessmentsArray);
                    $key = key($assessmentsArray);
                    $assessmentsArray[$key]['colspan']++;

                }

            }

            $cnt = 0;

            // Loop through rows to get students
            for ($row = 3; $row <= $lastRow; $row++) {

                $student = false;
                $studentQual = false;
                $studentAssessment = false;

                for ($col = 'A'; $col != $lastCol; $col++) {

                    // Get value of cell
                    $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                    // If first column, get the ID of the student but don't print it out
                    if ($col == 'A') {

                        // Qual ID
                        $studentID = (int)$cellValue;

                        // Check if we ticked this one
                        if (!in_array($studentID, $studFilter)) {
                            break;
                        }

                        $student = new \block_gradetracker\User($studentID);
                        $studentQual = new \block_gradetracker\Qualification\UserQualification($this->id);

                        if (!$student->isValid() || !$studentQual->isValid() || !$student->isOnQual($this->id, "STUDENT") || !$studentQual->loadStudent($studentID)) {
                            $studName = ($student->isValid()) ? $student->getDisplayName() : $objWorksheet->getCell("A".$row)->getCalculatedValue();
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:stud', 'block_gradetracker'), $studName ) . '<br>';
                            break;
                        }

                        $output .= "<br>";
                        $output .= "[{$row}] " . sprintf(get_string('import:datasheet:process:student', 'block_gradetracker'), $student->getDisplayName()) . '<br>';

                    } else if ($col != 'A' && $col != 'B' && $col != 'C' && $col != 'D') {
                        // Assessment we want to check for changes

                        // Work out the merged cell that has the assessment ID in, based on
                        // which cell we are in now and the colspan of the parent
                        $assessment = \block_gradetracker\DataImport::findAssessmentParentColumn($assessmentsArray, $col);
                        if (!$assessment) {
                            $output .= "[{$row}] " . get_string('import:datasheet:process:error:ass', 'block_gradetracker' ) . '<br>';
                            continue;
                        }

                        // Get the cell value of the column this is in, so we can see if it's
                        // a Grade column, a CETA column or a Custom Field
                        $column = $objWorksheet->getCell($col . "2")->getCalculatedValue();
                        $column = strtolower($column);

                        // Student Assessment
                        $studentAssessment = $studentQual->getUserAssessment($assessment['id']);

                        // If can't load it on this qual, must not be attached to this qual
                        if (!$studentAssessment) {
                            continue;
                        }

                        // Work out how many cells left until we're in the last merged cell of this assessment
                        if (!isset($mergeCellsLeft)) {
                            $mergeCellsLeft = $assessment['colspan'] - 1;
                        } else {
                            $mergeCellsLeft--;
                        }

                        // Grade cell
                        if ($column == 'grade') {

                            $userComments = false;

                            // Is this assessment using a grading structure or numeric grading?
                            $gradingMethod = $studentAssessment->getSetting('grading_method');

                            // Numeric
                            if ($gradingMethod == 'numeric') {
                                $score = $cellValue;
                                $min = $studentAssessment->getSetting('numeric_grading_min');
                                $max = $studentAssessment->getSetting('numeric_grading_max');
                                if (!is_numeric($score) || $score < $min || $score > $max) {
                                    $score = null;
                                }
                                $studentAssessment->setUserScore($score);
                            } else {
                                $GradingStructure = $studentAssessment->getQualificationAssessmentGradingStructure();
                                $grade = $GradingStructure->getAwardByShortName($cellValue);
                                // If they supplied an invalid award, it will just be set to nothing
                                if (is_null($grade) || !$grade) {
                                    $grade = new \block_gradetracker\CriteriaAward();
                                }
                                $studentAssessment->setUserGrade($grade);
                            }

                            // We'll do the comments here as the Grade cell is the only one that should always
                            // be on each assessment, and if we do it outside it'll do it multiple times
                            // Loop through headers in commentsWorksheet
                            $lastCommentsCol = $commentsWorkSheet->getHighestColumn();
                            $lastCommentsCol++;
                            for ($letter = 'C'; $letter != $lastCommentsCol; $letter++) {
                                $commentsHeaderCell = $commentsWorkSheet->getCell($letter . "1")->getCalculatedValue();
                                preg_match("/^\[([0-9]+)\]/", $commentsHeaderCell, $matches);
                                $id = (isset($matches[1])) ? $matches[1] : false;

                                if ($id && $id == $assessment['id']) {
                                    // We are assuming they haven't messed with the order of the rows and that
                                    // the comments are in the same row number as the grades
                                    // Can always change it to check for qualID, but better if they just don't fuck about with the spreadsheet
                                    $userComments = $commentsWorkSheet->getCell($letter . $row)->getCalculatedValue();
                                    $studentAssessment->setUserComments($userComments);
                                    break;
                                }
                            }

                        } else if ($column == 'ceta') {

                            $QualBuild = $studentQual->getBuild();
                            $award = $QualBuild->getAwardByName($cellValue);
                            if (is_null($award) || !$award) {
                                $award = new \block_gradetracker\QualificationAward();
                            }

                            $studentAssessment->setUserCeta($award);

                        } else if (preg_match("/^\[([0-9]+)\]/", $column, $matches)) {

                            // Custom Form Field
                            $fieldID = (isset($matches[1])) ? $matches[1] : false;
                            $field = new \block_gradetracker\FormElement($fieldID);
                            $studentAssessment->setUserCustomFieldValue($fieldID, $cellValue);
                            $column = $field->getName();

                        }

                        // Save the user's assessment
                        $studentAssessment->saveUser();

                        if ($cellValue == '') {
                            $cellValue = get_string('na', 'block_gradetracker');
                        }

                        $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:ass', 'block_gradetracker'), $studentAssessment->getName(), $column, $cellValue) . '<br>';
                        $cnt++;

                        // Comments as well after everything else (last cell of this column)
                        if ($mergeCellsLeft == 0) {
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:ass', 'block_gradetracker'), $studentAssessment->getName(), get_string('comments', 'block_gradetracker'), '"'.$userComments.'"') . '<br>';
                            unset($mergeCellsLeft);
                        }

                    }

                }

            }

        } else {

            // Checkboxes
            $studUnitFilter = ($settings['unit_students']) ? $settings['unit_students'] : array();

            // Loop through the worksheets (each unit has its own worksheet)
            for ($sheetNum = 0; $sheetNum < $cntSheets; $sheetNum++) {

                $objPHPExcel->setActiveSheetIndex($sheetNum);
                $objWorksheet = $objPHPExcel->getActiveSheet();
                $sheetName = $objWorksheet->getTitle();

                preg_match("/^\((\d+)\)/", $sheetName, $matches);
                if (!isset($matches[1])) {
                    $output .= get_string('invalidunit', 'block_gradetracker') . ' - ' . $sheetName;
                    continue;
                }

                $unitID = $matches[1];
                $unit = $this->getUnit($unitID);
                if (!$unit) {
                    $output .= get_string('invalidunit', 'block_gradetracker') . ' - ' . $sheetName;
                    break;
                }

                $output .= sprintf( get_string('import:datasheet:process:unit', 'block_gradetracker'), $unit->getDisplayName() ) . '<br>';

                $lastCol = $objWorksheet->getHighestColumn();
                $lastCol++;
                $lastRow = $objWorksheet->getHighestRow();

                $possibleValues = $unit->getAllPossibleValues();
                $possibleValueArray = array();
                if ($possibleValues) {
                    foreach ($possibleValues as $value) {
                        $possibleValueArray[$value->getShortName()] = $value;
                    }
                }

                $eventCriteria = array();
                $naValueObj = new \block_gradetracker\CriteriaAward();

                // Loop through rows to get students
                for ($row = 2; $row <= $lastRow; $row++) {

                    $student = false;

                    // Loop columns
                    for ($col = 'A'; $col != $lastCol; $col++) {

                        $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                        if ($col == 'A') {

                            $studentID = $cellValue;

                            // If not ticked, don't bother going any further
                            if (!array_key_exists($unitID, $studUnitFilter) || !in_array($studentID, $studUnitFilter[$unitID])) {
                                break;
                            }

                            $student = new \block_gradetracker\User($studentID);
                            if (!$student->isValid()) {
                                $output .= "[{$row}] " . get_string('invaliduser', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                break;
                            }

                            // Make sure student is actually on this qual and unit
                            if (!$student->isOnQualUnit($this->id, $unitID, "STUDENT")) {
                                $output .= "[{$row}] " . get_string('usernotonunit', 'block_gradetracker') . ' - ' . "[{$studentID}] " . $objWorksheet->getCell("B".$row)->getCalculatedValue() . " " . $objWorksheet->getCell("C" . $row)->getCalculatedValue() . " (".$objWorksheet->getCell("D" . $row)->getCalculatedValue().")";
                                break;
                            }

                            $unit->loadStudent($student);

                            $studentArray[$studentID] = $student;

                            $studentUnit = clone $unit;
                            $studentUnitArray[] = $studentUnit;

                            $output .= "<br>";
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:student', 'block_gradetracker'), $student->getDisplayName() ) . '<br>';
                            continue; // Don't want to print the id out
                        }

                        // A, B, C and D are the ID, firstname, lastname and username
                        if ($col != 'A' && $col != 'B' && $col != 'C' && $col != 'D') {

                            $value = $cellValue;

                            // Get studentCriteria to see if it has been updated since we downloaded the sheet
                            $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                            $studentCriterion = $unit->getCriterionByName($criteriaName);

                            $eventCriteria[$unitID] = $studentCriterion;

                            if ($studentCriterion) {

                                // Set new value
                                if (array_key_exists($value, $possibleValueArray) !== false || $value == $naValueObj->getShortName()) {

                                    $valueObj = (array_key_exists($value, $possibleValueArray)) ? $possibleValueArray[$value] : $naValueObj;

                                    // Update user
                                    $studentCriterion->setUserAward($valueObj);

                                    // If this is the last criteria on the unit, do the events
                                    $noEvent = ($col == $objWorksheet->getHighestColumn()) ? 'force' : true;

                                    $studentCriterion->saveUser(true, $noEvent);

                                    $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success', 'block_gradetracker'), $criteriaName, $value) . '<br>';
                                    $cnt++;

                                } else {
                                    $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:value', 'block_gradetracker'), $value ) . '<br>';
                                }

                            } else {
                                $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:criterion', 'block_gradetracker'), $criteriaName) . '<br>';
                            }

                        }

                    }

                }

                $output .= "<br><br>";

            }

            $output .= "<br>";

            // Recalculate unit awards
            if ($studentUnitArray) {
                foreach ($studentUnitArray as $studentUnit) {
                    $studentUnit->autoCalculateAwards();
                    $studentUnit->reloadUserAward(); // Reload it from DB incase we didn't do auto calc but did rule instead
                    $output .= sprintf( get_string('import:datasheet:process:autocalcstudunit', 'block_gradetracker'), $studentUnit->getStudent()->getDisplayName(), $studentUnit->getName(), $studentUnit->getUserAward()->getName()) . '<br>';
                }
            }

            // Recalculate qual awards
            if ($studentArray) {
                foreach ($studentArray as $stud) {
                    $this->loadStudent($stud);
                    $this->loadUnits();
                    $this->calculatePredictedAwards();
                }
            }

        }

        $output .= "<br>";

        // Delete file
        $del = unlink($file);
        if ($del) {
            $output .= sprintf( get_string('import:datasheet:process:deletedfile', 'block_gradetracker'), $file) . '<br>';
        }

        $output .= get_string('import:datasheet:process:end', 'block_gradetracker') . '<br>';

        $MSGS['confirmed'] = true;
        $MSGS['output'] = $output;
        $MSGS['cnt'] = $cnt;

    }

    /**
     * Import the student grid
     * @global \block_gradetracker\Qualification\type $CFG
     * @global type $MSGS
     * @param string $file
     * @return boolean
     */
    public function import($file = false) {

        global $CFG, $MSGS;

        if (!$this->getStudent()) {
            return false;
        }

        $settings = array(
            'qualID' => optional_param('qualID', false, PARAM_INT),
            'studentID' => optional_param('studentID', false, PARAM_INT),
            'now' => optional_param('now', false, PARAM_INT),
            'ass' => optional_param('ass', false, PARAM_INT),
            'quals' => df_optional_param_array_recursive('quals', false, PARAM_INT),
            'units' => df_optional_param_array_recursive('units', false, PARAM_INT),
        );

        $assessmentView = $settings['ass'];

        // ------------ Logging Info
        $Log = new \block_gradetracker\Log();
        $Log->context = \block_gradetracker\Log::GT_LOG_CONTEXT_GRID;
        $Log->details = \block_gradetracker\Log::GT_LOG_DETAILS_IMPORTED_STUDENT_GRID;
        $Log->attributes = array(
                \block_gradetracker\Log::GT_LOG_ATT_QUALID => $this->id,
                \block_gradetracker\Log::GT_LOG_ATT_STUDID => $this->student->id
            );

        $Log->save();
        // ------------ Logging Info

        if (!$file) {

            if (!$settings['qualID'] || !$settings['studentID'] || !$settings['now']) {
                print_error('errors:missingparams', 'block_gradetracker');
            }

            $file = \block_gradetracker\GradeTracker::dataroot() . '/tmp/' . $settings['qualID'] . '_' . $settings['studentID'] . '_' . $settings['now'] . '.xlsx';

        }

        // Open with PHPExcel reader
        try {
            $objPHPExcel = IOFactory::load($file);
        } catch (Exception $e) {
            print_error($e->getMessage());
            return false;
        }

        $output = "";
        $output .= sprintf( get_string('import:datasheet:process:file', 'block_gradetracker'), $file ) . '<br>';

        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $commentsWorkSheet = $objPHPExcel->getSheet(1);

        $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $objWorksheet->getTitle() ) . '<br>';
        $output .= sprintf( get_string('import:datasheet:process:worksheet', 'block_gradetracker'), $commentsWorkSheet->getTitle() ) . '<br>';

        $lastCol = $objWorksheet->getHighestColumn();
        $lastCol++;
        $lastRow = $objWorksheet->getHighestRow();

        // Assessment Grid
        if ($assessmentView) {

            // Checkboxes
            $qualFilter = ($settings['quals']) ? $settings['quals'] : array();

            // Get array of assessment IDs from column headers
            $assessmentsArray = array();

            for ($col = 'C'; $col != $lastCol; $col++) {

                $colValue = $objWorksheet->getCell($col . "1")->getCalculatedValue();

                // If the cell is not empty
                if ($colValue != '') {

                    preg_match("/^\[([0-9]+)\]/", $colValue, $matches);
                    $id = (isset($matches[1])) ? $matches[1] : false;

                    // If format of column is valid and we got an ID out of it
                    if ($id) {
                        $assessmentsArray[$id] = array('id' => $id, 'name' => $colValue, 'colspan' => 1, 'startingCell' => $col);
                    }

                } else if ($assessmentsArray) {

                    // Else if it's blank, it must be merged with a previous cell, so increment colspan
                    end($assessmentsArray);
                    $key = key($assessmentsArray);
                    $assessmentsArray[$key]['colspan']++;

                }

            }

            $cnt = 0;

            // Loop through rows to get quals
            for ($row = 3; $row <= $lastRow; $row++) {

                $studentQual = false;
                $studentAssessment = false;

                for ($col = 'A'; $col != $lastCol; $col++) {

                    // Get value of cell
                    $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                    // If first column, get the ID of the qual but don't print it out
                    if ($col == 'A') {

                        // Qual ID
                        $qualID = (int)$cellValue;

                        // Check if we ticked this one
                        if (!in_array($qualID, $qualFilter)) {
                            break;
                        }

                        $studentQual = new \block_gradetracker\Qualification\UserQualification($qualID);

                        if (!$studentQual->isValid() || !$this->getStudent()->isOnQual($this->id, "STUDENT") || !$studentQual->loadStudent($this->getStudentID())) {
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:qual', 'block_gradetracker'), $objWorksheet->getCell("B".$row)->getCalculatedValue() ) . '<br>';
                            break;
                        }

                        $output .= "<br>";
                        $output .= "[{$row}] " . sprintf(get_string('import:datasheet:process:qual', 'block_gradetracker'), $studentQual->getDisplayName()) . '<br>';

                    } else if ($col != 'A' && $col != 'B') {

                        // Assessment we want to check for changes

                        // Work out the merged cell that has the assessment ID in, based on
                        // which cell we are in now and the colspan of the parent
                        $assessment = \block_gradetracker\DataImport::findAssessmentParentColumn($assessmentsArray, $col);
                        if (!$assessment) {
                            $output .= "[{$row}] " . get_string('import:datasheet:process:error:ass', 'block_gradetracker' ) . '<br>';
                            continue;
                        }

                        // Get the cell value of the column this is in, so we can see if it's
                        // a Grade column, a CETA column or a Custom Field
                        $column = $objWorksheet->getCell($col . "2")->getCalculatedValue();
                        $column = strtolower($column);

                        // Student Assessment
                        $studentAssessment = $studentQual->getUserAssessment($assessment['id']);

                        // If can't load it on this qual, must not be attached to this qual
                        if (!$studentAssessment) {
                            continue;
                        }

                        // Work out how many cells left until we're in the last merged cell of this assessment
                        if (!isset($mergeCellsLeft)) {
                            $mergeCellsLeft = $assessment['colspan'] - 1;
                        } else {
                            $mergeCellsLeft--;
                        }

                        // Grade cell
                        if ($column == 'grade') {

                            $userComments = false;

                            // Is this assessment using a grading structure or numeric grading?
                            $gradingMethod = $studentAssessment->getSetting('grading_method');

                            // Numeric
                            if ($gradingMethod == 'numeric') {
                                $score = $cellValue;
                                $min = $studentAssessment->getSetting('numeric_grading_min');
                                $max = $studentAssessment->getSetting('numeric_grading_max');
                                if (!is_numeric($score) || $score < $min || $score > $max) {
                                    $score = null;
                                }
                                $studentAssessment->setUserScore($score);
                            } else {
                                $GradingStructure = $studentAssessment->getQualificationAssessmentGradingStructure();
                                $grade = $GradingStructure->getAwardByShortName($cellValue);
                                // If they supplied an invalid award, it will just be set to nothing
                                if (is_null($grade) || !$grade) {
                                    $grade = new \block_gradetracker\CriteriaAward();
                                }
                                $studentAssessment->setUserGrade($grade);
                            }

                            // We'll do the comments here as the Grade cell is the only one that should always
                            // be on each assessment, and if we do it outside it'll do it multiple times
                            // Loop through headers in commentsWorksheet
                            $lastCommentsCol = $commentsWorkSheet->getHighestColumn();
                            $lastCommentsCol++;
                            for ($letter = 'C'; $letter != $lastCommentsCol; $letter++) {
                                $commentsHeaderCell = $commentsWorkSheet->getCell($letter . "1")->getCalculatedValue();
                                preg_match("/^\[([0-9]+)\]/", $commentsHeaderCell, $matches);
                                $id = (isset($matches[1])) ? $matches[1] : false;

                                if ($id && $id == $assessment['id']) {
                                    // We are assuming they haven't messed with the order of the rows and that
                                    // the comments are in the same row number as the grades
                                    // Can always change it to check for qualID, but better if they just don't fuck about with the spreadsheet
                                    $userComments = $commentsWorkSheet->getCell($letter . $row)->getCalculatedValue();
                                    $studentAssessment->setUserComments($userComments);
                                    break;
                                }
                            }

                        } else if ($column == 'ceta') {

                            $QualBuild = $studentQual->getBuild();
                            $award = $QualBuild->getAwardByName($cellValue);
                            if (is_null($award) || !$award) {
                                $award = new \block_gradetracker\QualificationAward();
                            }

                            $studentAssessment->setUserCeta($award);

                        } else if (preg_match("/^\[([0-9]+)\]/", $column, $matches)) {

                            // Custom Form Field
                            $fieldID = (isset($matches[1])) ? $matches[1] : false;
                            $field = new \block_gradetracker\FormElement($fieldID);
                            $studentAssessment->setUserCustomFieldValue($fieldID, $cellValue);
                            $column = $field->getName();

                        }

                        // Save the user's assessment
                        $studentAssessment->saveUser();

                        if ($cellValue == '') {
                            $cellValue = get_string('na', 'block_gradetracker');
                        }

                        $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:ass', 'block_gradetracker'), $studentAssessment->getName(), $column, $cellValue) . '<br>';
                        $cnt++;

                        // Comments as well after everything else (last cell of this column)
                        if ($mergeCellsLeft == 0) {
                            $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:ass', 'block_gradetracker'), $studentAssessment->getName(), get_string('comments', 'block_gradetracker'), '"'.$userComments.'"') . '<br>';
                            unset($mergeCellsLeft);
                        }

                    }

                }

            }

        } else {

            // Normal grid

            // Checkboxes
            $unitFilter = ($settings['units']) ? $settings['units'] : array();

            $possibleValues = $this->getAllPossibleValues();
            $possibleValueArray = array();
            if ($possibleValues) {
                foreach ($possibleValues as $value) {
                    $possibleValueArray[$value->getShortName()] = $value;
                }
            }

            $unitArray = array();
            $naValueObj = new \block_gradetracker\CriteriaAward();
            $runEvents = array();

            $cnt = 0;

            // Loop through rows to get units
            for ($row = 2; $row <= $lastRow; $row++) {

                $studentUnit = false;

                // Loop columns
                for ($col = 'A'; $col != $lastCol; $col++) {

                    $cellValue = $objWorksheet->getCell($col . $row)->getCalculatedValue();

                    if ($col == 'A') {

                        $unitID = $cellValue;
                        $studentUnit = (isset($this->units[$unitID])) ? $this->units[$unitID] : false;

                        // If this unit doesn't exist on the qual, stop
                        if (!$studentUnit) {
                            break;
                        }

                        // If we didn't tick this one, stop as well
                        if (!in_array($unitID, $unitFilter)) {
                            break;
                        }

                        $unitArray[$unitID] = $studentUnit;
                        $output .= "<br>";
                        $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:unit', 'block_gradetracker'), $studentUnit->getDisplayName() ) . '<br>';
                        continue; // Don't want to print the id out
                    }

                    if ($col != 'A' && $col != 'B') {

                        $value = $cellValue;

                        // Get studentCriteria to see if it has been updated since we downloaded the sheet
                        $criteriaName = $objWorksheet->getCell($col . "1")->getCalculatedValue();
                        $studentCriterion = $studentUnit->getCriterionByName($criteriaName);

                        if ($studentCriterion) {

                            // Set new value
                            if (array_key_exists($value, $possibleValueArray) !== false || $value == $naValueObj->getShortName()) {

                                $valueObj = (array_key_exists($value, $possibleValueArray)) ? $possibleValueArray[$value] : $naValueObj;

                                // Update user
                                $studentCriterion->setUserAward($valueObj);
                                $commentsCellValue = (string)$commentsWorkSheet->getCell($col . $row)->getCalculatedValue();
                                $studentCriterion->setUserComments($commentsCellValue);
                                $studentCriterion->saveUser(true);

                                $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success', 'block_gradetracker'), $criteriaName, $value) . '<br>';
                                $cnt++;

                            } else {
                                $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:error:value', 'block_gradetracker'), $value ) . '<br>';
                            }

                        } else {

                            // Was it an IV column?
                            if ($this->getStructure() && $this->getStructure()->getSetting('iv_column') == 1) {

                                // Get the string to compare the column headers
                                $attribute = false;
                                $ivDateString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('date');
                                $ivWhoString = get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker');

                                // Check if we are in the date column
                                if ($criteriaName == $ivDateString) {
                                    // If it's an excel date convert to unix and back to string
                                    // Otherwise just insert whatever string it says
                                    if (is_float($value) && $value > 0) {
                                        $value = \gt_convert_excel_date_unix($value);
                                        $value = date('d-m-Y', $value);
                                    }

                                    $attribute = 'IV_date';

                                } else if ($criteriaName == $ivWhoString) {
                                    $attribute = 'IV_who';
                                }

                                // If attribute is valid save it
                                if ($attribute) {

                                    $value = trim($value);
                                    if ($value == '') {
                                        $value = null;
                                    }

                                    $studentUnit->updateAttribute($attribute, $value, $this->student->id);

                                    $output .= "[{$row}] " . sprintf( get_string('import:datasheet:process:success:misc', 'block_gradetracker'), $criteriaName, $value) . '<br>';
                                    $cnt++;

                                }

                            }

                        }

                    }

                }

            }

            $output .= "<br>";

            // Recalculate unit awards
            if ($unitArray) {
                foreach ($unitArray as $unit) {
                    $unit->autoCalculateAwards();
                    $unit->reloadUserAward(); // Reload it from DB incase we didn't do auto calc but did rule instead
                    $output .= sprintf( get_string('import:datasheet:process:autocalcunit', 'block_gradetracker'), $unit->getName(), $unit->getUserAward()->getName()) . '<br>';
                }
            }

            // Recalculate predicted grades
            $this->calculatePredictedAwards();

        }

        $output .= "<br>";

        // Delete file
        $del = unlink($file);
        if ($del) {
            $output .= sprintf( get_string('import:datasheet:process:deletedfile', 'block_gradetracker'), $file) . '<br>';
        }

        $output .= get_string('import:datasheet:process:end', 'block_gradetracker') . '<br>';

        $MSGS['confirmed'] = true;
        $MSGS['output'] = $output;
        $MSGS['cnt'] = $cnt;

    }

    /**
     * Export student grid
     * @global type $CFG
     * @global type $USER
     */
    public function export($assessmentView = false) {

        global $CFG, $USER, $GT;

        $name = preg_replace("/[^a-z 0-9]/i", "", $this->getDisplayName() . ' - ' . $this->student->getDisplayName());
        $filename = $name . '.xlsx';

        // Setup Spreadsheet
        $objPHPExcel = new excel($filename);
        $objPHPExcel->getSpreadsheet()->getProperties()
            ->setCreator(fullname($USER))
            ->setLastModifiedBy(fullname($USER))
            ->setTitle( $this->getDisplayName() . " - " . $this->student->getDisplayName() )
            ->setSubject( $this->getDisplayName() . " - " . $this->student->getDisplayName() )
            ->setDescription( $this->getDisplayName() . " - " . $this->student->getDisplayName() . " " . get_string('generatedbygt', 'block_gradetracker'))
            ->setCustomProperty( "GT-DATASHEET-TYPE" , "STUDENT", 's')
            ->setCustomProperty( "GT-DATASHEET-DOWNLOADED" , time(), 'i');

        $formats = array();
        $formats['blank'] = $objPHPExcel->add_format(['bg_color' => '#EDEDED']);
        $formats['centre'] = $objPHPExcel->add_format(['align' => 'center']);

        // Get top level criteria
        $criteria = $this->getHeaderCriteriaNames();

        // Set current sheet
        $sheets = array();
        $sheets['main'] = $objPHPExcel->addWorksheet( get_string('grades', 'block_gradetracker') );
        $sheets['comments'] = $objPHPExcel->addWorksheet( get_string('comments', 'block_gradetracker') );

        $rowNum = 0;

        // If it is an ALVL-style with assessments instead of units:
        $QualStructure = $this->getStructure();
        if ( !$QualStructure->isLevelEnabled("Units") || ($assessmentView == 1 && $this->getAssessments()) ) {

            $objPHPExcel->getSpreadsheet()->getProperties()->setCustomProperty("GT-DATASHEET-ASSESSMENT-VIEW", 1, 'i');

            // If we want to only show this 1 qualification on its own
            if ($GT->getSetting('assessment_grid_show_quals_one_page') == 1 && $QualStructure->getSetting('force_single_page') <> 1) {

                $qualifications = $this->student->getQualifications("STUDENT");

                // Filter out ones who want to be on their own page, not included in list
                $qualifications = array_filter($qualifications, function($obj) {
                    return ($obj->getStructure()->getSetting('force_single_page') != 1);
                });

                // Order them by name
                $Sorter = new \block_gradetracker\Sorter();
                $Sorter->sortQualificationsByName($qualifications);

                // Then move the chosen qual to the top so that is first
                \gt_array_element_to_top($qualifications, $this->id);

            } else {

                $qualifications = array($this);

            }

            $assessments = \block_gradetracker\Assessment::getAllAssessmentsOnQuals($qualifications);

            // Headers
            foreach ($sheets as $sheet) {
                $sheet->writeString($rowNum, 'A', 'QID');
                $sheet->writeString($rowNum, 'B', get_string('qualification', 'block_gradetracker'));
            }

            $letter = 'C';
            $commentsLetter = 'C';

            // Custom Form Fields
            $defaultColspan = 1;
            if ($GT->getSetting('use_assessments_comments') == 1) {
                $defaultColspan++;
            }

            $customFieldsArray = array();
            $colspanArray = array();
            if ($assessments) {

                foreach ($assessments as $ass) {

                    $colspan = $defaultColspan;

                    // Does the assessment have CETA enabled?
                    if ($this->isFeatureEnabledByName('cetagrades') && $ass->isCetaEnabled()) {
                        $colspan++;
                    }

                    // Does this assessment have any custom fields on it?
                    $fields = $ass->getEnabledCustomFormFields();
                    $customFieldsArray[$ass->getID()] = $fields;

                    $colspan += count($fields);

                    // Comments column
                    $colspanArray[$ass->getID()] = $colspan;

                }

            }

            // Display assessment names along the top
            if ($assessments) {

                foreach ($assessments as $assessment) {

                    $oldLetter = $letter;
                    $sheets['main']->writeString($rowNum, $letter, "[{$assessment->getID()}] " . $assessment->getName());

                    // If using CETA or Custom Form Fields, we will need to merge cells
                    if ( ($this->isFeatureEnabledByName('cetagrades') && $assessment->isCetaEnabled()) || array_key_exists($assessment->getID(), $customFieldsArray) ) {

                        // Custom Form Fields
                        if (array_key_exists($assessment->getID(), $customFieldsArray)) {
                            foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                $letter++;
                            }
                        }

                        // CETA Column
                        if ($this->isFeatureEnabledByName('cetagrades') && $assessment->isCetaEnabled()) {
                            $letter++;
                        }

                        // Merge them
                        $sheets['main']->mergeCells($rowNum, $oldLetter, $rowNum, $letter);
                        $sheets['main']->applyRangeFormat($oldLetter, $rowNum, $letter, $rowNum, $formats['centre']);

                    }

                    // Move forward one for the next assessment if there is one
                    $letter++;

                    // Comments worksheet
                    $sheets['comments']->writeString($rowNum, $commentsLetter, "[{$assessment->getID()}] " . $assessment->getName());
                    $commentsLetter++;

                }

            }

            $rowNum += 1;

            // Then display the Grade/Ceta row if ceta is enabled, or using any custom form fields
            if ($this->isFeatureEnabledByName('cetagrades') || $customFieldsArray) {

                $letter = 'C';

                if ($assessments) {

                    foreach ($assessments as $assessment) {

                        // Custom Form Fields first
                        if (array_key_exists($assessment->getID(), $customFieldsArray) && $customFieldsArray[$assessment->getID()]) {
                            foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                $sheets['main']->writeString($rowNum, $letter, "[{$field->getID()}] " . $field->getName());
                                $letter++;
                            }
                        }

                        // Then Grade column
                        $sheets['main']->writeString($rowNum, $letter, get_string('grade', 'block_gradetracker'));
                        $letter++;

                        // Then CETA column
                        if ($assessment->isCetaEnabled()) {
                            $sheets['main']->writeString($rowNum, $letter, get_string('ceta', 'block_gradetracker'));
                            $letter++;
                        }

                    }

                }

                $rowNum += 1;

            }

            // Loop through quals and assessments
            if ($qualifications) {

                foreach ($qualifications as $qual) {

                    if ($qual->getAssessments()) {

                        foreach ($sheets as $sheet) {
                            $sheet->writeString($rowNum, 'A', $qual->getID());
                            $sheet->writeString($rowNum, 'B', $qual->getDisplayName());
                        }

                        $letter = 'C';
                        $commentsLetter = 'C';

                        // Loop assessments
                        if ($assessments) {
                            foreach ($assessments as $assessment) {

                                $qualAssessment = $qual->getAssessment($assessment->getID());

                                // Custom Form Fields
                                if ($qualAssessment) {
                                    if ($customFieldsArray && array_key_exists($assessment->getID(), $customFieldsArray) && $customFieldsArray[$assessment->getID()]) {
                                        foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                            $qualAssessment->getExcelCustomFormFieldCell($sheets['main'], $rowNum, $letter, $field);
                                            $letter++;
                                        }
                                    }
                                } else {
                                    foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                        $sheets['main']->applyRangeFormat($letter, $rowNum, null, null, $formats['blank']);
                                        $letter++;
                                    }
                                }

                                // If this assessment is on this qual
                                if ($qualAssessment) {
                                    $qualAssessment->getExcelGradeCell($sheets['main'], $rowNum, $letter);
                                } else {
                                    $sheets['main']->applyRangeFormat($letter, $rowNum, null, null, $formats['blank']);
                                }

                                $letter++;

                                // Using CETA?
                                if ($this->isFeatureEnabledByName('cetagrades') && $assessment->isCetaEnabled()) {
                                    // If this assessment is on this qual
                                    if ($qualAssessment) {
                                        $qualAssessment->getExcelCetaCell($sheets['main'], $rowNum, $letter);
                                    } else {
                                        $sheets['main']->applyRangeFormat($letter, $rowNum, null, null, $formats['blank']);
                                    }
                                    $letter++;
                                }

                                // Comments worksheet
                                if ($qualAssessment) {
                                    $sheets['comments']->writeString($rowNum, $commentsLetter, $qualAssessment->getUserComments());
                                }
                                $commentsLetter++;

                            }
                        }

                        $rowNum++;

                    }

                }

            }

        } else {

            // If it's a normal qualification with units:

            // Headers
            foreach ($sheets as $sheet) {
                $sheet->writeString($rowNum, 'A', 'ID');
                $sheet->writeString($rowNum, 'B', get_string('unit', 'block_gradetracker'));
            }

            $letter = 'C';

            if ($criteria) {

                foreach ($criteria as $criterion) {

                    foreach ($sheets as $sheet) {
                        $sheet->writeString($rowNum, $letter, $criterion['name']);
                    }

                    $letter++;

                    if (isset($criterion['sub']) && $criterion['sub']) {

                        foreach ($criterion['sub'] as $sub) {

                            $subName = (isset($sub['name'])) ? $sub['name'] : $sub;

                            foreach ($sheets as $sheet) {
                                $sheet->writeString($rowNum, $letter, $subName);
                            }

                            $letter++;

                        }

                    }

                }

            }

            // IV Column?
            if ($this->getStructure() && $this->getStructure()->getSetting('iv_column') == 1) {
                $sheets['main']->writeString($rowNum, $letter, get_string('iv', 'block_gradetracker') . ' - ' . get_string('date'));
                $letter++;
                $sheets['main']->writeString($rowNum, $letter, get_string('iv', 'block_gradetracker') . ' - ' . get_string('verifier', 'block_gradetracker'));
                $letter++;
            }

            $rowNum += 1;

            // Loop through units
            if ($this->getUnits()) {

                foreach ($this->units as $unit) {

                    if ($this->student->isOnQualUnit($this->id, $unit->getID(), "STUDENT")) {

                        // ID & Name
                        foreach ($sheets as $sheet) {
                            $sheet->writeString($rowNum, 'A', $unit->getID());
                            $sheet->writeString($rowNum, 'B', $unit->getDisplayName());
                        }

                        $letter = 'C';

                        // Loop through criteria
                        if ($criteria) {

                            foreach ($criteria as $crit) {

                                $criterion = $unit->getCriterionByName( $crit['name'] );
                                if ($criterion) {

                                    // Value
                                    $criterion->getExcelCell( $sheets['main'], $rowNum, $letter );

                                    // Comments
                                    $comments = ($criterion->getUserComments()) ? $criterion->getUserComments() : '';
                                    $sheets['comments']->writeString($rowNum, $letter, $comments);

                                    $letter++;

                                } else {

                                    foreach ($sheets as $sheet) {
                                        $sheet->writeString($rowNum, $letter, '', $formats['blank']);
                                    }

                                    $letter++;
                                }

                                // Sub Criteria
                                if (isset($crit['sub']) && $crit['sub']) {
                                    foreach ($crit['sub'] as $sub) {
                                        $subName = (isset($sub['name'])) ? $sub['name'] : $sub;
                                        $subCriterion = $unit->getCriterionByName( $subName );
                                        if ($subCriterion) {

                                            // Value
                                            $subCriterion->getExcelCell( $sheets['main'], $rowNum, $letter );

                                            // Comments
                                            $comments = ($criterion->getUserComments()) ? $subCriterion->getUserComments() : '';
                                            $sheets['comments']-writeString($rowNum, $letter, $comments);

                                        } else {

                                            foreach ($sheets as $sheet) {
                                                $sheet->writeString($rowNum, $letter, '', $formats['blank']);
                                            }

                                        }
                                        $letter++;
                                    }
                                }
                            }
                        }

                        // IV Column?
                        if ($this->getStructure() && $this->getStructure()->getSetting('iv_column') == 1) {

                            $ivDate = $unit->getAttribute('IV_date', $this->student->id);
                            if (!$ivDate) {
                                $ivDate = '';
                            }

                            $ivWho = $unit->getAttribute('IV_who', $this->student->id);
                            if (!$ivWho) {
                                $ivWho = '';
                            }

                            $sheets['main']->writeString($rowNum, $letter, $ivDate);
                            $letter++;
                            $sheets['main']->writeString($rowNum, $letter, $ivWho);
                            $letter++;

                        }

                        $rowNum++;

                    }

                }

                // Set autosize for unit name column
                foreach ($sheets as $sheet) {
                    $lastColumn = $sheet->getWorksheet()->getHighestColumn();
                    for ($col = 'A'; $col <= $lastColumn; $col++) {
                        $sheet->getWorksheet()->getColumnDimension($col)->setAutoSize(true);
                    }
                }

            }

        }

        // Freeze rows and cols (everything to the left of D and above 2)
        $sheets['main']->getWorksheet()->freezePane('C2');
        $sheets['comments']->getWorksheet()->freezePane('C2');

        $objPHPExcel->serve();
        exit;

    }

    /**
     * Export the class grid
     * @global \block_gradetracker\Qualification\type $CFG
     * @global \block_gradetracker\Qualification\type $USER
     */
    public function exportClass($assessmentView = false) {

        global $CFG, $USER, $GT;

        $courseID = optional_param('courseID', false, PARAM_INT);
        $groupID = optional_param('groupID', false, PARAM_INT);

        $name = preg_replace("/[^a-z 0-9]/i", "", $this->getDisplayName());
        $filename = $name . '.xlsx';

        // Setup Spreadsheet
        $objPHPExcel = new excel($filename);
        $objPHPExcel->getSpreadsheet()->getProperties()
            ->setCreator(fullname($USER))
            ->setLastModifiedBy(fullname($USER))
            ->setTitle( $this->getDisplayName() )
            ->setSubject( $this->getDisplayName())
            ->setDescription( $this->getDisplayName() . " " . get_string('generatedbygt', 'block_gradetracker'))
            ->setCustomProperty( "GT-DATASHEET-TYPE" , "CLASS", 's')
            ->setCustomProperty( "GT-DATASHEET-DOWNLOADED" , time(), 'i');

        $formats = array();
        $formats['blank'] = $objPHPExcel->add_format(['bg_color' => '#EDEDED']);
        $formats['centre'] = $objPHPExcel->add_format(['align' => 'center']);

        $QualStructure = $this->getStructure();

        if (!$QualStructure->isLevelEnabled("Units") || ($assessmentView == 1 && $this->getAssessments()) ) {

            $objPHPExcel->getSpreadsheet()->getProperties()->setCustomProperty("GT-DATASHEET-ASSESSMENT-VIEW", 1, 'i');

            $sheets = array();
            $sheets['main'] = $objPHPExcel->addWorksheet( get_string('grades', 'block_gradetracker') );
            $sheets['comments'] = $objPHPExcel->addWorksheet( get_string('comments', 'block_gradetracker') );

            $rowNum = 0;

            // The student list
            $students = $this->getUsers("STUDENT", $courseID, $groupID);

            // Get the assessments on this qual
            $assessments = $this->getAssessments();

            foreach ($sheets as $sheet) {
                $sheet->writeString($rowNum, 'A', 'SID');
                $sheet->writeString($rowNum, 'B', get_string('firstname'));
                $sheet->writeString($rowNum, 'C', get_string('lastname'));
                $sheet->writeString($rowNum, 'D', get_string('username'));
            }

            $letter = 'E';
            $commentsLetter = 'E';
            $letterAfterStudCols = $letter;

            // Default colspan to use
            $defaultColspan = 1;
            if ($GT->getSetting('use_assessments_comments') == 1) {
                $defaultColspan++;
            }

            // Custom Form Fields
            $customFieldsArray = array();
            $colspanArray = array();
            if ($assessments) {

                foreach ($assessments as $ass) {

                    $colspan = $defaultColspan;

                    // Does the assessment have CETA enabled?
                    if ($this->isFeatureEnabledByName('cetagrades') && $ass->isCetaEnabled()) {
                        $colspan++;
                    }

                    // Does this assessment have any custom fields on it?
                    $fields = $ass->getEnabledCustomFormFields();
                    $customFieldsArray[$ass->getID()] = $fields;

                    $colspan += count($fields);

                    // Comments column
                    $colspanArray[$ass->getID()] = $colspan;

                }

            }

            // Display assessment names along the top
            if ($assessments) {

                foreach ($assessments as $assessment) {

                    $oldLetter = $letter;
                    $sheets['main']->writeString($rowNum, $letter, "[{$assessment->getID()}] " . $assessment->getName());

                    // If using CETA or Custom Form Fields, we will need to merge cells
                    if ( ($this->isFeatureEnabledByName('cetagrades') && $assessment->isCetaEnabled()) || array_key_exists($assessment->getID(), $customFieldsArray) ) {

                        // Custom Form Fields
                        if (array_key_exists($assessment->getID(), $customFieldsArray)) {
                            foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                $letter++;
                            }
                        }

                        // CETA Column
                        if ($this->isFeatureEnabledByName('cetagrades') && $assessment->isCetaEnabled()) {
                            $letter++;
                        }

                        // Merge them
                        $sheets['main']->mergeCells($rowNum, $oldLetter, $rowNum, $letter);
                        $sheets['main']->applyRangeFormat($oldLetter, $rowNum, $letter, $rowNum, $formats['centre']);

                    }

                    // Move forward one for the next assessment if there is one
                    $letter++;

                    // Comments worksheet
                    $sheets['comments']->writeString($rowNum, $commentsLetter, "[{$assessment->getID()}] " . $assessment->getName());
                    $commentsLetter++;

                }

            }

            // Then display the Grade/Ceta row if ceta is enabled, or using any custom form fields
            if ($this->isFeatureEnabledByName('cetagrades') || $customFieldsArray) {

                $letter = $letterAfterStudCols;
                $rowNum++;

                if ($assessments) {

                    foreach ($assessments as $assessment) {

                        // Custom Form Fields first
                        if (array_key_exists($assessment->getID(), $customFieldsArray) && $customFieldsArray[$assessment->getID()]) {
                            foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                $sheets['main']->writeString($rowNum, $letter, "[{$field->getID()}] " . $field->getName());
                                $letter++;
                            }
                        }

                        // Then Grade column
                        $sheets['main']->writeString($rowNum, $letter, get_string('grade', 'block_gradetracker'));
                        $letter++;

                        // Then CETA column
                        if ($assessment->isCetaEnabled()) {
                            $sheets['main']->writeString($rowNum, $letter, get_string('ceta', 'block_gradetracker'));
                            $letter++;
                        }

                    }

                }

            }

            $rowNum++;

            // Loop through students
            if ($students) {
                foreach ($students as $student) {

                    $this->loadStudent($student);

                    foreach ($sheets as $sheet) {
                        $sheet->writeString($rowNum, 'A', $student->id);
                        $sheet->writeString($rowNum, 'B', $student->firstname);
                        $sheet->writeString($rowNum, 'C', $student->lastname);
                        $sheet->writeString($rowNum, 'D', $student->username);
                    }

                    $letter = 'E';
                    $commentsLetter = 'E';

                    // Assessments
                    if ($assessments) {

                        foreach ($assessments as $assessment) {

                            $assessment->loadStudent($student);

                            // Custom Form Fields first
                            if (array_key_exists($assessment->getID(), $customFieldsArray) && $customFieldsArray[$assessment->getID()]) {
                                foreach ($customFieldsArray[$assessment->getID()] as $field) {
                                    $assessment->getExcelCustomFormFieldCell($sheets['main'], $rowNum, $letter, $field);
                                    $letter++;
                                }
                            }

                            // Then Grade column
                            $assessment->getExcelGradeCell($sheets['main'], $rowNum, $letter);
                            $letter++;

                            // Then CETA column
                            if ($assessment->isCetaEnabled()) {
                                $assessment->getExcelCetaCell($sheets['main'], $rowNum, $letter);
                                $letter++;
                            }

                            // Comments sheet
                            $sheets['comments']->writeString($rowNum, $commentsLetter, $assessment->getUserComments());
                            $commentsLetter++;

                        }

                    }

                    $rowNum++;

                }

            }

        } else if ($this->getUnits()) {

            $unitNum = 0;

            foreach ($this->units as $unit) {

                $unitName = "(".$unit->getID() . ")" . \gt_strip_chars_non_alpha($unit->getName());
                $cnt = strlen($unitName);
                $diff = 30 - $cnt;
                if ($diff < 0) {
                    $unitName = substr($unitName, 0, $diff);
                }

                // Set current sheet
                $sheet = $objPHPExcel->addWorksheet($unitName);

                // User & Criteria headers
                $students = $unit->getUsers("STUDENT", false, $courseID, $groupID);
                $criteria = $unit->getHeaderCriteriaNames();

                $rowNum = 0;

                $sheet->writeString($rowNum, 'A', 'ID');
                $sheet->writeString($rowNum, 'B', get_string('firstname'));
                $sheet->writeString($rowNum, 'C', get_string('lastname'));
                $sheet->writeString($rowNum, 'D', get_string('username'));

                $letter = 'E';

                if ($criteria) {

                    foreach ($criteria as $criterion) {

                        $sheet->writeString($rowNum, $letter, $criterion['name']);;
                        $letter++;

                        if (isset($criterion['sub']) && $criterion['sub']) {
                            foreach ($criterion['sub'] as $sub) {
                                $subName = (isset($sub['name'])) ? $sub['name'] : $sub;
                                $sheet->writeString($rowNum, $letter, $subName);;
                                $letter++;
                            }
                        }

                    }

                }

                $rowNum++;

                if ($students) {

                    foreach ($students as $student) {

                        $unit->loadStudent($student);

                        $sheet->writeString($rowNum, 'A', $student->id);
                        $sheet->writeString($rowNum, 'B', $student->firstname);
                        $sheet->writeString($rowNum, 'C', $student->lastname);
                        $sheet->writeString($rowNum, 'D', $student->username);

                        $letter = 'E';

                        if ($criteria) {
                            foreach ($criteria as $crit) {

                                $criterion = $unit->getCriterionByName($crit['name']);
                                $criterion->getExcelCell($sheet, $rowNum, $letter);
                                $letter++;

                                if (isset($crit['sub']) && $crit['sub']) {
                                    foreach ($crit['sub'] as $sub) {
                                        $subName = (isset($sub['name'])) ? $sub['name'] : $sub;
                                        $subCriterion = $unit->getCriterionByName($subName);
                                        $subCriterion->getExcelCell($sheet, $rowNum, $letter);
                                        $letter++;
                                    }
                                }

                            }
                        }

                        $rowNum++;

                    }

                }

                $unitNum++;

            }

        }

        // Serve the spreadsheet.
        $objPHPExcel->serve();
        exit;

    }

}
