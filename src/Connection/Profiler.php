<?php

namespace Doctrine1\Connection;

class Profiler implements \Doctrine1\Overloadable, \IteratorAggregate, \Countable
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
     * list of availible listeners, see \Doctrine1\EventListener
     *
     * @param  string $m the name of the method
     * @param  array  $a method arguments
     * @see    \Doctrine1\EventListener
     * @return void
     */
    public function __call($m, $a)
    {
        $event = $a[0] ?? null;

        // first argument should be an instance of \Doctrine1\Event
        if (!$event instanceof \Doctrine1\Event) {
            throw new Profiler\Exception("Couldn't listen event. Event should be an instance of \Doctrine1\Event.");
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
    public function get($key): ?\Doctrine1\Event
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
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->events);
    }

    public function count(): int
    {
        return count($this->events);
    }

    /**
     * pop the last event from the event stack
     */
    public function pop(): \Doctrine1\Event
    {
        $event = array_pop($this->events);
        if ($event !== null) {
            unset($this->eventSequences[$event->getSequence()]);
        }
        return $event;
    }

    /**
     * Get the \Doctrine1\Event object for the last query that was run, regardless if it has
     * ended or not. If the event has not ended, it's end time will be None.
     */
    public function lastEvent(): ?\Doctrine1\Event
    {
        if (empty($this->events)) {
            return null;
        }

        end($this->events);
        return current($this->events);
    }
}
