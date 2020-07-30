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
 * This class deals with Module Linking. Defining the database tables and columns so we can link up the GT
 * to activities of this Module. E.g. Assignment or Turnitin
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') or die();

class ModuleLink {

    public static $supportedMods = array('assign');

    private $id = false;
    private $modID;
    private $modTable;
    private $partTable;
    private $partModCol;
    private $modCourseCol;
    private $modStartCol;
    private $modDueCol;
    private $modTitleCol;
    private $partTitleCol;
    private $subTable;
    private $subPartCol;
    private $subModCol;
    private $subUserCol;
    private $subDateCol;
    private $subStatusCol;
    private $subStatusVal;
    private $auto;
    private $enabled;
    private $deleted;

    private $courseModID;
    private $recordID = false;
    private $record = false;
    private $recordParts = false;

    private $errors = array();

    public function __construct($id = false) {

        global $DB;

        if ($id) {

            $record = $DB->get_record("bcgt_mods", array("id" => $id));
            if ($record) {

                $this->id = $record->id;
                $this->modID = $record->modid;
                $this->modTable = $record->modtable;
                $this->partTable = $record->parttable;
                $this->partModCol = $record->partmodcol;
                $this->modCourseCol = $record->modcoursecol;
                $this->modStartCol = $record->modstartcol;
                $this->modDueCol = $record->modduecol;
                $this->modTitleCol = $record->modtitlecol;
                $this->partTitleCol = $record->parttitlecol;
                $this->subTable = $record->submissiontable;
                $this->subPartCol = $record->submissionpartcol;
                $this->subModCol = $record->submissionmodcol;
                $this->subUserCol = $record->submissionusercol;
                $this->subDateCol = $record->submissiondatecol;
                $this->subStatusCol = $record->submissionstatuscol;
                $this->subStatusVal = $record->submissionstatusval;
                $this->auto = $record->auto;
                $this->enabled = $record->enabled;
                $this->deleted = $record->deleted;

            }

        }

    }

    public function isValid() {
        return ($this->id !== false && !$this->isDeleted());
    }

    public function isDeleted() {
        return ($this->deleted == 1);
    }

    public function isEnabled() {
        return ($this->enabled == 1);
    }

    public function hasAutoUpdates() {
        return ($this->auto == 1);
    }

    public function hasParts() {
        return (!is_null($this->partTable));
    }

    public function getID() {
        return $this->id;
    }

    public function getModID() {
        return $this->modID;
    }

    public function getModName() {

        global $DB;

        if (isset($this->modName)) {
            return $this->modName;
        } else {

            $record = $DB->get_record("modules", array("id" => $this->modID));
            if ($record) {
                $this->modName = $record->name;
                return $this->modName;
            } else {
                return false;
            }

        }

    }

    public function setModID($id) {
        $this->modID = $id;
        return $this;
    }

    public function getModTable() {
        return addslashes($this->modTable);
    }

    public function setModTable($value) {
        $this->modTable = trim($value);
        return $this;
    }

    public function getPartTable() {
        return addslashes($this->partTable);
    }

    public function setPartTable($value) {
        $this->partTable = (is_null($value)) ? $value : trim($value);
        return $this;
    }

    public function getPartModCol() {
        return addslashes($this->partModCol);
    }

    public function setPartModCol($value) {
        $this->partModCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }

    public function getModCourseCol() {
        return addslashes($this->modCourseCol);
    }

    public function setModCourseCol($value) {
        $this->modCourseCol = trim($value);
        return $this;
    }

    public function getModStartCol() {
        return addslashes($this->modStartCol);
    }

    public function setModStartCol($value) {
        $this->modStartCol = trim($value);
        return $this;
    }

    public function getModDueCol() {
        return addslashes($this->modDueCol);
    }

    public function setModDueCol($value) {
        $this->modDueCol = trim($value);
        return $this;
    }

    public function getModTitleCol() {
        return addslashes($this->modTitleCol);
    }

    public function setModTitleCol($value) {
        $this->modTitleCol = trim($value);
        return $this;
    }

    public function getPartTitleCol() {
        return addslashes($this->partTitleCol);
    }

    public function setPartTitleCol($value) {
        $this->partTitleCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }

    public function getSubTable() {
        return addslashes($this->subTable);
    }

