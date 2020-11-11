<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC147Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'DC147_Product';
            static::$tables[] = 'DC147_Site';
            static::$tables[] = 'DC147_Multiple';
            static::$tables[] = 'DC147_MultipleValue';
            parent::prepareTables();
        }

        public function testInlineMultiple()
        {
            $yml = <<<END
---
DC147_Multiple:
  ISBN2:
    name: isbn2
  ISBN3:
    name: isbn3
DC147_Product: 
  Product_1: 
    name: book3
    MultipleValues:
      Multi_1:
        value: 123345678
        Multiple: ISBN2
      Multi_2:
        value: 232323233
        Multiple: ISBN3
  Product_2: 
    name: book4
    MultipleValues:
      Multi_3:
        value: 444455555
        Multiple: ISBN2
      Multi_4:
        value: 232323233
        Multiple: ISBN3
END;
            file_put_contents('test.yml', $yml);
                \Doctrine_Core::loadData('test.yml', true);

                static::$conn->clear();

                $query = new \Doctrine_Query();
                $query->from('DC147_Product p, p.MultipleValues v, v.Multiple m')
                ->where('p.name = ?', 'book3');

                $product = $query->fetchOne();

                $this->assertEquals($product->name, 'book3');
                $this->assertEquals($product->MultipleValues->count(), 2);
                $this->assertEquals($product->MultipleValues[0]->value, '123345678');
                $this->assertEquals(is_object($product->MultipleValues[0]->Multiple), true);
                $this->assertEquals($product->MultipleValues[0]->Multiple->name, 'isbn2');

                $query = new \Doctrine_Query();
                $query->from('DC147_Product p, p.MultipleValues v, v.Multiple m')
                ->where('p.name = ?', 'book4');

                $product = $query->fetchOne();

                $this->assertEquals($product->name, 'book4');
                $this->assertEquals($product->MultipleValues->count(), 2);
                $this->assertEquals($product->MultipleValues[0]->value, '444455555');
                $this->assertEquals($product->MultipleValues[1]->value, '232323233');
                $this->assertEquals(is_object($product->MultipleValues[0]->Multiple), true);
                $this->assertEquals(is_object($product->MultipleValues[1]->Multiple), true);
                $this->assertEquals($product->MultipleValues[0]->Multiple->name, 'isbn2');
                $this->assertEquals($product->MultipleValues[1]->Multiple->name, 'isbn3');

                
            unlink('test.yml');
        }
    }
}

namespace {
    class DC147_Product extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('site_id', 'integer', null, ['type' => 'integer']);
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'notnull' => true, 'length' => '255']);
        }

        public function setUp()
        {
            $this->hasOne(
                'DC147_Site as Site',
                ['local' => 'site_id',
                'foreign'             => 'id']
            );
            $this->hasMany(
                'DC147_MultipleValue as MultipleValues',
                ['local' => 'id',
                'foreign'       => 'product_id']
            );
        }
    }
    class DC147_Site extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'length' => '255']);
        }

        public function setUp()
        {
            $this->hasMany(
                'DC147_Product as Products',
                ['local' => 'id',
                'foreign'                 => 'site_id']
            );
        }
    }
    class DC147_Multiple extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255, ['type' => 'string', 'notnull' => true, 'length' => '255']);
        }

        public function setUp()
        {
            $this->hasMany(
                'DC147_MultipleValue as MultipleValues',
                ['local' => 'id',
                'foreign'                    => 'multiple_id']
            );
        }
    }
    class DC147_MultipleValue extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('product_id', 'integer', null, ['type' => 'integer', 'primary' => true]);
            $this->hasColumn('multiple_id', 'integer', null, ['type' => 'integer', 'primary' => true]);
            $this->hasColumn('value', 'clob', null, ['type' => 'clob']);
        }

        public function setUp()
        {
            $this->hasOne(
                'DC147_Multiple as Multiple',
                ['local' => 'multiple_id',
                'foreign'  => 'id']
            );

            $this->hasOne(
                'DC147_Product as Product',
                ['local' => 'product_id',
                'foreign'               => 'id']
            );
        }
    }
}
