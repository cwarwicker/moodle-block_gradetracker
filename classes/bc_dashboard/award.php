<?php

namespace GT\bc_dashboard;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

/**
 * Description of award
 *
 * @author cwarwicker
 */
class award extends \BCDB\Report\Element {
    
    protected $level = 'individual';
    protected $type = 'function';
    protected $datatype = 'string';
    
    public function __construct($params = null) {
        
        $this->options = array(
            array('select', get_string('reportoption:type', 'block_gradetracker'), array('average' => get_string('predictedgrade', 'block_gradetracker'), 'min' => get_string('predictedmingrade', 'block_gradetracker'), 'max' => get_string('predictedmaxgrade', 'block_gradetracker'), 'final' => get_string('predictedfinalgrade', 'block_gradetracker')))
        );
        parent::__construct($params);
        
    }
   
    public function call(&$results){
        
        $type = $this->getParam(0);
        
        $GTEXE = \GT\Execution::getInstance();
        $GTEXE->min();
        
        $alias = $this->getAliasName();
        
        if ($results['users'])
        {
            foreach($results['users'] as $key => $row)
            {
                
                $array = array();
                
                // Get their list of quals
                $user = new \GT\User($row['id']);
                $grades = $user->getAllUserAwards($type, null);
                if ($grades)
                {
                    foreach($grades as $grade)
                    {
                        $array[] = $grade['grade']->getName();
                    }
                }
                
                $results['users'][$key][$alias] = implode(", ", $array);
                
            }
        }
        
    }
    
    
    
    public function get() {}

}
