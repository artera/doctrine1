<?php

namespace Doctrine1\Record;

use Doctrine1\Event;

interface ListenerInterface
{
    public function setOption(string $name, mixed $value = null): void;
    public function getOptions(): array;
    public function getOption(string $name): mixed;
    public function preSerialize(Event $event): void;
    public function postSerialize(Event $event): void;
    public function preUnserialize(Event $event): void;
    public function postUnserialize(Event $event): void;
    public function preSave(Event $event): void;
    public function postSave(Event $event): void;
    public function preDelete(Event $event): void;
    public function postDelete(Event $event): void;
    public function preUpdate(Event $event): void;
    public function postUpdate(Event $event): void;
    public function preInsert(Event $event): void;
    public function postInsert(Event $event): void;
    public function preHydrate(Event $event): void;
    public function postHydrate(Event $event): void;
    public function preValidate(Event $event): void;
    public function postValidate(Event $event): void;
}
