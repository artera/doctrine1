<?php

namespace Doctrine1\Deserializer;

trait WithDeserializers
{
    /**
     * List of column deserializers that can convert PHP types to DB types. Only one deserializer of each type is allowed.
     * @phpstan-var array<class-string<DeserializerInterface>, array{DeserializerInterface, int}>
     */
    public array $deserializers = [];

    public function clearDeserializers(): void
    {
        $this->deserializers = [];
    }

    public function registerDeserializer(DeserializerInterface $deserializer, int $priority = 50): void
    {
        $this->deserializers[$deserializer::class] = [$deserializer, $priority];
        uasort($this->deserializers, fn($a, $b) => ($a[1] <=> $b[1]) * -1);
    }

    /** @param class-string<DeserializerInterface> $deserializerClass */
    public function unregisterDeserializer(string $deserializerClass): void
    {
        unset($this->deserializers[$deserializerClass]);
    }

    /** @return DeserializerInterface[] */
    public function getDeserializers(): array
    {
        return array_map(fn($s) => $s[0], $this->deserializers);
    }
}
