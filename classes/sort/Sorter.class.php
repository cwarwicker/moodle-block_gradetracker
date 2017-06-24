<?php
/**
 * Sorter
 *
 * This class deals with sorting things like Units, Criteria, etc...
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com> <moodlesupport@bedford.ac.uk>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

namespace GT;

class Sorter {
    
    /**
     * Sort the criteria
     * @param type $criteria
     * @return boolean
     */
    public function sortCriteria(&$criteria, $objects = true, $multiDimensional = false)
    {
        
        global $EXECUTION;
        
        // Some scripts don't want to sort criteria, to improve performance
        if (isset($EXECUTION) && $EXECUTION->EXCLUDE_CRITERIA_SORT === true){
            return false;
        }
        
        
        if (!$criteria) return false;
        
        
        if ($criteria && $objects)
        {
            
            // Sort this top level
            uasort($criteria, function($a, $b){
                return strnatcasecmp($a->getName(), $b->getName());
            });
            
            // Now loop through and do children
            foreach($criteria as $criterion)
            {
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $this->sortCriteria( $children );
                    $criterion->setChildren($children);
                }
            }
            
        }
        
        // Sorting an array by its "name" value
        elseif ($criteria && !$objects && $multiDimensional)
        {
            
            // Sort this top level
            uasort($criteria, function($a, $b){
                
                return strnatcasecmp($a['name'], $b['name']);
                
            });
            
        }
        
         // Sorting an array
        elseif ($criteria && !$objects)
        {
            
            // Sort this top level
            uasort($criteria, function($a, $b){
                
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
    public function sortCriteriaCustom(&$criteria, $order, $objects = true, $multiDimensional = false)
    {
        
        global $EXECUTION;
        
        // Some scripts don't want to sort criteria, to improve performance
        if (isset($EXECUTION) && $EXECUTION->CRIT_NO_SORT === true){
            return false;
        }
        
        if (!$criteria) return false;
        
        // Explode order string into array
        $order = explode(",", $order);
        
                
        // If criteria objects
        if ($criteria && $objects)
        {
            
            // Sort top level
            $this->customCriteriaUASort($criteria, $order, true, false);            
                                    
            // Then loop through and do children
            foreach($criteria as $criterion)
            {
                if ($criterion->getChildren())
                {
                    $children = $criterion->getChildren();
                    $this->customCriteriaUASort( $children, $order );
                    $criterion->setChildren($children);
                }
            }
            
        }
        
        else
        {
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
    protected function customCriteriaUASort(&$criteria, $order, $objects = true, $multiDimensional = false){
                
        usort($criteria, function($obj1, $obj2) use ($order, $objects, $multiDimensional) {

            
            if (!$objects && $multiDimensional){
                $a = $obj1['name'];
                $b = $obj2['name'];
            } elseif ($objects) {
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

            if ($aMatches){
                $letterA = $aMatches[1];
                $numberA = $aMatches[2];
                $remainderA = $aMatches[3];
            }

            if ($bMatches){
                $letterB = $bMatches[1];
                $numberB = $bMatches[2];
                $remainderB = $bMatches[3];
            }


            // If both have wildcards in the order
            if ( in_array($letterA."%", $order) && in_array($letterB."%", $order) ){
                    
                // If the letter and number are the same, order by the remainder
                if ($letterA == $letterB && $numberA == $numberB){
                    $result = strnatcasecmp($remainderA, $remainderB);
                }
                // If the letters are the same but the numbers are not, order by the number
                elseif ($letterA == $letterB){
                    $result = ( $numberA > $numberB );
                } 
                // If the letters are different, order by the position in the order
                else {
                    $result = ( array_search($letterA."%", $order) > array_search($letterB."%", $order) );
                }

            }

            // If only one has a wildcard
            elseif ( in_array($letterA."%", $order) || in_array($letterB."%", $order) ){

                $wildcardAPos = array_search($letterA."%", $order);
                $wildcardBPos = array_search($letterB."%", $order);
                $aPos = array_search($a, $order);
                $bPos = array_search($b, $order);
                                   

                if ($wildcardAPos !== false){

                    // If b isn't in the order array at all, then B must come last
                    if ($bPos === false){
                        $result = -1;
                    } else {
                        // B is in the order array
                        $result = ($wildcardAPos > $bPos);
                    }

                } elseif ($wildcardBPos !== false){

                    // If a isn't in the order array at all, then A must come last
                    if ($aPos === false){
                         $result = 1;
                    } else {
                        // A is in the order array
                        $result = ($aPos > $wildcardBPos);
                    }

                }

            }


            // If both in array
            elseif (in_array($a, $order) && in_array($b, $order)){
                $result = ( array_search($a, $order) > array_search($b, $order) );
            }

            // Else if only one of them is
            elseif (in_array($a, $order) || in_array($b, $order)){
               $result = (in_array($a, $order)) ? 0 : 1;     
            }

            // Lastly just do name
            else {
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
    public function sortUnitsByLevel(&$units, $objects = true)
    {
        
        if (!$units) return false;
        
        // Sorting \GT\Unit objects
        if ($objects)
        {
            
            uasort($units, function($a, $b){
                
                // FIrst try level
                if ($a->getLevelID() <> $b->getLevelID()){
                    return ($a->getLevel()->getOrderNumber() > $b->getLevel()->getOrderNumber());
                }
                
                // Then try by Unit Number
                elseif ($a->getUnitNumber() <> $b->getUnitNumber()){
                    return ($a->getUnitNumber() > $b->getUnitNumber());
                }
                
                // Then try name
                else {
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
    public function sortUnits(&$units, $objects = true)
    {
        
        if (!$units) return false;
        
        // Sorting \GT\Unit objects
        if ($objects)
        {
            
            uasort($units, function($a, $b){
                
                // Try by Unit Number
                if ($a->getUnitNumber() <> $b->getUnitNumber()){
                    return ($a->getUnitNumber() > $b->getUnitNumber());
                }
                
                // Then try name
                else {
                    return (strcasecmp($a->getName(), $b->getName()));
                }
                
            });
                        
        }
        
    }
    
    public function sortUnitsCustom(&$units, $order, $objects = true)
    {
        
        if (!$units) return false;
        
        // Explode order string into array
        $order = explode(",", $order);
        
        // Sorting \GT\Unit objects
        if ($objects)
        {
            $this->customUnitsUASort($units, $order, $objects);                        
        }
        
    }
    
    
    private function customUnitsUASort(&$units, $order, $objects = true)
    {
                
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

            if ($aMatches){
                $letterA = $aMatches[1];
                $numberA = $aMatches[2];
                $remainderA = $aMatches[3];
            }

            if ($bMatches){
                $letterB = $bMatches[1];
                $numberB = $bMatches[2];
                $remainderB = $bMatches[3];
            }
            

            // If both have wildcards in the order
            if ( in_array($letterA."%", $order) && in_array($letterB."%", $order) ){
                    
                // If the letter and number are the same, order by the remainder
                if ($letterA == $letterB && $numberA == $numberB){
                    $result = strnatcasecmp($remainderA, $remainderB);
                }
                // If the letters are the same but the numbers are not, order by the number
                elseif ($letterA == $letterB){
                    $result = ( $numberA > $numberB );
                } 
                // If the letters are different, order by the position in the order
                else {
                    $result = ( array_search($letterA."%", $order) > array_search($letterB."%", $order) );
                }

            }

            // If only one has a wildcard
            elseif ( in_array($letterA."%", $order) || in_array($letterB."%", $order) ){

                $wildcardAPos = array_search($letterA."%", $order);
                $wildcardBPos = array_search($letterB."%", $order);
                $aPos = array_search($a, $order);
                $bPos = array_search($b, $order);
                                   

                if ($wildcardAPos !== false){

                    // If b isn't in the order array at all, then B must come last
                    if ($bPos === false){
                        $result = -1;
                    } else {
                        // B is in the order array
                        $result = ($wildcardAPos > $bPos);
                    }

                } elseif ($wildcardBPos !== false){

                    // If a isn't in the order array at all, then A must come last
                    if ($aPos === false){
                         $result = 1;
                    } else {
                        // A is in the order array
                        $result = ($aPos > $wildcardBPos);
                    }

                }

            }


            // If both in array
            elseif (in_array($a, $order) && in_array($b, $order)){
                $result = ( array_search($a, $order) > array_search($b, $order) );
            }

            // Else if only one of them is
            elseif (in_array($a, $order) || in_array($b, $order)){
               $result = (in_array($a, $order)) ? 0 : 1;     
            }

            // Try by Unit Number
            elseif ($a <> $b){
                return ($a > $b);
            }

            // Then try name
            elseif ($objects) {
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
    public function sortUsers(&$users){
        
        if (!$users) return false;
        
        uasort($users, function($a, $b){

            // Try by last name
            if ( strnatcasecmp($a->lastname, $b->lastname) <> 0 ){
                return ( strnatcasecmp($a->lastname, $b->lastname) );
            }

            // Then try by first name
            elseif ( strnatcasecmp($a->firstname, $b->firstname) <> 0 ){
                return ( strnatcasecmp($a->firstname, $b->firstname) );
            }

            // Then try username
            else {
                return ( strcasecmp($a->username, $b->username) );
            }

        });            
                    
    }
    
    /**
     * Sort courses
     * @param type $courses
     * @return boolean
     */
    public function sortCourses(&$courses){
        
        if (!$courses) return false;
        
        uasort($courses, function($a, $b){
            
            // Order by fullname
            return ( strnatcasecmp($a->fullname, $b->fullname) );
            
        });
        
    }
    
    
    /**
     * Sort the values that can be given to criteria, with MET ones first, then points, 
     * then lastly name
     * @param type $values
     */
    public function sortCriteriaValues(&$values, $order = 'asc'){
        
        if (!$values) return false;
        
        uasort($values, function($a, $b) use ($order){
                        
            // First by met
            if ($a->isMet() != $b->isMet())
            {
                return ($a->isMet() < $b->isMet());
            }
            
            // Then by points
            if ($a->getPoints() != $b->getPoints())
            {
                if ($order == 'desc'){
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
    public function sortUnitValues(&$values, $order = 'asc'){
        
        if (!$values) return false;
        
        uasort($values, function($a, $b) use ($order){
                                    
            // By points
            if ($a->getPoints() != $b->getPoints())
            {
                if ($order == 'desc'){
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
    public function sortQualAwards(&$awards, $order = 'asc'){
        
        if (!$awards) return false;
        
        uasort($awards, function($a, $b) use ($order){
                                    
            // By points
            if ($a->getRank() != $b->getRank())
            {
                if ($order == 'desc'){
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
    public function sortAssessmentsByDate(&$assessments, $order = 'asc'){
        
        if (!$assessments) return false;
        
        uasort($assessments, function($a, $b) use ($order){
                                    
            // By date
            if ($a->getDate() != $b->getDate())
            {
                if ($order == 'desc'){
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
    public function sortQualificationsByName(&$quals){
        
        if (!$quals) return false;
        
        uasort($quals, function($a, $b){
                                                
            // By name            
            return strcasecmp($a->getName(), $b->getName());
            
        });
        
    }
    
    public function sortQualifications(&$quals){
        
        if (!$quals) return false;
        
        uasort($quals, function($a, $b){
            
            // First compare type
            $cmp = strcasecmp($a->getStructureName(), $b->getStructureName());
            if ($cmp <> 0) return $cmp;
            
            // Then level
            if ($a->getLevelOrderNum() <> $b->getLevelOrderNum()){
                return ($a->getLevelOrderNum() > $b->getLevelOrderNum());
            }
            
            // Then subtype
            $cmp = strcasecmp($a->getSubTypeName(), $b->getSubTypeName());
            if ($cmp <> 0) return $cmp;
            
            // Then name
            return strcasecmp($a->getName(), $b->getName());
            
        });
        
    }
    
    /**
     * Sort an array of QualificationBuilds
     * @param type $builds
     * @return boolean
     */
    public function sortQualificationBuilds(&$builds){
        
        if (!$builds) return false;
        
        uasort($builds, function($a, $b){
            
            // First sort by Structure name
            $cmp = strcasecmp($a->getStructureName(), $b->getStructureName());
            if ($cmp <> 0) return $cmp;
            
            // Then by level ordernum
            $aLvl = $a->getLevel();
            $bLvl = $b->getLevel();
            if ($aLvl && $bLvl && ($aLvl->getOrderNumber() <> $bLvl->getOrderNumber())){
                return ($aLvl->getOrderNumber() > $bLvl->getOrderNumber());
            }
            
            // Then by sub type name
            $cmp = strcasecmp($a->getSubTypeName(), $b->getSubTypeName());
            if ($cmp <> 0) return $cmp;
            
            // Nothing else to sort by
            return 0;
            
        });
        
    }
    
    
}
