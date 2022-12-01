<?php

namespace Doctrine1\Hydrator;

class ArrayShallowDriver extends \Doctrine1\Hydrator\ScalarDriver
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
