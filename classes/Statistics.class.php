<?php

namespace GT;

interface StatisticsOutput
{
    public function output(array $data);
}

class StatisticsArrayOutput implements StatisticsOutput
{
    public function output(array $data){
        return $data;
    }
}

class StatisticsSerializedArrayOutput implements StatisticsOutput
{
    public function output(array $data){
        return serialize($data);
    }   
}

class StatisticsJsonOutput implements StatisticsOutput
{
        
    public function output(array $data){
        return json_encode($data);
    }   
}

class StatisticsDataFileOutput implements StatisticsOutput
{
    
    public function output(array $data){
        
        $data = serialize($data);
        $code = hash('sha256', $data);
        
        // Save to file
        $file = $code . '.data';
        $path = \gt_save_file_contents($data, $file);
        if ($path){
            return $code;
        }
        
        return false;
        
    }
    
}

/**
 * Expects fields: "name", "cnt", "total"
 */
class StatisticsBarChartOutput implements StatisticsOutput
{
    
    private $title, $group, $class, $args = array();
    
    public function __construct($title, $group, $class, $args = null){
        $this->title = get_string($title, 'block_gradetracker');
        $this->group = $group;
        $this->class = $class;
        if ($args){
            foreach($args as $arg => $val){
                $this->args[$arg] = $val;
            }
        }
    }
    
    public function output(array $data){
        
        global $CFG;
        
        $total = count($data);
        $this->splitDataIntoGroups($data);
        
        $link = '';
        if (isset($this->args['output'])){
            $link = '&output=' . $this->args['output'];
        } elseif (isset($this->args['context']) && isset($this->args['field'])){
            $link = '&context=' . $this->args['context'] . '&field=' . $this->args['field'];
        }
        
        $output = "";
        $output .= "<dl class='gt_bar_chart'>";
            $output .= "<dt class='gt_c'>{$this->title}</dt>";
            foreach($data as $group => $records)
            {
                
                $st = new \GT\Statistics();
                $st->setRecords($records);
                $str = $st->output( new \GT\StatisticsDataFileOutput() );
                
                $cnt = count($records);
                $percent = round(($cnt / $total) * 100, 1);
                $output .= "<dd>";
                    $output .= "<div class='gt_bar_chart_title'><a href='#' onclick='gtOpenUrlInPopUp(\"{$this->title}\", \"{$CFG->wwwroot}/blocks/gradetracker/data.php?data={$str}{$link}\");return false;'>".\gt_html($group)."</a></div>";
                    $output .= "<div class='gt_bar_chart_bar_wrap'><div style='width:{$percent}%;' class='gt_bar_chart_bar gt_bar_chart_{$this->class}'>&nbsp;</div></div>";
                    $output .= "<span>{$cnt}</span>";
                $output .= "</dd>";
            }
        $output .= "</dl>";
        
        return $output;
                
        
    }
    
    private function splitDataIntoGroups(array &$data){
        
        $field = $this->group;
        $array = array();
        
        if ($data)
        {
            foreach($data as $row)
            {
                if (isset($row->$field))
                {
                    
                    $group = $row->$field;
                    
                    if (!array_key_exists($group, $array))
                    {
                        $array[$group] = array();
                    }
                    
                    $array[$group][] = $row;
                    
                }                
            }
        }
        
        $data = $array;
        return $data;
        
    }
    
    
}

/**
 * 
 * Different output types: array, json encoded, bar chart
 *
 * @author cwarwicker
 */
class Statistics {
    
    private $records = array();
    
    public function getRecords(){
        return $this->records;
    }
    
