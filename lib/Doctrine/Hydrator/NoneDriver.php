<?php

class Doctrine_Hydrator_NoneDriver extends Doctrine_Hydrator_Abstract
{
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt): array
    {
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }
}
