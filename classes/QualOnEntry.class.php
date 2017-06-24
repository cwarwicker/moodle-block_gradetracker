<?php
namespace GT;


class QualOnEntry {
    
    private $id = false;
    private $userID;
    private $gradeID;
    private $subjectID;
    private $year;
    private $grade;
    private $type;
    
    const GCSENORMAL = 'GCSE';
    const GCSESHORT = 'GCSE Short Course';
    const GCSEDOUBLE = 'GCSE Double Award';
    
    public function __construct($id = false) {
        
        global $DB;
        
        if ($id)
        {
            $record = $DB->get_record("bcgt_user_qoe", array("id" => $id));
            if ($record)
            {

                $this->id = $record->id;
                $this->userID = $record->userid;
                $this->gradeID = $record->qoegradeid;
                $this->subjectID = $record->qoesubjectid;
                $this->year = $record->examyear;
                $this->grade = $DB->get_record("bcgt_qoe_grades", array("id" => $this->gradeID));
                if ($this->grade){
                    $this->type = $DB->get_record("bcgt_qoe_types", array("id" => $this->grade->qoeid));
                }

            }
        }
        
    }
    
    public function isValid(){
        return ($this->id !== false);
    }
    
    public function getID(){
        return $this->id;
    }
    
    public function getUserID(){
        return $this->userID;
    }
    
    public function setUserID($id){
        $this->userID = $id;
        return $this;
    }
    
    public function getGradeID(){
        return $this->gradeID;
    }
    
    public function setGradeID($id){
        $this->gradeID = $id;
        return $this;
    }
    
