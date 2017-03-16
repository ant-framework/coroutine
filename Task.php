<?php
namespace Ant\Coroutine;

/**
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
     * Task constructor.
     * @param \Generator $coroutine
     * @param int $taskId
     */
    public function __construct(\Generator $coroutine, $taskId = 0)
    {
        $this->coroutine = $coroutine;
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
        while($this->signal !== Signal::TASK_DONE) {
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
     * 遍历,手动遍历
     *
     * @return \Generator
     */
    public function each()
    {
        while($this->signal !== Signal::TASK_DONE) {
            try{
                $signal = $this->scheduler->schedule();
                if (Signal::isSignal($signal)) {
                    $this->signal = $signal;
                    switch ($this->signal) {
                        case Signal::TASK_CONTINUE:
                            yield $this->coroutine->key() => $this->coroutine->current();
                            $this->reenter();
                            break;
                        case Signal::TASK_SLEEP:
                        case Signal::TASK_KILLED:
                        case Signal::TASK_WAIT:
                            return null;
                            break;
                    }
                }
            } catch (\Exception $e){
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