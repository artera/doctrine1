<?php

class Doctrine_Hydrator_ScalarDriver extends Doctrine_Hydrator_Abstract
{
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt): array
    {
        $cache  = [];
        $result = [];

        while ($data = $stmt->fetch(Doctrine_Core::FETCH_ASSOC)) {
            $result[] = $this->_gatherRowData($data, $cache);
        }

        return $result;
    }

    /**
     * @param  array $data
     * @param  array $cache
     * @param  bool  $aliasPrefix
     * @return array
     */
    protected function _gatherRowData($data, &$cache, $aliasPrefix = true)
    {
        $rowData = [];
        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if (!isset($cache[$key])) {
                if ($key == 'DOCTRINE_ROWNUM') {
                    continue;
                }
                // cache general information like the column name <-> field name mapping
                $e                       = explode('__', $key);
                $columnName              = strtolower(array_pop($e));
                $cache[$key]['dqlAlias'] = $this->_tableAliases[strtolower(implode('__', $e))];
                $table                   = $this->_queryComponents[$cache[$key]['dqlAlias']]['table'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg']) && isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName            = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                    $cache[$key]['isAgg'] = true;
                } else {
                    $fieldName            = $table->getFieldName($columnName);
                    $cache[$key]['isAgg'] = false;
                }

                $cache[$key]['fieldName'] = $fieldName;

                // cache type information
                $type = $table->getTypeOfColumn($columnName);
                if ($type == 'integer' || $type == 'string') {
                    $cache[$key]['isSimpleType'] = true;
                } else {
                    $cache[$key]['type']         = $type;
                    $cache[$key]['isSimpleType'] = false;
                }
            }

            $table     = $this->_queryComponents[$cache[$key]['dqlAlias']]['table'];
            $dqlAlias  = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            $rowDataKey = $aliasPrefix ? $dqlAlias . '_' . $fieldName:$fieldName;

            if ($cache[$key]['isSimpleType'] || $cache[$key]['isAgg']) {
                $rowData[$rowDataKey] = $value;
            } else {
                $rowData[$rowDataKey] = $table->prepareValue(
                    $fieldName,
                    $value,
                    $cache[$key]['type']
                );
            }
        }
        return $rowData;
    }
}
