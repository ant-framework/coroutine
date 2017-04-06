<?php
namespace Ant\Coroutine;

use SplStack;

/**
 * Todo 避免重返入栈
 * 协程调度器
 *
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
        // 加入栈
        $this->stack->push($task->getCoroutine());
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
     * 捕获异常
     *
     * @param \Exception $exception
     * @throws \Exception
     */
    public function tryCatch(\Exception $exception)
    {
        // 如果已是最后一层栈时,不去再去捕获异常
        if ($this->stack->isEmpty()) {
            throw $exception;
        }

        try {
            $coroutine = $this->stack->pop();
            $coroutine->throw($exception);
            $this->task->setCoroutine($coroutine);
        } catch (\Exception $e) {
            $this->tryCatch($e);
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
            if (!$this->isIterable($yieldValue)) {
                return $yieldValue;
            }

            $yieldValue = $this->convertToGenerator($yieldValue);
        }
        // 入栈
        $this->stack->push($this->task->getCoroutine());
        $this->task->setCoroutine($yieldValue);
        return $this->handleYieldValue($yieldValue);
    }

    /**
     * 是否时可迭代类型
     *
     * @param $value
     * @return bool
     */
    protected function isIterable($value)
    {
        // PHP7.1加入Iterable类型(可迭代类型)
        return is_array($value) || $value instanceof \Iterator;
    }

    /**
     * 转化为协程函数
     *
     * @param $yieldValue
     * @return \Generator
     */
    protected function convertToGenerator($yieldValue)
    {
        // 因为是,数组,迭代器,不会处理重入协程的值
        foreach ($yieldValue as $value) {
            yield $value;
        }
    }
}