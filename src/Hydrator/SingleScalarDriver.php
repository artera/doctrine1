<?php

namespace Doctrine1\Hydrator;

class SingleScalarDriver extends \Doctrine1\Hydrator\AbstractHydrator
{
    /**
     * @param \Doctrine1\Connection\Statement $stmt
     *
     * @return (null|scalar)[]|null|scalar
     */
    public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): mixed
    {
        $result = [];
        while (($val = $stmt->fetchColumn()) !== false) {
            $result[] = $val;
        }
        if (count($result) === 1) {
            return $result[0];
        } else {
            return $result;
        }
    }
}
