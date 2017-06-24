<?php
/**
 * Event
 *
 * The class is used to notify Listeners that an Event has occured
 * 
 * @copyright 2015 Bedford College
 * @package Bedford College Grade Tracker
 * @version 1.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com> <moodlesupport@bedford.ac.uk>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

namespace GT;

define('GT_EVENT_CRIT_UPDATE', 'onCriterionAwardUpdate');
define('GT_EVENT_UNIT_UPDATE', 'onUnitAwardUpdate');

class Event {
    
    protected $event;
    protected $params;
    protected $listeners = array();
    
    public function __construct($event, $params = false) {
        
        $this->event = $event;
        $this->params = $params;
        
        $this->addListener( new \GT\RuleSet() );
        
    }
    
    protected function addListener($listener){
        $this->listeners[] = $listener;
    }
    
    public function notify(){
                
        if ($this->listeners)
        {
            foreach($this->listeners as $listener)
            {
                \gt_debug("Notifying ".get_class($listener)." of event ({$this->event}): " . print_r($this->params, true));
                $listener->notify( $this->event, $this->params );
            }
        }
        
    }
    
}
