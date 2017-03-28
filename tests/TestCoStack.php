<?php
namespace Tests;

use Generator;
use Ant\Coroutine\Task;

class TestCoStack extends \PHPUnit_Framework_TestCase
{
    public function testCoStack()
    {
        Task::start(function () {
            $co = $this->createGenerator();
            $this->assertInstanceOf(Generator::class, $co);
            yield $co;
        });
    }

    protected function createGenerator()
    {
        yield 1;
        yield 2;
        yield 3;
        yield 4;
        yield 5;
    }
}