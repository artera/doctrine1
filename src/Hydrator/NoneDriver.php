<?php

namespace Doctrine1\Hydrator;

use PDO;

class NoneDriver extends \Doctrine1\Hydrator\AbstractHydrator
{
    public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): array
    {
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }
}
