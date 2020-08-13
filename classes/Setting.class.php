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
 * Get and Set plugin configuration settings.
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class Setting
{

    /**
     * Get a setting's value
     * @param type $setting
     */
    public static function getSetting($setting, $userID = null) {

        global $DB;

        $record = $DB->get_record("bcgt_settings", array("setting" => $setting, "userid" => $userID), "value");
        return ($record) ? $record->value : false;

    }



    /**
     * Update a plugin setting
     * @global type $DB
     * @param type $setting
     * @param type $value
     * @param type $userID
     * @return type
     */
    public static function updateSetting($setting, $value, $userID = null) {

        global $DB;

        $check = $DB->get_record("bcgt_settings", array("setting" => $setting, "userid" => $userID));

        // If one already exists, update the value
        if ($check) {
            $check->value = $value;
            return $DB->update_record("bcgt_settings", $check);
        }

        // Doesn't exist, so create one
        $obj = new \stdClass();
        $obj->setting = $setting;
        $obj->value = $value;
        $obj->userid = $userID;

        return $DB->insert_record("bcgt_settings", $obj);

    }

}
