<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ELBP\Plugins;

require_once 'lib.php';

class elbp_gt_prior_learning extends Plugin {
    
    /**
     * Construct the plugin object
     * @param bool $install If true, we want to send the default info to the parent constructor, to install the record into the DB
     */
    public function __construct($install = false) {
        
        if ($install){
            parent::__construct( array(
                "name" => strip_namespace(get_class($this)),
                "title" => "Prior Learning",
                "path" => '/blocks/gradetracker/',
                "version" => \ELBP\ELBP::getBlockVersionStatic()
            ) );
        }
        else
        {
            parent::__construct( strip_namespace(get_class($this)) );
        }

    }
    
    
    public function ajax($action, $params, $ELBP) {
        
    }

    public function getSummaryBox() {
        
        $TPL = new \ELBP\Template();
        
        $TPL->set("obj", $this);
        
        $user = new \GT\User($this->student->id);
                
        $TPL->set("prior", $user->getQualsOnEntry());
        $TPL->set("avgScore", $user->getAverageGCSEScore());
        
        try {
            return $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_gt_prior_learning/summary.html');
        }
        catch (\ELBP\ELBPException $e){
            return $e->getException();
        }
        
    }

    
    public function getDisplay($params = array()){
                
        $output = "";
        
        $TPL = new \ELBP\Template();
        
        $user = new \GT\User($this->student->id);
        
        $TPL->set("prior", $user->getQualsOnEntry());
        $TPL->set("avgScore", $user->getAverageGCSEScore());
        $TPL->set("obj", $this);
        $TPL->set("access", $this->access);      
        
        try {
            $output .= $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_gt_prior_learning/expanded.html');
        } catch (\ELBP\ELBPException $e){
            $output .= $e->getException();
        }

        return $output;
        
    }
    
    
    public function getConfigPath()
    {
        $path = $this->getPath() . 'config_'.$this->getName().'.php';
        return $path;
    }
    
    
    public function install() {
        
        global $DB;
        
        $return = true;
        $this->id = $this->createPlugin();
        
        // Hooks
        $DB->insert_record("lbp_hooks", array("pluginid" => $this->id, "name" => "English GCSE"));
        $DB->insert_record("lbp_hooks", array("pluginid" => $this->id, "name" => "Maths GCSE"));
        
        return $return;
    }

    public function upgrade() {
        
        global $DB;
        
        $return = true;
        
        return $return;
        
    }
    
    
    public function _callHook_English_GCSE($obj, $params){
               
       if (!$this->isEnabled()) return false;
       if (!isset($obj->student->id)) return false;
                
       // Load student
       $this->loadStudent($obj->student->id);
       
       $user = new \GT\User($this->student->id);
       
       // $PL = new \UserPriorLearning();
       $prior = $user->getQualsOnEntry();
              
       if ($prior)
       {
           foreach($prior as $qual)
           {
               //return $qual->getSubjectName($qual->getSubjectID());
               if ($qual->getType()->name == 'GCSE' && ($qual->getSubjectName($qual->getSubjectID()) == 'English' || $qual->getSubjectName($qual->getSubjectID()) == 'English Language'))
               {
                   return $qual->getGradeObject()->grade;
               }
               
           }
       }
       
       return get_string('na', 'block_gradetracker');
       
    }
    
    public function _callHook_Maths_GCSE($obj, $params){
       if (!$this->isEnabled()) return false;
       if (!isset($obj->student->id)) return false;
                
       // Load student
       $this->loadStudent($obj->student->id);
       
       $user = new \GT\User($this->student->id);
       
       // $PL = new \UserPriorLearning();
       $prior = $user->getQualsOnEntry();
              
       if ($prior)
       {
           foreach($prior as $qual)
           {
               
               if ($qual->getType()->name == 'GCSE' && ($qual->getSubjectName($qual->getSubjectID()) == 'Mathematics' || $qual->getSubjectName($qual->getSubjectID()) == 'Maths'))
               {
                   return $qual->getGradeObject()->grade;
               }
               
           }
       }
       
       return get_string('na', 'block_gradetracker');
       
    }
}

