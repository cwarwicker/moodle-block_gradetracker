<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * The class is used to notify Listeners that an Event has occured
 *
 * @copyright   2011-2017 Bedford College, 2017 onwards Conn Warwicker
 * @package     block_gradetracker
 * @version     2.0
 * @author      Conn Warwicker <conn@cmrwarwicker.com>
 */
namespace GT;

defined('MOODLE_INTERNAL') or die();

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

    protected function addListener($listener) {
        $this->listeners[] = $listener;
    }

    public function notify() {

        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                \gt_debug("Notifying ".get_class($listener)." of event ({$this->event}): " . print_r($this->params, true));
                $listener->notify( $this->event, $this->params );
            }
        }

    }

}
