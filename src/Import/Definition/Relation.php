<?php

namespace Doctrine1\Import\Definition;

use Doctrine1\Column;

class Relation
{
    public function __construct(
        public readonly string $alias,
        public readonly string $class,
        public readonly string $local,
        public readonly string $foreign,
        public readonly bool $many = false,
        public readonly ?string $refClass = null,
        public readonly ?string $refClassRelationAlias = null,
        public readonly ?string $onDelete = null,
        public readonly ?string $onUpdate = null,
        public readonly ?string $cascade = null,
        public readonly ?string $equal = null,
        public readonly ?string $owningSide = null,
        public readonly ?string $foreignKeyName = null,
        public readonly ?string $orderBy = null,
        public readonly ?string $deferred = null,
    ) {
    }
}
