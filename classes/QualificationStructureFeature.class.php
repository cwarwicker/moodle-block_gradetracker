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
 * The class that defines a feature of a qualification structure
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') or die();

class QualificationStructureFeature {

    private $id = false;
    private $name;

    /**
     * Construct the feature object
     * @global type $DB
     * @param type $id
     */
    public function __construct($id = false, $name = false) {

        global $DB;

        $record = false;

        if ($id) {

            $record = $DB->get_record("bcgt_qual_structure_features", array("id" => $id));

        } else if ($name) {

            $record = $DB->get_record("bcgt_qual_structure_features", array("name" => $name));

        }

        if ($record) {

            $this->id = $record->id;
            $this->name = $record->name;

        }

    }

    public function isValid() {
        return ($this->id !== false);
    }

    public function getID() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

}
