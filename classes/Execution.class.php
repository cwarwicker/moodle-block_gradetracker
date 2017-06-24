<?php

namespace GT;

class Execution
{
    
    const STUD_LOAD_LEVEL_QUAL = 1;
    const STUD_LOAD_LEVEL_UNIT = 2;
    const STUD_LOAD_LEVEL_ALL = 3;
    
    private static $instance;
    
    public $QUAL_STRUCTURE_MIN_LOAD = null;
    public $QUAL_BUILD_MIN_LOAD = null;
    public $QUAL_MIN_LOAD = null;
    public $UNIT_MIN_LOAD = null;
    public $UNIT_NO_SORT = null;
    public $CRIT_NO_SORT = null;
    public $STUDENT_LOAD_LEVEL = null;
    public $COURSE_CAT_MIN_LOAD = null;
    
    
    protected function __construct(){}
    private function __clone(){}
    private function __wakeup(){}
    
    public function min(){
        
        $this->QUAL_STRUCTURE_MIN_LOAD = true;
        $this->QUAL_BUILD_MIN_LOAD = true;
        $this->QUAL_MIN_LOAD = true;
        $this->UNIT_MIN_LOAD = true;
        $this->UNIT_NO_SORT = true;
        
        
    }
    
    public static function getInstance()
    {
        
        if (null === static::$instance){
            static::$instance = new \GT\Execution();
        }
        
        return static::$instance;
        
    }
    
}