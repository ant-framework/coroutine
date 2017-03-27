<?php
namespace Ant\Coroutine;

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
     * @return int
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
        return new SysCall(function (Task $task) {
            return Signal::TASK_KILLED;
        });
    }

    /**
     * 获取当前id
     *
     * @return SysCall
     */
    public static function getTaskId()
    {
        return new SysCall(function (Task $task) {
            $task->setReenterValue($task->getTaskId());
            return Signal::TASK_CONTINUE;
        });
    }
}