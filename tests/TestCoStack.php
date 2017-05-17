<?php
namespace Tests;

use Ant\Coroutine\Task;

/**
 * Todo 测试,在等待过程中,继续执行其他任务
 * 启动任务A,暂停0.5s,同时执行任务B,在0.5s内执行完任务B
 *
 * Class TestCoStack
 * @package Tests
 */
class TestCoStack extends \PHPUnit_Framework_TestCase
{
    public function testCoStack()
    {
        Task::start([$this, 'createGenerator']);
    }

    protected function createGenerator()
    {
        $start = microtime(true);

        yield \Ant\Coroutine\sleep(0.05);

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertGreaterThan(0.04, $interval);
    }
}