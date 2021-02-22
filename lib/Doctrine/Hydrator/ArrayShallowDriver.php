<?php

class Doctrine_Hydrator_ArrayShallowDriver extends Doctrine_Hydrator_ScalarDriver
{
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt): array
    {
        $cache  = [];
        $result = [];
        while ($data = $stmt->fetch(Doctrine_Core::FETCH_ASSOC)) {
            $result[] = $this->_gatherRowData($data, $cache, false);
        }
        return $result;
    }
}
