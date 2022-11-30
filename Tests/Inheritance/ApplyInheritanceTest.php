<?php
namespace Tests\Inheritance;

use Tests\DoctrineUnitTestCase;

class ApplyInheritanceTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['InheritanceDeal', 'InheritanceEntityUser', 'InheritanceUser'];

    public function testApplyInheritance()
    {
        $query = new \Doctrine1\Query();
        $query->from('InheritanceDeal d, d.Users u');
        $query->where('u.id = 1');

        $sql = 'SELECT i.id AS i__id, i.name AS i__name, i2.id AS i2__id, i2.username AS i2__username FROM inheritance_deal i LEFT JOIN inheritance_entity_user i3 ON (i.id = i3.entity_id) AND i3.type = 1 LEFT JOIN inheritance_user i2 ON i2.id = i3.user_id WHERE (i2.id = 1)';

        $this->assertEquals($sql, $query->getSqlQuery());
    }
}
