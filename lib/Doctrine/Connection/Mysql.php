<?php

/**
 * @property Doctrine_DataDict_Mysql $dataDict
 */
class Doctrine_Connection_Mysql extends Doctrine_Connection
{
    protected string $driverName = 'Mysql';

    public function __construct(Doctrine_Manager $manager, PDO|array $adapter)
    {
        $this->setAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_TYPE, 'INNODB');
        $this->supported = [
                          'sequences'            => 'emulated',
                          'indexes'              => true,
                          'affected_rows'        => true,
                          'transactions'         => true,
                          'savepoints'           => false,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => 'emulated',
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => true,
                          'sub_selects'          => true,
                          'auto_increment'       => true,
                          'primary_key'          => true,
                          'result_introspection' => true,
                          'prepared_statements'  => 'emulated',
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => true
                          ];

        $this->properties['string_quoting'] = ['start'          => "'",
                                                    'end'            => "'",
                                                    'escape'         => '\\',
                                                    'escape_pattern' => '\\'];

        $this->properties['identifier_quoting'] = ['start'  => '`',
                                                        'end'    => '`',
                                                        'escape' => '`'];

        $this->properties['sql_comments'] = [
                                            ['start' => '-- ', 'end' => "\n", 'escape' => false],
                                            ['start' => '#', 'end' => "\n", 'escape' => false],
                                            ['start' => '/*', 'end' => '*/', 'escape' => false],
                                            ];

        $this->properties['varchar_max_length'] = 255;

        parent::__construct($manager, $adapter);
    }

    public function connect(): bool
    {
        $connected = parent::connect();
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        return $connected;
    }

    public function getDatabaseName(): string
    {
        return $this->fetchOne('SELECT DATABASE()');
    }

    public function setCharset(string $charset): void
    {
        $query = 'SET NAMES ' . $this->quote($charset);
        $this->exec($query);
        parent::setCharset($charset);
    }

    public function replace(Doctrine_Table $table, array $fields, array $keys): int
    {
        if (empty($keys)) {
            throw new Doctrine_Connection_Exception('Not specified which fields are keys');
        }

        $columns = [];
        $values  = [];
        $params  = [];
        foreach ($fields as $fieldName => $value) {
            $columns[] = $table->getColumnName($fieldName);
            $values[]  = '?';
            $params[]  = $value;
        }

        $query = 'REPLACE INTO ' . $this->quoteIdentifier($table->getTableName()) . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';

        return $this->exec($query, $params);
    }
}
