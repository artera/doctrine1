<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1449Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1449_Document';
            static::$tables[] = 'Ticket_1449_Attachment';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $document                      = new \Ticket_1449_Document();
            $document->name                = 'test';
            $document->Attachments[]->name = 'test 1';
            $document->Attachments[]->name = 'test 2';
            $document->Attachments[]->name = 'test 3';
            $document->save();
        }

        public function testTest()
        {
            $document = \Doctrine_Query::create()
            ->select('d.id, d.name, a.id, a.document_id')
            ->from('Ticket_1449_Document d')
            ->leftJoin('d.Attachments a')
            ->limit(1)
            ->fetchOne();
            $this->assertEquals($document->state(), 4);
            foreach ($document->Attachments as $attachment) {
                $this->assertEquals($attachment->state(), 4);
            }
        }
    }
}

namespace {
    class Ticket_1449_Document extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('test', 'string', 255);
        }

        public function setUp()
        {
            $this->hasMany(
                'Ticket_1449_Attachment as Attachments',
                ['local'   => 'id',
                'foreign' => 'document_id']
            );
        }
    }

    class Ticket_1449_Attachment extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('document_id', 'integer');
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp()
        {
            $this->hasOne(
                'Ticket_1449_Document as Document',
                ['local'   => 'document_id',
                'foreign' => 'id']
            );
        }
    }
}