    public function setSubTable($value) {
        $this->subTable = trim($value);
        return $this;
    }

    public function getSubPartCol() {
        return addslashes($this->subPartCol);
    }

    public function setSubPartCol($value) {
        $this->subPartCol = (is_null($value)) ? $value : trim($value);
        return $this;
    }

    public function getSubModCol() {
        return addslashes($this->subModCol);
    }

    public function setSubModCol($value) {
        $this->subModCol = trim($value);
        return $this;
    }

    public function getSubUserCol() {
        return addslashes($this->subUserCol);
    }

    public function setSubUserCol($value) {
        $this->subUserCol = trim($value);
        return $this;
    }

    public function getSubDateCol() {
        return addslashes($this->subDateCol);
    }

    public function setSubDateCol($value) {
        $this->subDateCol = trim($value);
        return $this;
    }

    public function getSubStatusCol() {
        return addslashes($this->subStatusCol);
    }

    public function setSubStatusCol($value) {
        $this->subStatusCol = $value;
        return $this;
    }

    public function getSubStatusVal() {
        return addslashes($this->subStatusVal);
    }

    public function setSubStatusVal($value) {
        $this->subStatusVal = $value;
        return $this;
    }

    public function getAuto() {
        return $this->auto;
    }

    public function setAuto($value) {
        $this->auto = trim($value);
        return $this;
    }

    public function getEnabled() {
        return $this->enabled;
    }

    public function setEnabled($value) {
        $this->enabled = $value;
        return $this;
    }

    public function getDeleted() {
        return $this->deleted;
    }

    public function setDeleted($value) {
        $this->deleted = $value;
        return $this;
    }

    /**
     * Get the icon of this module
     * @return boolean
     */
    public function getModIcon() {

        global $CFG, $DB;

        if (!$this->record) {
            return false;
        }

        $record = $DB->get_record("modules", array("id" => $this->modID));
        if ($record) {

            $icon = $CFG->dirroot . '/mod/' . $record->name . '/pix/icon.png';
            if (file_exists($icon)) {
                return str_replace($CFG->dirroot, $CFG->wwwroot, $icon);
            }

        }

        return $CFG->wwwroot . '/blocks/gradetracker/pix/no_image.jpg';

    }

    public function setCourseModID($id) {
        $this->courseModID = $id;
        return $this;
    }

    public function getCourseModID() {
        return $this->courseModID;
    }

    public function getRecordID() {
        return $this->recordID;
    }

    public function clearRecords() {
        $this->recordID = false;
        $this->record = false;
        $this->recordParts = false;
    }

    public function setRecordID($id) {

        global $DB;

        $this->recordID = $id;
        $this->record = $DB->get_record($this->modTable, array("id" => $this->recordID));

        if ($this->partTable && $this->partModCol) {
            $this->partTitleCol = addslashes($this->partTitleCol);
            $this->recordParts = $DB->get_records($this->partTable, array($this->partModCol => $this->recordID), false, "*, {$this->partTitleCol} as name");
        }

        return $this;
    }

    /**
     * Get the name of the record instance of this mod
     * @return boolean
     */
    public function getRecordName() {

        if (!$this->record) {
            return false;
        }

        $field = $this->modTitleCol;
        return (isset($this->record->$field)) ? $this->record->$field : false;

    }

    /**
     * Get the due date of the record instance of this mod
     * @param type $format
     * @return boolean
     */
    public function getRecordDueDate($format = false, $partID = false) {

        if (!$this->record) {
            return false;
        }

        $field = $this->modDueCol;

        // Are we getting the due date of a part of the activity?
        if ($partID) {

            if (isset($this->recordParts[$partID])) {
                $part = $this->recordParts[$partID];
                return (isset($part->$field)) ? ( ($format) ? date($format, $part->$field) : $part->$field) : false;
            }

        } else {
            return (isset($this->record->$field)) ? ( ($format) ? date($format, $this->record->$field) : $this->record->$field) : false;
        }

        return false;

    }

    /**
     * Get the parts of this record instance, if it has any
     * e.g. turnitintool has parts stored in turnitintool_parts
     * @global \GT\type $DB
     */
    public function getRecordParts() {
        return $this->recordParts;
    }

    public function getRecordPart($id) {
        return (array_key_exists($id, $this->recordParts)) ? $this->recordParts[$id] : false;
    }


