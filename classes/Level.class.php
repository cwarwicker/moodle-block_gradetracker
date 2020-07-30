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
 * The class for Qualification Levels, e.g. Level 1, Level 2, Level 3, etc...
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') or die();

class Level {

    private $id = false;
    private $name;
    private $shortName;
    private $ordernum;
    private $deleted = 0;
    private $errors = array();

    public function __construct($id = false) {

        global $DB;

        if ($id) {

            $record = $DB->get_record("bcgt_qual_levels", array("id" => $id));
            if ($record) {

                $this->id = $record->id;
                $this->name = $record->name;
                $this->shortName = $record->shortname;
                $this->ordernum = $record->ordernum;
                $this->deleted = $record->deleted;

            }

        }

    }

    /**
     * Is it a valid record from the DB?
     * @return type
     */
    public function isValid() {
        return ($this->id !== false);
    }

    public function isDeleted() {
        return ($this->deleted == 1);
    }

    /**
     * Get the id of the level
     * @return type
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Get the name of the level
     * @return type
     */
    public function getName() {
        return \gt_html($this->name);
    }

    /**
     * Get the short name of the level
     * @return type
     */
    public function getShortName() {
        return \gt_html($this->shortName);
    }

    /**
     * Get the order number of the level
     * @return type
     */
    public function getOrderNumber() {
        return $this->ordernum;
    }

    /**
     * Get the value of deleted
     * @return type
     */
    public function getDeleted() {
        return $this->deleted;
    }

    public function getErrors() {
        return $this->errors;
    }


    public function setID($id) {
        $this->id = $id;
        return $this;
    }

    public function setName($name) {
        $this->name = trim($name);
        return $this;
    }

    public function setShortName($name) {
        $this->shortName = trim($name);
        return $this;
    }

    public function setOrderNum($num) {
        $this->ordernum = trim($num);
        return $this;
    }

    public function setDeleted($val) {
        $this->deleted = $val;
        return $this;
    }

    public function countQualifications() {

        // todo check if conn wants quals or qual_builds
        global $DB;
        $results = $DB->get_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
        $count = 0;
        foreach ($results as $result) {
            $count += $DB->count_records("bcgt_qualifications", array("buildid" => $result->id, "deleted" => 0));
        }
        return $count;
    }


    public function countQualificationBuilds() {
        global $DB;
        return $DB->count_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
    }


    public function delete() {

        global $DB;

        $this->deleted = 1;
        $this->save();

        $qual_builds = $DB->get_records("bcgt_qual_builds", array("levelid" => $this->id, "deleted" => 0));
        foreach ($qual_builds as $build) {
            $qual_build = new \GT\QualificationBuild($build->id);
            $qual_build->delete();
        }

        return true;

    }

    /**
     * Save the level
     * @global type $DB
     * @return type
     */
    public function save() {

        global $DB;

        if ($this->isValid()) {

            $obj = new \stdClass();
            $obj->id = $this->id;
            $obj->name = $this->name;
            $obj->shortname = $this->shortName;
            $obj->ordernum = $this->ordernum;
            $obj->deleted = $this->deleted;
            return $DB->update_record("bcgt_qual_levels", $obj);

        } else {

            $obj = new \stdClass();
            $obj->name = $this->name;
            $obj->shortname = $this->shortName;
            $obj->ordernum = $this->ordernum;
            $obj->deleted = 0;
            $this->id = $DB->insert_record("bcgt_qual_levels", $obj);
            return $this->id;

        }

    }

    /**
     * Check to make sure it has no errors
     * @return type
     */
    public function hasNoErrors() {

        if (empty($this->name)) {
            $this->errors[] = get_string('errors:quallevels:name', 'block_gradetracker');
        }

        if (empty($this->shortName)) {
            $this->errors[] = get_string('errors:quallevels:shortname', 'block_gradetracker');
        }

        if (!is_numeric($this->ordernum)) {
            $this->errors[] = get_string('errors:quallevels:order', 'block_gradetracker');
        }

        return (!$this->errors);

    }

    public function loadPostData() {

        $settings = array(
            'level_name' => optional_param('level_name', false, PARAM_TEXT),
            'level_shortname' => optional_param('level_shortname', false, PARAM_TEXT),
            'level_order' => optional_param('level_order', false, PARAM_TEXT),
            'level_id' => optional_param('level_id', false, PARAM_INT),
            'level_deleted' => optional_param('level_deleted', false, PARAM_INT),
        );

        $name = $settings['level_name'];
        $shortName = $settings['level_shortname'];
        $order = $settings['level_order'];
        $deleted = 0;

        if ($settings['level_id']) {
            $this->setID($settings['level_id']);
        }

        if ($settings['level_deleted']) {
            $deleted = $settings['level_deleted'];
        }

        $this->setName($name);
        $this->setShortName($shortName);
        $this->setOrderNum($order);
        $this->setDeleted($deleted);

    }



    /**
     * Get all the possible levels for a structure, based on the builds available
     * @global \GT\type $DB
     * @param type $structureID
     * @return \GT\Level
     */
    public static function getAllStructureLevels($structureID) {

        global $DB;

        $return = array();
        $records = $DB->get_records_sql("SELECT DISTINCT levelid
                                         FROM {bcgt_qual_builds}
                                         WHERE structureid = ? AND deleted = 0", array($structureID));

        if ($records) {
            foreach ($records as $record) {
                $return[] = new \GT\Level($record->levelid);
            }
        }

        return $return;

    }

    /**
     * Get all the defined levels
     * @global type $DB
     * @return type
     */
    public static function getAllLevels() {

        global $DB;

        $records = $DB->get_records("bcgt_qual_levels", array("deleted" => 0), "ordernum ASC, name ASC", "id");

        $return = array();
        if ($records) {
            foreach ($records as $record) {
                $return[] = new \GT\Level($record->id);
            }
        }

        return $return;

    }

    /**
     * Find a level by its name
     * @global \GT\type $DB
     * @param type $name
     * @return type
     */
    public static function findByName($name) {

        global $DB;

        $record = $DB->get_record("bcgt_qual_levels", array("name" => $name, "deleted" => 0));
        return ($record) ? new \GT\Level($record->id) : false;

    }

}