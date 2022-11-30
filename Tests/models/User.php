<?php

require_once 'Entity.php';

// UserTable doesn't extend \Doctrine1\Table -> \Doctrine1\Connection
// won't initialize grouptable when \Doctrine1\Connection->getTable('User') is called
/** @phpstan-extends \Doctrine1\Table<User> */
class UserTable extends \Doctrine1\Table
{
}

/**
 * @property Email $Email
 * @phpstan-extends Entity<UserTable>
 */
class User extends Entity
{
    public bool $customValidationEnabled = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->hasMany('Address', [
            'local'    => 'user_id',
            'foreign'  => 'address_id',
            'refClass' => 'EntityAddress',
        ]);
        $this->hasMany('Address as Addresses', [
            'local'    => 'user_id',
            'foreign'  => 'address_id',
            'refClass' => 'EntityAddress',
        ]);
        $this->hasMany('Album', ['local' => 'id', 'foreign' => 'user_id']);
        $this->hasMany('Book', ['local' => 'id', 'foreign' => 'user_id']);
        $this->hasMany('Group', [
            'local'    => 'user_id',
            'foreign'  => 'group_id',
            'refClass' => 'GroupUser',
        ]);
    }

    /**
     * Custom validation
     */
    public function validate()
    {
        // Allow only one name!
        if ($this->customValidationEnabled && $this->name !== 'The Saint') {
            $this->errorStack()->add('name', 'notTheSaint');
        }
    }

    public function validateOnInsert()
    {
        if ($this->customValidationEnabled && $this->password !== 'Top Secret') {
            $this->errorStack()->add('password', 'pwNotTopSecret');
        }
    }

    public function validateOnUpdate()
    {
        if ($this->customValidationEnabled && $this->loginname !== 'Nobody') {
            $this->errorStack()->add('loginname', 'notNobody');
        }
    }
}
