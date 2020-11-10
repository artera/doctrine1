<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class UnitOfWorkTest extends DoctrineUnitTestCase
{
    private $correct  = ['Task', 'ResourceType', 'Resource', 'Assignment', 'ResourceReference'];

    public function testbuildFlushTree()
    {
        $task = new \Task();

        $tree = static::$unitOfWork->buildFlushTree(['Task']);
        $this->assertEquals($tree, ['Task']);

        $tree = static::$unitOfWork->buildFlushTree(['Task','Resource']);
        $this->assertEquals($tree, ['Task', 'Resource', 'Assignment']);

        $tree = static::$unitOfWork->buildFlushTree(['Task', 'Assignment', 'Resource']);
        $this->assertEquals($tree, ['Task', 'Resource', 'Assignment']);

        $tree = static::$unitOfWork->buildFlushTree(['Assignment', 'Task', 'Resource']);
        $this->assertEquals($tree, ['Resource', 'Task', 'Assignment']);
    }

    public function testbuildFlushTree2()
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread'];

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board']);
        $this->assertEquals($tree, ['Forum_Board']);

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Category','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Category', 'Forum_Board']);
    }

    public function testBuildFlushTree3()
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread','Forum_Entry'];

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Entry','Forum_Board']);

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Entry']);
    }

    public function testBuildFlushTree4()
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Board', 'Forum_Thread']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread']);
    }

    public function testBuildFlushTree5()
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Entry']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry','Forum_Thread']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }

    public function testBuildFlushTree6()
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board','Forum_Thread']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Thread','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }

    public function testBuildFlushTree7()
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Entry']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Entry','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }

    public function testBuildFlushTree8()
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Category']);
        $this->assertEquals($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Category','Forum_Thread','Forum_Board']);
        $this->assertEquals($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Category']);
        $this->assertEquals($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
    }
}
