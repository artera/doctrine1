<?php

class Doctrine_Connection_Profiler implements Doctrine_Overloadable, IteratorAggregate, Countable
{
    /**
     * all listened events
     */
    private array $events = [];

    /**
     * sequences of all listened events as keys
     */
    private array $eventSequences = [];

    public function __construct()
    {
    }

    public function setFilterQueryType(): void
    {
    }

    /**
     * method overloader
     * this method is used for invoking different listeners, for the full
     * list of availible listeners, see Doctrine_EventListener
     *
     * @param  string $m the name of the method
     * @param  array  $a method arguments
     * @see    Doctrine_EventListener
     * @return void
     */
    public function __call($m, $a)
    {
        $event = $a[0] ?? null;

        // first argument should be an instance of Doctrine_Event
        if (!$event instanceof Doctrine_Event) {
            throw new Doctrine_Connection_Profiler_Exception("Couldn't listen event. Event should be an instance of Doctrine_Event.");
        }

        if (substr($m, 0, 3) === 'pre') {
            // pre-event listener found
            $event->start();

            $eventSequence = $event->getSequence();
            if (!isset($this->eventSequences[$eventSequence])) {
                $this->events[] = $event;
                $this->eventSequences[$eventSequence] = true;
            }
        } else {
            // after-event listener found
            $event->end();
        }
    }

    /**
     * @param mixed $key
     */
    public function get($key): ?Doctrine_Event
    {
        if (isset($this->events[$key])) {
            return $this->events[$key];
        }
        return null;
    }

    /**
     * returns all profiled events as an array
     *
     * @return array all events in an array
     */
    public function getAll(): array
    {
        return $this->events;
    }

    /**
     * returns an iterator that iterates through the logged events
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->events);
    }

    public function count(): int
    {
        return count($this->events);
    }

    /**
     * pop the last event from the event stack
     */
    public function pop(): Doctrine_Event
    {
        $event = array_pop($this->events);
        if ($event !== null) {
            unset($this->eventSequences[$event->getSequence()]);
        }
        return $event;
    }

    /**
     * Get the Doctrine_Event object for the last query that was run, regardless if it has
     * ended or not. If the event has not ended, it's end time will be Null.
     */
    public function lastEvent(): ?Doctrine_Event
    {
        if (empty($this->events)) {
            return null;
        }

        end($this->events);
        return current($this->events);
    }
}
