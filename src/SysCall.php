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
}