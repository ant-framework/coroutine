<?php
namespace Ant\Coroutine;

use SplStack;
use React\SocketClient\ConnectorInterface;

/**
 * Class Scheduler
 * @package Ant\Coroutine
 */
class Scheduler
{
    /**
     * @var Task
     */
    protected $task;

    /**
     * @var SplStack
     */
    protected $stack;

    /**
     * Scheduler constructor.
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->stack = new SplStack();
    }

    /**
     * 任务安排
     *
     * @return int
     */
    public function schedule()
    {
        $coroutine = $this->task->getCoroutine();

        $yieldValue = $this->handleYieldValue($coroutine);

        if ($yieldValue instanceof SysCall) {
            return call_user_func($yieldValue, $this->task);
        }

        if ($coroutine->valid()) {
            // 当前协程还未完成,继续任务
            return Signal::TASK_CONTINUE;
        }

        if (!$this->stack->isEmpty()) {
            // 将协程函数依次出栈
            $this->task->setCoroutine($this->stack->pop());
            $this->task->reenter();
            return Signal::TASK_RUNNING;
        }

        return Signal::TASK_DONE;
    }

    /**
     * 抛出异常
     *
     * @param \Exception $exception
     */
    public function throwException(\Exception $exception)
    {
        if ($this->stack->isEmpty()) {
            $this->task->sendException($exception);
            return;
        }

        try {
            $this->task->setCoroutine($this->stack->pop());
            $this->task->sendException($exception);
        } catch (\Exception $e) {
            $this->throwException($e);
        }
    }

    /**
     * 处理协程函数返回值
     *
     * @param \Generator $coroutine
     * @return mixed
     */
    protected function handleYieldValue(\Generator $coroutine)
    {
        $yieldValue = $coroutine->current();

        if (!$yieldValue instanceof \Generator) {
            return $yieldValue;
        }
        // 入栈
        $this->stack->push($this->task->getCoroutine());
        $this->task->setCoroutine($yieldValue);
        return $this->handleYieldValue($yieldValue);
    }
}