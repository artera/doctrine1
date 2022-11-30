<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1383Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1383_Image';
            static::$tables[] = 'Ticket_1383_Brand_Image';
            static::$tables[] = 'Ticket_1383_Brand';
            parent::prepareTables();
        }

        public function testTest()
        {
            $orig = \Doctrine1\Manager::getInstance()->getAttribute(\Doctrine1\Core::ATTR_VALIDATE);
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);
            $brand                                         = new \Ticket_1383_Brand;
            $brand->name                                   = 'The Great Brand';
            $brand->Ticket_1383_Brand_Image[0]->name       = 'imagename';
            $brand->Ticket_1383_Brand_Image[0]->owner_id   = 1;
            $brand->Ticket_1383_Brand_Image[0]->owner_type = 0;
            $brand->save();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, $orig);
        }
    }
}

namespace {
    class Ticket_1383_Image extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('owner_id', 'integer', null, ['notnull' => true]);
            $this->hasColumn('owner_type', 'integer', 5, ['notnull' => true]);
            $this->hasColumn('name', 'string', 128, ['notnull' => true, 'unique' => true]);

            $this->setSubclasses(
                [
                'Ticket_1383_Brand_Image' => ['owner_type' => 0]
                ]
            );
        }
    }

    class Ticket_1383_Brand_Image extends Ticket_1383_Image
    {
    }

    class Ticket_1383_Brand extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 255, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1383_Brand_Image',
                [
                'local'   => 'id',
                'foreign' => 'owner_id'
                ]
            );
        }
    }
}
