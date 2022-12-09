<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1763Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1763_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setValidate(\Doctrine1\Core::VALIDATE_ALL);
            $user  = new \Ticket_1763_User();
            $valid = $user->isValid();
            $this->assertFalse($valid);
            $string = $user->getErrorStackAsString();
            $this->validateErrorString($string);

            $this->expectException(\Exception::class);
            $user->save();

            \Doctrine1\Manager::getInstance()->setValidate(\Doctrine1\Core::VALIDATE_NONE);
        }

        protected function validateErrorString($string)
        {
            $this->assertStringContainsString('Validation failed in class Ticket_1763_User', $string);
            $this->assertStringContainsString('3 fields had validation errors:', $string);
            $this->assertStringContainsString('* 2 validators failed on email_address (The input must not be null, Invalid type given. String expected)', $string);
            $this->assertStringContainsString('* 1 validator failed on username (The input must not be null)', $string);
            $this->assertStringContainsString('* 2 validators failed on ip_address (The input must not be null, Invalid type given. String expected)', $string);
        }
    }
}

namespace {
    class Ticket_1763_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'email_address',
                'string',
                255,
                ['unique'  => true,
                                                               'notnull' => true,
                'email'   => true]
            );
            $this->hasColumn(
                'username',
                'string',
                255,
                ['unique'  => true,
                'notnull' => true]
            );
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('ip_address', 'string', 255, ['notnull' => true, 'ip' => true]);
        }
    }
}
