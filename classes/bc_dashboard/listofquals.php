<?php

namespace GT\bc_dashboard;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';

/**
 * Description of avggcse
 *
 * @author cwarwicker
 */
class listofquals extends \BCDB\Report\Element {
    
    protected $level = 'individual';
    protected $type = 'function';
    protected $datatype = 'string';

    public function call(&$results){
                
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
                $quals = $user->getQualifications("STUDENT");
                if ($quals)
                {
                    foreach($quals as $qual)
                    {
                        $array[] = $qual->getDisplayName();
                    }
                }
                
                $results['users'][$key][$alias] = implode("; ", $array);
                                                
            }
        }
        
    }
    
    
    
    public function get() {}

}