    public function getErrors() {
        return $this->errors;
    }

    public function hasNoErrors() {

        global $DB;

        // Check everything is filled out
        if (empty($this->modID) || empty($this->modTable) || empty($this->modCourseCol) || empty($this->modStartCol)
            || empty($this->modDueCol) || empty($this->modTitleCol) || empty($this->subTable) || empty($this->subUserCol)
            || empty($this->subDateCol) || empty($this->subModCol)) {
            $this->errors[] = get_string('errors:missingparams', 'block_gradetracker');
        }

        // Check this table isn't already in use
        $check = $DB->get_record_select("bcgt_mods", "modid = ? AND deleted = 0 AND id <> ?", array($this->modID, $this->id));
        if ($check) {
            $this->errors[] = get_string('modlinking:error:mod', 'block_gradetracker');
        }

        return (!$this->errors);

    }

    /**
     * Save the mod link
     * @global type $DB
     * @return type
     */
    public function save() {

        global $DB;

        if ($this->isValid()) {

            $record = new \stdClass();
            $record->id = $this->id;
            $record->modid = $this->modID;
            $record->modtable = $this->modTable;
            $record->parttable = $this->partTable;
            $record->partmodcol = $this->partModCol;
            $record->modcoursecol = $this->modCourseCol;
            $record->modstartcol = $this->modStartCol;
            $record->modduecol = $this->modDueCol;
            $record->modtitlecol = $this->modTitleCol;
            $record->parttitlecol = $this->partTitleCol;
            $record->submissiontable = $this->subTable;
            $record->submissionpartcol = $this->subPartCol;
            $record->submissionusercol = $this->subUserCol;
            $record->submissiondatecol = $this->subDateCol;
            $record->submissionmodcol = $this->subModCol;
            $record->submissionstatuscol = $this->subStatusCol;
            $record->submissionstatusval = $this->subStatusVal;
            $record->auto = $this->auto;

            return $DB->update_record("bcgt_mods", $record);

        } else {

            $record = new \stdClass();
            $record->modid = $this->modID;
            $record->modtable = $this->modTable;
            $record->parttable = $this->partTable;
            $record->partmodcol = $this->partModCol;
            $record->modcoursecol = $this->modCourseCol;
            $record->modstartcol = $this->modStartCol;
            $record->modduecol = $this->modDueCol;
            $record->modtitlecol = $this->modTitleCol;
            $record->parttitlecol = $this->partTitleCol;
            $record->submissiontable = $this->subTable;
            $record->submissionpartcol = $this->subPartCol;
            $record->submissionusercol = $this->subUserCol;
            $record->submissiondatecol = $this->subDateCol;
            $record->submissionmodcol = $this->subModCol;
            $record->submissionstatuscol = $this->subStatusCol;
            $record->submissionstatusval = $this->subStatusVal;
            $record->auto = $this->auto;

            $this->id = $DB->insert_record("bcgt_mods", $record);
            return $this->id;

        }

    }