    public function setRecords(array $records){
        $this->records = $records;
    }
    
    
    public function output(\GT\StatisticsOutput $output, $key = false){
        return ($key) ? $output->output($this->records[$key]) : $output->output($this->records);
    }
    
    
    /**
     * Get a list of qualifications from the system that are either Active (attached to a course) or Inactive
     * @global type $DB
     * @param type $status
     * @return \GT\Statistics|boolean
     */
    public static function getQualifications($status){
        
        global $DB;
        
        if (!in_array($status, array('active', 'inactive'))){
            return false;
        }
        
        $statusSQL = ($status == 'active') ? 'IS NOT NULL' : 'IS NULL';
        
        $obj = new \GT\Statistics();
        
        $records = $DB->get_records_sql("SELECT DISTINCT CONCAT(s.id, '_', q.id) as id, s.name, q.id as qualid, q.name as qualname
                                        FROM {bcgt_qual_structures} s
                                        INNER JOIN {bcgt_qual_builds} b ON b.structureid = s.id
                                        INNER JOIN {bcgt_qualifications} q ON q.buildid = b.id
                                        LEFT JOIN {bcgt_course_quals} cq ON cq.qualid = q.id
                                        WHERE s.deleted = 0 AND q.deleted = 0 AND cq.id {$statusSQL}
                                        ORDER BY s.name, q.name ");       
        
        $obj->setRecords($records);
        
        return $obj;
        
    }
    
    /**
     * Get a list of qualifications from the system that either have the Correct or Incorrect amount of credits
     * @global \GT\type $DB
     * @param type $status
     * @return \GT\Statistics|boolean
     */
    public static function getQualificationsByCredits($status){
        
        global $DB;
        
        if (!in_array($status, array('correct', 'incorrect'))){
            return false;
        }
        
        $statusSQL = ($status == 'correct') ? "AND (ba.value > 0 AND cc.credits = ba.value)" : "AND (ba.value > 0 AND cc.credits < ba.value)";
        
        $obj = new \GT\Statistics();
        
        $records = $DB->get_records_sql("SELECT DISTINCT CONCAT(s.id, '_', q.id) as id, s.name, q.id as qualid, ba.value as defaultcredits, cc.credits
                                        FROM {bcgt_qual_structures} s
                                        INNER JOIN {bcgt_qual_builds} b ON b.structureid = s.id
                                        INNER JOIN {bcgt_qualifications} q ON q.buildid = b.id
                                        LEFT JOIN {bcgt_qual_build_attributes} ba ON (ba.buildid = b.id AND ba.attribute = 'build_default_credits')
                                        LEFT JOIN {bcgt_course_quals} cq ON cq.qualid = q.id
                                        LEFT JOIN 
                                        (
                                            SELECT qu.qualid, SUM(u.credits) as credits
                                            FROM {bcgt_qual_units} qu
                                            INNER JOIN {bcgt_units} u ON u.id = qu.unitid
                                            GROUP BY qu.qualid
                                        ) cc ON cc.qualid = q.id
                                        WHERE s.deleted = 0 AND q.deleted = 0 AND cq.id IS NOT NULL 
                                        {$statusSQL}
                                        ORDER BY s.name, q.name");
                
        $obj->setRecords($records);
        
        return $obj;
        
    }
    
    /**
     * Get a list of units from the system that are either Active (attached to a qualification) or Inactive
     * @global \GT\type $DB
     * @param type $status
     * @return \GT\Statistics|boolean
     */
    public static function getUnits($status){
        
        global $DB;
        
        if (!in_array($status, array('active', 'inactive'))){
            return false;
        }
        
        $statusSQL = ($status == 'active') ? 'IS NOT NULL' : 'IS NULL';
        
        $obj = new \GT\Statistics();
        
        $records = $DB->get_records_sql("SELECT DISTINCT CONCAT(s.id, '_', u.id) as id, s.name, u.id as unitid
                                         FROM {bcgt_qual_structures} s
                                         INNER JOIN {bcgt_units} u ON u.structureid = s.id
                                         LEFT JOIN {bcgt_qual_units} qu ON qu.unitid = u.id
                                         WHERE s.deleted = 0 AND u.deleted = 0
                                         AND qu.id {$statusSQL}
                                         ORDER BY s.name, u.name");       
        
        $obj->setRecords($records);
        
        return $obj;
        
    }
    
}
