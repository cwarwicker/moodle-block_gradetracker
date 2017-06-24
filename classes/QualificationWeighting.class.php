<?php
/**
 * Description of QualificationWeighting
 *
 * @author cwarwicker
 */

namespace GT;

class QualificationWeighting {
    
    /**
     * Calculate the weighting percentile
     * @param type $targetUCAS The UCAS points of the target grade
     * @param type $gradeUCAS The UCAS points of the grade (qualification award, probably based on the assessment grade)
     * @param type $multiplier The multiplier 
     * @param type $qualID
     * @param int $noUsers Number of users
     */
    public function calculateWeightingPercentile($targetUCAS, $gradeUCAS, $multiplier, $qualID, $noUsers = 1)
    {
     
        $obj = new \stdClass();

        // Calculate the coefficient score
        $coefficientScore = $this->calculateWeightingScore($targetUCAS, $gradeUCAS, $multiplier, $noUsers);
        \gt_debug("Calculated coeffcient score: {$coefficientScore}");
                
        // Get the weighting record from this coefficient score, for this qualification
        $weightingRecord = $this->getQualWeightingByCoefficient($qualID, $coefficientScore);
        if ($weightingRecord)
        {
            $explode = explode("_", $weightingRecord);
            $percentile = end($explode);
            $obj->score = $coefficientScore;
            $obj->percentile = (int)$percentile;
        }
        elseif ($coefficientScore)
        {
            
            // We have a coefficient score, but no weighting record
            // This means it must be either the top percentile or bottom percentile
            if($coefficientScore > 1)
            {
                //then its a 1 (top)
                $score = 1;
            }
            else
            {
                //then its a 9
                $score = \GT\Setting::getSetting('qual_weighting_percentiles');
            } 
            
            $obj->percentile = $score;
            $obj->score = $coefficientScore;
            
        }
        else
        {
            $obj = false;
        }
        
        \gt_debug( print_r($obj, true) );
        
        return $obj;
        
    }
    
    /**
     * This calculates the percentile weighting score, based on the UCAS points of their target grade and
     * the grade or ceta (depending on which was used) of a particular assessment
     * 
     * It works it out by doing
     * 
     * (
        * (UCAS Points of Grade/Ceta) - (UCAS Points of target grade)
        *      divided by
        * (multiplier) * (Number of users)
     * )
     *      plus 1
     * 
     * So for example, consider the percentile values of AS English Literature:
     * 
     * 1: 1.26
     * 2: 1.13
     * 3: 1.07
     * 4: 1.03
     * 5: 0.99
     * 6: 0.95
     * 7: 0.87
     * 8: 0.66
     * 
     * Now let's say we have two students and we want to calculate the weighting score for their CETA grade for
     * one of their assessments.
     * 
     * Both students have the same CETA grade for the assessment - D
     * 
     * Student one (Emily) has a Target Grade (NOT weighted target) of C
     * Student two (Robbie) has a Target grade of D/E
     * 
     * For AS Level, the UCAS points of a D grade is 30.0
     * C is 40.0
     * D/E is 26.6
     * 
     * The muliplier we use for AS Level is 50
     * 
     * So for Emily we would do:
     *      Achieved UCAS - Target UCAS
     *      (30.0 - 40.0) = -10
     * 
     *      Muliplier * Number of Students
     *      (50 * 1) = 50
     * 
     *      (-10 / 50) = -0.2
     * 
     *      -0.2 + 1 = 0.8
     *      
     * So Emily's coefficient is 0.8.
     * Using the same thing Robbie's is 1.068
     * 
     * We then check the weightings for English Lit and find the first one that is less than or equal to the coefficient
     * 
     * For Emily that is ("8: 0.66")
     * Robbie is ("4: 1.03")
     * 
     * So 8 is the weighting we display for Emily and 4 for Robbie.
     * 
     * 
     * This is all used so that you can see at a glance, who is doing well and who isn't, depending on how high
     * they should be aiming (based on their target grade). For example here Emily is aiming for a C, but is
     * currently expected to achieve a D, which is therefore a lower score than Robbie who is aiming for a D/E
     * but is currently expected a D
     * 
     * @param type $ucasTarget
     * @param type $ucasAchieved
     * @param type $multiplier
     * @param type $noEntries
     * @return type
     */
    public function calculateWeightingScore($ucasTarget, $ucasAchieved, $multiplier, $noEntries)
    {
        //The alps entrymultipyer is dependant on the Qualification       
        $value = ($noEntries > 0 && $multiplier > 0) ? ((($ucasAchieved - $ucasTarget)/($multiplier * $noEntries))) : 0;
        return $value + 1;
    }
    
    public function getQualWeightingByCoefficient($qualID, $coefficientScore)
    {
        
        global $DB;
        
        $records = $DB->get_records_sql("SELECT * 
                                         FROM {bcgt_qual_attributes}
                                         WHERE qualid = ?
                                         AND attribute LIKE 'coefficient_%'
                                         AND value <= ?
                                         ORDER BY value DESC", array($qualID, $coefficientScore), 0, 1);
        
        if ($records){
            $record = end($records);
            return $record->attribute;
        }
        
        // If no weightings for the qual, check the qual build for defaults
        else {
            $build = \GT\Qualification::getBuildFromQualID($qualID);
            if ($build){
                $records = $DB->get_records_sql("SELECT * 
                                         FROM {bcgt_settings}
                                         WHERE setting LIKE ?
                                         AND value <= ?
                                         ORDER BY value DESC", array('build_coefficient_'.$build->getID().'_%', $coefficientScore), 0, 1);
                if ($records){
                    $record = end($records);
                    return $record->setting;
                }
            }
        }
        
        return false;
        
    }
    
    /**
     * Get the background colour for a percentile column, or black by default if none found
     * @param type $p
     * @return type
     */
    public static function getPercentileColour($p){
        
        $colour = \GT\Setting::getSetting('weighting_percentile_color_'.$p);
        return ($colour !== false && $colour != '') ? $colour : '#000000';
        
    }
    
}
