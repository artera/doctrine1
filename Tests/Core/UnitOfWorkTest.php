<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class UnitOfWorkTest extends DoctrineUnitTestCase
{
    /** @var string[] */
    private array $correct  = ['Task', 'ResourceType', 'Resource', 'Assignment', 'ResourceReference'];

    public function testbuildFlushTree(): void
    {
        $task = new \Task();

        $tree = static::$unitOfWork->buildFlushTree(['Task']);
        $this->assertEquals(['Task'], $tree);

        $tree = static::$unitOfWork->buildFlushTree(['Task','Resource']);
        $this->assertEquals(['Task', 'Resource', 'Assignment'], $tree);

        $tree = static::$unitOfWork->buildFlushTree(['Task', 'Assignment', 'Resource']);
        $this->assertEquals(['Task', 'Resource', 'Assignment'], $tree);

        $tree = static::$unitOfWork->buildFlushTree(['Assignment', 'Task', 'Resource']);
        $this->assertEquals(['Resource', 'Task', 'Assignment'], $tree);
    }

    public function testbuildFlushTree2(): void
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread'];

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board']);
        $this->assertEquals(['Forum_Board'], $tree);

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Category','Forum_Board']);
        $this->assertEquals(['Forum_Category', 'Forum_Board'], $tree);
    }

    public function testBuildFlushTree3(): void
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread','Forum_Entry'];

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board']);
        $this->assertEquals(['Forum_Entry','Forum_Board'], $tree);

        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry']);
        $this->assertEquals(['Forum_Board','Forum_Entry'], $tree);
    }

    public function testBuildFlushTree4(): void
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board']);
        $this->assertEquals(['Forum_Board', 'Forum_Thread'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread']);
        $this->assertEquals(['Forum_Board','Forum_Thread'], $tree);
    }

    public function testBuildFlushTree5(): void
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Entry']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry','Forum_Thread']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
    }

    public function testBuildFlushTree6(): void
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board','Forum_Thread']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Entry','Forum_Thread','Forum_Board']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
    }

    public function testBuildFlushTree7(): void
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Entry']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Entry','Forum_Board']);
        $this->assertEquals(['Forum_Board','Forum_Thread','Forum_Entry'], $tree);
    }

    public function testBuildFlushTree8(): void
    {
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Category']);
        $this->assertEquals(['Forum_Category','Forum_Board','Forum_Thread'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Category','Forum_Thread','Forum_Board']);
        $this->assertEquals(['Forum_Category','Forum_Board','Forum_Thread'], $tree);
        $tree = static::$unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Category']);
        $this->assertEquals(['Forum_Category','Forum_Board','Forum_Thread'], $tree);
    }
}
