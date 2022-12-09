<?php

use Doctrine1\Column;
use Doctrine1\Column\Type;

class AddPhonenumber extends \Doctrine1\Migration\Base
{
    public function migrate($direction)
    {
        $this->table($direction, 'migration_phonenumber', [
            new Column('id', Type::Integer, 20, autoincrement: true, primary: true),
            new Column('user_id', Type::Integer, 2147483647),
            new Column('phonenumber', Type::String, 2147483647),
        ], ['indexes' => [], 'primary' => [0 => 'id']]);
    }
}
