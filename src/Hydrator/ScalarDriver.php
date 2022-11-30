<?php

namespace Doctrine1\Hydrator;

class ScalarDriver extends \Doctrine1\Hydrator\AbstractHydrator
{
    public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): array
    {
        $cache  = [];
        $result = [];

        while ($data = $stmt->fetch(\Doctrine1\Core::FETCH_ASSOC)) {
            $result[] = $this->gatherRowData($data, $cache);
        }

        return $result;
    }

    /**
     * @param  array $data
     * @param  array $cache
     * @param  bool  $aliasPrefix
     * @return array
     */
    protected function gatherRowData($data, &$cache, $aliasPrefix = true)
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
                $cache[$key]['dqlAlias'] = $this->tableAliases[strtolower(implode('__', $e))];
                $table                   = $this->queryComponents[$cache[$key]['dqlAlias']]['table'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->queryComponents[$cache[$key]['dqlAlias']]['agg']) && isset($this->queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName            = $this->queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
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

            $table     = $this->queryComponents[$cache[$key]['dqlAlias']]['table'];
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
