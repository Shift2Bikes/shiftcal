<?php

class EventTime extends fActiveRecord {

    public static function matchEventTimesToDates($event, $phpDates) {
        $dates = array();
        foreach ($phpDates as $dateVal) {
            $dates []= $dateVal->format('Y-m-d');
        }

        foreach ($event->buildEventTimes('id') as $eventTime) {
            // For all existing dates
            $formattedDate = $eventTime->getFormattedDate();
            if (!in_array($formattedDate, $dates)) {
                // If they didn't submit this existing date delete it
                $eventTime->delete();
            }
            else {
                if (($key = array_search($formattedDate, $dates)) !== false) {
                    unset($dates[$key]);
                }
            }
        }
        foreach ($dates as $newDate) {
            $eventTime = new EventTime();
            $eventTime->setModified(time());
            $eventTime->setId($event->getId());
            $eventTime->setEventdate($newDate);
            $eventTime->setEventstatus('A');
            $eventTime->store();
        }
        // Flourish is suck. I can't figure out the "right" way to do one-to-many cause docs are crap
        // This clears a cache that causes subsequent operations (buildEventTimes) to return stale data
        $event->related_records = array();
    }

    public static function getRange($firstDay, $lastDay) {
        return fRecordSet::build(
            'EventTime', // class
            array(
                'eventdate>=' => $firstDay,
                'eventdate<=' => $lastDay
            ), // where
            array('eventdate' => 'asc')  // order by
        );
    }

    private function getEvent() {
        if ($this->getEventstatus() === 'E') {
            return $this->createEvent('exceptionid');
        }
        return $this->createEvent('id');
    }

    public function getFormattedDate() {
        return $this->getEventdate()->format('Y-m-d');
    }

    public function toEventSummaryArray() {
        $eventArray = $this->getEvent()->toArray();
        $eventArray['date'] = $this->getFormattedDate();
        return $eventArray;
    }
}

fORM::mapClassToTable('EventTime', 'caldaily');