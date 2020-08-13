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
 * Class for dealing with Unit Awards
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class UnitAward {

    private $id = false;
    private $gradingStructureID;
    private $name;
    private $shortname;
    private $points;
    private $pointsLower;
    private $pointsUpper;

    private $errors = array();

    public function __construct($id = false) {

        global $DB;

        if ($id) {

            $record = $DB->get_record("bcgt_unit_awards", array("id" => $id));
            if ($record) {

                $this->id = $record->id;
                $this->gradingStructureID = $record->gradingstructureid;
                $this->name = $record->name;
                $this->shortname = $record->shortname;
                $this->points = $record->points;
                $this->pointsLower = $record->pointslower;
                $this->pointsUpper = $record->pointsupper;

            }

        } else {
            $this->name = get_string('notattempted', 'block_gradetracker');
            $this->shortname = get_string('na', 'block_gradetracker');
        }

    }

    public function isValid() {
        return ($this->id !== false);
    }

    /**
     * Since units can only have met awards, just return if it's a valid award, so it must be met
     * @return type
     */
    public function isMet() {
        return ($this->isValid());
    }

    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
        return $this;
    }

    public function getGradingStructureID() {
        return $this->gradingStructureID;
    }

    public function setGradingStructureID($id) {
        $this->gradingStructureID = $id;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = trim($name);
        return $this;
    }

    public function getShortName() {
        return $this->shortname;
    }

    public function setShortName($name) {
        $this->shortname = trim($name);
        return $this;
    }

    public function getPoints() {
        return $this->points;
    }

    public function setPoints($points) {
        $this->points = $points;
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

    public function getErrors() {
        return $this->errors;
    }

    /**
     * Check the award has no errors
     * @return type
     */
    public function hasNoErrors() {

        // Name
        if (strlen($this->name) == 0) {
            $this->errors[] = get_string('errors:gradestructures:awards:name', 'block_gradetracker');
        }

        // Shortname - If not set, set to first letter of name
        if (strlen($this->shortname) == 0) {
            $this->shortname = strtoupper( substr($this->name, 0, 1) );
        }

        // Points
        if ($this->points == '') {
            $this->errors[] = get_string('errors:gradestructures:awards:points', 'block_gradetracker');
        }

        return (!$this->errors);

    }

    /**
     * Save the unit award
     * @global type $DB
     * @return boolean
     */
    public function save() {

        global $DB;

        $obj = new \stdClass();

        if ($this->isValid()) {
            $obj->id = $this->id;
        }

        $obj->gradingstructureid = $this->gradingStructureID;
        $obj->name = $this->name;
        $obj->shortname = $this->shortname;
        $obj->points = $this->points;
        $obj->pointslower = $this->pointsLower;
        $obj->pointsupper = $this->pointsUpper;

        if ($this->isValid()) {
            $result = $DB->update_record("bcgt_unit_awards", $obj);
        } else {
            $this->id = $DB->insert_record("bcgt_unit_awards", $obj);
            $result = $this->id;
        }

        if (!$result) {
            $this->errors[] = get_string('errors:save', 'block_gradetracker');
            return false;
        }

        return true;

    }

}