    public function getGradeObject(){
        return $this->grade;
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function getSubjectID(){
        return $this->subjectID;
    }
    
    public function setSubjectID($id){
        $this->subjectID = $id;
        return $this;
    }
    
    public function getYear(){
        return $this->year;
    }
    
    public function setYear($year){
        $this->year = $year;
        return $this;
    }
    
    /**
     * Save user's record
     * @global \GT\type $DB
     * @return type
     */
    public function save(){
        
        global $DB;
        
        $obj = new \stdClass();
        if ($this->isValid()){
            $obj->id = $this->id;
        }
        $obj->userid = $this->userID;
        $obj->qoegradeid = $this->gradeID;
        $obj->qoesubjectid = $this->subjectID;
        $obj->examyear = $this->year;
        
        if ($this->isValid()){
            return $DB->update_record("bcgt_user_qoe", $obj);
        } else {
            return $DB->insert_record("bcgt_user_qoe", $obj);
        }
        
        
    }
    
    /**
     * Get a user's record
     * @global \GT\type $DB
     * @param type $userID
     * @param type $subjectID
     * @return type
     */
    public static function getRecord($userID, $subjectID){
        
        global $DB;
        
        $record = $DB->get_record("bcgt_user_qoe", array("userid" => $userID, "qoesubjectid" => $subjectID));
        return ($record) ? new \GT\QualOnEntry($record->id) : false;
        
    }
    
    /**
     * Convert possible combinations of name and type to standard
     * @param type $name
     * @param type $type
     * @return type
     */
    public static function convertQualName($name, $type)
    {
        
        $newName = $name;
        
        if($name == 'GCSE' || $name == 'GCSEs in Vocational Subjects')
        {
            $newName = self::GCSENORMAL;
            if($type == 'Double' || $type == 'Double Award' || $type == 'GCSE Double Award')
            {
                $newName = self::GCSEDOUBLE;
            }
            elseif($type == 'Short' || $type == 'Short Course' || $type == 'GCSE Short Course' || $type == 'Short Course GCSE')
            {
                $newName = self::GCSESHORT;
            }
        }
        elseif($name == 'Short Course GCSE')
        {
            $newName = self::GCSESHORT;
        }
        
        return trim($newName);
    }
    
    /**
     * Get all the QoE subjects
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllSubjects(){
        
        global $DB;
        return $DB->get_records("bcgt_qoe_subjects", null, "name ASC");
        
    }
    
    /**
     * Get a QoE subject record id
     * @global \GT\type $DB
     * @param type $subject
     * @return type
     */
    public static function getSubject($subject){
        
        global $DB;
        $record = $DB->get_record("bcgt_qoe_subjects", array("name" => $subject));
        return ($record) ? $record->id : false;
        
    }
    
    
    public static function getSubjectName($subjectID){
        
        global $DB;
        $record = $DB->get_record("bcgt_qoe_subjects", array("id" => $subjectID));
        return ($record) ? $record->name : false;
        
    }
    
    /**
     * Save a subject record
     * @global \GT\type $DB
     * @param type $id
     * @param type $name
     * @return type
     */
    public static function saveSubject($id, $name){
        
        global $DB;
        
        $record = new \stdClass();

        $check = $DB->get_record("bcgt_qoe_subjects", array("id" => $id));
        if ($check)
        {
            $record->id = $id;
            $record->name = $name;
            return $DB->update_record("bcgt_qoe_subjects", $record);
        }
        else
        {
            return \GT\QualOnEntry::createSubject($name);
        }
        
    }
    
    /**
     * Delete subjects that we haven't saved
     * @global \GT\type $DB
     * @param type $idArray
     */
    public static function deleteSubjectsNotSaved($idArray){
        
        global $DB;
        
        if ($idArray){
            
            $ph = gt_create_sql_placeholders($idArray);
            return $DB->delete_records_select("bcgt_qoe_subjects", "id NOT IN ({$ph})", $idArray);
            
        } else {
            return $DB->delete_records("bcgt_qoe_subjects");
        }
        
    }
    
     /**
     * Create a QoE grade record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $grade
     * @return type
     */
    public static function createSubject($subject){
        
        global $DB;
        
        $ins = new \stdClass();
        $ins->name = $subject;
        return $DB->insert_record("bcgt_qoe_subjects", $ins);
        
    }
    
     /**
     * Get all the QoE types
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllTypes(){
        
        global $DB;
        return $DB->get_records("bcgt_qoe_types", null, "lvl ASC, name ASC");
        
    }
    
    
    /**
     * Get a QOE type record id
     * @global type $DB
     * @param type $name
     * @param type $type
     * @param type $level
     * @return type
     */
    public static function getQual($name, $level){
        
        global $DB;
        
        //$name = self::convertQualName($name, $type);
        $record = $DB->get_record("bcgt_qoe_types", array("name" => $name, "lvl" => $level));
        return ($record) ? $record->id : false;
        
    }
    
     /**
     * Save a type record
     * @global \GT\type $DB
     * @param type $id
     * @param type $name
     * @return type
     */
    public static function saveType($id, $name, $level, $weight){
        
        global $DB;
                
        $record = new \stdClass();

        $check = $DB->get_record("bcgt_qoe_types", array("id" => $id));
        if ($check)
        {
            $record->id = $id;
            $record->name = $name;
            $record->lvl = $level;
            $record->weighting = $weight;
            return $DB->update_record("bcgt_qoe_types", $record);
        }
        else
        {
            return \GT\QualOnEntry::createQual($name, '', $level, $weight);
        }
        
    }
    
    /**
     * Create a QOE type record
     * @global \GT\type $DB
     * @param type $name
     * @param type $type
     * @param int $level
     * @param int $weight
     * @return type
     */
    public static function createQual($name, $level, $weight = 1){
        
        global $DB;
        
//        $name = self::convertQualName($name, $type);
        $ins = new \stdClass();
        $ins->name = $name;
        $ins->lvl = $level;
        $ins->weighting = $weight;
               
        return $DB->insert_record("bcgt_qoe_types", $ins);
        
    }
    
    /**
     * Delete types that we haven't saved
     * @global \GT\type $DB
     * @param type $idArray
     */
    public static function deleteTypesNotSaved($idArray){
        
        global $DB;
        
        if ($idArray){
            
            $ph = gt_create_sql_placeholders($idArray);
            return $DB->delete_records_select("bcgt_qoe_types", "id NOT IN ({$ph})", $idArray);
            
        } else {
            return $DB->delete_records("bcgt_qoe_types");
        }
        
    }
    
    
    
    
     /**
     * Get all the QoE grades
     * @global \GT\type $DB
     * @return type
     */
    public static function getAllGrades(){
        
        global $DB;
        return $DB->get_records("bcgt_qoe_grades", null, "qoeid ASC, points DESC, grade ASC");
        
    }
    
    
    /**
     * Get a QoE grade record id
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $grade
     * @return type
     */
    public static function getGrade($qualID, $grade){
        
        global $DB;
        $record = $DB->get_record("bcgt_qoe_grades", array("qoeid" => $qualID, "grade" => $grade));
        return ($record) ? $record->id : false;
    }
    
    
    /**
     * Save a grade record
     * @global \GT\type $DB
     * @param type $id
     * @param type $name
     * @return type
     */
    public static function saveGrade($id, $type, $name, $points, $weight){
        
        global $DB;
                
        $record = new \stdClass();

        $check = $DB->get_record("bcgt_qoe_grades", array("id" => $id));
        if ($check)
        {
            $record->id = $id;
            $record->qoeid = (is_numeric($type)) ? $type : 0;
            $record->grade = $name;
            $record->points = $points;
            $record->weighting = $weight;
            return $DB->update_record("bcgt_qoe_grades", $record);
        }
        else
        {
            return \GT\QualOnEntry::createGrade($type, $name, $points, $weight);
        }
        
    }
    
    
    /**
     * Create a QoE grade record
     * @global \GT\type $DB
     * @param type $qualID
     * @param type $grade
     * @return type
     */
    public static function createGrade($qualID, $grade, $points = 0, $weight = 1){
        
        global $DB;
        
        $ins = new \stdClass();
        $ins->qoeid = $qualID;
        $ins->grade = $grade;
        $ins->points = $points;
        $ins->weighting = $weight;
        return $DB->insert_record("bcgt_qoe_grades", $ins);
        
    }
    
    
    /**
     * Delete grades that we haven't saved
     * @global \GT\type $DB
     * @param type $idArray
     */
    public static function deleteGradesNotSaved($idArray){
        
        global $DB;
        
        if ($idArray){
            
            $ph = gt_create_sql_placeholders($idArray);
            return $DB->delete_records_select("bcgt_qoe_grades", "id NOT IN ({$ph})", $idArray);
            
        } else {
            return $DB->delete_records("bcgt_qoe_grades");
        }
        
    }
    
    /**
     * Delete the QoE data for a user
     * @global \GT\type $DB
     * @param type $userID
     * @return type
     */
    public static function deleteUsersData($userID){
        
        global $DB;
        
        $DB->delete_records("bcgt_user_qoe", array("userid" => $userID));
        $DB->delete_records("bcgt_user_qoe_scores", array("userid" => $userID));
        
        return;
        
    }
    
    
    
    
    
}
