<?php
namespace Ant\Coroutine;

/**
 * 系统回调
 *
 * Class SysCall
 * @package Ant\Coroutine
 */
class SysCall
{
    protected $callback;

    /**
     * SysCall constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->callback, func_get_args());
    }

    /**
     * 等待到流可读
     *
     * @param resource $stream
     * @return SysCall
     */
    public static function waitForRead($stream)
    {
        // Todo 保证每次触发异步回调,触发的任务都不应该是同一个任务
        return new SysCall(function (Task $task) use ($stream) {
            $task->getLoop()->addReadStream($stream, function ($stream) use ($task) {
                $task->setReenterValue($stream);
                $task->reenter();
                $task->run();
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
    public static function waitForWrite($stream)
    {
        return new SysCall(function (Task $task) use ($stream) {
            $task->getLoop()->addWriteStream($stream, function ($stream) use ($task) {
                $task->setReenterValue($stream);
                $task->reenter();
                $task->run();
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
    public static function sleep($time)
    {
        return new SysCall(function (Task $task) use ($time) {
            $task->getLoop()->addTimer($time, function () use ($task) {
                $task->reenter();
                $task->run();
            });

            return Signal::TASK_SLEEP;
        });
    }

    /**
     * 结束任务
     *
     * @return SysCall
     */
    public static function killed()
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
    public static function getLoop()
    {
        return new SysCall(function (Task $task) {
            $task->setReenterValue($task->getLoop());
            return Signal::TASK_CONTINUE;
        });
    }

    /**
     * 获取Task
     *
     * @return SysCall
     */
    public static function getTask()
    {
        return new SysCall(function (Task $task) {
            $task->setReenterValue($task);
            return Signal::TASK_CONTINUE;
        });
    }
}