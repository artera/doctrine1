<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket736Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $delegate          = new \T736_ModuleDelegate();
            $delegate->content = 'Lorem Ipsum and so on...';
            $delegate->save();

            $module                   = new \T736_Module();
            $module->moduledelegateid = $delegate->id;

            $delegate->parent = $module;
            $delegate->save();
        }

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T736_Module';
            static::$tables[] = 'T736_ModuleDelegate';
            parent::prepareTables();
        }

        public function testForHydrationOverwrintingLocalInstancesWhenItShouldnt()
        {
            $module = \Doctrine1\Core::getTable('T736_Module')->find(1);
            $module->moduledata->content = 'foo';
            $module->moduledata->save();
            $this->assertEquals('foo', $module->moduledata->content); // should be "foo" is "Lorem Ipsum and so on..."
        }
    }
}

namespace {
    class T736_Module extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('lastchange', 'timestamp');
            $this->hasColumn('moduledelegateid', 'integer', 4, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->addListener(new \T736_ModuleLoaderListener());
        }
    }

    class T736_ModuleDelegate extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('moduleid', 'integer', 4, []);
            $this->hasColumn('content', 'string', 2000);
        }

        public function setUp(): void
        {
            $this->hasOne('T736_Module as parent', ['local' => 'moduleid', 'foreign' => 'id']);
        }


        public function preUpdate($event): void
        {
            $this->parent->lastchange = date('Y-m-d H:i:s', time());
            $this->parent->save();
        }
    }


    class T736_ModuleLoaderListener extends \Doctrine1\Record\Listener
    {
        public function postHydrate(\Doctrine1\Event $event): void
        {
            $contents = $event->data;
            $delegate = \Doctrine1\Core::getTable('T736_ModuleDelegate')->find($contents['moduledelegateid'], hydrateArray: !$contents instanceof \Doctrine1\Record);
            if ($contents instanceof \Doctrine1\Record) {
                $contents->mapValue('moduledata', $delegate);
                $delegate->parent = $contents;
            } else {
                $contents['moduledata'] = $delegate;
            }
            $event->data = $contents;
        }
    }
}
