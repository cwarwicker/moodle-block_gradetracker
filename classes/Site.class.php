<?php
/*
 * Description of Site
 *
 * @author cwarwicker
 */

namespace GT;

class Site {
    
    public $name;
    public $url;
    public $privacy;
    public $admin;
    public $adminemail;
    public $moodleversion; # e.g. 3.1
    public $moodlebuild; # e.g. 2017013100
    public $gtversion; # e.g. 1.0.0
    public $gtbuild; # e.g. 2017013100
    public $stats = array();
    public $uin;
    
    public function __construct() {
        
        global $CFG, $GT;
        
        if (is_null($GT)){
            $GT = new \GT\GradeTracker();
        }
        
        $siteCourse = \get_site();
        $adminObj = \get_admin();
        
        // Uin
        $uin = \GT\Setting::getSetting('registration_uin');
        if ($uin){
            $this->uin = $uin;
        }
        
        $name = \GT\Setting::getSetting('registration_sitename');
        $this->name = ($name) ? $name : $siteCourse->fullname;
        
        $this->url = $CFG->wwwroot;
        
        $admin = \GT\Setting::getSetting('registration_admin');
        $this->admin = ($admin) ? $admin : fullname($adminObj);
        
        $email = \GT\Setting::getSetting('registration_adminemail');
        $this->adminemail = ($email) ? $email : $adminObj->email;
        
        $privacy = \GT\Setting::getSetting('registration_privacy');
        if ($privacy !== false){
            $this->privacy = $privacy;
        }
        
        $this->moodleversion = \moodle_major_version();
        $this->moodlebuild = \get_config(null, 'version');

        $this->gtversion = $GT->getPluginVersion();
        $this->gtbuild = $GT->getBlockVersion();
        
        // Stats
        
        
    }
    
    /**
     * Get a specific site stat and load it if it hasn't been loaded yet
     * @param type $name
     * @return type
     */
    public function getStat($name){
        
        if (!array_key_exists($name, $this->stats)){
            $this->loadStat($name);
        }
        
        return (array_key_exists($name, $this->stats)) ? $this->stats[$name] : false;
        
    }
    
    
    private function loadStat($name){
        
        switch($name)
        {
            
            case 'structures':
                
                $structures = \GT\QualificationStructure::getAllStructures();
                $this->stats[$name] = count($structures);
                
            break;
        
            case 'quals':
                $this->stats[$name] = \GT\Qualification::countQuals();
            break;
            
            case 'units':
                $this->stats[$name] = \GT\Unit::countUnits();
            break;
        
            case 'criteria':
                $this->stats[$name] = \GT\Criterion::countCriteria();
            break;
            
        }
        
    }
    
    /**
     * Load the bits of data which can be changed in the form, from the submitted form data
     * @param type $data
     */
    public function load($data){
        
        $this->name = $data['name'];
        $this->privacy = $data['privacy'];
        $this->admin = $data['admin'];
        $this->adminemail = $data['adminemail'];
        
        if ($data['stats'])
        {
            foreach($data['stats'] as $stat => $val)
            {
                $this->stats[$stat] = $val;
            }
        }
                
    }
    
    
    public function cron(){
        
        // If it's been manually registered, update it via cron
        if (!self::registered()){
            return "Site is not registered";
        }        
        
        // Get the latest stats
        $stats = array('structures', 'quals', 'units', 'criteria');
        foreach($stats as $stat)
        {
            $this->loadStat($stat);
        }
        
        return $this->submit();
        
    }
    
    
    public function submit(){
        
        global $CFG;
        
        // Clear any output
        ob_end_clean();
        
        // Set headers
        header('Content-Type: text/plain');

        // Create curl object
        require_once $CFG->dirroot . '/lib/filelib.php';
        $curl = new \curl();
        
        // Initial parameters
        $params['moodlewsrestformat'] = 'json';
        $params['wsfunction'] = 'local_bcgt_reg_add_site';
        $params['wstoken'] = \GT\GradeTracker::REMOTE_HUB_TOKEN;
        
        $site = get_object_vars($this);
        $params['site'] = $site;
                        
        $options = array();
        $options['RETURNTRANSFER'] = true;
        $options['SSL_VERIFYPEER'] = false;

        $result = $curl->get(\GT\GradeTracker::REMOTE_HUB, $params, $options);
        
        $decode = json_decode($result);
                
        // Has it returned a UIN?
        if (is_string($decode) && preg_match("/[a-z0-9]{6}-[a-z0-9]{6}-[a-z0-9]{6}-[a-z0-9]{6}-[a-z0-9]{6}/i", $decode)){
            
            // Set UIN
            \GT\Setting::updateSetting('registration_uin', $decode);
            
            // Set the values they entered for site name, privacy, admin and admin email, so the cron doesn't override them
            \GT\Setting::updateSetting('registration_sitename', $this->name);
            \GT\Setting::updateSetting('registration_privacy', $this->privacy);
            \GT\Setting::updateSetting('registration_admin', $this->admin);
            \GT\Setting::updateSetting('registration_adminemail', $this->adminemail);
            \GT\Setting::updateSetting('registration_lastupdated', time());
            
        }
        
        return $result;
        
    }
    
    
    public static function lastUpdated($format = false){
        $unix = \GT\Setting::getSetting('registration_lastupdated');
        return ($unix && $format) ? date($format, $unix) : $unix;
    }
        
           
    
    /**
     * Are we registered?
     * @return type
     */
    public static function registered(){
        return ( \GT\Setting::getSetting('registration_uin') !== false );
    }
    
    
}
