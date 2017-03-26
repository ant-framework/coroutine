<?php
namespace Ant\Coroutine;

use React\EventLoop\LoopInterface;

/**
 * Todo: 单元测试
 *
 * 协程堆栈
 *
 * Class Task
 * @package Ant\Coroutine
 */
class Task
{
    /**
     * 任务ID
     *
     * @var int
     */
    protected $taskId = 0;

    /**
     * 当前协程任务
     *
     * @var \Generator
     */
    protected $coroutine;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * 重入参数
     *
     * @var mixed
     */
    protected $reenterValue;

    /**
     * 协程堆栈调度器
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * 当前信号
     *
     * @var int
     */
    protected $signal = 0;

    /**
     * @param $coroutine
     * @param int $taskId
     */
    public static function start($coroutine, $taskId = 0)
    {
        (new static($coroutine, GlobalLoop::get(), $taskId))->run();
    }

    /**
     * Task constructor.
     * @param $coroutine
     * @param int $taskId
     */
    public function __construct($coroutine, LoopInterface $loop, $taskId = 0)
    {
        if (is_callable($coroutine)) {
            $coroutine = call_user_func($coroutine);
        }

        if (!$coroutine instanceof \Generator) {
            throw new \RuntimeException('$coroutine must be Generator object');
        }

        $this->coroutine = $coroutine;
        $this->loop = $loop;
        $this->taskId = $taskId;
        $this->scheduler = new Scheduler($this);
    }

    /**
     * 启动程序,自动迭代
     *
     * @return null
     */
    public function run()
    {
        while ($this->signal !== Signal::TASK_DONE) {
            try {
                $signal = $this->scheduler->schedule();
                if (Signal::isSignal($signal)) {
                    $this->signal = $signal;
                    switch ($this->signal) {
                        case Signal::TASK_CONTINUE:
                            $this->reenter();
                            break;
                        case Signal::TASK_SLEEP:
                        case Signal::TASK_KILLED:
                        case Signal::TASK_WAIT:
                            return null;
                            break;
                    }
                }
            } catch (\Exception $e) {
                $this->scheduler->throwException($e);
            }
        }
    }

    /**
     * 重入协程函数
     */
    public function reenter()
    {
        $this->coroutine->send($this->reenterValue);
        $this->reenterValue = null;
    }

    /**
     * 设置重入协程函数的参数
     *
     * @param null $value
     */
    public function setReenterValue($value = null)
    {
        $this->reenterValue = $value;
    }

    /**
     * @return mixed
     */
    public function getReenterValue()
    {
        return $this->reenterValue;
    }

    /**
     * @param \Exception $exception
     */
    public function sendException(\Exception $exception)
    {
        $this->coroutine->throw($exception);
    }

    /**
     * 获取协程任务
     *
     * @return \Generator
     */
    public function getCoroutine()
    {
        return $this->coroutine;
    }

    /**
     * 设置协程任务
     *
     * @param \Generator $coroutine
     */
    public function setCoroutine(\Generator $coroutine)
    {
        $this->coroutine = $coroutine;
    }

    /**
     * 获取当前任务ID
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * 获取当前信号
     *
     * @return int
     */
    public function getSignal()
    {
        return $this->signal;
    }

    /**
     * 设置当前信号
     *
     * @param $signal
     */
    public function setSignal($signal)
    {
        $this->signal = $signal;
    }
}