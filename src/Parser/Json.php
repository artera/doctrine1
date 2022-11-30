<?php

namespace Doctrine1\Parser;

class Json extends \Doctrine1\Parser
{
    public function dumpData(array $array, ?string $path = null, ?string $charset = null): int|string|null
    {
        $data = json_encode($array, JSON_THROW_ON_ERROR);
        return $this->doDump($data, $path);
    }

    public function loadData(string $path): array
    {
        $contents = $this->doLoad($path);
        $json = json_decode($contents, flags: JSON_THROW_ON_ERROR);
        return $json;
    }
}
