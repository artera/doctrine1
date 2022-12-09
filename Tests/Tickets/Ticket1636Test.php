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
            $profiler = new \Doctrine1\Connection\Profiler();
            static::$conn->setListener($profiler);

            $cacheDriver = new \Doctrine1\Cache\PHPArray();

            $query = \Doctrine1\Query::create()
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
    class Ticket_1636_FileType extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, [
                'unsigned' => true,
                'notnull'  => true,
                'primary'  => true,
                'autoinc'  => true
            ]);

            $this->hasColumn('name', 'string', 32, [
                'notnull' => true
            ]);

            $this->setTableName('files_types');
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

    class Ticket_1636_File extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 10, [
                'unsigned' => true,
                'notnull'  => true,
                'primary'  => true,
                'autoinc'  => true
            ]);

            $this->hasColumn('type_id', 'integer', 4, [
                'notnull' => true
            ]);

            $this->hasColumn('filename', 'string', 255, [
                'notnull' => true
            ]);

            $this->setTableName('files');
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
