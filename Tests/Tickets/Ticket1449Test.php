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
            $document = \Doctrine1\Query::create()
            ->select('d.id, d.name, a.id, a.document_id')
            ->from('Ticket_1449_Document d')
            ->leftJoin('d.Attachments a')
            ->limit(1)
            ->fetchOne();
            $this->assertEquals($document->state()->value, 4);
            foreach ($document->Attachments as $attachment) {
                $this->assertEquals($attachment->state()->value, 4);
            }
        }
    }
}

namespace {
    class Ticket_1449_Document extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('test', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1449_Attachment as Attachments',
                ['local'   => 'id',
                'foreign' => 'document_id']
            );
        }
    }

    class Ticket_1449_Attachment extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('document_id', 'integer');
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1449_Document as Document',
                ['local'   => 'document_id',
                'foreign' => 'id']
            );
        }
    }
}
