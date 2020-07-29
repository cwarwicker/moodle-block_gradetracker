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
 * The class for Qualification Sub Types, e.g. Diploma, Certificate, Award, etc...
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') or die();

class SubType {

    private $id = false;
    private $name;
    private $shortname;
    private $deleted = 0;

    private $errors = array();

    public function __construct($id = false) {

        global $DB;

        if ($id) {

            $record = $DB->get_record("bcgt_qual_subtypes", array("id" => $id));
            if ($record) {

                $this->id = $record->id;
                $this->name = $record->name;
                $this->shortname = $record->shortname;
                $this->deleted = $record->deleted;

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

    /**
     * Get the subtype id
     * @return type
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Get the subtype name
     * @return type
     */
    public function getName() {
        return \gt_html($this->name);
    }

    /**
     * Get the subtype shortname
     * @return type
     */
    public function getShortName() {
        return \gt_html($this->shortname);
    }

    /**
     * Get the deleted value
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
        $this->shortname = trim($name);
        return $this;
    }

    public function setDeleted($val) {
        $this->deleted = $val;
        return $this;
    }

    public function countQualifications() {

        global $DB;
        $results = $DB->get_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
        $count = 0;
        foreach ($results as $result) {
            $count += $DB->count_records("bcgt_qualifications", array("buildid" => $result->id, "deleted" => 0));
        }
        return $count;
    }


    public function countQualificationBuilds() {
        global $DB;
        return $DB->count_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
    }

    public function delete() {

        global $DB;

        $this->deleted = 1;
        $this->save();

        $qual_builds = $DB->get_records("bcgt_qual_builds", array("subtypeid" => $this->id, "deleted" => 0));
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
            $obj->shortname = $this->shortname;
            $obj->deleted = $this->deleted;
            return $DB->update_record("bcgt_qual_subtypes", $obj);

        } else {

            $obj = new \stdClass();
            $obj->name = $this->name;
            $obj->shortname = $this->shortname;
            $obj->deleted = 0;
            $this->id = $DB->insert_record("bcgt_qual_subtypes", $obj);
            return $this->id;

        }

    }

    /**
     * Check to make sure it has no errors
     * @return type
     */
    public function hasNoErrors() {

        if (empty($this->name)) {
            $this->errors[] = get_string('errors:qualsubtype:name', 'block_gradetracker');
        }

        if (empty($this->shortname)) {
            $this->errors[] = get_string('errors:qualsubtype:shortname', 'block_gradetracker');
        }

        return (!$this->errors);

    }

    public function loadPostData() {

        $settings = array(
            'subtype_name' => optional_param('subtype_name', false, PARAM_TEXT),
            'subtype_shortname' => optional_param('subtype_shortname', false, PARAM_TEXT),
            'subtype_id' => optional_param('subtype_id', false, PARAM_INT),
            'subtype_deleted' => optional_param('subtype_deleted', false, PARAM_INT),
        );

        $name = $settings['subtype_name'];
        $shortName = $settings['subtype_shortname'];
        $deleted = 0;

        if ($settings['subtype_id']) {
            $this->setID($settings['subtype_id']);
        }

        if ($settings['subtype_deleted']) {
            $deleted = $settings['subtype_deleted'];
        }

        $this->setName($name);
        $this->setShortName($shortName);
        $this->setDeleted($deleted);

    }

    /**
     * Get all the defined subtypes
     * @global type $DB
     * @return type
     */
    public static function getAllSubTypes() {

        global $DB;

        $records = $DB->get_records("bcgt_qual_subtypes", array("deleted" => 0), "name ASC", "id");

        $return = array();
        if ($records) {
            foreach ($records as $record) {
                $return[] = new \GT\SubType($record->id);
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

        $record = $DB->get_record("bcgt_qual_subtypes", array("name" => $name, "deleted" => 0));
        return ($record) ? new \GT\SubType($record->id) : false;

    }



}
