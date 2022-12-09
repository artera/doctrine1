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

    public function testIsValidType(): void
    {
        $var = '123';
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = 123;
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = 123.12;
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = '123.12';
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = '';
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = null;
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = 'str';
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = [];
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'object'));

        $var = new \Exception();
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'string'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'integer'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'float'));
        $this->assertFalse(\Doctrine1\Validator::isValidType($var, 'array'));
        $this->assertTrue(\Doctrine1\Validator::isValidType($var, 'object'));
    }

    public function testValidate2(): void
    {
        $test           = new \ValidatorTest();
        $test->mymixed  = 'message';
        $test->myrange  = 1;
        $test->myregexp = '123a';

        $validator = new \Doctrine1\Validator();
        $validator->validateRecord($test);

        $stack = $test->errorStack();

        $this->assertInstanceOf(\Doctrine1\Validator\ErrorStack::class, $stack);

        $this->assertContains('The input must not be null', $stack['mystring']);
        $this->assertContains('Value is required and can\'t be empty', $stack['myemail2']);
        $this->assertContains('The input is not between \'4\' and \'123\', inclusively', $stack['myrange']);
        $this->assertContains("The input does not match against pattern '/^[0-9]+$/'", $stack['myregexp']);
        $test->mystring = 'str';

        $test->save();
    }

    public function testValidate(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);
        $user = static::$connection->getTable('User')->find(4);

        $set = ['password'  => 'this is an example of too long password',
                     'loginname' => 'this is an example of too long loginname',
                     'name'      => 'valid name',
                     'created'   => 'invalid'];
        $user->setArray($set);
        $email          = $user->Email;
        $email->address = 'zYne@invalid';

        $this->assertTrue($user->getModified() == $set);

        $validator = new \Doctrine1\Validator();
        $validator->validateRecord($user);


        $stack = $user->errorStack();

        $this->assertInstanceOf(\Doctrine1\Validator\ErrorStack::class, $stack);
        $this->assertContains('length', $stack['loginname']);
        $this->assertContains('length', $stack['password']);
        $this->assertContains('type', $stack['created']);

        $validator->validateRecord($email);
        $stack = $email->errorStack();
        $this->assertContains("'invalid' is not a valid hostname for the email address", $stack['address']);

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    /**
     * Tests saving records with invalid attributes.
     */
    public function testSave(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);
        $user = static::$connection->getTable('User')->find(4);
        $user->clearRelated('Email');
        try {
            $user->name = 'this is an example of too long name not very good example but an example nevertheless';
            $user->save();
        } catch (\Doctrine1\Validator\Exception $e) {
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
            $user->Email = new \Email();
            $user->Email->address = 'jackdaniels@drinkmore.info...';
            $user->name           = 'this is an example of too long user name not very good example but an example nevertheless';
            $user->save();
        } catch (\Doctrine1\Validator\Exception $e) {
            $a = $e->getInvalidRecords();
            $this->assertTrue(is_array($a));
            $emailStack = $user->Email->errorStack();
            $userStack  = $user->errorStack();
            $this->assertContains('The input is not a valid email address. Use the basic format local-part@hostname', $emailStack['address']);
            $this->assertContains('length', $userStack['name']);
        }
        $this->assertInstanceOf(\Exception::class, $e);
        unset($e);

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    /**
     * Tests whether the validate() callback works correctly
     * in descendants of \Doctrine1\Record.
     */
    public function testValidationHooks(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);

        // Tests validate() and validateOnInsert()
        $user = new \User();
        $user->customValidationEnabled = true;

        try {
            $user->name     = "I'm not The Saint";
            $user->password = '1234';
            $user->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine1\Validator\Exception $e) {
            $this->assertCount(1, $e);
            $invalidRecords = $e->getInvalidRecords();
            $this->assertCount(1, $invalidRecords);

            $stack = $invalidRecords[0]->errorStack();

            $this->assertCount(2, $stack);
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
        } catch (\Doctrine1\Validator\Exception $e) {
            $invalidRecords = $e->getInvalidRecords();
            $this->assertCount(1, $invalidRecords);

            $stack = $invalidRecords[0]->errorStack();

            $this->assertCount(1, $stack);
            $this->assertContains('notNobody', $stack['loginname']);  // validateOnUpdate() hook constraint
        }

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    /**
     * Tests whether the validateOnInsert() callback works correctly
     * in descendants of \Doctrine1\Record.
     */
    public function testHookValidateOnInsert(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);

        $user           = new \User();
        $user->customValidationEnabled = true;
        $user->password = '1234';

        try {
            $user->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine1\Validator\Exception $ex) {
            $errors = $user->errorStack();
            $this->assertContains('pwNotTopSecret', $errors['password']);
        }

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    public function testValidationOnManyToManyRelations(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);
        try {
            $client                                       = new \ValidatorTest_ClientModel();
            $client->short_name                           = 'test';
            $client->ValidatorTest_AddressModel[0]->state = 'az';
            $client->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine1\Validator\Exception $dve) {
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

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    public function testSaveInTransactionThrowsValidatorException(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);
        $savepoint = static::$conn->beginTransaction();
        try {
            $client                                       = new \ValidatorTest_ClientModel();
            $client->short_name                           = 'test';
            $client->ValidatorTest_AddressModel[0]->state = 'az';
            $client->save();
            $this->assertFalse('Should not be reached');
        } catch (\Doctrine1\Validator\Exception $dve) {
            $savepoint->rollback();
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

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    public function testSetBooleanWithNumericZeroOrOne(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);

        $test             = new \BooleanTest();
        $test->is_working = '1';
        $test->save();

        $test             = new \BooleanTest();
        $test->is_working = '0';
        $test->save();

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    public function testNoValidationOnExpressions(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);

        $entry        = new \Log_Entry();
        $entry->stamp = new \Doctrine1\Expression('NOW()');
        $entry->save();

        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }

    public function testValidationIsTriggeredOnFlush(): void
    {
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);
        static::$conn->clear();

        $r             = new \ValidatorTest_Person();
        $r->is_football_player = 'abc';

        $this->expectException(\Doctrine1\Validator\Exception::class);
        static::$conn->flush();
        static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
    }
}
