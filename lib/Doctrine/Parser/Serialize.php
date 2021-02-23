<?php

class Doctrine_Parser_Serialize extends Doctrine_Parser
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
