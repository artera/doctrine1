<?php
namespace Tests\Record {
    use Tests\DoctrineUnitTestCase;

    class SerializeUnserializeTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'SerializeTest';
            static::$tables[] = 'TestRecord';

            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testSerializeUnserialize()
        {
            $object                = new \SerializeTest();
            $object->booltest      = true;
            $object->integertest   = 13;
            $object->floattest     = 0.13;
            $object->stringtest    = 'string';
            $object->arraytest     = [1, 2, 3];
            $object->objecttest    = new \TestObject(13);
            $object->enumtest      = 'java';
            $object->blobtest      = 'blobtest';
            $object->clobtest      = 'clobtest';
            $object->gziptest      = 'gziptest';
            $object->timestamptest = '2007-08-07 11:55:00';
            $object->timetest      = '11:55:00';
            $object->datetest      = '2007-08-07';

            $object->save();

            $object_before = clone($object);
            $serialized    = serialize($object);
            $object_after  = unserialize($serialized);

            $this->assertSame($object_before->booltest, $object_after->booltest);
            $this->assertSame($object_before->integertest, $object_after->integertest);
            $this->assertSame($object_before->floattest, $object_after->floattest);
            $this->assertSame($object_before->stringtest, $object_after->stringtest);
            $this->assertSame($object_before->arraytest, $object_after->arraytest);
            $this->assertSame($object_before->enumtest, $object_after->enumtest);
            $this->assertEquals($object_before->objecttest, $object_after->objecttest);
            $this->assertSame($object_before->blobtest, $object_after->blobtest);
            $this->assertSame($object_before->clobtest, $object_after->clobtest);
            $this->assertSame($object_before->gziptest, $object_after->gziptest);
            $this->assertSame($object_before->timestamptest, $object_after->timestamptest);
            $this->assertSame($object_before->timetest, $object_after->timetest);
            $this->assertSame($object_before->datetest, $object_after->datetest);
        }

        public function testSerializeUnserializeRecord()
        {
            $test = new \TestRecord();
            $test->save();

            $object             = new \SerializeTest();
            $object->objecttest = $test;

            $object->save();

            $object_before = clone($object);

            $serialized   = serialize($object);
            $object_after = unserialize($serialized);

            $this->assertSame(get_class($object_after->objecttest), 'TestRecord');
        }
    }
}

namespace {
    class TestObject
    {
        private $test_field;

        public function __construct($value)
        {
            $this->test_field = $value;
        }
    }
}
