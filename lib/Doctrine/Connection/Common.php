<?php

class Doctrine_Connection_Common extends Doctrine_Connection
{
    public function modifyLimitQuery(string $query, int|bool $limit = false, int|bool $offset = false, bool $isManip = false): string
    {
        $limit  = (int) $limit;
        $offset = (int) $offset;

        if ($limit && $offset) {
            $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } elseif ($limit && !$offset) {
            $query .= ' LIMIT ' . $limit;
        } elseif (!$limit && $offset) {
            $query .= ' LIMIT 999999999999 OFFSET ' . $offset;
        }

        return $query;
    }
}
