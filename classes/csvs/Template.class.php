<?php

namespace GT\CSV;

/**
 * Description of Template
 *
 * @author cwarwicker
 */
class Template {
    
    public static $headersTargetGrades = array(
        'QualType',
        'QualLevel',
        'QualSubType',
        'QualName',
        'Username',
        'TargetGrade',
        'WeightedTargetGrade',
        'AvgGCSE'
    );
    
    public static $headersAspirationalGrades = array(
        'QualType',
        'QualLevel',
        'QualSubType',
        'QualName',
        'Username',
        'AspirationalGrade'
    );
    
    public static $headersCetaGrades = array(
        'QualType',
        'QualLevel',
        'QualSubType',
        'QualName',
        'Username',
        'Ceta',
        'Course'
    );
    
    public static $headersQOE = array(
        'Username',
        'Subject',
        'Qual',
        'Level',
        'Grade',
        'Year'
    );
    
    public static $headersWCoe = array(
        'QualType',
        'QualLevel',
        'QualSubType',
        'QualName',
        'PercentileNumber',
        'Value',
    );
    
    public static $headersAssGrades = array(
        'Username',
        'Course',
        'QualType',
        'QualLevel',
        'QualSubType',
        'QualName',
        'Grade',
        'CETA'
    );
    
    public static $headersAvgGCSE = array(
        'Username',
        'Score'
    );
    
    /**
     * Given a file and the headers, create a template file
     * @param type $file
     * @param type $headers
     * @return boolean
     */
    public static function createTemplate($file, $headers){
        
        // Using "w" we truncate the file if it already exists
        $fh = fopen($file, 'w');
        if ($fh === false){
            return false;
        }
        
        $fp = fputcsv($fh, $headers);
        
        if ($fp === false){
            return false;
        }
        
        fclose($fh); 
        return true;
        
    }
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateTemplateTargetGradesCSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/targetgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersTargetGrades;
        self::createTemplate($file, $headers);
               
        return $code;     
        
        
    }
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateTemplateAspirationalGradesCSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/aspirationalgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersAspirationalGrades;
        self::createTemplate($file, $headers);
               
        return $code;     
        
        
    }
    
    public static function generateTemplateCetaGradesCSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/cetagrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersCetaGrades;
        self::createTemplate($file, $headers);
               
        return $code;     
        
        
    }
    
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateTemplateQoECSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/qoe.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersQOE;
        self::createTemplate($file, $headers);
               
        return $code;     
        
        
    }
    
    
    /**
     * Generate the template for Target Grades csv import
     * @param type $reload
     * @return boolean
     */
    public static function generateTemplateWCoeCSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/wcoe.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersWCoe;
        self::createTemplate($file, $headers);
               
        return $code;     
        
        
    }
    
    /**
     * Generate template CSV for avg gcse import
     * @param type $reload
     * @return type
     */
    public static function generateTemplateAvgGCSECSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/avggcse.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersAvgGCSE;
        self::createTemplate($file, $headers);
               
        return $code;  
        
    }
    
    /**
     * Create template file for Assessment Grades
     * @param type $reload
     * @return type
     */
    public static function generateTemplateAssGradesCSV($reload = false){
        
        $file = \GT\GradeTracker::dataroot() . '/csv/templates/assgrades.csv';
        $code = \gt_create_data_path_code($file);
        
        // If it already exists and we don't want to reload it, just return
        if (file_exists($file) && !$reload){
            return $code;
        }
                
        // Create the directories if they don't exist
        \gt_create_data_directory( 'csv/templates' );
        
        // Now lets create the new one - The headers are going to be in English so we can easily compare headers
        $headers = self::$headersAssGrades;
        self::createTemplate($file, $headers);
               
        return $code;  
        
    }
    
    
}
