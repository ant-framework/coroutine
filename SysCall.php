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
        return call_user_func_array($this->callback,func_get_args());
    }

    public static function killTask()
    {
        return new SysCall(function (Task $task) {
            return Signal::TASK_KILLED;
        });
    }

    public static function getTaskId()
    {
        return new SysCall(function (Task $task) {
            $task->setReenterValue($task->getTaskId());
            return Signal::TASK_CONTINUE;
        });
    }
}