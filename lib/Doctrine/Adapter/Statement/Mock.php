<?php

class Doctrine_Adapter_Statement_Mock extends Doctrine_Connection_Statement
{
    private Doctrine_Adapter_Mock $_mock;

    public string $queryString;

    public function __construct(Doctrine_Adapter_Mock $mock)
    {
        $this->_mock = $mock;
    }

    public function bindColumn(int|string $column, string &$param, mixed ...$args): bool
    {
        return false;
    }

    public function bindValue(int|string $parameter, mixed $value, int $data_type = PDO::PARAM_STR): bool
    {
        return false;
    }

    public function bindParam(int|string $parameter, mixed &$variable, mixed ...$args): bool
    {
        return false;
    }

    public function closeCursor(): bool
    {
        return true;
    }

    public function columnCount(): int
    {
        return 0;
    }

    public function errorCode(): ?string
    {
        return null;
    }

    public function errorInfo(): array
    {
        return ['', '', ''];
    }

    public function fetch(int $fetch_style = PDO::FETCH_BOTH, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0): mixed
    {
        return [];
    }

    public function fetchAll(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, mixed $fetch_argument = null, array $ctor_args = []): array
    {
        return [];
    }

    public function execute(array $input_parameters = null): bool
    {
        if (is_object($this->_mock)) {
            $this->_mock->addQuery($this->queryString);
        }
        return true;
    }

    public function fetchColumn(int $column_number = 0): mixed
    {
        return 0;
    }

    public function fetchObject(string $class_name = \stdClass::class, array $ctor_args = []): mixed
    {
        return new $class_name();
    }

    public function getAttribute(int $attribute): mixed
    {
        return null;
    }

    /** @return mixed[] */
    public function getColumnMeta(int $column): array
    {
        return [
            'native_type' => '',
            'driver:decl_type' => '',
            'flags' => '',
            'name' => '',
            'table' => '',
            'len' => 0,
            'precision' => 0,
            'pdo_type' => '',
        ];
    }

    public function nextRowset(): bool
    {
        return true;
    }

    public function rowCount(): int
    {
        return 0;
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return false;
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        return false;
    }
}
