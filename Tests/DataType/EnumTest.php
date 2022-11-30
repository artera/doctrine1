<?php
namespace Tests\DataType;

use Tests\DoctrineUnitTestCase;

class EnumTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['EnumTest', 'EnumTest2', 'EnumTest3'];

    public function testParameterConversion()
    {
        $test         = new \EnumTest();
        $test->status = 'open';
        $this->assertEquals($test->status, 'open');
        $test->save();

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest WHERE EnumTest.status = 'open'");
        $this->assertEquals(count($ret), 1);
    }

    public function testUpdate()
    {
        $test         = new \EnumTest2();
        $test->status = 'open';
        $this->assertEquals($test->status, 'open');
        $test->save();

        $test_update         = \Doctrine1\Core::getTable('EnumTest2')->find(1);
        $test_update->status = 'verified';
        $this->assertEquals($test_update->status, 'verified');
        $test_update->save();
    }

    public function testDqlUpdate()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $query->update('EnumTest2 u')
            ->set('u.status', '?', 'verified');

        $this->assertEquals($query->getSqlQuery(), 'UPDATE enum_test2 SET status = ?');

        $query->execute();

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest2 WHERE EnumTest2.status = 'verified'");
        $this->assertEquals(count($ret), 1);
    }

    public function testParameterConversionInCount()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->parseDqlQuery("FROM EnumTest WHERE EnumTest.status = 'open'")
            ->count();
        $this->assertEquals($ret, 1);

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->parseDqlQuery('FROM EnumTest WHERE EnumTest.status = ?')
            ->count(['open']);
        $this->assertEquals($ret, 1);
    }

    public function testInAndNotIn()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest WHERE EnumTest.status IN ('open')");
        $this->assertEquals(count($ret), 1);

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest WHERE EnumTest.status NOT IN ('verified', 'closed')");
        $this->assertEquals(count($ret), 1);
    }

    public function testExpressionComposition()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest e WHERE e.id > 0 AND (e.status != 'closed' OR e.status = 'verified')");
        $this->assertEquals(count($ret), 1);
    }

    public function testNotEqual()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query("FROM EnumTest WHERE EnumTest.status != 'closed'");
        $this->assertEquals(count($ret), 1);
    }

    public function testEnumType()
    {
        $enum         = new \EnumTest();
        $enum->status = 'open';
        $this->assertEquals($enum->status, 'open');
        $enum->save();
        $this->assertEquals($enum->status, 'open');
        $enum->refresh();
        $this->assertEquals($enum->status, 'open');

        $enum->status = 'closed';

        $this->assertEquals($enum->status, 'closed');

        $enum->save();
        $this->assertEquals($enum->status, 'closed');
        $this->assertTrue(is_numeric($enum->id));
        $enum->refresh();
        $this->assertEquals($enum->status, 'closed');
    }

    public function testEnumTypeWithCaseConversion()
    {
        static::$conn->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);

        $enum = new \EnumTest();

        $enum->status = 'open';
        $this->assertEquals($enum->status, 'open');

        $enum->save();
        $this->assertEquals($enum->status, 'open');

        $enum->refresh();
        $this->assertEquals($enum->status, 'open');

        $enum->status = 'closed';

        $this->assertEquals($enum->status, 'closed');

        $enum->save();
        $this->assertEquals($enum->status, 'closed');

        $enum->refresh();
        $this->assertEquals($enum->status, 'closed');

        static::$conn->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
    }

    public function testFailingRefresh()
    {
        $enum = static::$connection->getTable('EnumTest')->find(1);

        static::$conn->exec('DELETE FROM enum_test WHERE id = 1');

        $this->expectException(\Doctrine1\Record\Exception::class);
        $enum->refresh();
    }

    public function testEnumFetchArray()
    {
        $q = new \Doctrine1\Query();
        $q->select('e.*')
            ->from('EnumTest e')
            ->limit(1);
        $ret = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertFalse(is_numeric($ret[0]['status']));
    }

    public function testLiteralEnumValueConversionSupportsJoins()
    {
        $q = new \Doctrine1\Query(static::$connection);
        $q->addSelect('e.*')
            ->addSelect('e3.*')
            ->from('EnumTest e')
            ->leftJoin('e.Enum3 e3')
            ->where("e.status = 'verified'")
            ->execute();

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.status AS e__status, e.text AS e__text, e2.text AS e2__text FROM enum_test e LEFT JOIN enum_test3 e2 ON e.text = e2.text WHERE (e.status = 'verified')");
    }

    public function testInvalidValueErrors()
    {
        $orig = \Doctrine1\Manager::getInstance()->getAttribute(\Doctrine1\Core::ATTR_VALIDATE);
        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

        $this->expectException(\Doctrine1\Validator\Exception::class);
        $test         = new \EnumTest();
        $test->status = 'opeerertn';
        $test->save();

        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, $orig);
    }
}
