<?php
namespace Ant\Coroutine;

/**
 * Todo: 单元测试
 * Todo: 添加Task id属性,通过ID控制不同任务的流程
 *
 * 协程堆栈
 *
 * Class Task
 * @package Ant\Coroutine
 */
class Task
{
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
     * 尝试启动协程
     *
     * @param $coroutine
     * @param $args
     */
    public static function createFrom($coroutine, $args = [])
    {
        if (is_callable($coroutine)) {
            $coroutine = call_user_func_array($coroutine, $args);
        }

        if ($coroutine instanceof \Generator) {
            (new static($coroutine))->run();
        }
    }

    /**
     * Task constructor.
     * @param \Generator $coroutine
     */
    public function __construct(\Generator $coroutine)
    {
        $this->coroutine = $coroutine;
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
                $this->signal = $this->scheduler->schedule();

                // 根据不同信号进行处理
                switch ($this->signal) {
                    case Signal::TASK_CONTINUE:
                        $this->reenter();
                        break;
                    case Signal::TASK_SLEEP:
                    case Signal::TASK_WAIT:
                        // 暂停任务,进入等待状态
                        return;
                }
            } catch (\Exception $exception) {
                $this->scheduler->tryCatch($exception);
            }
        }
    }

    /**
     * 恢复任务
     *
     * @param null $value
     */
    public function resume($value = null)
    {
        // 如果任务仍在运行,无法恢复任务
        if (!in_array($this->signal, [Signal::TASK_CONTINUE, Signal::TASK_RUNNING])) {
            // 继续Task,恢复栈结构
            $this->setReenterValue($value);
            $this->reenter();
            $this->run();
        }
    }

    /**
     * 重入协程函数
     */
    public function reenter()
    {
        $this->coroutine->send($this->getReenterValue());
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
     * @throws \Exception
     */
    public function throwException(\Exception $exception)
    {
        $this->scheduler->tryCatch($exception);
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