<?php

namespace Doctrine1\Record;

interface ListenerInterface
{
    /**
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public function setOption($name, $value = null);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param  string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * @return void
     */
    public function preSerialize(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postSerialize(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preUnserialize(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postUnserialize(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preSave(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postSave(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preDelete(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postDelete(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preUpdate(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postUpdate(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preInsert(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postInsert(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preHydrate(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postHydrate(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function preValidate(\Doctrine1\Event $event);

    /**
     * @return void
     */
    public function postValidate(\Doctrine1\Event $event);
}
