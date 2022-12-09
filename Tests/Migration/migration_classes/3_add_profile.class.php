<?php

use Doctrine1\Column;
use Doctrine1\Column\Type;

class AddProfile extends \Doctrine1\Migration\Base
{
    public function migrate($direction)
    {
        $this->table($direction, 'migration_profile', [
            new Column('id', Type::Integer, 20, autoincrement: true, primary: true),
            new Column('name', Type::String, 255),
        ], ['indexes' => [], 'primary' => [0 => 'id']]);
    }
}
