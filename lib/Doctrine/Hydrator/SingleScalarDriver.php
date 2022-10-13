<?php

class Doctrine_Hydrator_SingleScalarDriver extends Doctrine_Hydrator_Abstract
{
    /**
     * @param Doctrine_Connection_Statement $stmt
     *
     * @return (null|scalar)[]|null|scalar
     */
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt): mixed
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
