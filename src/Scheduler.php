<?php
namespace Ant\Coroutine;

use SplStack;

/**
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
     * 是否是第一次入栈
     *
     * @var bool
     */
    protected $isFirst = true;

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
            // 系统调用
            $signal = call_user_func($yieldValue, $this->task);
            // 默认继续任务
            return Signal::isSignal($signal)
                ? $signal
                : Signal::TASK_CONTINUE;
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
        if ($this->stack->isEmpty()) {
            // 如果栈中的协程函数无法处理异常
            // 就将此异常抛出,交给上级应用程序处理
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

            // 如果是可迭代格式,将其转换为协程函数
            $yieldValue = $this->convertToGenerator($yieldValue);
        }

        if ($this->isFirst) {
            // 第一次堆栈不入栈,避免重复入栈
            $this->isFirst = false;
        } else {
            // 入栈
            $this->stack->push($this->task->getCoroutine());
        }

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
        foreach ($yieldValue as $value) {
            // 因为数组,迭代器,不会处理重入协程的值
            // 所以可以直接将其转换为迭代器
            // 而不需要去处理重入协程函数的值
            yield $value;
        }
    }
}