<?php

namespace Doctrine1\Serializer;

trait WithSerializers
{
    /**
     * List of column serializers that can convert PHP types to DB types
     * @phpstan-var array<class-string<SerializerInterface>, int>
     */
    public array $serializers = [];

    public function clearSerializers(): void
    {
        $this->serializers = [];
    }

    /** @param class-string<SerializerInterface> $serializerClass */
    public function registerSerializer(string $serializerClass, int $priority = 50): void
    {
        $this->serializers[$serializerClass] = $priority;
    }

    /** @param class-string<SerializerInterface> $serializerClass */
    public function unregisterSerializer(string $serializerClass): void
    {
        unset($this->serializers[$serializerClass]);
    }

    /** @return SerializerInterface[] */
    public function getSerializers(): array
    {
        arsort($this->serializers, SORT_NUMERIC);
        return array_map(fn($s) => new $s(), array_keys($this->serializers));
    }
}
