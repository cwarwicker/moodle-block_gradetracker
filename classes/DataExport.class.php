<?php

namespace GT;

class DataExport {
   
    
    /**
     * Select all the QoE records to export
     * @global \GT\type $DB
     * @return type
     */
    public function getUsersQoe() {
        
        global $DB;
        return $DB->get_records_sql('SELECT uq.id,
                                    u.username as Username,
                                    u.firstname as First_Name,
                                    u.lastname as Last_Name,
                                    qs.name as Subject,
                                    qt.name as Qual,
                                    qt.lvl as Level,
                                    qg.grade as Grade, 
                                    uq.examyear as Year
                                    FROM {bcgt_user_qoe} uq
                                    INNER JOIN {user} u ON uq.userid = u.id
                                    INNER JOIN {bcgt_qoe_grades} qg ON uq.qoegradeid = qg.id
                                    INNER JOIN {bcgt_qoe_types} qt ON qg.qoeid = qt.id
                                    INNER JOIN {bcgt_qoe_subjects} qs ON uq.qoesubjectid=qs.id
                                    ORDER BY  Last_Name, First_Name, Qual, Level, Subject');
        
    }
    
    /**
     * Select all the QoE score records to export
     * @global \GT\type $DB
     * @return type
     */
    public function getUsersAverageGCSE(){
        
        global $DB;
        return $DB->get_records_sql("SELECT uqs.id, u.username, uqs.score
                                    FROM {bcgt_user_qoe_scores} uqs
                                    INNER JOIN {user} u ON u.id = uqs.userid
                                    ORDER BY u.username");
        
    }
    
    /**
     * Select all the Target Grade records to export
     * @global \GT\type $DB
     * @return type
     */
    public function getUsersTg($options = array()) {
        
        global $DB, $GT;
        
        $params = array();
        
        $sql = "SELECT DISTINCT ";
                    
        if (in_array('course', $options)){
            $sql .= $DB->sql_concat('ug.id', "'_'", 'ra.id') . ' as id, ';
        } else {
            $sql .= "ug.id, ";
        }
                    
        $sql .= "qstr.name as qualtype,
                ql.name as quallevel,
                qsub.name as qualsubtype,
                q.name as qualname,
                u.username as username,
                qba.name as targetgrade,
                qba2.name as weightedtargetgrade,
                uqs.score as avggcse ";
        
        if (in_array('course', $options)){
            $sql .= ", c.fullname, c.shortname, c.idnumber as cidnumber ";
        }
                                   
        $sql .= "FROM {bcgt_user_grades} ug
                INNER JOIN {user} u ON ug.userid = u.id
                INNER JOIN {bcgt_qualifications} q on ug.qualid = q.id
                INNER JOIN {bcgt_qual_builds} qb on q.buildid = qb.id
                INNER JOIN {bcgt_qual_structures} qstr on qb.structureid = qstr.id
                INNER JOIN {bcgt_qual_subtypes} qsub on qb.subtypeid = qsub.id
                INNER JOIN {bcgt_qual_levels} ql on qb.levelid = ql.id
                INNER JOIN {bcgt_qual_build_awards} qba on ug.grade = qba.id
                LEFT JOIN {bcgt_user_grades} ug2 ON (ug2.userid = ug.userid AND ug2.qualid = ug.qualid AND ug2.type = 'weighted_target')
                LEFT JOIN {bcgt_qual_build_awards} qba2 on ug2.grade = qba2.id
                LEFT JOIN {bcgt_user_qoe_scores} uqs on u.id = uqs.userid ";
        
        if (in_array('course', $options)){
                
                $roles = $GT->getStudentRoles();
                $in = \gt_create_sql_placeholders($roles);
            
                $sql .= "LEFT JOIN {bcgt_course_quals} cq ON cq.qualid = q.id
                         LEFT JOIN {course} c ON c.id = cq.courseid 
                         LEFT JOIN {context} x ON (x.instanceid = c.id AND x.contextlevel = ?)
                         LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid = x.id) 
                         LEFT JOIN {role} r ON (r.id = ra.roleid AND r.shortname IN ({$in}))
                                    ";
                
                $params[] = CONTEXT_COURSE;
                $params = array_merge($params, $roles);
                
        }
        
        $sql .= "WHERE ug.type = 'target' ";
        
        if (in_array('course', $options)){
            $sql .= "AND ra.id IS NOT NULL ";
        }
        
        $sql .= "ORDER BY qstr.name, ql.ordernum, qsub.name, q.name";
                        
        return $DB->get_records_sql($sql, $params);
        
    }
    
    /**
     * Select all the aspirational grades to export
     * @global \GT\type $DB
     * @return type
     */
    public function getUsersAg() {
        
        global $DB;
        return $DB->get_records_sql('SELECT 
                                    ug.id,
                                    qstr.name as qualtype,
                                    ql.name as quallevel,
                                    qsub.name as qualsubtype,
                                    q.name as qualname,
                                    u.username as username,
                                    qba.name as aspgrade
                                    FROM {bcgt_user_grades} ug
                                    INNER JOIN {user} u ON ug.userid = u.id
                                    INNER JOIN {bcgt_qualifications} q on ug.qualid = q.id
                                    INNER JOIN {bcgt_qual_builds} qb on q.buildid = qb.id
                                    INNER JOIN {bcgt_qual_structures} qstr on qb.structureid = qstr.id
                                    INNER JOIN {bcgt_qual_subtypes} qsub on qb.subtypeid = qsub.id
                                    INNER JOIN {bcgt_qual_levels} ql on qb.levelid = ql.id
                                    INNER JOIN {bcgt_qual_build_awards} qba on ug.grade = qba.id
                                    WHERE ug.type = "aspirational"
                                    ORDER BY qstr.name, ql.ordernum, qsub.name, q.name');
        
    }
    
    /**
     * Select all the CETA grades to export
     * @global \GT\type $DB
     * @return type
     */
    public function getUsersCg() {
        
        global $DB;
        return $DB->get_records_sql('SELECT 
                                    ug.id,
                                    qstr.name as qualtype,
                                    ql.name as quallevel,
                                    qsub.name as qualsubtype,
                                    q.name as qualname,
                                    u.username as username,
                                    qba.name as ceta,
                                    c.shortname as shortname,
                                    c.idnumber as idnumber,
                                    c.id as courseid
                                    FROM {bcgt_user_grades} ug
                                    INNER JOIN {user} u ON ug.userid = u.id
                                    LEFT JOIN {bcgt_qualifications} q on ug.qualid = q.id
                                    LEFT JOIN {bcgt_qual_builds} qb on q.buildid = qb.id
                                    LEFT JOIN {bcgt_qual_structures} qstr on qb.structureid = qstr.id
                                    LEFT JOIN {bcgt_qual_subtypes} qsub on qb.subtypeid = qsub.id
                                    LEFT JOIN {bcgt_qual_levels} ql on qb.levelid = ql.id
                                    LEFT JOIN {bcgt_qual_build_awards} qba on ug.grade = qba.id
                                    LEFT JOIN {course} c on ug.courseid = c.id
                                    WHERE ug.type = "ceta"
                                    ORDER BY qstr.name, ql.ordernum, qsub.name, q.name');
        
    }
    
    /**
     * Select all the weighting coefficients to export
     * @global \GT\type $DB
     * @return type
     */
    public function getWCoe() {
        
        global $DB;
        return $DB->get_records_sql('SELECT 
                                    qa.id, 
                                    qs.name as qualtype,
                                    ql.name as quallevel,
                                    qsub.name as qualsubtype,
                                    q.name as qualname,
                                    qa.attribute as percentilenumber,
                                    qa.value
                                    FROM {bcgt_qual_attributes} qa
                                    INNER JOIN {bcgt_qualifications} q ON qa.qualid = q.id
                                    INNER JOIN {bcgt_qual_builds} qb ON q.buildid = qb.id
                                    INNER JOIN {bcgt_qual_levels} ql ON qb.levelid = ql.id
                                    INNER JOIN {bcgt_qual_structures} qs on qb.structureid = qs.id
                                    INNER JOIN {bcgt_qual_subtypes} qsub on qb.subtypeid = qsub.id');
        
    }
    
    /**
     * Get the assessment grades from the database
     * @global type $DB
     * @global type $GT
     * @param type $assessmentID
     * @return type
     */
    public function getUsersAssGrades($assessmentID){
        
        global $DB, $GT;
        
        $shortnames = $GT->getStudentRoles();
        $in = \gt_create_sql_placeholders($shortnames);
        
        $params = $shortnames;
        $params[] = $assessmentID;
        $params[] = CONTEXT_COURSE;
                
        return $DB->get_records_sql("SELECT DISTINCT
                                    CONCAT(u.id, '_', c.id, '_', q.id) as id,
                                    u.username, u.firstname, u.lastname, 
                                    c.shortname as course, 
                                    s.name as qualtype, l.name as lvl, sb.name as subtype, q.name as qual,
                                    ca.shortname as grade, qba.name as ceta, ua.comments
                                    FROM {bcgt_assessments} a
                                    INNER JOIN {bcgt_assessment_quals} aq on aq.assessmentid = a.id
                                    INNER JOIN {bcgt_qualifications} q ON q.id = aq.qualid
                                    INNER JOIN {bcgt_qual_builds} b ON b.id = q.buildid
                                    INNER JOIN {bcgt_qual_structures} s ON s.id = b.structureid
                                    INNER JOIN {bcgt_qual_levels} l on l.id = b.levelid
                                    INNER JOIN {bcgt_qual_subtypes} sb ON sb.id = b.subtypeid
                                    INNER JOIN {bcgt_course_quals} cq ON cq.qualid = q.id
                                    INNER JOIN {course} c on c.id = cq.courseid
                                    INNER JOIN {context} x ON x.instanceid = c.id
                                    INNER JOIN {role_assignments} ra ON ra.contextid = x.id
                                    INNER JOIN {role} r ON r.id = ra.roleid
                                    INNER JOIN {user} u ON u.id = ra.userid
                                    INNER JOIN {bcgt_user_quals} uq ON (uq.userid = u.id AND uq.qualid = q.id AND uq.role = 'student')
                                    LEFT JOIN {bcgt_user_assessments} ua ON (ua.userid = u.id AND ua.assessmentid = a.id AND ua.qualid = q.id)
                                    LEFT JOIN {bcgt_criteria_awards} ca ON ca.id = ua.grade
                                    LEFT JOIN {bcgt_qual_build_awards} qba ON qba.id = ua.ceta
                                    WHERE r.shortname IN ({$in}) AND a.id = ? AND x.contextlevel = ? AND u.deleted = 0 AND a.deleted = 0 AND q.deleted = 0
                                    ORDER BY c.shortname, q.name, u.lastname, u.firstname", $params);
        
        
    }
    
    /**
     * Download the QoE records in a CSV
     * @global type $CFG
     * @param type $all_users_qoe
     */
    public function downloadUsersQoe($all_users_qoe) {
        if($all_users_qoe){
            global $CFG;
            $file = fopen("{$CFG->dataroot}/gt_qoe.csv", "w");
            $user_arrays = [array('Username', 'First Name', 'Last Name', 'Subject', 'Qual', 'Level', 'Grade', 'Year')];
            foreach ($all_users_qoe as $qu) {
                $user_arrays[] = array($qu->username, $qu->first_name, $qu->last_name, $qu->subject, $qu->qual, $qu->level, $qu->grade, $qu->year);
            }
            foreach ($user_arrays as $fields) {
                fputcsv($file, $fields);
            }

            send_file("{$CFG->dataroot}/gt_qoe.csv", "gt_qoe.csv");
            fclose($file);
         }else {
             echo get_string('nodata', 'block_gradetracker');
         }
    }
    
    /**
     * Download the target grade records in a CSV
     * @global \GT\type $CFG
     * @param type $all_users_tg
     */
    public function downloadUsersTg($all_users_tg, $options) {
        if($all_users_tg){
            global $CFG;
            $file = fopen("{$CFG->dataroot}/gt_tg.csv", "w");
            $headers = array('QualType', 'QualLevel', 'QualSubType', 'QualName', 'Username', 'TargetGrade', 'WeightedTargetGrade', 'AvgGCSE');
            if (in_array('course', $options)){
                $headers[] = 'Course Full';
                $headers[] = 'Course Short';
                $headers[] = 'Course ID Number';
            }
            $user_arrays = [$headers];
            
            foreach ($all_users_tg as $tu) {
                $data = array($tu->qualtype, $tu->quallevel, $tu->qualsubtype, $tu->qualname, $tu->username, $tu->targetgrade, $tu->weightedtargetgrade, $tu->avggcse);
                if (in_array('course', $options)){
                    $data[] = $tu->fullname;
                    $data[] = $tu->shortname;
                    $data[] = $tu->cidnumber;
                }
                $user_arrays[] = $data;
            }
            
            foreach ($user_arrays as $fields) {
                fputcsv($file, $fields);
            }
            
            send_file("{$CFG->dataroot}/gt_tg.csv", "gt_tg.csv");
            fclose($file);
         }else {
             echo get_string('nodata', 'block_gradetracker');
         }
    }
    
    /**
     * Download the aspirational grades in a CSV
     * @global \GT\type $CFG
     * @param type $all_users_ag
     */
    public function downloadUsersAg($all_users_ag) {
        if($all_users_ag){
            global $CFG;
            $file = fopen("{$CFG->dataroot}/gt_ag.csv", "w");
            $user_arrays = [array('QualType', 'QualLevel', 'QualSubType', 'QualName', 'Username', 'AspirationalGrade')];
                        
            foreach ($all_users_ag as $au) {
                $user_arrays[] = array($au->qualtype, $au->quallevel, $au->qualsubtype, $au->qualname, $au->username, $au->aspgrade );
            }
            
            foreach ($user_arrays as $fields) {
                fputcsv($file, $fields);
            }
            
            send_file("{$CFG->dataroot}/gt_ag.csv", "gt_ag.csv");
            fclose($file);
         }else {
             echo get_string('nodata', 'block_gradetracker');
         }
    }
    
    /**
     * Download the CETA grades in a CSV
     * @global \GT\type $CFG
     * @param type $all_users_cg
     */
    public function downloadUsersCg($all_users_cg) {
        if($all_users_cg){
            global $CFG;
            $file = fopen("{$CFG->dataroot}/gt_cg.csv", "w");
            $user_arrays = [array('QualType', 'QualLevel', 'QualSubType', 'QualName', 'Username', 'Ceta', 'Course')];
                
            foreach ($all_users_cg as $cu) {
                
                if(isset($_POST['export'])){
                    $selectedoption = $_POST['export'];
                    if ($selectedoption == 'shortname'){
                        $courseinfo = $cu->shortname;
                    }
                    elseif ($selectedoption == 'idnumber'){
                        $courseinfo = $cu->courseid;
                    }
                }
            
                $user_arrays[] = array($cu->qualtype, $cu->quallevel, $cu->qualsubtype, $cu->qualname, $cu->username, $cu->ceta, $courseinfo);
            
            }
            
            foreach ($user_arrays as $fields) {
                fputcsv($file, $fields);
            }
            
            send_file("{$CFG->dataroot}/gt_cg.csv", "gt_cg.csv");
            fclose($file);
         } else {
             echo get_string('nodata', 'block_gradetracker');
         }
    }
    
    /**
     * Download the weighting coefficients in a CSV
     * @global \GT\type $CFG
     * @param type $all_wcoe
     */
    public function downloadWCoe($all_wcoe) {
        if($all_wcoe){
            global $CFG;
            $file = fopen("{$CFG->dataroot}/gt_wcoe.csv", "w");
            $wcoe_arrays = [array('QualType', 'QualLevel', 'QualSubType', 'QualName', 'PercentileNumber', 'Value')];
            
            foreach ($all_wcoe as $tu) {
                $wcoe_arrays[] = array($tu->qualtype, $tu->quallevel, $tu->qualsubtype, $tu->qualname, $tu->percentilenumber, $tu->value);
            }
            
            foreach ($wcoe_arrays as $fields) {
                fputcsv($file, $fields);
            }
            
            send_file("{$CFG->dataroot}/gt_wcoe.csv", "gt_wcoe.csv");
            fclose($file);
         }else {
            echo get_string('nodata', 'block_gradetracker');
         }
    }
    
    /**
     * Download the assessment grades in a CSV
     * @global \GT\type $CFG
     * @param type $data
     * @param type $names
     */
    public function downloadUsersAssGrades($data, $names = false) {
            
        if($data)
        {
         
            global $CFG;
            
            $file = fopen("{$CFG->dataroot}/gt_assgrades.csv", "w");
            $headers = \GT\CSV\Template::$headersAssGrades;
            
            if ($names){
                $headers[] = 'First Name';
                $headers[] = 'Last Name';
            }
            
            fputcsv($file, $headers);
          
            foreach($data as $row)
            {
                                
                $values = array(
                    $row->username,
                    $row->course,
                    $row->qualtype,
                    $row->lvl,
                    $row->subtype,
                    $row->qual,
                    $row->grade,
                    $row->ceta,
                    $row->comments
                );
                
                if ($names){
                    $values[] = $row->firstname;
                    $values[] = $row->lastname;
                }
                
                fputcsv($file, $values);
            }

            send_file("{$CFG->dataroot}/gt_assgrades.csv", "gt_assgrades.csv");
            fclose($file);
            
        } else {
            echo get_string('nodata', 'block_gradetracker');
        }
         
    }
    
    /**
     * Download the average gcse scores in a CSV
     * @param type $data
     */
    public function downloadUsersAverageGCSE($data){
    
        if($data)
        {
         
            global $CFG;
            
            $file = fopen("{$CFG->dataroot}/gt_avggcse.csv", "w");
            $headers = \GT\CSV\Template::$headersAvgGCSE;
            fputcsv($file, $headers);
          
            foreach($data as $row)
            {
                                
                $values = array(
                    $row->username,
                    $row->score
                );
                
                fputcsv($file, $values);
                
            }

            fclose($file);
            send_file("{$CFG->dataroot}/gt_avggcse.csv", "gt_avggcse.csv");
            
        } else {
            echo get_string('nodata', 'block_gradetracker');
        }
        
    }
    
    public function downloadSQLReport($id){
        
        global $CFG, $DB;
        
        $query = $DB->get_record("bcgt_query_reporting", array("id" => $id));
        if (!$query){
            echo get_string('nodata', 'block_gradetracker');
            return;
        }

        $results = $DB->get_records_sql($query->query);
        if ($results)
        {
            
            $first = reset($results);
            $keys = array_keys( (array)$first );
            $reporting_arrays = [$keys];

            foreach ($results as $result) {
                $reporting_arrays[] = (array)$result;
            }

            $file = fopen("{$CFG->dataroot}/gt_reporting.csv", "w");

            foreach ($reporting_arrays as $fields) {
                fputcsv($file, $fields);
            }

            \send_file("{$CFG->dataroot}/gt_reporting.csv", "gt_reporting.csv");
            fclose($file);
            
        } else {
            echo get_string('nodata', 'block_gradetracker');
        }
        
    }
    
    
}
