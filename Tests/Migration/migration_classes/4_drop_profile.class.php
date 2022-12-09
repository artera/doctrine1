<?php

use Doctrine1\Column;
use Doctrine1\Column\Type;

class DropProfile extends \Doctrine1\Migration\Base
{
    public function migrate($direction)
    {
        $direction = $direction == 'up' ? 'down' : 'up';
        $this->table($direction, 'migration_profile', [
            new Column('id', Type::Integer, 20, autoincrement: true, primary: true),
            new Column('name', Type::String, 255),
        ], ['indexes' => [], 'primary' => [0 => 'id']]);
    }
}
