<?php

namespace Doctrine1\Record;

class Listener implements ListenerInterface
{
    /**
     * @var array $options        an array containing options
     */
    protected $options = ['disabled' => false];

    /**
     * setOption
     * sets an option in order to allow flexible listener
     *
     * @param mixed $name  the name of the option to set
     * @param mixed $value the value of the option
     *
     * @return void
     */
    public function setOption($name, $value = null)
    {
        if (is_array($name)) {
            $this->options = \Doctrine1\Lib::arrayDeepMerge($this->options, $name);
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * getOptions
     * returns all options of this template and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * getOption
     * returns the value of given option
     *
     * @param  string $name the name of the option
     * @return mixed        the value of given option
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * @return void
     */
    public function preSerialize(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postSerialize(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preUnserialize(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postUnserialize(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preDqlSelect(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preSave(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postSave(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preDqlDelete(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preDelete(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postDelete(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preDqlUpdate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preUpdate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postUpdate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preInsert(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postInsert(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preHydrate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postHydrate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function preValidate(\Doctrine1\Event $event)
    {
    }

    /**
     * @return void
     */
    public function postValidate(\Doctrine1\Event $event)
    {
    }
}
