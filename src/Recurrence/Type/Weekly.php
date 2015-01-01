<?php

namespace Plummer\Calendarful\Recurrence\Type;

use Plummer\Calendarful\Recurrence\RecurrenceInterface;

/**
 * Class Weekly
 *
 * The default recurrence type for generating occurrences for events that recur weekly.
 *
 * @package Plummer\Calendarful
 */
class Weekly implements RecurrenceInterface
{
    /**
     * @var string
     */
    protected $label = 'weekly';

    /**
     * @var string
     */
    protected $limit = '+5 year';

    /**
     * Get the label of the recurrence type.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the limit of the recurrence type.
     *
     * @return string
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Generate the occurrences for each weekly recurring event.
     *
     * @param  array     $events
     * @param  \DateTime $fromDate
     * @param  \DateTime $toDate
     * @param  int|null  $limit
     * @return array
     */
    public function generateOccurrences(Array $events, \DateTime $fromDate, \DateTime $toDate, $limit = null)
    {
        $return = array();
        $object = $this;

        $weeklyEvents = array_filter($events, function ($event) use ($object) {
            return $event->getRecurrenceType() === $object->getLabel();
        });

        foreach ($weeklyEvents as $weeklyEvent) {
            list(, $weeklyEventTime) = explode(' ', $weeklyEvent->getStartDate());

            // Retrieve the day of the week that the event takes place on
            $day = date('w', strtotime($weeklyEvent->getStartDate()));

            $startMarker = $fromDate > new \DateTime($weeklyEvent->getStartDate())
                ? clone($fromDate)
                : new \DateTime($weeklyEvent->getStartDate());

            while ($startMarker->format('w') != $day) {
                $startMarker->modify('P1D');
            }

            $maxEndMarker = clone($startMarker);
            $maxEndMarker->modify($this->limit);

            $endMarker = $weeklyEvent->getRecurrenceUntil()
                ? min(new \DateTime($weeklyEvent->getRecurrenceUntil()), clone($toDate), $maxEndMarker)
                : min(clone($toDate), $maxEndMarker);

            $actualEndMarker = clone($endMarker);

            // The DatePeriod class does not actually include the end date so you have to increment it first
            $endMarker->modify('+1 day');

            $dateInterval = new \DateInterval('P1W');
            $datePeriod = new \DatePeriod($startMarker, $dateInterval, $endMarker);

            $limitMarker = 0;

            foreach ($datePeriod as $date) {
                if (($limit and ($limit === $limitMarker)) or ($date > $actualEndMarker)) {
                    break;
                }

                $newWeeklyEvent = clone($weeklyEvent);
                $newStartDate = new \DateTime($date->format('Y-m-d').' '.$weeklyEventTime);

                if ($newStartDate < $startMarker) {
                    continue;
                }

                $duration = $newWeeklyEvent->getDuration();

                $newWeeklyEvent->setStartDate($newStartDate);
                $newStartDate->add($duration);
                $newWeeklyEvent->setEndDate($newStartDate);
                $newWeeklyEvent->setRecurrenceType();

                $return[] = $newWeeklyEvent;

                $limit and $limitMarker++;
            }
        }

        return $return;
    }
}
