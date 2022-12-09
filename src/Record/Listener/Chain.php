<?php

namespace Doctrine1\Record\Listener;

/**
 * \Doctrine1\Record\Listener\Chain
 * this class represents a chain of different listeners,
 * useful for having multiple listeners listening the events at the same time
 */
class Chain extends \Doctrine1\Access implements \Doctrine1\Record\ListenerInterface
{
    /**
     * @var array $listeners        an array containing all listeners
     */
    protected $listeners = [];

    /**
     * @var array $options        an array containing chain options
     */
    protected $options = ['disabled' => false];

    /**
     * setOption
     * sets an option in order to allow flexible listener chaining
     *
     * @param mixed $name  the name of the option to set
     * @param mixed $value the value of the option
     *
     * @return void
     */
    public function setOption(mixed $name, mixed $value = null): void
    {
        if (is_array($name)) {
            $this->options = \Doctrine1\Lib::arrayDeepMerge($this->options, $name);
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * getOption
     * returns the value of given option
     *
     * @param  string $name the name of the option
     * @return mixed        the value of given option
     */
    public function getOption(string $name): mixed
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * Get array of configured options
     *
     * @return array $options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * add
     * adds a listener to the chain of listeners
     *
     * @param  object $listener
     * @param  string $name
     * @return void
     */
    public function add($listener, $name = null): void
    {
        if (!($listener instanceof \Doctrine1\Record\ListenerInterface)
            && !($listener instanceof \Doctrine1\Overloadable)
        ) {
            throw new \Doctrine1\EventListener\Exception("Couldn't add eventlistener. Record listeners should implement either \Doctrine1\Record\ListenerInterface or \Doctrine1\Overloadable");
        }
        if ($name === null) {
            $this->listeners[] = $listener;
        } else {
            $this->listeners[$name] = $listener;
        }
    }

    /**
     * returns a \Doctrine1\Record\Listener on success
     * and null on failure
     *
     * @param  mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->listeners[$key])) {
            return null;
        }
        return $this->listeners[$key];
    }

    /**
     * set
     *
     * @param  mixed                    $key
     * @param  \Doctrine1\Record\Listener $listener listener to be added
     * @return void
     */
    public function set($key, $listener)
    {
        $this->listeners[$key] = $listener;
    }

    public function preSerialize(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preSerialize', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preSerialize', $disabled))) {
                    $listener->preSerialize($event);
                }
            }
        }
    }

    public function postSerialize(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postSerialize', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postSerialize', $disabled))) {
                    $listener->postSerialize($event);
                }
            }
        }
    }

    public function preUnserialize(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preUnserialize', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preUnserialize', $disabled))) {
                    $listener->preUnserialize($event);
                }
            }
        }
    }

    public function postUnserialize(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postUnserialize', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postUnserialize', $disabled))) {
                    $listener->postUnserialize($event);
                }
            }
        }
    }

    public function preDqlSelect(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preDqlSelect', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preDqlSelect', $disabled))) {
                    $listener->preDqlSelect($event);
                }
            }
        }
    }

    public function preSave(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preSave', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preSave', $disabled))) {
                    $listener->preSave($event);
                }
            }
        }
    }

    public function postSave(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postSave', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postSave', $disabled))) {
                    $listener->postSave($event);
                }
            }
        }
    }

    public function preDqlDelete(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preDqlDelete', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preDqlDelete', $disabled))) {
                    $listener->preDqlDelete($event);
                }
            }
        }
    }

    public function preDelete(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preDelete', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preDelete', $disabled))) {
                    $listener->preDelete($event);
                }
            }
        }
    }

    public function postDelete(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postDelete', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postDelete', $disabled))) {
                    $listener->postDelete($event);
                }
            }
        }
    }

    public function preDqlUpdate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preDqlUpdate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preDqlUpdate', $disabled))) {
                    $listener->preDqlUpdate($event);
                }
            }
        }
    }

    public function preUpdate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preUpdate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preUpdate', $disabled))) {
                    $listener->preUpdate($event);
                }
            }
        }
    }

    public function postUpdate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postUpdate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postUpdate', $disabled))) {
                    $listener->postUpdate($event);
                }
            }
        }
    }

    public function preInsert(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preInsert', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preInsert', $disabled))) {
                    $listener->preInsert($event);
                }
            }
        }
    }

    public function postInsert(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postInsert', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postInsert', $disabled))) {
                    $listener->postInsert($event);
                }
            }
        }
    }

    public function preHydrate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preHydrate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preHydrate', $disabled))) {
                    $listener->preHydrate($event);
                }
            }
        }
    }

    public function postHydrate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postHydrate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postHydrate', $disabled))) {
                    $listener->postHydrate($event);
                }
            }
        }
    }

    public function preValidate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('preValidate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('preValidate', $disabled))) {
                    $listener->preValidate($event);
                }
            }
        }
    }

    public function postValidate(\Doctrine1\Event $event): void
    {
        $disabled = $this->getOption('disabled');

        if ($disabled !== true && !(is_array($disabled) && in_array('postValidate', $disabled))) {
            foreach ($this->listeners as $listener) {
                $disabled = $listener->getOption('disabled');

                if ($disabled !== true && !(is_array($disabled) && in_array('postValidate', $disabled))) {
                    $listener->postValidate($event);
                }
            }
        }
    }
}
