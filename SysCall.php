<?php
namespace Ant\Coroutine;

class SysCall
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

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
     * 等待
     *
     * @return SysCall
     */
    public static function wait()
    {
        return new SysCall(function () {
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

    public static function deliver($value)
    {
        return new SysCall(function (Task $task) use ($value) {
            $task->setReenterValue($value);
            return Signal::TASK_CONTINUE;
        });
    }
}