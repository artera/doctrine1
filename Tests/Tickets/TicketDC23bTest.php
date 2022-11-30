<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC23bTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_Product';
            static::$tables[] = 'Ticket_Site';
            static::$tables[] = 'Ticket_Multiple';
            static::$tables[] = 'Ticket_MultipleValue';
            parent::prepareTables();
        }

        public function testInlineSite()
        {
            $yml = <<<END
---
Ticket_Product:
  Product_1:
    name: book
    Site:
      name: test
END;
            file_put_contents('test.yml', $yml);
                \Doctrine1\Core::loadData('test.yml', true);

                static::$conn->clear();

                $query = new \Doctrine1\Query();
                $query->from('Ticket_Product p, p.Site s')
                ->where('p.name = ?', 'book');

                $product = $query->execute()->getFirst();

                $this->assertEquals($product->name, 'book');
                $this->assertEquals(is_object($product->Site), true);
                $this->assertEquals($product->Site->name, 'test');


            unlink('test.yml');
        }

        public function testMultiple()
        {
            $yml = <<<END
---
Ticket_Multiple:
  ISBN:
    name: isbn

Ticket_Product:
  Product_1:
    name: book2

Ticket_MultipleValue:
  Val_1:
    value: 123345678
    Multiple: ISBN
    Product: Product_1
END;
            file_put_contents('test.yml', $yml);
                \Doctrine1\Core::loadData('test.yml', true);

                static::$conn->clear();

                $query = new \Doctrine1\Query();
                $query->from('Ticket_Product p, p.MultipleValues v, v.Multiple m')
                ->where('p.name = ?', 'book2');

                $product = $query->fetchOne();

                $this->assertEquals($product->name, 'book2');
                $this->assertEquals($product->MultipleValues->count(), 1);
                $this->assertEquals($product->MultipleValues[0]->value, '123345678');
                $this->assertEquals(is_object($product->MultipleValues[0]->Multiple), true);
                $this->assertEquals($product->MultipleValues[0]->Multiple->name, 'isbn');


            unlink('test.yml');
        }

        public function testInlineMultiple()
        {
            $yml = <<<END
---
Ticket_Multiple:
  ISBN2:
    name: isbn2

Ticket_Product:
  Product_1:
    name: book3
    MultipleValues:
      Multi_1:
        value: 123345678
        Multiple: ISBN2
END;
            file_put_contents('test.yml', $yml);
                \Doctrine1\Core::loadData('test.yml', true);

                static::$conn->clear();

                $query = new \Doctrine1\Query();
                $query->from('Ticket_Product p, p.MultipleValues v, v.Multiple m')
                ->where('p.name = ?', 'book3');

                $product = $query->fetchOne();

                $this->assertEquals($product->name, 'book3');
                $this->assertEquals($product->MultipleValues->count(), 1);
                $this->assertEquals($product->MultipleValues[0]->value, '123345678');
                $this->assertEquals(is_object($product->MultipleValues[0]->Multiple), true);
                $this->assertEquals($product->MultipleValues[0]->Multiple->name, 'isbn2');


            unlink('test.yml');
        }
    }
}

namespace {
    class Ticket_Product extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('site_id', 'integer', null, ['type' => 'integer']);
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'notnull' => true, 'length' => '255']);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_Site as Site',
                ['local' => 'site_id',
                'foreign'              => 'id']
            );
            $this->hasMany(
                'Ticket_MultipleValue as MultipleValues',
                ['local' => 'id',
                'foreign'        => 'product_id']
            );
        }
    }
    class Ticket_Site extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'length' => '255']);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_Product as Products',
                ['local' => 'id',
                'foreign'                  => 'site_id']
            );
        }
    }
    class Ticket_Multiple extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'notnull' => true, 'length' => '255']);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_MultipleValue as MultipleValues',
                ['local' => 'id',
                'foreign'                     => 'multiple_id']
            );
        }
    }
    class Ticket_MultipleValue extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('product_id', 'integer', null, ['type' => 'integer', 'primary' => true]);
            $this->hasColumn('multiple_id', 'integer', null, ['type' => 'integer', 'primary' => true]);
            $this->hasColumn('value', 'clob', null, ['type' => 'clob']);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_Multiple as Multiple',
                ['local' => 'multiple_id',
                'foreign'   => 'id']
            );

            $this->hasOne(
                'Ticket_Product as Product',
                ['local' => 'product_id',
                'foreign'                => 'id']
            );
        }
    }
}
