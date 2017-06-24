<?php

namespace GT\CSV;

/**
 * Description of Template
 *
 * @author cwarwicker
 */
class Example {
    
    
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateExampleTargetGradesCSV($reload = false){
        
        global $DB;
        
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/targetgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersTargetGrades;
        fputcsv($fh, $headers);
        
        
        
        // Create some example data
        $quals = $DB->get_records_sql("SELECT id FROM {bcgt_qualifications}
                                       WHERE deleted = 0
                                       ORDER BY RAND()", null, 0, 5);
        
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $qual = new \GT\Qualification($qual->id);
                if ($qual->isValid())
                {
                    
                    $grades = $qual->getBuild()->getAwards();
                    if ($grades){
                        shuffle($grades);
                        $grade = reset($grades)->getName();
                        shuffle($grades);
                        $weightedGrade = reset($grades)->getName();
                    } else {
                        $grade = '';
                        $weightedGrade = '';
                    }
                    
                    $avgGCSE = ( mt_rand(0, 5) >= 2 || $grade == '' ) ? mt_rand(200, 500) / 10 : '';
                    
                    $data = array(
                        'QualType' => $qual->getStructureExactName(),
                        'QualLevel' => $qual->getLevelName(),
                        'QualSubType' => $qual->getSubTypeName(),
                        'QualName' => $qual->getName(),
                        'Username' => 'student' . mt_rand(1, 100),
                        'TargetGrade' => $grade,
                        'WeightedTargetGrade' => $weightedGrade,
                        'AvgGCSE' => $avgGCSE
                    );
                    
                    fputcsv($fh, $data);
                    
                }
            }
        }
        
               
        fclose($fh);
        return $code;     
        
        
    }
    
    public static function generateExampleAspirationalGradesCSV($reload = false){
        
        global $DB;
        
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/aspirationalgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersAspirationalGrades;
        fputcsv($fh, $headers);
        
        
        
        // Create some example data
        $quals = $DB->get_records_sql("SELECT id FROM {bcgt_qualifications}
                                       WHERE deleted = 0
                                       ORDER BY RAND()", null, 0, 5);
        
        
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $qual = new \GT\Qualification($qual->id);
                if ($qual->isValid())
                {
                    
                    $grades = $qual->getBuild()->getAwards();
                    if ($grades){
                        shuffle($grades);
                        $grade = reset($grades)->getName();
                    } else {
                        $grade = '';
                    }
                    
                    $avgGCSE = ( mt_rand(0, 5) >= 2 || $grade == '' ) ? mt_rand(200, 500) / 10 : '';
                    
                    $data = array(
                        'QualType' => $qual->getStructureExactName(),
                        'QualLevel' => $qual->getLevelName(),
                        'QualSubType' => $qual->getSubTypeName(),
                        'QualName' => $qual->getName(),
                        'Username' => 'student' . mt_rand(1, 100),
                        'AspirationalGrade' => $grade
                    );
                    
                    fputcsv($fh, $data);
                    
                }
            }
        }
        
               
        fclose($fh);
        return $code;     
        
        
    }
    
    public static function generateExampleCetaGradesCSV($reload = false){
        
        global $DB;
        
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/cetagrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersCetaGrades;
        fputcsv($fh, $headers);
        
        
        
        // Create some example data
        $quals = $DB->get_records_sql("SELECT id FROM {bcgt_qualifications}
                                       WHERE deleted = 0
                                       ORDER BY RAND()", null, 0, 5);
        
        
        if ($quals)
        {
            foreach($quals as $qual)
            {
                $qual = new \GT\Qualification($qual->id);
                if ($qual->isValid())
                {
                    
                    $grades = $qual->getBuild()->getAwards();
                    if ($grades){
                        shuffle($grades);
                        $grade = reset($grades)->getName();
                    } else {
                        $grade = '';
                    }
                    
                    $avgGCSE = ( mt_rand(0, 5) >= 2 || $grade == '' ) ? mt_rand(200, 500) / 10 : '';
                    
                    $data = array(
                        'QualFamily' => $qual->getStructureExactName(),
                        'QualLevel' => $qual->getLevelName(),
                        'QualSubType' => $qual->getSubTypeName(),
                        'QualName' => $qual->getName(),
                        'Username' => 'student' . mt_rand(1, 100),
                        'Ceta' => $grade,
                        'Course' => 'Course here'
                    );
                    
                    fputcsv($fh, $data);
                    
                }
            }
        }
        
               
        fclose($fh);
        return $code;     
        
        
    }
    
    
    
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateExampleQoECSV($reload = false){
                
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/qoe.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersQOE;
        fputcsv($fh, $headers);
        
        fputcsv($fh, array('student1', 'English Lit', 'GCSE', '2', 'A', '2014'));
        fputcsv($fh, array('student1', 'Maths', 'GCSE', '2', 'A*', '2014'));
        fputcsv($fh, array('student1', 'Business Studies', 'GCSE', '2', 'C', '2014'));
        fputcsv($fh, array('student1', 'Science', 'GCSE Double Award', '2', 'CC', '2014'));
        fputcsv($fh, array('student2', 'English Language', 'GCSE', '2', 'B', '2012'));
        fputcsv($fh, array('student2', 'P.E', 'GCSE', '2', 'D', '2012'));
        fputcsv($fh, array('student2', 'I.T', 'GCSE', '2', 'A', '2012'));
        fputcsv($fh, array('student2', 'History', 'GCSE', '2', 'C', '2012'));
        fputcsv($fh, array('student2', 'Dutch', 'GCSE Short Course', '2', 'A*', '2012'));
        fputcsv($fh, array('student3', 'Horse Care', 'BTEC Diploma', '3', 'DDM', '2013'));
        fputcsv($fh, array('student4', 'Functional Skills', '', '', '', '2010'));
        
        fclose($fh);
        return $code;     
        
        
    }
    
    
    public static function generateExampleWCoeCSV($reload = false){
                
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/wcoe.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersWCoe;
        fputcsv($fh, $headers);
        
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_1', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_2', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_3', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_4', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_5', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_6', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_7', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_8', '0.1'));
        fputcsv($fh, array('AS Level', 'Level 3', 'AS', 'English Literature', 'coefficient_9', '0.1'));
        
        fclose($fh);
        return $code;     
        
        
    }
    
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateExampleAvgGCSECSV($reload = false){
                
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/avggcse.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersAvgGCSE;
        fputcsv($fh, $headers);
        
        fputcsv($fh, array('student1', '50.2'));
        fputcsv($fh, array('student2', '48.8'));
        fputcsv($fh, array('student3', '25.8'));
        fputcsv($fh, array('student4', '14'));
        fputcsv($fh, array('student5', '38'));
        fputcsv($fh, array('student6', '40.1'));
        fputcsv($fh, array('student7', '50.3'));
        fputcsv($fh, array('student8', '49.25'));
        fputcsv($fh, array('student9', '30'));
        fputcsv($fh, array('student10', '42'));
        
        fclose($fh);
        return $code;     
        
        
    }
    
    /**
     * Generate an example file for assessment grades data
     * @global type $DB
     * @param type $reload
     * @return boolean
     */
    public static function generateExampleAssGradesCSV($reload = false){
        
        global $DB;
        
        $file = \GT\GradeTracker::dataroot() . '/csv/examples/assgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/examples' );
        
        // Open the file for writing
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = \GT\CSV\Template::$headersAssGrades;
        fputcsv($fh, $headers);
        
        fputcsv($fh, array( 'student001', 'ABC101-16', 'A Level', 'Level 3', 'AS', 'English', 'B', 'A', 'Well done' ));
        fputcsv($fh, array( 'student002', 'ABC101-16', 'A Level', 'Level 3', 'AS', 'English', 'D', 'C', '' ));
        fputcsv($fh, array( 'student003', 'ABC202-17', 'A Level', 'Level 3', 'AS', 'Maths', 'E', 'E', 'Need to do better' ));
        fputcsv($fh, array( 'student004', 'ABC202-17', 'A Level', 'Level 3', 'AS', 'Maths', 'A*', 'A*', 'Well done!' ));
        fputcsv($fh, array( 'student005', 'ABC303-17', 'A Level', 'Level 3', 'AS', 'History', 'B', 'B', 'On track' ));
        fputcsv($fh, array( 'student006', 'ABC303-17', 'A Level', 'Level 3', 'AS', 'History', 'B', 'C', '' ));

        fclose($fh);
        return $code;  
        
    }
    
}
