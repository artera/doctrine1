<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1636Test extends DoctrineUnitTestCase
    {
        private $resultCacheLifeSpan = 5;

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1636_File';
            static::$tables[] = 'Ticket_1636_FileType';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            for ($i = 1; $i <= 2; $i++) {
                $fileType       = new \Ticket_1636_FileType();
                $fileType->id   = $i;
                $fileType->name = 'Type ' . $i;
                $fileType->save();
            }

            for ($i = 1; $i <= 10; $i++) {
                $file           = new \Ticket_1636_File();
                $file->id       = $i;
                $file->type_id  = 1;
                $file->filename = 'File ' . $i;
                $file->save();
            }
        }

        public function testResultCacheShouldStoreRelatedComponentsData()
        {
            // Profiler
            $profiler = new \Doctrine_Connection_Profiler();
            static::$conn->setListener($profiler);

            $cacheDriver = new \Doctrine_Cache_Array();

            $query = \Doctrine_Query::create()
            ->useResultCache($cacheDriver, $this->resultCacheLifeSpan)
            ->from('Ticket_1636_File f')
            ->innerJoin('f.type t')
            ->where('f.type_id = ?')
            ->orderBy('f.id DESC')
            ->limit(2);

            // Execute query first time.
            // Results should be placed into memcache.
            $files = $query->execute([1]);

            // Execute query second time.
            // Results should be getted from memcache.
            $files = $query->execute([1]);

            if (count($files)) {
                foreach ($files as $file) {
                    $justForTest = $file->type->id;
                }
            }

            $executeQueryCount = 0;

            foreach ($profiler as $event) {
                if ($event->getName() == 'execute') {
                    $executeQueryCount++;
                    //echo $event->getQuery(), "\n";
                }
            }

            // It should be only one really executed query.
            $this->assertEquals($executeQueryCount, 1);
        }
    }
}

namespace {
    class Ticket_1636_FileType extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            static $columns = [
            'id' => [
                'type'     => 'integer',
                'length'   => 4,
                'unsigned' => true,
                'notnull'  => true,
                'primary'  => true,
                'autoinc'  => true
            ],
            'name' => [
                'type'    => 'string',
                'length'  => 32,
                'notnull' => true
            ]
            ];

            $this->setTableName('files_types');
            $this->hasColumns($columns);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1636_File as files',
                [
                'local'   => 'id',
                'foreign' => 'type_id'
                ]
            );
        }
    }

    class Ticket_1636_File extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            static $columns = [
            'id' => [
                'type'     => 'integer',
                'length'   => 10,
                'unsigned' => true,
                'notnull'  => true,
                'primary'  => true,
                'autoinc'  => true
            ],
            'type_id' => [
                'type'    => 'integer',
                'length'  => 4,
                'notnull' => true
            ],
            'filename' => [
                'type'    => 'string',
                'length'  => 255,
                'notnull' => true
            ]
            ];

            $this->setTableName('files');
            $this->hasColumns($columns);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1636_FileType as type',
                [
                'local'   => 'type_id',
                'foreign' => 'id'
                ]
            );
        }
    }
}
