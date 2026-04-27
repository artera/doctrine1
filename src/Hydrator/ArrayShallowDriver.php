<?php

namespace Doctrine1\Hydrator;

class ArrayShallowDriver extends ScalarDriver
{
    public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): array
    {
        $cache  = [];
        $result = [];
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $this->gatherRowData($data, $cache, false);
        }
        return $result;
    }
}
