<?php
/**
 * GT\Qualification\Data
 *
 * This class handles all the reporting functionality and data for Qualifications
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

namespace GT\Qualification;

require_once 'Qualification.class.php';

class DataQualification extends \GT\Qualification {
    
    /**
     * Build the SQL query to get a full report on all the students on this qualification
     * @global \GT\Qualification\type $DB
     * @param type $unitAwards
     * @param type $view
     * @param type $criteriaNames
     * @param type $shortCriteriaNames
     * @param type $courseID
     * @param type $specificAwards
     * @return type
     */
    public function getQualificationReportStudents($unitAwards, $view, $criteriaNames, $shortCriteriaNames, $courseID = false, $specificAwards = false){
        
        global $DB;
        
                                  
        $usedFieldNames = array();
        $usedFieldNames['unit'] = array();
        $usedFieldNames['crit'] = array();
        
        $inSpecificAwards = '';
        if ($specificAwards){
            $inSpecificAwards = " OR a.name IN (".  gt_create_sql_placeholders($specificAwards).") ";
        }
        
        $params = array();
        
        $sql = "SELECT DISTINCT 
                u.id, u.username, u.firstname, u.lastname, 
                tg.name as targetgrade, tg.id as targetgradeid, tg.rank as targetgraderank,
                wtg.name as weightedtargetgrade, wtg.id as weightedtargetgradeid,
                ag.name as aspirationalgrade, ag.id as aspirationalgradeid, ag.rank as aspirationalgraderank,
                cg.name as cetagrade, cg.id as cetagradeid,
                qa.name as qualaward,
                qa.type as qualawardtype,
                qa.rank as qualawardrank,
                uu.cnt as unitscount, uu.ccnt as creditscount,
                uua.cnt as unitsawardedcount, uua.ccnt as creditsawardedcount,
                tbl_cA_all.cnt as critawardcnt_all,tbl_c_all.cnt as critcnt_all,";
        
                if ($unitAwards)
                {
                    foreach($unitAwards as $award)
                    {
                        $fieldName = \gt_make_db_field_safe($award, $usedFieldNames['unit']);
                        $sql .= "tbl_uA_{$fieldName}.cnt as unitawardcnt_{$fieldName},";
                    }
                }       
                
                if ($view == 'view-criteria-short' && $shortCriteriaNames)
                {

                    foreach($shortCriteriaNames as $name)
                    {
                        $name = \gt_make_db_field_safe($name, $usedFieldNames['crit']);
                        $sql .= "tbl_cA_{$name}.cnt as critawardcnt_{$name},";
                        $sql .= "tbl_c_{$name}.cnt as critcnt_{$name},";
                    }

                }
                elseif ($view == 'view-criteria-full' && $criteriaNames)
                {

                    foreach($criteriaNames as $name)
                    {
                        $name = \gt_make_db_field_safe($name['name'], $usedFieldNames['crit']);
                        $sql .= "tbl_cA_{$name}.cnt as critawardcnt_{$name},";
                        $sql .= "tbl_c_{$name}.cnt as critcnt_{$name},";
                    }

                }
        
        $sql .= "1
                FROM {user} u 
                INNER JOIN {bcgt_user_quals} uq ON uq.userid = u.id 

                LEFT JOIN ( 
                    SELECT a.id, a.name, a.rank, ug.userid 
                    FROM {bcgt_qual_build_awards} a 
                    INNER JOIN {bcgt_user_grades} ug ON ug.grade = a.id 
                    WHERE ug.type = 'target' AND ug.qualid = ? 
                ) tg ON tg.userid = u.id 
                
                LEFT JOIN ( 
                    SELECT a.id, a.name, ug.userid 
                    FROM {bcgt_qual_build_awards} a 
                    INNER JOIN {bcgt_user_grades} ug ON ug.grade = a.id 
                    WHERE ug.type = 'weighted_target' AND ug.qualid = ? 
                ) wtg ON tg.userid = u.id 

                LEFT JOIN ( 
                    SELECT a.id, a.name, a.rank, ug.userid 
                    FROM {bcgt_qual_build_awards} a
                    INNER JOIN {bcgt_user_grades} ug ON ug.grade = a.id
                    WHERE ug.type = 'aspirational' AND ug.qualid = ? 
                ) ag ON ag.userid = u.id 
                
                LEFT JOIN (  
                    SELECT a.id, a.name, ug.userid 
                    FROM {bcgt_qual_build_awards} a
                    INNER JOIN {bcgt_user_grades} ug ON ug.grade = a.id
                    WHERE ug.type = 'ceta' AND ug.qualid = ? 
                ) cg ON cg.userid = u.id 

                LEFT JOIN (
                    SELECT t.userid, t.type, a.name, a.rank
                    FROM {bcgt_user_qual_awards} t
                    INNER JOIN {bcgt_qual_build_awards} a ON a.id = t.awardid
                    WHERE t.qualid = ?
                    AND (
                            t.type = 'average' AND
                            NOT EXISTS(
                                SELECT * FROM {bcgt_user_qual_awards} t2
                                WHERE t.qualid = t2.qualid AND t.userid = t2.userid AND t2.type = 'final'
                            ) 
                            OR t.type = 'final'
                        )
                ) qa ON qa.userid = u.id
                
                LEFT JOIN (
                    SELECT uqu.userid, COUNT(uqu.id) as cnt, SUM(u.credits) as ccnt
                    FROM {bcgt_user_qual_units} uqu
                    INNER JOIN {bcgt_units} u ON u.id = uqu.unitid
                    INNER JOIN {bcgt_qual_units} qu ON qu.unitid = uqu.unitid AND qu.qualid = uqu.qualid
                    WHERE uqu.qualid = ? AND uqu.role = 'STUDENT' AND u.deleted = 0
                    GROUP BY userid
                ) uu ON uu.userid = u.id

                LEFT JOIN (
                    SELECT uu.userid, COUNT(uu.id) as cnt, SUM(u.credits) as ccnt
                    FROM {bcgt_user_units} uu
                    INNER JOIN {bcgt_qual_units} qu ON qu.unitid = uu.unitid
                    INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uu.userid AND uqu.unitid = uu.unitid AND uqu.qualid = qu.qualid
                    INNER JOIN {bcgt_units} u ON u.id = uu.unitid
                    WHERE uu.awardid > 0 AND qu.qualid = ? AND u.deleted = 0
                    GROUP BY uu.userid
                ) uua ON uua.userid = u.id
                
                ";
        
        $params[] = $this->id;
        $params[] = $this->id;
        $params[] = $this->id;
        $params[] = $this->id;
        $params[] = $this->id;
        $params[] = $this->id;
        $params[] = $this->id;
        
        // Unit awards
        if ($unitAwards)
        {
            foreach($unitAwards as $award)
            {
                $fieldName = \gt_make_db_field_safe($award, $usedFieldNames['unit']);
                $sql .= "LEFT JOIN (

                            SELECT uu.userid, COUNT(uu.id) as cnt
                                FROM {bcgt_user_units} uu
                                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = uu.unitid
                                INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uu.userid AND uqu.unitid = uu.unitid AND uqu.qualid = qu.qualid
                                INNER JOIN {bcgt_unit_awards} a ON a.id = uu.awardid
                                INNER JOIN {bcgt_units} u ON u.id = uu.unitid
                                WHERE uu.awardid > 0 AND qu.qualid = ? AND a.name = ? AND u.deleted = 0
                            GROUP BY uu.userid

                        ) tbl_uA_{$fieldName} ON tbl_uA_{$fieldName}.userid = u.id ";
                $params[] = $this->id;
                $params[] = $award;
            }
        }
        
        // Criteria
                        
        // Short criteria names, e.g. for BTEC where we want to group them by first letter "P", "M", "D"
        if ($view == 'view-criteria-short' && $shortCriteriaNames)
        {
            
            foreach($shortCriteriaNames as $name)
            {
                
                $name = \gt_make_db_field_safe($name, $usedFieldNames['crit']);
                
                // How many awarded
                $sql .= "LEFT JOIN ( 
                            SELECT uc.userid, COUNT(uc.id) as cnt
                            FROM {bcgt_user_criteria} uc
                            INNER JOIN {bcgt_criteria} c on c.id = uc.critid
                            INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uc.userid AND uqu.unitid = c.unitid
                            INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                            INNER JOIN {bcgt_criteria_awards} a ON a.id = uc.awardid
                            WHERE uqu.qualid = ? AND (a.met = 1 {$inSpecificAwards}) AND c.name LIKE ? AND c.deleted = 0
                            GROUP BY uc.userid
                        ) tbl_cA_{$name} ON tbl_cA_{$name}.userid = u.id ";
                $params[] = $this->id;
                
                if ($specificAwards){
                    foreach($specificAwards as $specificName){
                        $params[] = $specificName;
                    }
                }
                
                $params[] = "{$name}%";
                
                // How many in total
                $sql .= "LEFT JOIN (

                            SELECT COUNT(c.id) as cnt, uqu.userid
                            FROM {bcgt_user_qual_units} uqu
                            INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                            INNER JOIN {bcgt_criteria} c ON c.unitid = uqu.unitid
                            WHERE uqu.qualid = ? AND uqu.role = 'student' AND c.name LIKE ? AND c.deleted = 0
                            GROUP BY uqu.userid

                        ) tbl_c_{$name} ON tbl_c_{$name}.userid = u.id ";
                
                $params[] = $this->id;
                $params[] = "{$name}%";
                
            }
            
        }
        
        // Full criteria names, where we only have a few different criteria names across all units and we want
        // to see the stats for each one, e.g. "Task 1", "Task 2"
        elseif ($view == 'view-criteria-full' && $criteriaNames)
        {
                        
            foreach($criteriaNames as $name)
            {
                
                $name = \gt_make_db_field_safe($name['name'], $usedFieldNames['crit']);
                
                // How many awarded
                $sql .= "LEFT JOIN ( 
                            SELECT uc.userid, COUNT(uc.id) as cnt
                            FROM {bcgt_user_criteria} uc
                            INNER JOIN {bcgt_criteria} c on c.id = uc.critid
                            INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uc.userid AND uqu.unitid = c.unitid
                            INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                            INNER JOIN {bcgt_criteria_awards} a ON a.id = uc.awardid
                            WHERE uqu.qualid = ? AND (a.met = 1 {$inSpecificAwards}) AND c.name LIKE ? AND c.deleted = 0
                            GROUP BY uc.userid
                        ) tbl_cA_{$name} ON tbl_cA_{$name}.userid = u.id ";
                $params[] = $this->id;
                
                if ($specificAwards){
                    foreach($specificAwards as $specificName){
                        $params[] = $specificName;
                    }
                }
                
                $params[] = $name;
                
                // How many in total
                $sql .= "LEFT JOIN (

                            SELECT COUNT(c.id) as cnt, uqu.userid
                            FROM {bcgt_user_qual_units} uqu
                            INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                            INNER JOIN {bcgt_criteria} c ON c.unitid = uqu.unitid
                            WHERE uqu.qualid = ? AND uqu.role = 'student' AND c.name LIKE ? AND c.deleted = 0
                            GROUP BY uqu.userid

                        ) tbl_c_{$name} ON tbl_c_{$name}.userid = u.id ";
                
                $params[] = $this->id;
                $params[] = $name;
                
            }
            
        }
        
        $sql .= "
                LEFT JOIN ( 
                    SELECT uc.userid, COUNT(uc.id) as cnt
                    FROM {bcgt_user_criteria} uc
                    INNER JOIN {bcgt_criteria} c on c.id = uc.critid
                    INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uc.userid AND uqu.unitid = c.unitid
                    INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                    INNER JOIN {bcgt_criteria_awards} a ON a.id = uc.awardid
                    WHERE uqu.qualid = ? AND (a.met = 1 {$inSpecificAwards}) AND c.deleted = 0
                    GROUP BY uc.userid
                ) tbl_cA_all ON tbl_cA_all.userid = u.id ";
                
        $params[] = $this->id;
        
        if ($specificAwards){
            foreach($specificAwards as $specificName){
                $params[] = $specificName;
            }
        }
                    
        $sql .= "LEFT JOIN (
                    SELECT COUNT(c.id) as cnt, uqu.userid
                    FROM {bcgt_user_qual_units} uqu
                    INNER JOIN {bcgt_qual_units} qu ON (qu.qualid = uqu.qualid AND qu.unitid = uqu.unitid)
                    INNER JOIN {bcgt_criteria} c ON c.unitid = uqu.unitid
                    WHERE uqu.qualid = ? AND uqu.role = 'student' AND c.deleted = 0
                    GROUP BY uqu.userid

                ) tbl_c_all ON tbl_c_all.userid = u.id  ";
        
        $params[] = $this->id;
        
        // Course
        if ($courseID > 0){
            $sql .= "LEFT JOIN (
                        SELECT ra.userid
                        FROM {role_assignments} ra
                        INNER JOIN {context} x ON x.id = ra.contextid
                        WHERE x.contextlevel = ? AND x.instanceid = ?
                    ) tbl_role_assign ON tbl_role_assign.userid = u.id";
            $params[] = CONTEXT_COURSE;
            $params[] = $courseID;
        }

        $sql .= " WHERE uq.qualid = ? AND uq.role = 'student' AND u.deleted = 0 ";
        $params[] = $this->id;
        
        // Course
        if ($courseID > 0){
            $sql .= "AND tbl_role_assign.userid IS NOT NULL ";
        }
        
        $sql .= "ORDER BY u.lastname, u.firstname, u.username";
                                               
        $records = $DB->get_records_sql($sql, $params);
        
        return $records;
        
    }
    /**
     * Build the SQL query to get the full report on all the units on this qual
     * @global type $CFG
     * @global \GT\Qualification\type $DB
     * @param type $unitAwards
     * @return type
     */
    public function getQualificationReportUnits($unitAwards){
        
        global $CFG, $DB;
        
        $usedFieldNames = array();
        $usedFieldNames['unit'] = array();
        
        $params = array();
        
        $sql = "SELECT u.id, uu.cnt as studsonunit, uua.cnt as studsawardedunit, ";
        
        if ($unitAwards)
        {
            foreach($unitAwards as $award)
            {
                $fieldName = \gt_make_db_field_safe($award, $usedFieldNames['unit']);
                $sql .= "tbl_uA_{$fieldName}.cnt as unitawardcnt_{$fieldName},";
            }
        }       

        
        $sql .= "1
                FROM {bcgt_units} u
                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                
                LEFT JOIN (
                    SELECT uqu.unitid, COUNT(uqu.id) as cnt
                    FROM {bcgt_user_qual_units} uqu
                    WHERE uqu.qualid = ? AND uqu.role = 'student'
                    GROUP BY uqu.unitid
                ) as uu ON uu.unitid = u.id
                
                LEFT JOIN (
                    SELECT uu.unitid, COUNT(uu.id) as cnt
                    FROM {bcgt_user_units} uu
                    INNER JOIN {bcgt_qual_units} qu ON qu.unitid = uu.unitid
                    INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uu.userid AND uqu.unitid = uu.unitid AND uqu.qualid = qu.qualid
                    WHERE uu.awardid > 0 AND qu.qualid = ?
                    GROUP BY uu.unitid
                ) as uua ON uua.unitid = u.id ";
        
        $params[] = $this->id;
        $params[] = $this->id;
        
         
        // Unit awards
        if ($unitAwards)
        {
            foreach($unitAwards as $award)
            {
                $fieldName = \gt_make_db_field_safe($award, $usedFieldNames['unit']);
                $sql .= "LEFT JOIN (

                            SELECT uu.unitid, COUNT(uu.id) as cnt
                                FROM {bcgt_user_units} uu
                                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = uu.unitid
                                INNER JOIN {bcgt_user_qual_units} uqu ON uqu.userid = uu.userid AND uqu.unitid = uu.unitid AND uqu.qualid = qu.qualid
                                INNER JOIN {bcgt_unit_awards} a ON a.id = uu.awardid
                                WHERE uu.awardid > 0 AND qu.qualid = ? AND a.name = ?
                            GROUP BY uu.unitid

                        ) tbl_uA_{$fieldName} ON tbl_uA_{$fieldName}.unitid = u.id ";
                $params[] = $this->id;
                $params[] = $award;
            }
        }
        
        
        $sql .= " WHERE qu.qualid = ?
                ORDER BY u.unitnumber * 1, u.unitnumber, u.name";
        
        $params[] = $this->id;

        $records = $DB->get_records_sql($sql, $params);
                
        return $records;
        
    }
    
    /**
     * Count the criteria on a qual, with a given grading structure id
     * @global type $DB
     * @param type $qualID
     * @param type $gradingStructureID
     * @return type
     */
    public function countCriteriaByGradingStructureID($gradingStructureID){
        
        global $DB;
        
        $sql = "SELECT COUNT(distinct c.id) as 'cnt'
                FROM {bcgt_criteria} c
                INNER JOIN {bcgt_units} u ON u.id = c.unitid
                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                WHERE qu.qualid = ? AND c.gradingstructureid = ?";
        
        $record = $DB->get_record_sql($sql, array($this->id, $gradingStructureID));
        
        return ($record) ? $record->cnt : 0;
        
    }
    
    /**
     * Count number of criteria on this qualification with this grading structure that this student has achieved
     * @global \GT\Qualification\type $DB
     * @param type $gradingStructureID
     * @param type $studentID
     * @return type
     */
    public function countCriteriaAwardsByGradingStructure($gradingStructureID, $studentID){
        
        global $DB;
        
        $sql = "SELECT COUNT(uc.id) as 'cnt'
                FROM {bcgt_criteria} c
                INNER JOIN {bcgt_units} u ON u.id = c.unitid
                INNER JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                INNER JOIN {bcgt_user_criteria} uc ON uc.critid = c.id
                INNER JOIN {bcgt_criteria_awards} a ON a.id = uc.awardid
                WHERE qu.qualid = ? AND c.gradingstructureid = ? AND uc.userid = ? AND a.met = 1";
        
        $record = $DB->get_record_sql($sql, array($this->id, $gradingStructureID, $studentID));
        
        return ($record) ? $record->cnt : 0;
        
    }
    
}
