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
 * This class deals with sorting things like Units, Criteria, etc...
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace block_gradetracker;

defined('MOODLE_INTERNAL') or die();

class Sorter {

    /**
     * Sort the criteria
     * @param type $criteria
     * @return boolean
     */
    public function sortCriteria(&$criteria, $objects = true, $multiDimensional = false) {

        global $EXECUTION;

        // Some scripts don't want to sort criteria, to improve performance
        if (isset($EXECUTION) && $EXECUTION->EXCLUDE_CRITERIA_SORT === true) {
            return false;
        }

        if (!$criteria) {
            return false;
        }

        if ($criteria && $objects) {

            // Sort this top level
            uasort($criteria, function($a, $b) {
                return strnatcasecmp($a->getName(), $b->getName());
            });

            // Now loop through and do children
            foreach ($criteria as $criterion) {
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $this->sortCriteria( $children );
                    $criterion->setChildren($children);
                }
            }

        } else if ($criteria && !$objects && $multiDimensional) {

            // Sorting an array by its "name" value

            // Sort this top level
            uasort($criteria, function($a, $b) {

                return strnatcasecmp($a['name'], $b['name']);

            });

        } else if ($criteria && !$objects) {
            // Sorting an array

            // Sort this top level
            uasort($criteria, function($a, $b) {

                return strnatcasecmp($a, $b);

            });

        }

    }


    /**
     * Sort the criteria by a custom ordering setting, e.g. "P%, M%, D%"
     * @global type $EXECUTION
     * @param type $criteria
     * @param type $order
     * @param type $objects
     * @param type $multiDimensional
     * @return boolean
     */
    public function sortCriteriaCustom(&$criteria, $order, $objects = true, $multiDimensional = false) {

        global $EXECUTION;

        // Some scripts don't want to sort criteria, to improve performance
        if (isset($EXECUTION) && $EXECUTION->CRIT_NO_SORT === true) {
            return false;
        }

        if (!$criteria) {
            return false;
        }

        // Explode order string into array
        $order = explode(",", $order);

        // If criteria objects
        if ($criteria && $objects) {

            // Sort top level
            $this->customCriteriaUASort($criteria, $order, true, false);

            // Then loop through and do children
            foreach ($criteria as $criterion) {
                if ($criterion->getChildren()) {
                    $children = $criterion->getChildren();
                    $this->customCriteriaUASort( $children, $order );
                    $criterion->setChildren($children);
                }
            }

        } else {
            $this->customCriteriaUASort($criteria, $order, false, $multiDimensional);
        }

    }

    /**
     * The custom uasort when calling a custom criteria sorting
     * @param type $criteria
     * @param type $order
     * @param type $objects
     * @param type $multiDimensional
     */
    protected function customCriteriaUASort(&$criteria, $order, $objects = true, $multiDimensional = false) {

        usort($criteria, function($obj1, $obj2) use ($order, $objects, $multiDimensional) {

            if (!$objects && $multiDimensional) {
                $a = $obj1['name'];
                $b = $obj2['name'];
            } else if ($objects) {
                $a = $obj1->getName();
                $b = $obj2->getName();
            } else {
                $a = $obj1;
                $b = $obj2;
            }

            // Check for wildcards first
            $letterA = false;
            $letterB = false;
            $numberA = false;
            $numberB = false;
            $remainderA = false;
            $remainderB = false;

            preg_match("/([a-z]+)([0-9]+)(.*+)/i", $a, $aMatches);
            preg_match("/([a-z]+)([0-9]+)(.*+)/i", $b, $bMatches);

            if ($aMatches) {
                $letterA = $aMatches[1];
                $numberA = $aMatches[2];
                $remainderA = $aMatches[3];
            }

            if ($bMatches) {
                $letterB = $bMatches[1];
                $numberB = $bMatches[2];
                $remainderB = $bMatches[3];
            }

            // If both have wildcards in the order
            if ( in_array($letterA."%", $order) && in_array($letterB."%", $order) ) {

                // If the letter and number are the same, order by the remainder
                if ($letterA == $letterB && $numberA == $numberB) {
                    $result = strnatcasecmp($remainderA, $remainderB);
                } else if ($letterA == $letterB) {
                    // If the letters are the same but the numbers are not, order by the number
                    $result = ( $numberA > $numberB );
                } else {
                    // If the letters are different, order by the position in the order
                    $result = ( array_search($letterA."%", $order) > array_search($letterB."%", $order) );
                }

            } else if ( in_array($letterA."%", $order) || in_array($letterB."%", $order) ) {

                // If only one has a wildcard

                $wildcardAPos = array_search($letterA."%", $order);
                $wildcardBPos = array_search($letterB."%", $order);
                $aPos = array_search($a, $order);
                $bPos = array_search($b, $order);

                if ($wildcardAPos !== false) {

                    // If b isn't in the order array at all, then B must come last
                    if ($bPos === false) {
                        $result = -1;
                    } else {
                        // B is in the order array
                        $result = ($wildcardAPos > $bPos);
                    }

                } else if ($wildcardBPos !== false) {

                    // If a isn't in the order array at all, then A must come last
                    if ($aPos === false) {
                         $result = 1;
                    } else {
                        // A is in the order array
                        $result = ($aPos > $wildcardBPos);
                    }

                }

            } else if (in_array($a, $order) && in_array($b, $order)) {
                // If both in array
                $result = ( array_search($a, $order) > array_search($b, $order) );
            } else if (in_array($a, $order) || in_array($b, $order)) {
                // Else if only one of them is
                $result = (in_array($a, $order)) ? 0 : 1;
            } else {
                // Lastly just do name
                $result = ( strnatcasecmp($a, $b) );
            }

            $result = (int)$result;
            return $result;

        });

    }


    /**
     * Sort units by level, then unit number, then name
     * @param type $units
     * @param type $objects
     * @return boolean
     */
    public function sortUnitsByLevel(&$units, $objects = true) {

        if (!$units) {
            return false;
        }

        // Sorting \block_gradetracker\Unit objects
        if ($objects) {

            uasort($units, function($a, $b) {

                // FIrst try level
                if ($a->getLevelID() <> $b->getLevelID()) {
                    return ($a->getLevel()->getOrderNumber() > $b->getLevel()->getOrderNumber());
                } else if ($a->getUnitNumber() <> $b->getUnitNumber()) {
                    // Then try by Unit Number
                    return ($a->getUnitNumber() > $b->getUnitNumber());
                } else {
                    // Then try name
                    return (strcasecmp($a->getName(), $b->getName()));
                }

            });

        }

    }


     /**
      * Sort units by unit number, then name
      * @param type $units
      * @param type $objects
      * @return boolean
      */
    public function sortUnits(&$units, $objects = true) {

        if (!$units) {
            return false;
        }

        // Sorting \block_gradetracker\Unit objects
        if ($objects) {

            uasort($units, function($a, $b) {

                // Try by Unit Number
                if ($a->getUnitNumber() <> $b->getUnitNumber()) {
                    return ($a->getUnitNumber() > $b->getUnitNumber());
                } else {
                    // Then try name.
                    return (strcasecmp($a->getName(), $b->getName()));
                }

            });

        }

    }

    public function sortUnitsCustom(&$units, $order, $objects = true) {

        if (!$units) {
            return false;
        }

        // Explode order string into array
        $order = explode(",", $order);

        // Sorting \block_gradetracker\Unit objects
        if ($objects) {
            $this->customUnitsUASort($units, $order, $objects);
        }

    }


    private function customUnitsUASort(&$units, $order, $objects = true) {

        uasort($units, function($obj1, $obj2) use ($order, $objects) {

            if ($objects) {
                $a = $obj1->getUnitNumber();
                $b = $obj2->getUnitNumber();
            } else {
                $a = $obj1;
                $b = $obj2;
            }

            // Check for wildcards first
            $letterA = false;
            $letterB = false;
            $numberA = false;
            $numberB = false;
            $remainderA = false;
            $remainderB = false;

            preg_match("/([a-z]+)([0-9]+)(.*+)/i", $a, $aMatches);
            preg_match("/([a-z]+)([0-9]+)(.*+)/i", $b, $bMatches);

            if ($aMatches) {
                $letterA = $aMatches[1];
                $numberA = $aMatches[2];
                $remainderA = $aMatches[3];
            }

            if ($bMatches) {
                $letterB = $bMatches[1];
                $numberB = $bMatches[2];
                $remainderB = $bMatches[3];
            }

            // If both have wildcards in the order
            if ( in_array($letterA."%", $order) && in_array($letterB."%", $order) ) {

                // If the letter and number are the same, order by the remainder
                if ($letterA == $letterB && $numberA == $numberB) {
                    $result = strnatcasecmp($remainderA, $remainderB);
                } else if ($letterA == $letterB) {
                    // If the letters are the same but the numbers are not, order by the number
                    $result = ( $numberA > $numberB );
                } else {
                    // If the letters are different, order by the position in the order
                    $result = ( array_search($letterA."%", $order) > array_search($letterB."%", $order) );
                }

            } else if ( in_array($letterA."%", $order) || in_array($letterB."%", $order) ) {

                // If only one has a wildcard
                $wildcardAPos = array_search($letterA."%", $order);
                $wildcardBPos = array_search($letterB."%", $order);
                $aPos = array_search($a, $order);
                $bPos = array_search($b, $order);

                if ($wildcardAPos !== false) {

                    // If b isn't in the order array at all, then B must come last
                    if ($bPos === false) {
                        $result = -1;
                    } else {
                        // B is in the order array
                        $result = ($wildcardAPos > $bPos);
                    }

                } else if ($wildcardBPos !== false) {

                    // If a isn't in the order array at all, then A must come last
                    if ($aPos === false) {
                         $result = 1;
                    } else {
                        // A is in the order array
                        $result = ($aPos > $wildcardBPos);
                    }

                }

            } else if (in_array($a, $order) && in_array($b, $order)) {
                // If both in array
                $result = ( array_search($a, $order) > array_search($b, $order) );
            } else if (in_array($a, $order) || in_array($b, $order)) {
                // Else if only one of them is
                $result = (in_array($a, $order)) ? 0 : 1;
            } else if ($a <> $b) {
                // Try by Unit Number
                return ($a > $b);
            } else if ($objects) {
                // Then try name
                return (strcasecmp($obj1->getName(), $obj2->getName()));
            }

            $result = (int)$result;
            return $result;

        });

    }

    /**
     * Sort users by their name
     * @param type $users
     */
    public function sortUsers(&$users) {

        if (!$users) {
            return false;
        }

        uasort($users, function($a, $b) {

            // Try by last name
            if ( strnatcasecmp($a->lastname, $b->lastname) <> 0 ) {
                return ( strnatcasecmp($a->lastname, $b->lastname) );
            } else if ( strnatcasecmp($a->firstname, $b->firstname) <> 0 ) {
                // Then try by first name
                return ( strnatcasecmp($a->firstname, $b->firstname) );
            } else {
                // Then try username
                return ( strcasecmp($a->username, $b->username) );
            }

        });

    }

    /**
     * Sort courses
     * @param type $courses
     * @return boolean
     */
    public function sortCourses(&$courses) {

        if (!$courses) {
            return false;
        }

        uasort($courses, function($a, $b) {

            // Order by fullname
            return ( strnatcasecmp($a->fullname, $b->fullname) );

        });

    }


    /**
     * Sort the values that can be given to criteria, with MET ones first, then points,
     * then lastly name
     * @param type $values
     */
    public function sortCriteriaValues(&$values, $order = 'asc') {

        if (!$values) {
            return false;
        }

        uasort($values, function($a, $b) use ($order) {

            // First by met
            if ($a->isMet() != $b->isMet()) {
                return ($a->isMet() < $b->isMet());
            }

            // Then by points
            if ($a->getPoints() != $b->getPoints()) {
                if ($order == 'desc') {
                    return ($a->getPoints() < $b->getPoints());
                } else {
                    return ($a->getPoints() > $b->getPoints());
                }
            }

            // Lastly by name
            return strcasecmp($a->getName(), $b->getName());

        });

    }


     /**
      * Sort the values that can be given to units, ordered by points, then name
      * @param type $values
      */
    public function sortUnitValues(&$values, $order = 'asc') {

        if (!$values) {
            return false;
        }

        uasort($values, function($a, $b) use ($order) {

            // By points
            if ($a->getPoints() != $b->getPoints()) {
                if ($order == 'desc') {
                    return ($a->getPoints() < $b->getPoints());
                } else {
                    return ($a->getPoints() > $b->getPoints());
                }
            }

            // Lastly by name
            return strcasecmp($a->getName(), $b->getName());

        });

    }


     /**
      * Sort the values that can be given to units, ordered by points, then name
      * @param type $values
      */
    public function sortQualAwards(&$awards, $order = 'asc') {

        if (!$awards) {
            return false;
        }

        uasort($awards, function($a, $b) use ($order) {

            // By points
            if ($a->getRank() != $b->getRank()) {
                if ($order == 'desc') {
                    return ($a->getRank() < $b->getRank());
                } else {
                    return ($a->getRank() > $b->getRank());
                }
            }

            // Lastly by name
            return strcasecmp($a->getName(), $b->getName());

        });

    }


    /**
     * Sort assessments by their date
     * @param type $values
     */
    public function sortAssessmentsByDate(&$assessments, $order = 'asc') {

        if (!$assessments) {
            return false;
        }

        uasort($assessments, function($a, $b) use ($order) {

            // By date
            if ($a->getDate() != $b->getDate()) {
                if ($order == 'desc') {
                    return ($a->getDate() < $b->getDate());
                } else {
                    return ($a->getDate() > $b->getDate());
                }
            }

            // Lastly by name
            return strcasecmp($a->getName(), $b->getName());

        });

    }



    /**
     * Sort assessments by their date
     * @param type $values
     */
    public function sortQualificationsByName(&$quals) {

        if (!$quals) {
            return false;
        }

        uasort($quals, function($a, $b) {

            // By name
            return strcasecmp($a->getName(), $b->getName());

        });

    }

    public function sortQualifications(&$quals) {

        if (!$quals) {
            return false;
        }

        uasort($quals, function($a, $b) {

            // First compare type
            $cmp = strcasecmp($a->getStructureName(), $b->getStructureName());
            if ($cmp <> 0) {
                return $cmp;
            }

            // Then level
            if ($a->getLevelOrderNum() <> $b->getLevelOrderNum()) {
                return ($a->getLevelOrderNum() > $b->getLevelOrderNum());
            }

            // Then subtype
            $cmp = strcasecmp($a->getSubTypeName(), $b->getSubTypeName());
            if ($cmp <> 0) {
                return $cmp;
            }

            // Then name
            return strcasecmp($a->getName(), $b->getName());

        });

    }

    /**
     * Sort an array of QualificationBuilds
     * @param type $builds
     * @return boolean
     */
    public function sortQualificationBuilds(&$builds) {

        if (!$builds) {
            return false;
        }

        uasort($builds, function($a, $b) {

            // First sort by Structure name
            $cmp = strcasecmp($a->getStructureName(), $b->getStructureName());
            if ($cmp <> 0) {
                return $cmp;
            }

            // Then by level ordernum
            $aLvl = $a->getLevel();
            $bLvl = $b->getLevel();
            if ($aLvl && $bLvl && ($aLvl->getOrderNumber() <> $bLvl->getOrderNumber())) {
                return ($aLvl->getOrderNumber() > $bLvl->getOrderNumber());
            }

            // Then by sub type name
            $cmp = strcasecmp($a->getSubTypeName(), $b->getSubTypeName());
            if ($cmp <> 0) {
                return $cmp;
            }

            // Nothing else to sort by
            return 0;

        });

    }


}
