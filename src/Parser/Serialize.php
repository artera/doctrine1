<?php

namespace Doctrine1\Parser;

class Serialize extends \Doctrine1\Parser
{
    public function dumpData(array $array, ?string $path = null, ?string $charset = null): int|string|null
    {
        $data = serialize($array);

        return $this->doDump($data, $path);
    }

    public function loadData(string $path): array
    {
        $contents = $this->doLoad($path);

        return unserialize($contents);
    }
}
