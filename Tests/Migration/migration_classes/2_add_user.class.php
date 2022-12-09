<?php

use Doctrine1\Column;
use Doctrine1\Column\Type;

class AddUser extends \Doctrine1\Migration\Base
{
    public function migrate($direction)
    {
        $this->table($direction, 'migration_user', [
            new Column('id', Type::Integer, 20, autoincrement: true, primary: true),
            new Column('username', Type::String, 255),
            new Column('password', Type::String, 255),
        ], ['indexes' => [], 'primary' => [0 => 'id']]);
    }
}
