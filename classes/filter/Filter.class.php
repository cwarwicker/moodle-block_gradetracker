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
 * This class deals with filtering arrays of things
 *
 * @copyright 2020 Conn Warwicker
 * @package block_gradetracker
 * @version 2.0
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace GT;

defined('MOODLE_INTERNAL') or die();

class Filter {

    public function filterCriteriaNotReadOnly(&$criteria) {

        if (!$criteria) {
            return false;
        }

        $result = array_filter($criteria, function($obj) {

            return ($obj->getAttribute('readonly') != 1);

        });

        return $result;

    }

    /**
     *  Array
        (
            [field] => number
            [conjunction] => is
            [value] => 5
        )
     * @param type $units
     * @param type $filters
     * @return boolean
     */
    public function filterUnits(&$units, $filters) {

        if (!$units) {
            return false;
        }

        $result = array_filter($units, function($obj) use ($filters) {

            // If the filters aren't set, return true as that will return all of the units
            if (!$filters || !isset($filters['conjunction']) || !isset($filters['field']) || !isset($filters['value'])) {
                return true;
            }

            switch($filters['conjunction']) {

                // Filter by if x IS y
                case 'is':

                    switch($filters['field']) {
                        case 'name':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if (strcasecmp($obj->getName(), $val) == 0) {
                                        return true;
                                    }

                                }

                            } else {

                                return (strcasecmp($obj->getName(), $filters['value']) == 0);

                            }

                        break;
                        case 'number':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if ($obj->getUnitNumber() == $val) {
                                        return true;
                                    }

                                }

                            } else {
                                return ($obj->getUnitNumber() == $filters['value']);
                            }

                        break;
                        case 'code':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if (strcasecmp($obj->getCode(), $val) == 0) {
                                        return true;
                                    }

                                }

                            } else {
                                return (strcasecmp($obj->getCode(), $filters['value']) == 0);
                            }

                        break;
                    }

                break;

                // Filter by if x IS NOT y
                case 'is not':

                    switch($filters['field']) {
                        case 'name':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if (strcasecmp($obj->getName(), $val) <> 0) {
                                        return true;
                                    }

                                }

                            } else {

                                return (strcasecmp($obj->getName(), $filters['value']) <> 0);

                            }

                        break;
                        case 'number':

                             // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if ($obj->getUnitNumber() != $val) {
                                        return true;
                                    }

                                }

                            } else {
                                return ($obj->getUnitNumber() != $filters['value']);
                            }

                        break;
                        case 'code':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if (strcasecmp($obj->getCode(), $val) <> 0) {
                                        return true;
                                    }

                                }

                            } else {
                                return (strcasecmp($obj->getCode(), $filters['value']) <> 0);
                            }

                        break;
                    }

                break;

                // Filter by if x CONTAINS y
                case 'contains':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_contains($obj->getName(), $filters['value']);
                        break;
                        case 'number':
                            return \gt_str_contains($obj->getUnitNumber(), $filters['value']);
                        break;
                        case 'code':
                            return \gt_str_contains($obj->getCode(), $filters['value']);
                        break;
                    }

                break;

                // Filter by if x STARTS WITH y
                case 'starts':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_starts($obj->getName(), $filters['value']);
                        break;
                        case 'number':
                            return \gt_str_starts($obj->getUnitNumber(), $filters['value']);
                        break;
                        case 'code':
                            return \gt_str_starts($obj->getCode(), $filters['value']);
                        break;
                    }

                break;

                // Filter by if x ENDS WITH y
                case 'ends':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_ends($obj->getName(), $filters['value']);
                        break;
                        case 'number':
                            return \gt_str_ends($obj->getUnitNumber(), $filters['value']);
                        break;
                        case 'code':
                            return \gt_str_ends($obj->getCode(), $filters['value']);
                        break;
                    }

                break;

                case 'matches':

                    // If it's an array, set it to the first element as we only use one
                    if (is_array($filters['value'])) {
                        $filters['value'] = reset($filters['value']);
                    }

                    switch($filters['field']) {
                        case 'name':
                            return ( preg_match($filters['value'], $obj->getName()) );
                        break;
                        case 'number':
                            return ( preg_match($filters['number'], $obj->getUnitNumber()) );
                        break;
                        case 'code':
                            return ( preg_match($filters['value'], $obj->getCode()) );
                        break;
                    }

                break;

            }

        });

        return $result;

    }

    /**
     *
     * @param type $criteria
     * @param type $filters
     */
    public function filterCriteria(&$criteria, $filters) {

        if (!$criteria) {
            return false;
        }

        // Strip backslashes
        $filters['value'] = \gt_strip_slashes($filters['value']);

        $result = array_filter($criteria, function($obj) use ($filters) {

            // If the filters aren't set, return true as that will return all of the units
            if (!$filters || !isset($filters['conjunction']) || !isset($filters['field']) || !isset($filters['value'])) {
                return true;
            }

            switch($filters['conjunction']) {

                // Filter by if x IS y
                case 'is':

                    switch($filters['field']) {
                        case 'name':

                            // If it's an array, match any of them
                            if (is_array($filters['value'])) {

                                foreach ($filters['value'] as $val) {

                                    if (strcasecmp($obj->getName(), $val) == 0) {
                                        return true;
                                    }

                                }

                            } else {
                                return (strcasecmp($obj->getName(), $filters['value']) == 0);
                            }

                        break;
                    }

                break;

                // Filter by if x IS NOT y
                case 'is not':

                    switch($filters['field']) {
                        case 'name':

                            if (is_array($filters['value'])) {

                                $result = true;

                                foreach ($filters['value'] as $val) {

                                    $result = $result && (strcasecmp($obj->getName(), $val) <> 0);

                                }

                                return $result;

                            } else {
                                return (strcasecmp($obj->getName(), $filters['value']) <> 0);
                            }

                        break;
                    }

                break;

                // Filter by if x STARTS WITH y
                case 'contains':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_contains($obj->getName(), $filters['value']);
                        break;
                    }

                break;

                // Filter by if x STARTS WITH y
                case 'starts':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_starts($obj->getName(), $filters['value']);
                        break;
                    }

                break;

                // Filter by if x ENDS WITH y
                case 'ends':

                    switch($filters['field']) {
                        case 'name':
                            return \gt_str_ends($obj->getName(), $filters['value']);
                        break;
                    }

                break;

                case 'matches':

                    // If it's an array, set it to the first element as we only use one
                    if (is_array($filters['value'])) {
                        $filters['value'] = reset($filters['value']);
                    }

                    switch($filters['field']) {
                        case 'name':
                            return ( preg_match($filters['value'], $obj->getName()) );
                        break;
                    }

                break;

            }

        });

        return $result;

    }


    public static function getAllFilters() {
        return array('is', 'is not', 'contains', 'starts', 'ends', 'matches');
    }

    public static function getFilterableFields($object) {

        if ($object == 'unit') {
            return array('name', 'number', 'code');
        } else if ($object == 'criterion') {
            return array('name');
        }

    }

}
