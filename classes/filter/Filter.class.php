<?php
/**
 * Filter
 *
 * This class deals with filtering arrays of things
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

class Filter {
    
    public function filterCriteriaNotReadOnly(&$criteria){
        
        if (!$criteria) return false;
 
        $result = array_filter($criteria, function($obj){
            
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
    public function filterUnits(&$units, $filters){
        
        if (!$units) return false;
        
        $result = array_filter($units, function($obj) use ($filters) {
            
            // If the filters aren't set, return true as that will return all of the units
            if (!$filters || !isset($filters['conjunction']) || !isset($filters['field']) || !isset($filters['value'])) return true;
            
            switch($filters['conjunction'])
            {
                
                // Filter by if x IS y
                case 'is':
                                        
                    switch($filters['field'])
                    {
                        case 'name':
                            
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if (strcasecmp($obj->getName(), $val) == 0){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                
                                return (strcasecmp($obj->getName(), $filters['value']) == 0);
                                
                            }
                            
                        break;
                        case 'number':
                                                        
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if ($obj->getUnitNumber() == $val){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                return ($obj->getUnitNumber() == $filters['value']);
                            }
                            
                        break;
                        case 'code':
                            
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if (strcasecmp($obj->getCode(), $val) == 0){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                return (strcasecmp($obj->getCode(), $filters['value']) == 0);
                            }
                            
                        break;
                    }
                    
                break;
            
                // Filter by if x IS NOT y
                case 'is not':
                                        
                    switch($filters['field'])
                    {
                        case 'name':
                            
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if (strcasecmp($obj->getName(), $val) <> 0){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                
                                return (strcasecmp($obj->getName(), $filters['value']) <> 0);
                                
                            }
                            
                        break;
                        case 'number':
                            
                             // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if ($obj->getUnitNumber() != $val){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                return ($obj->getUnitNumber() != $filters['value']);
                            }
                            
                        break;
                        case 'code':
                            
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if (strcasecmp($obj->getCode(), $val) <> 0){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                return (strcasecmp($obj->getCode(), $filters['value']) <> 0);
                            }
                            
                        break;
                    }
                    
                break;
            
                // Filter by if x STARTS WITH y
                case 'contains':
                    
                    switch($filters['field'])
                    {
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
                                        
                    switch($filters['field'])
                    {
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
                    
                    switch($filters['field'])
                    {
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
                    if (is_array($filters['value'])){
                        $filters['value'] = reset($filters['value']);
                    }
                    
                    switch($filters['field'])
                    {
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
    public function filterCriteria(&$criteria, $filters){
        
        if (!$criteria) return false;
        
        // Strip backslashes
        $filters['value'] = \gt_strip_slashes($filters['value']);
        
        $result = array_filter($criteria, function($obj) use ($filters) {
            
            // If the filters aren't set, return true as that will return all of the units
            if (!$filters || !isset($filters['conjunction']) || !isset($filters['field']) || !isset($filters['value'])) return true;
            
            switch($filters['conjunction'])
            {
                
                // Filter by if x IS y
                case 'is':
                    
                    switch($filters['field'])
                    {
                        case 'name':
                            
                            // If it's an array, match any of them
                            if (is_array($filters['value'])){
                                
                                foreach($filters['value'] as $val){
                                    
                                    if (strcasecmp($obj->getName(), $val) == 0){
                                        return true;
                                    }
                                    
                                }
                                
                            }
                            
                            // Otherwise
                            else {
                                return (strcasecmp($obj->getName(), $filters['value']) == 0);
                            }
                            
                        break;
                    }
                    
                break;
            
                // Filter by if x IS NOT y
                case 'is not':                    
                    
                    switch($filters['field'])
                    {
                        case 'name':
                            
                            if (is_array($filters['value'])){
                                
                                $result = true;
                                
                                foreach($filters['value'] as $val){
                                    
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
                    
                    switch($filters['field'])
                    {
                        case 'name':
                            return \gt_str_contains($obj->getName(), $filters['value']);
                        break;
                    }
                    
                break;
            
                // Filter by if x STARTS WITH y
                case 'starts':
                                        
                    switch($filters['field'])
                    {
                        case 'name':
                            return \gt_str_starts($obj->getName(), $filters['value']);
                        break;
                    }
                    
                break;
            
                // Filter by if x ENDS WITH y
                case 'ends':
                    
                    switch($filters['field'])
                    {
                        case 'name':
                            return \gt_str_ends($obj->getName(), $filters['value']);
                        break;
                    }
                    
                break;
            
                case 'matches':
            
                    // If it's an array, set it to the first element as we only use one
                    if (is_array($filters['value'])){
                        $filters['value'] = reset($filters['value']);
                    }
                    
                    switch($filters['field'])
                    {
                        case 'name':
                            return ( preg_match($filters['value'], $obj->getName()) );
                        break;
                    }
                    
                break;
            
            }
            
        });
        
        return $result;
        
    }
    
    
    public static function getAllFilters(){
        return array('is', 'is not', 'contains', 'starts', 'ends', 'matches');
    }
    
    public static function getFilterableFields($object){
        
        if ($object == 'unit'){
            return array('name', 'number', 'code');
        } elseif ($object == 'criterion'){
            return array('name');
        }
        
    }
    
}
