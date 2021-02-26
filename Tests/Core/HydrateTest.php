<?php
namespace Tests\Core {

    use Tests\DoctrineUnitTestCase;

    class HydrateTest extends DoctrineUnitTestCase
    {
        protected $testData1 = [
            [
                'e' => ['id' => 1, 'name' => 'zYne'],
                'p' => ['id' => 1, 'phonenumber' => '123 123', 'user_id' => 1]
            ],
            [
                'e' => ['id' => 2, 'name' => 'John'],
                'p' => ['id' => 2, 'phonenumber' => '222 222', 'user_id' => 2]
            ],
            [
                'e' => ['id' => 2, 'name' => 'John'],
                'p' => ['id' => 3, 'phonenumber' => '343 343', 'user_id' => 2]
            ],
            [
                'e' => ['id' => 3, 'name' => 'Arnold'],
                'p' => ['id' => 4, 'phonenumber' => '333 333', 'user_id' => 3]
            ],
            [
                'e' => ['id' => 4, 'name' => 'Arnold'],
                'p' => ['id' => null, 'phonenumber' => null, 'user_id' => null]
            ],
        ];

        public static function prepareTables(): void
        {
            static::$tables = array_merge(static::$tables, ['SerializeTest']);
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testHydrateHooks()
        {
            $user = new \User();
            $user->getTable()->addRecordListener(new \HydrationListener);

            $user->name = 'zYne';
            $user->save();

            static::$conn->clear();

            $user = \Doctrine_Query::create()->from('User u')->fetchOne();

            $this->assertEquals('ZYNE', $user->name);
            $this->assertEquals('DEFAULT PASS', $user->password);
        }

        public function testDeserializers()
        {
            $ser = new \SerializeTest();
            $ser->datetimetest = new \DateTime();
            $ser->save();

            static::$conn->clear();

            $ser = \Doctrine_Query::create()->from('SerializeTest t')->fetchOne();
            $today = new \DateTimeImmutable();

            $this->assertInstanceOf(\DateTimeImmutable::class, $ser->datetimetest);

            $ser->datetimetest = $today;
            $this->assertFalse($ser->isModified());
        }
    }
}

namespace {
    class HydrationListener extends Doctrine_Record_Listener
    {
        public function preHydrate(Doctrine_Event $event)
        {
            $data = $event->data;
            $data['password'] = 'default pass';

            $event->data = $data;
        }

        public function postHydrate(Doctrine_Event $event)
        {
            foreach ($event->data as $key => $value) {
                $event->data[$key] = is_string($value) ? strtoupper($value) : $value;
            }
        }
    }

    class Doctrine_Hydrate_Mock extends Doctrine_Hydrator_Abstract
    {
        protected $data;

        public function setData($data)
        {
            $this->data = $data;
        }

        public function hydrateResultSet(Doctrine_Connection_Statement $stmt): bool
        {
            return true;
        }
    }
}
