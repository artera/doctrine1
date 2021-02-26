<?php

namespace Doctrine1\Serializer;

trait WithSerializers
{
    /**
     * List of column serializers that can convert PHP types to DB types. Only one serializer of each type is allowed.
     * @phpstan-var array<class-string<SerializerInterface>, array{SerializerInterface, int}>
     */
    public array $serializers = [];

    public function clearSerializers(): void
    {
        $this->serializers = [];
    }

    public function registerSerializer(SerializerInterface $serializer, int $priority = 50): void
    {
        $this->serializers[$serializer::class] = [$serializer, $priority];
    }

    /** @param class-string<SerializerInterface> $serializerClass */
    public function unregisterSerializer(string $serializerClass): void
    {
        unset($this->serializers[$serializerClass]);
    }

    /** @return SerializerInterface[] */
    public function getSerializers(): array
    {
        uasort($this->serializers, fn($a, $b) => ($a[1] <=> $b[1]) * -1);
        return array_map(fn($s) => $s[0], $this->serializers);
    }
}
