<?php

namespace GT\bc_dashboard;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

/**
 * Description of avggcse
 *
 * @author cwarwicker
 */
class avggcse extends \BCDB\Report\Element {
    
    protected $level = 'aggregate';
    protected $type = 'sql';
    
    public function get() {
        
        $this->sql['select'] = $this->alias.'.score';
        $this->sql['join'][] = 'left join {bcgt_user_qoe_scores} '.$this->alias.' on ('.$this->alias.'.userid = user.id)';
                        
    }
    
    /**
     * Aggregate the avg gcse scores into an average
     * @param type $results
     * @return type
     */
    public function aggregate($results) {
        
        $field = $this->getAliasName();
        $ttl = 0;
        $cnt = count($results);

        // Loop through the users
        foreach($results as $row)
        {
            $ttl += $row[$field];
        }

        // Average
        $ttl = ($cnt > 0) ? round($ttl / $cnt, 2) : 0;

        return array($field => $ttl);
        
    }

    public function call(&$results) {}

}
