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
 * The class that deals with the awards that can be given to qualifications, and to target grades
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class QualificationAward {

    private $id = false;
    private $buildID;
    private $name;
    private $rank;
    private $pointsLower;
    private $pointsUpper;
    private $ucas;
    private $qoeLower;
    private $qoeUpper;
    private $type;

    private $errors = array();

    /**
     * Construct the Qualification Award object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false, $type = false) {

        global $DB;

        if ($id) {

            $record = $DB->get_record("bcgt_qual_build_awards", array("id" => $id));
            if ($record) {

                $this->id = $record->id;
                $this->buildID = $record->buildid;
                $this->name = $record->name;
                $this->rank = $record->rank;
                $this->pointsLower = $record->pointslower;
                $this->pointsUpper = $record->pointsupper;
                $this->ucas = $record->ucas;
                $this->qoeLower = $record->qoescorelower;
                $this->qoeUpper = $record->qoescoreupper;
                $this->type = $type;

            }

        }

    }

    /**
     * Is it a valid DB record?
     * @return type
     */
    public function isValid() {
        return ($this->id !== false);
    }

    public function getID() {
        return $this->id;
    }

    public function getBuildID() {
        return $this->buildID;
    }

    public function setBuildID($id) {
        $this->buildID = $id;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = trim($name);
        return $this;
    }

    /**
     * Use this as the Name if the award is not valid
     * @param type $name
     */
    public function setDefaultName($name) {
        if (!$this->isValid()) {
            $this->name = $name;
        }
    }

    public function getUcas() {
        return $this->ucas;
    }

    public function setUcas($ucas) {
        $this->ucas = $ucas;
        return $this;
    }

    public function getPointsLower() {
        return $this->pointsLower;
    }

    public function setPointsLower($points) {
        $this->pointsLower = $points;
        return $this;
    }

    public function getPointsUpper() {
        return $this->pointsUpper;
    }

    public function setPointsUpper($points) {
        $this->pointsUpper = $points;
        return $this;
    }

    public function getRank() {
        return $this->rank;
    }

    public function setRank($rank) {
        $this->rank = $rank;
        return $this;
    }

    public function getQOELower() {
        return $this->qoeLower;
    }

    public function setQOELower($score) {
        $this->qoeLower = $score;
        return $this;
    }

    public function getQOEUpper() {
        return $this->qoeUpper;
    }

    public function setQOEUpper($score) {
        $this->qoeUpper = $score;
        return $this;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getType() {
        return $this->type;
    }

    /**
     * Check the award has no errors
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function hasNoErrors() {

        global $DB;

        $submission = array(
            'update' => optional_param('update', false, PARAM_TEXT),
        );

        // If no build has been specified
        if (is_null($this->buildID)) {
            $this->errors[] = get_string('errors:qualawards:buildid', 'block_gradetracker');
        }

        // If no name specified
        if (strlen($this->name) == 0) {
            $this->errors[] = get_string('errors:qualawards:name', 'block_gradetracker');
        }

        // If name already exists
        $check = $DB->get_record("bcgt_qual_build_awards", array("buildid" => $this->buildID, "name" => $this->name));
        if ($check && $check->id <> $this->id) {
            if ($submission['update'] && $this->id == false) {
                $this->id = $check->id;
            } else {
                $this->errors[] = get_string('errors:qualawards:name:duplicate', 'block_gradetracker', $this->name);
            }
        }

        // CHeck precision
        if ($this->pointsLower && $this->pointsLower > 99999.99) {
            $this->errors[] = sprintf( get_string('errors:qualawards:precision', 'block_gradetracker'), $this->pointsLower, 99999.99 );
        }

        if ($this->pointsUpper && $this->pointsUpper > 99999.99) {
            $this->errors[] = sprintf( get_string('errors:qualawards:precision', 'block_gradetracker'), $this->pointsUpper, 99999.99 );
        }

        if ($this->qoeLower && $this->qoeLower > 999.99) {
            $this->errors[] = sprintf( get_string('errors:qualawards:precision', 'block_gradetracker'), $this->qoeLower, 999.99 );
        }

        if ($this->qoeUpper && $this->qoeUpper > 999.99) {
            $this->errors[] = sprintf( get_string('errors:qualawards:precision', 'block_gradetracker'), $this->qoeUpper, 999.99 );
        }

        if ($this->ucas && $this->ucas > 999.9) {
            $this->errors[] = sprintf( get_string('errors:qualawards:precision', 'block_gradetracker'), $this->ucas, 999.99 );
        }

        return (!$this->errors);

    }

    /**
     * Save a Qual Build Award
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function save() {

        global $DB;

        $obj = new \stdClass();

        if ($this->isValid()) {
            $obj->id = $this->id;
        }

        $obj->buildid = $this->buildID;
        $obj->name = $this->name;
        $obj->ucas = $this->ucas;
        $obj->pointslower = $this->pointsLower;
        $obj->pointsupper = $this->pointsUpper;
        $obj->rank = $this->rank;
        $obj->qoescorelower = $this->qoeLower;
        $obj->qoescoreupper = $this->qoeUpper;

        if ($this->isValid()) {
            return $DB->update_record("bcgt_qual_build_awards", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_qual_build_awards", $obj);
            return $this->id;
        }

    }

    /**
     * Delete the award
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function delete() {

        global $DB;
        return $DB->delete_records("bcgt_qual_build_awards", array("id" => $this->id));

    }

    /**
     *
     * @global \block_gradetracker\type $DB
     * @param type $buildID
     * @param type $name
     */
    public static function findAwardByName($buildID, $name) {

        global $DB;

        $record = $DB->get_record("bcgt_qual_build_awards", array("buildid" => $buildID, "name" => $name), "id");
        return ($record) ? new \block_gradetracker\QualificationAward($record->id) : false;

    }

}
