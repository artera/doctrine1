<?php
namespace Tests\Validator;

use Tests\DoctrineUnitTestCase;

class ValidatorTest extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        static::$connection->beginTransaction();
    }

    public function tearDown(): void
    {
        static::$connection->rollback();
    }

    public static function prepareTables(): void
    {
        static::$tables[] = 'ValidatorTest';
        static::$tables[] = 'ValidatorTest_Person';
        static::$tables[] = 'ValidatorTest_FootballPlayer';
        static::$tables[] = 'ValidatorTest_ClientModel';
        static::$tables[] = 'ValidatorTest_ClientToAddressModel';
        static::$tables[] = 'ValidatorTest_AddressModel';
        static::$tables[] = 'BooleanTest';
        static::$tables[] = 'Log_Entry';
        static::$tables[] = 'Log_Status';
        parent::prepareTables();
    }

    public function testIsValidType()
    {
        $var = '123';
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = 123;
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = 123.12;
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = '123.12';
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = '';
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = null;
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'object'));

        $var = 'str';
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = [];
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'object'));

        $var = new \Exception();
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine_Validator::isValidType($var, 'array'));
        $this->assertTrue(\Doctrine_Validator::isValidType($var, 'object'));
    }

    public function testValidate2()
    {
        $test           = new \ValidatorTest();
        $test->mymixed  = 'message';
        $test->myrange  = 1;
        $test->myregexp = '123a';

        $validator = new \Doctrine_Validator();
        $validator->validateRecord($test);

        $stack = $test->errorStack();

        $this->assertInstanceOf(\Doctrine_Validator_ErrorStack::class, $stack);

        $this->assertContains('The input must not be null', $stack['mystring']);
        $this->assertContains('Value is required and can\'t be empty', $stack['myemail2']);
        $this->assertContains('The input is not between \'4\' and \'123\', inclusively', $stack['myrange']);
        $this->assertContains("The input does not match against pattern '/^[0-9]+$/'", $stack['myregexp']);
        $test->mystring = 'str';

        $test->save();
    }

    public function testValidate()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
        $user = static::$connection->getTable('User')->find(4);

        $set = ['password'  => 'this is an example of too long password',
                     'loginname' => 'this is an example of too long loginname',
                     'name'      => 'valid name',
                     'created'   => 'invalid'];
        $user->setArray($set);
        $email          = $user->Email;
        $email->address = 'zYne@invalid';

        $this->assertTrue($user->getModified() == $set);

        $validator = new \Doctrine_Validator();
        $validator->validateRecord($user);


        $stack = $user->errorStack();

        $this->assertInstanceOf(\Doctrine_Validator_ErrorStack::class, $stack);
        $this->assertContains('length', $stack['loginname']);
        $this->assertContains('length', $stack['password']);
        $this->assertContains('type', $stack['created']);

        $validator->validateRecord($email);
        $stack = $email->errorStack();
        $this->assertContains("'invalid' is not a valid hostname for the email address", $stack['address']);

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    /**
     * Tests saving records with invalid attributes.
     */
    public function testSave()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
        $user = static::$connection->getTable('User')->find(4);
        $user->clearRelated('Email');
        try {
            $user->name = 'this is an example of too long name not very good example but an example nevertheless';
            $user->save();
        } catch (\Doctrine_Validator_Exception $e) {
            $this->assertEquals(1, $e->count());
            $invalidRecords = $e->getInvalidRecords();
            $this->assertEquals(1, count($invalidRecords));
            $stack = $invalidRecords[0]->errorStack();
            $this->assertContains('length', $stack['name']);
        }
        $this->assertInstanceOf(\Exception::class, $e);
        unset($e);

        try {
            $user                 = static::$connection->create('User');
            $user->Email->address = 'jackdaniels@drinkmore.info...';
            $user->name           = 'this is an example of too long user name not very good example but an example nevertheless';
            $user->save();
        } catch (\Doctrine_Validator_Exception $e) {
            $a = $e->getInvalidRecords();
            $this->assertTrue(is_array($a));
            $emailStack = $user->Email->errorStack();
            $userStack  = $user->errorStack();
            $this->assertContains('The input is not a valid email address. Use the basic format local-part@hostname', $emailStack['address']);
            $this->assertContains('length', $userStack['name']);
        }
        $this->assertInstanceOf(\Exception::class, $e);
        unset($e);

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    /**
     * Tests whether the validate() callback works correctly
     * in descendants of Doctrine_Record.
     */
    public function testValidationHooks()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        // Tests validate() and validateOnInsert()
        $user = new \User();
        $user->customValidationEnabled = true;

        try {
            $user->name     = "I'm not The Saint";
            $user->password = '1234';
            $user->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine_Validator_Exception $e) {
            $this->assertEquals($e->count(), 1);
            $invalidRecords = $e->getInvalidRecords();
            $this->assertEquals(count($invalidRecords), 1);

            $stack = $invalidRecords[0]->errorStack();

            $this->assertEquals($stack->count(), 2);
            $this->assertContains('notTheSaint', $stack['name']);  // validate() hook constraint
            $this->assertContains('pwNotTopSecret', $stack['password']); // validateOnInsert() hook constraint
        }

        // Tests validateOnUpdate()
        $user = static::$connection->getTable('User')->find(4);
        $user->customValidationEnabled = true;

        try {
            $user->name      = 'The Saint';  // Set correct name
            $user->password  = 'Top Secret'; // Set correct password
            $user->loginname = 'Somebody'; // Wrong login name!
            $user->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine_Validator_Exception $e) {
            $invalidRecords = $e->getInvalidRecords();
            $this->assertEquals(count($invalidRecords), 1);

            $stack = $invalidRecords[0]->errorStack();

            $this->assertEquals($stack->count(), 1);
            $this->assertContains('notNobody', $stack['loginname']);  // validateOnUpdate() hook constraint
        }

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    /**
     * Tests whether the validateOnInsert() callback works correctly
     * in descendants of Doctrine_Record.
     */
    public function testHookValidateOnInsert()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        $user           = new \User();
        $user->customValidationEnabled = true;
        $user->password = '1234';

        try {
            $user->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine_Validator_Exception $ex) {
            $errors = $user->errorStack();
            $this->assertContains('pwNotTopSecret', $errors['password']);
        }

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testValidationOnManyToManyRelations()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
        try {
            $client                                       = new \ValidatorTest_ClientModel();
            $client->short_name                           = 'test';
            $client->ValidatorTest_AddressModel[0]->state = 'az';
            $client->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine_Validator_Exception $dve) {
            $s = $dve->getInvalidRecords();
            $this->assertEquals(1, count($dve->getInvalidRecords()));
            $invalids = $dve->getInvalidRecords();
            $stack = $client->ValidatorTest_AddressModel[0]->getErrorStack();

            $this->assertContains('The input must not be null', $stack['address1']);
            $this->assertContains('Value is required and can\'t be empty', $stack['address1']);
            $this->assertContains('The input must not be null', $stack['address2']);
            $this->assertContains('The input must not be null', $stack['city']);
            $this->assertContains('Value is required and can\'t be empty', $stack['city']);
            $this->assertContains('The input must not be null', $stack['zip']);
            $this->assertContains('Value is required and can\'t be empty', $stack['zip']);
        }

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testSaveInTransactionThrowsValidatorException()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
        try {
            static::$conn->beginTransaction();
            $client                                       = new \ValidatorTest_ClientModel();
            $client->short_name                           = 'test';
            $client->ValidatorTest_AddressModel[0]->state = 'az';
            $client->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine_Validator_Exception $dve) {
            static::$conn->rollback();
            $s = $dve->getInvalidRecords();
            $this->assertEquals(1, count($dve->getInvalidRecords()));
            $stack = $client->ValidatorTest_AddressModel[0]->getErrorStack();

            $this->assertContains('The input must not be null', $stack['address1']);
            $this->assertContains('Value is required and can\'t be empty', $stack['address1']);
            $this->assertContains('The input must not be null', $stack['address2']);
            $this->assertContains('The input must not be null', $stack['city']);
            $this->assertContains('Value is required and can\'t be empty', $stack['city']);
            $this->assertContains('The input must not be null', $stack['zip']);
            $this->assertContains('Value is required and can\'t be empty', $stack['zip']);
        }

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testSetBooleanWithNumericZeroOrOne()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        $test             = new \BooleanTest();
        $test->is_working = '1';
        $test->save();

        $test             = new \BooleanTest();
        $test->is_working = '0';
        $test->save();

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testNoValidationOnExpressions()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        $entry        = new \Log_Entry();
        $entry->stamp = new \Doctrine_Expression('NOW()');
        $entry->save();

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testValidationIsTriggeredOnFlush()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
        static::$conn->clear();

        $r             = new \ValidatorTest_Person();
        $r->is_football_player = 'abc';

        $this->expectException(\Doctrine_Validator_Exception::class);
        static::$conn->flush();
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }
}
