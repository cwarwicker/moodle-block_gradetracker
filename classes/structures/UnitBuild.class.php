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
 * This is not a real object, there is no actual UnitBuild
 *
 * This is instead used as a class to define a static method for getting the attributes for units
 * based on their structureID & levelID combination, which for the sake of consistency we are
 * calling a Unit Build here
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class UnitBuild {

    private $structureID;
    private $levelID;

    public function setStructureID($id) {
        $this->structureID = $id;
        return $this;
    }

    public function setLevelID($id) {
        $this->levelID = $id;
        return $this;
    }

    /**
     * Get an attribute for this combination
     * @global type $DB
     * @param type $attribute
     * @return boolean
     */
    public function getAttribute($attribute) {

        global $DB;

        if (is_null($this->structureID) || is_null($this->levelID)) {
            return false;
        }

        $record = $DB->get_record("bcgt_unit_build_attributes", array("qualstructureid" => $this->structureID, "levelid" => $this->levelID, "attribute" => $attribute));
        return ($record) ? $record->value : false;

    }

    /**
     * Update an attribute
     * @global \block_gradetracker\type $DB
     * @param type $attribute
     * @param type $value
     */
    public function updateAttribute($attribute, $value) {

        global $DB;

        $check = $DB->get_record("bcgt_unit_build_attributes", array("qualstructureid" => $this->structureID, "levelid" => $this->levelID, "attribute" => $attribute));
        if ($check) {
            $check->value = $value;
            $DB->update_record("bcgt_unit_build_attributes", $check);
        } else {
            $ins = new \stdClass();
            $ins->qualstructureid = $this->structureID;
            $ins->levelid = $this->levelID;
            $ins->attribute = $attribute;
            $ins->value = $value;
            $DB->insert_record("bcgt_unit_build_attributes", $ins);
        }

    }

    /**
     * Get all default values for this build
     * @global \block_gradetracker\type $DB
     * @return type
     */
    public function getAllDefaultValues() {

        global $DB;

        $return = array();
        $records = $DB->get_records_select("bcgt_unit_build_attributes", "qualstructureid = ? AND levelid = ? AND attribute LIKE 'default_%'", array($this->structureID, $this->levelID));
        if ($records) {
            foreach ($records as $record) {
                $id = str_replace("default_", "", $record->attribute);
                $return[$id] = $record->value;
            }
        }

        return $return;

    }

    /**
     * Load the structure id and level id into the object
     * @param type $structureID
     * @param type $levelID
     * @return \block_gradetracker\UnitBuild
     */
    public static function load($structureID, $levelID) {

        $obj = new \block_gradetracker\UnitBuild();
        $obj->setStructureID($structureID);
        $obj->setLevelID($levelID);
        return $obj;

    }

}