    /**
     * Delete the mod link and any activities linked to it
     * @global \GT\type $DB
     */
    public function delete() {

        global $DB;

        $this->setDeleted(1);

        // Set this mod to deleted in the DB
        $record = new \stdClass();
        $record->id = $this->id;
        $record->deleted = $this->deleted;
        $DB->update_record("bcgt_mods", $record);

        // Then set all activity refs of this mod to deleted as well
        $refs = $DB->get_records_sql("SELECT a.id
                                        FROM {bcgt_activity_refs} a
                                        INNER JOIN {course_modules} cm ON cm.id = a.cmid
                                        INNER JOIN mdl_modules m ON m.id = cm.module
                                        WHERE m.name = ?", array($this->getModName()));

        if ($refs) {
            foreach ($refs as $ref) {
                $activity = new \GT\Activity($ref->id);
                $activity->remove();
            }
        }

        return true;

    }

    public function getQualsOnModule($partID = null) {

        $return = array();
        $quals = \GT\Activity::getQualsLinkedToCourseModule($this->courseModID, $partID);
        if ($quals) {
            foreach ($quals as $qualID) {
                $qual = new \GT\Qualification($qualID);
                if ($qual->isValid()) {
                    $return[] = $qual;
                }
            }
        }

        return $return;

    }

    public function getUnitsOnModule($qualID = false) {

        $return = array();
        $unitIDs = \GT\Activity::getUnitsLinkedToCourseModule($this->courseModID, $qualID);
        if ($unitIDs) {
            foreach ($unitIDs as $unitID) {
                $unit = new \GT\Unit($unitID);
                if ($unit->isValid()) {
                    $return[] = $unit;
                }
            }
        }

        return $return;

    }

    /**
     * Count the number of criteria linked to this module activity
     * @param type $qualID
     * @return type
     */
    public function countCriteriaOnModule($qualID = false, $unit = false, $partID = null) {

        $critIDs = \GT\Activity::getCriteriaLinkedToCourseModule($this->courseModID, $partID, $qualID);
        return count($critIDs);

    }

    /**
     * Get list of modules attached to qual unit
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $unitID
     * @return \GT\ModuleLink
     */
    public static function getModulesOnUnit($qualID, $unitID, $courseID = false) {

        global $DB;

        $return = array();
        $cmIDs = \GT\Activity::getCourseModulesLinkedToUnit($qualID, $unitID);

        if ($cmIDs) {

            foreach ($cmIDs as $cmID) {

                $courseMod = $DB->get_record("course_modules", array("id" => $cmID));
                if ($courseMod) {
                    $modLink = $DB->get_record("bcgt_mods", array("modid" => $courseMod->module));
                    if ($modLink) {

                        // If we've specified a course id, the course mod has to be on this course
                        if ($courseID) {
                            if ($courseMod->course <> $courseID) {
                                continue;
                            }
                        }

                        $mod = new \GT\ModuleLink($modLink->id);
                        $mod->setRecordID($courseMod->instance);
                        $mod->setCourseModID($courseMod->id);
                        $return[$courseMod->id] = $mod;
                    }
                }

            }

        }

        return $return;

    }

    /**
     * Get criteria on this module activity
     * @param type $qualID
     * @param type $unit
     * @return type
     */
    public function getCriteriaOnModule($qualID, $unit, $partID = null) {

        $return = array();
        $critIDs = \GT\Activity::getCriteriaLinkedToCourseModule($this->courseModID, $partID, $qualID, $unit->getID());
        if ($critIDs) {
            foreach ($critIDs as $critID) {
                $criterion = $unit->getCriterion($critID);
                if ($criterion && $criterion->isValid()) {
                    $return[] = $criterion;
                }
            }
        }

        return $return;

    }

    /**
     * Count the number of activity refs that use this module
     * @global \GT\type $DB
     * @return type
     */
    public function countActivityRefs() {

        global $DB;

        $records = $DB->get_record_sql("SELECT COUNT(a.id) as 'cnt'
                                        FROM {bcgt_activity_refs} a
                                        INNER JOIN {course_modules} cm ON cm.id = a.cmid
                                        INNER JOIN {modules} m ON m.id = cm.module
                                        WHERE m.name = ? AND a.deleted = 0", array($this->getModName()));

        return $records->cnt;

    }

    /**
     * Load data from the form
     */
    public function loadPostData() {

        $submission = array(
            'submit_mod_link' => optional_param('submit_mod_link', false, PARAM_TEXT),
        );

        $settings = array(
            'modid' => optional_param('modid', false, PARAM_INT),
            'modtable' => optional_param('modtable', false, PARAM_TEXT),
            'parttable' => optional_param('parttable', false, PARAM_TEXT),
            'partmodcol' => optional_param('partmodcol', false, PARAM_TEXT),
            'modcoursecol' => optional_param('modcoursecol', false, PARAM_TEXT),
            'modstartcol' => optional_param('modstartcol', false, PARAM_TEXT),
            'modduecol' => optional_param('modduecol', false, PARAM_TEXT),
            'modtitlecol' => optional_param('modtitlecol', false, PARAM_TEXT),
            'parttitlecol' => optional_param('parttitlecol', false, PARAM_TEXT),
            'submissiontable' => optional_param('submissiontable', false, PARAM_TEXT),
            'submissionpartcol' => optional_param('submissionpartcol', false, PARAM_TEXT),
            'submissionusercol' => optional_param('submissionusercol', false, PARAM_TEXT),
            'submissiondatecol' => optional_param('submissiondatecol', false, PARAM_TEXT),
            'submissionmodinstancecol' => optional_param('submissionmodinstancecol', false, PARAM_TEXT),
            'submissionstatuscol' => optional_param('submissionstatuscol', false, PARAM_TEXT),
            'submissionstatusval' => optional_param('submissionstatusval', false, PARAM_TEXT),
            'auto' => optional_param('auto', 0, PARAM_INT),
            'enabled' => optional_param('enabled', 0, PARAM_INT),
        );

        if ($submission['submit_mod_link']) {

            $this->setModID($settings['modid']);
            $this->setModTable($settings['modtable']);
            $this->setPartTable( ($settings['parttable']) ? $settings['parttable'] : null );
            $this->setPartModCol( ($settings['partmodcol']) ? $settings['partmodcol'] : null );
            $this->setModCourseCol($settings['modcoursecol']);
            $this->setModStartCol($settings['modstartcol']);
            $this->setModDueCol($settings['modduecol']);
            $this->setModTitleCol($settings['modtitlecol']);
            $this->setPartTitleCol( ($settings['parttitlecol']) ? $settings['parttitlecol'] : null );
            $this->setSubTable($settings['submissiontable']);
            $this->setSubPartCol( ($settings['submissionpartcol']) ? $settings['submissionpartcol'] : null );
            $this->setSubUserCol($settings['submissionusercol']);
            $this->setSubDateCol($settings['submissiondatecol']);
            $this->setSubModCol($settings['submissionmodinstancecol']);
            $this->setSubStatusCol($settings['submissionstatuscol']);
            $this->setSubStatusVal($settings['submissionstatusval']);
            $this->setAuto( ($settings['auto'] ? 1 : 0 ) );
            $this->setEnabled( ( $settings['enabled'] ? 1 : 0 ) );

        }

    }

    /**
     * Get mod links which are enabled
     * @global \GT\type $DB
     * @return \GT\ModuleLink
     */
    public static function getEnabledModLinks() {

        global $DB;

        $return = array();
        $records = $DB->get_records("bcgt_mods", array("enabled" => 1, "deleted" => 0), "modid ASC", "id");
        if ($records) {
            foreach ($records as $record) {
                $obj = new \GT\ModuleLink($record->id);
                $return[] = $obj;
            }
        }

        return $return;

    }

    /**
     * Get all the mods installed in Moodle
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllInstalledMods() {

        global $DB;

        $records = $DB->get_records("modules", array("visible" => 1), "name ASC", "id, name");
        return $records;

    }

    /**
     * Get the supported mods
     * @return type
     */
    public static function getSupportedMods() {
        return self::$supportedMods;
    }

    /**
     * Clear the records off all the mod links in an array
     * @param type $modLinks
     */
    public static function clearModRecords(&$modLinks) {

        if ($modLinks) {

            foreach ($modLinks as $modLink) {

                $modLink->clearRecords();

            }

        }

    }

    /**
     * Get a bcgt_mod ModuleLink based on a courseModuleID
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getModuleLinkFromCourseModule($cmID) {

        global $DB;

        $modID = \GT\Activity::getActivityModuleFromCourseModule($cmID);
        $check = $DB->get_record("bcgt_mods", array("modid" => $modID), "id");
        if ($check) {
            $moduleLink = new \GT\ModuleLink($check->id);
            if ($moduleLink->isValid()) {
                $instance = \GT\Activity::getActivityInstanceFromCourseModule($cmID);
                $moduleLink->setRecordID($instance);
                $moduleLink->setCourseModID($cmID);
                return $moduleLink;
            }
        }

        return false;

    }

    /**
     * Get course module record from id
     * @global \GT\type $DB
     * @param type $cmID
     * @return type
     */
    public static function getCourseModule($cmID) {

        global $DB;
        return $DB->get_record("course_modules", array("id" => $cmID));

    }

    /**
     * Get the ModuleLink object by the name of the module
     * @global \GT\type $DB
     * @param type $name
     * @return boolean|\GT\ModuleLink
     */
    public static function getByModName($name) {

        global $DB;

        $mod = $DB->get_record("modules", array("name" => $name));
        if ($mod) {
            $record = $DB->get_record("bcgt_mods", array("modid" => $mod->id), "id");
            if ($record) {
                return new \GT\ModuleLink($record->id);
            }
        }

        return false;

    }

}
