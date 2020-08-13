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
 * Abstract Report class
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker\Reports;

defined('MOODLE_INTERNAL') or die();

abstract class Report {

    protected $name;

    public function __construct() {

        // Register new function to handle fatal errors
        register_shutdown_function("gt_ajax_shutdown");

    }

    abstract public function run(array $params);

    public function getReportHistoryTable() {

    }

    protected function convertStringToWorksheetName($str) {

        // Excel worksheet names can't be more than 31 characters long
        $maxLength = 31;

        // First convert to camel-case and strip out any whitespace
        $str = ucwords( strtolower($str) );
        $str = preg_replace("/\s/", "", $str);

        // Now strip out any non alphanumeric characters
        $str = \gt_strip_chars_non_alpha($str);

        // Length
        if (strlen($str) > $maxLength) {
            $diff = $maxLength - strlen($str);
            $str = substr($str, 0, $diff);
        }

        return $str;

    }

    protected function extractParam($name, $params) {

        if ($params) {
            foreach ($params as $param) {
                if ($param['name'] == $name && isset($param['value'])) {
                    return $param['value'];
                }
            }
        }

        return false;

    }

    /**
     * Get the weighting score for a particular criteria letter
     * @param type $letter
     * @param type $weightings
     * @return int
     */
    protected function getCriteriaNameWeighting($letter, $weightings) {

        if ($weightings) {

            foreach ($weightings as $row) {

                if ($row->letter == $letter) {
                    return $row->score;
                }

            }

        }

        return 0;

    }


    /**
     * Get the maximum value of a given field in an array of records
     * @param type $value
     * @param type $records
     * @return type
     */
    protected function getMaxValue($value, $records) {

        $max = 0;
        if ($records) {
            foreach ($records as $record) {
                if (isset($record->$value) && $record->$value > $max) {
                    $max = $record->$value;
                }
            }
        }

        return $max;

    }


    /**
     * Get the colours to use for the column, depending on percentage value
     * @param type $value
     * @return type
     */
    protected function getPercentageStyle($value) {

        if (!is_numeric($value)) {
            return array();
        }

        if ($value < 50) {
            $font = '8b0000';
            $bg = 'fc9e9e';
        } else if ($value < 70) {
            $font = 'aa2e00';
            $bg = 'ffc000';
        } else if ($value < 85) {
            $font = '634806';
            $bg = 'faec7d';
        } else if ($value <= 100) {
            $font = '00552a';
            $bg = '8af192';
        } else if ($value > 100) {
            $font = 'ffffff';
            $bg = 'ff0000';
        } else {
            $font = '000000';
            $bg = 'ffffff';
        }

        return array(
            'bg_color' => '#' . $bg,
            'color' => '#' . $font,
            'bold' => true
        );

    }

    /**
     * Get array of words not allowed in custom SQL reports for security reasons
     * @return type
     */
    public static function getDisallowedWords() {
        return array('INSERT', 'INTO', 'UPDATE', 'DELETE', 'EXECUTE', 'SHOW VIEW', 'CREATE', 'ALTER', 'REFERENCES', 'INDEX', 'DROP', 'TRIGGER', 'GRANT', 'LOCK', 'TRUNCATE');
    }

    /**
     * Check if some SQL contains any of the disallowed words
     * @param type $sql
     * @return type
     */
    public static function checkContainsDisallowedWords($sql) {
        return preg_match('/\b('.implode('|', self::getDisallowedWords()).')\b/i', $sql);
    }

}
