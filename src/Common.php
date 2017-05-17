<?php
namespace Ant\Coroutine;

use React\EventLoop\Timer\Timer;

/**
 * @param $interval
 * @param callable $callback
 * @return \React\EventLoop\Timer\TimerInterface
 */
function addTimer($interval, callable $callback)
{
    return GlobalLoop::get()->addTimer($interval, $callback);
}

/**
 * @param $interval
 * @param callable $callback
 * @return \React\EventLoop\Timer\TimerInterface
 */
function addPeriodicTimer($interval, callable $callback)
{
    return GlobalLoop::get()->addPeriodicTimer($interval, $callback);
}

/**
 * @param Timer $timer
 * @return bool
 */
function isTimerActive(Timer $timer)
{
    return GlobalLoop::get()->isTimerActive($timer);
}

/**
 * @param Timer $timer
 */
function cancelTimer(Timer $timer)
{
    GlobalLoop::get()->cancelTimer($timer);
}

/**
 * @param callable $callback
 */
function futureTick(callable $callback)
{
    GlobalLoop::get()->futureTick($callback);
}

/**
 * @param callable $callback
 */
function nextTick(callable $callback)
{
    GlobalLoop::get()->nextTick($callback);
}

/**
 * @param $stream
 * @param callable $listener
 */
function addReadStream($stream, callable $listener)
{
    GlobalLoop::get()->addReadStream($stream, $listener);
}

/**
 * @param $stream
 * @param callable $listener
 */
function addWriteStream($stream, callable $listener)
{
    GlobalLoop::get()->addWriteStream($stream, $listener);
}

/**
 * @param $stream
 */
function removeReadStream($stream)
{
    GlobalLoop::get()->removeReadStream($stream);
}

/**
 * @param $stream
 */
function removeWriteStream($stream)
{
    GlobalLoop::get()->removeWriteStream($stream);
}

/**
 * @param $stream
 */
function removeStream($stream)
{
    GlobalLoop::get()->removeStream($stream);
}

/**
 * 等待到流可读
 *
 * @param resource $stream
 * @return SysCall
 */
function waitForRead($stream)
{
    return new SysCall(function (Task $task) use ($stream) {
        addReadStream($stream, function ($stream) use ($task) {
            // IO完成后,不再触发协程上下文切换
            removeReadStream($stream);
            $task->resume();
        });

        return Signal::TASK_WAIT;
    });
}

/**
 * 等待到流可写
 *
 * @param $stream
 * @return SysCall
 */
function waitForWrite($stream)
{
    return new SysCall(function (Task $task) use ($stream) {
        addWriteStream($stream, function ($stream) use ($task) {
            // IO完成后,不再触发协程上下文切换
            removeWriteStream($stream);
            $task->resume();
        });

        return Signal::TASK_WAIT;
    });
}

/**
 * 沉睡指定时间
 *
 * @param int|double $time
 * @return SysCall
 */
function sleep($time)
{
    return new SysCall(function (Task $task) use ($time) {
        addTimer($time, function () use ($task) {
            $task->resume();
        });

        return Signal::TASK_SLEEP;
    });
}

/**
 * 结束任务
 *
 * @return SysCall
 */
function killed()
{
    return new SysCall(function () {
        return Signal::TASK_KILLED;
    });
}

/**
 * 获取Loop
 *
 * @return SysCall
 */
function getLoop()
{
    return new SysCall(function (Task $task) {
        $task->setReenterValue(GlobalLoop::get());
        return Signal::TASK_CONTINUE;
    });
}

/**
 * 获取Task
 *
 * @return SysCall
 */
function getTask()
{
    return new SysCall(function (Task $task) {
        $task->setReenterValue($task);
        return Signal::TASK_CONTINUE;
    });
}

/**
 * 异步读取流指定长度的数据
 *
 * @param resource $stream
 * @param int $length
 * @return AsyncCall
 */
function asyncRead($stream, $length)
{
    return new AsyncCall(function (Task $task) use ($stream, $length) {
        GlobalLoop::addReadStream($stream, function ($stream) use ($task, $length) {
            $chunk = fread($stream, $length);

            if ($chunk === '' || $chunk === false) {
                GlobalLoop::removeReadStream($stream);
                $error = __createLastError("Read failed");
                $task->throwException($error);
                return;
            }

            GlobalLoop::removeReadStream($stream);
            $task->resume($chunk);
        });

        return Signal::TASK_WAIT;
    });
}

/**
 * 异步写入到一个流
 *
 * @param resource $stream
 * @param string $buffer
 * @return AsyncCall
 */
function asyncWrite($stream, $buffer)
{
    return new AsyncCall(function (Task $task) use ($stream, $buffer) {
        GlobalLoop::addWriteStream($stream, function ($stream) use ($task, $buffer) {
            $sent = @fwrite($stream, $buffer);

            if ($sent === 0 || $sent === false) {
                GlobalLoop::removeWriteStream($stream);
                $error = __createLastError("Send failed");
                $task->throwException($error);
                return;
            }

            GlobalLoop::removeWriteStream($stream);
            $task->resume($sent);
        });

        return Signal::TASK_WAIT;
    });
}

/**
 * 获取最后一个错误
 *
 * @param string $defaultMessage
 * @return \ErrorException|\RuntimeException
 */
function __createLastError($defaultMessage = '')
{
    $error = error_get_last();

    if ($error === null) {
        return new \RuntimeException($defaultMessage);
    }

    return new \ErrorException(
        $error['message'],
        0,
        $error['type'],
        $error['file'],
        $error['line']
    );
}

/**
 * 新建任务
 *
 * @param array|\Generator|callable $task
 * @return SysCall
 */
function newTask($coroutine)
{
    return new SysCall(function () use ($coroutine) {
        if (!$coroutine instanceof \Generator) {
            throw new \InvalidArgumentException;
        }

        Task::createFrom($coroutine);

        return Signal::TASK_CONTINUE;
    });
}

/**
 * 等待任务完成
 *
 * @param array $taskList
 * @return SysCall
 */
function waitTask(array $taskList)
{
    // Todo 利用ReactPhp.Promise完成
    return new SysCall(function (Task $task) use ($taskList) {
        $taskCount = count($taskList);
        $completed = 0;

        foreach ($taskList as $coroutine) {
            // 新建任务
            Task::createFrom(function () use (
                $coroutine,
                $taskCount,
                &$completed,
                $task
            ) {
                yield $coroutine;

                // 查看任务完成数
                if (++$completed === $taskCount) {
                    $task->resume();
                }
            });
        }

        return Signal::TASK_WAIT;
    });
}