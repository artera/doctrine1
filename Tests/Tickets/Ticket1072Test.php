<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1072Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1072BankTransaction';
            static::$tables[] = 'T1072PaymentDetail';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $bt       = new \T1072BankTransaction();
            $bt->name = 'Test Bank Transaction';

            // (additional check: value must be NULL)
            $this->assertEquals(gettype($bt->payment_detail_id), gettype(null));

            // If I access this relation...

            if ($bt->T1072PaymentDetail) {
            }

            // (additional check: value must still be NULL not an object)
            // [romanb]: This is expected behavior currently. Accessing a related record will create
            // a new \one if there is none yet. This makes it possible to use syntax like:
            // $record = new \Record();
            // $record->Related->name = 'foo'; // will implicitly create a new \Related
            // In addition the foreign key field is set to a reference to the new \record (ugh..).
            // No way to change this behavior at the moment for BC reasons.
            $this->assertEquals(gettype($bt->payment_detail_id), gettype(null));

            // ...save...
            // [romanb]: Related T1072PaymentDetail will not be saved because its not modified
            // (isModified() == false)
            $bt->save();

            // ...and access the relation column it will throw
            // an exception here but it shouldn't.
            // [romanb]: This has been fixed now. $bt->payment_detail_id will be an empty
            // object as before.
            if ($bt->payment_detail_id) {
            }

            // (additional check: value must still be NULL not an object)
            // [romanb]: See above. This is an empty object now, same as before.
            $this->assertEquals(gettype($bt->payment_detail_id), gettype(null));
        }


        public function testTicket2()
        {
            $bt       = new \T1072BankTransaction();
            $bt->name = 'Test Bank Transaction';

            // [romanb]: Accessing a related record will create
            // a new \one if there is none yet. This makes it possible to use syntax like:
            // $record = new \Record();
            // $record->Related->name = 'foo'; // will implicitly create a new \Related
            $bt->T1072PaymentDetail->name = 'foo';

            $this->assertEquals(gettype($bt->T1072PaymentDetail), 'object');
            $this->assertEquals(gettype($bt->T1072PaymentDetail->name), 'string');
            $this->assertEquals(gettype($bt->payment_detail_id), gettype(null));

            $bt->save();

            // After the object gets saved, the foreign key is finally set
            $this->assertEquals($bt->payment_detail_id, 1);
        }
    }
}

namespace {
    class T1072BankTransaction extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('t1072_bank_transaction');
            $this->hasColumn('payment_detail_id', 'integer', null);
            $this->hasColumn('name', 'string', 255, ['notnull' => true]);
            $this->option('charset', 'utf8');
        }

        public function setUp()
        {
            parent::setUp();
            $this->hasOne(
                'T1072PaymentDetail',
                ['local'   => 'payment_detail_id',
                'foreign' => 'id']
            );
        }
    }

    class T1072PaymentDetail extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('t1072_payment_detail');
            $this->hasColumn('name', 'string', 255, ['notnull' => true]);
            $this->option('charset', 'utf8');
        }

        public function setUp()
        {
            parent::setUp();
            $this->hasOne(
                'T1072BankTransaction',
                ['local'   => 'id',
                'foreign' => 'payment_detail_id']
            );
        }
    }
}
