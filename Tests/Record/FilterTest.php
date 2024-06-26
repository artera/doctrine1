<?php
namespace Tests\Record {
    use Tests\DoctrineUnitTestCase;

    class FilterTest extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }
        protected static array $tables = ['CompositeRecord', 'RelatedCompositeRecord'];
        public function testStandardFiltersThrowsExceptionWhenGettingUnknownProperties()
        {
            $u = new \User();

            $this->expectException(\Doctrine1\Record\UnknownPropertyException::class);
            $u->unknown;
        }

        public function testStandardFiltersThrowsExceptionWhenSettingUnknownProperties()
        {
            $u = new \User();

            $this->expectException(\Doctrine1\Record\UnknownPropertyException::class);
            $u->unknown = 'something';
        }

        public function testCompoundFilterSupportsAccessingRelatedComponentProperties()
        {
            $u = new \CompositeRecord();

            $u->name    = 'someone';
            $u->address = 'something';

            $u->save();

            $this->assertEquals($u->name, 'someone');
            $this->assertEquals($u->address, 'something');
            $this->assertEquals($u->Related->address, 'something');
        }
    }
}

namespace {
    class CompositeRecord extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string');
        }
        public function setUp(): void
        {
            $this->hasOne('RelatedCompositeRecord as Related', ['foreign' => 'foreign_id']);

            $this->unshiftFilter(new \Doctrine1\Record\Filter\Compound(['Related']));
        }
    }
    class RelatedCompositeRecord extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('address', 'string');
            $this->hasColumn('foreign_id', 'integer');
        }
    }
}
