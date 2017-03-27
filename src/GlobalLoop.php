<?php
namespace Ant\Coroutine;

use React\EventLoop\Factory;
use React\EventLoop\Timer\Timer;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * 此类是参考ReactPhp的全局类
 * 因为ReactPhp全局类尚未加入稳定版本,所以在此单独封装
 * @see https://github.com/reactphp/event-loop/pull/82
 *
 * Class GlobalLoop
 * @package Ant\Coroutine
 *
 * @method TimerInterface addTimer($interval, callable $callback)
 * @method TimerInterface addPeriodicTimer($interval, callable $callback)
 * @method bool isTimerActive(Timer $timer)
 * @method cancelTimer(Timer $timer)
 * @method futureTick(callable $callback)
 * @method nextTick(callable $callback)
 * @method addReadStream($stream, callable $listener)
 * @method addWriteStream($stream, callable $listener)
 * @method removeStream($stream)
 * @method removeReadStream($stream, callable $listener)
 * @method removeWriteStream($stream, callable $listener)
 */
final class GlobalLoop
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected static $loop;

    /**
     * 生成EventLoop的回调
     *
     * @var callable
     */
    protected static $factory;

    /**
     * 是否启动
     *
     * @var bool
     */
    protected static $didRun = false;

    /**
     * 是否在脚本结束时自动启动loop
     *
     * @var bool
     */
    protected static $disableRunOnShutdown = false;

    /**
     * 设置EventLoop工厂
     *
     * @param callable $factory
     */
    public static function setFactory(callable $factory)
    {
        if (static::$didRun) {
            throw new \RuntimeException(
                'Setting a factory after the global loop has been started is not allowed'
            );
        }

        static::$factory = $factory;
    }

    /**
     * 禁用自动启动功能
     * 在脚本结束时,如果没有启动loop,则在shutdown回调中启动
     */
    public static function disableRunOnShutdown()
    {
        self::$disableRunOnShutdown = true;
    }

    /**
     * 获取全局Loop
     *
     * @return LoopInterface
     */
    public static function get()
    {
        if (!static::$loop) {
            register_shutdown_function(function () {
                // 禁用自动启动,已经启动,或者loop不存在的情况下,结束脚本
                if (self::$disableRunOnShutdown || self::$didRun || !self::$loop) {
                    return;
                }

                // 启动loop
                static::$loop->run();
            });

            static::setLoop(static::create());

            static::$loop->futureTick(function () {
                // 将Loop状态改为已启动
                static::$didRun = true;
            });
        }

        return static::$loop;
    }

    /**
     * 设置全局Loop
     *
     * @param LoopInterface $loop
     */
    public static function setLoop(LoopInterface $loop)
    {
        static::$loop = $loop;
    }

    /**
     * 重置全局容器
     */
    public static function reset()
    {
        static::$loop = null;
        static::$didRun = false;
    }

    /**
     * 通过全局工厂获取Loop
     *
     * @return LoopInterface
     */
    public static function create()
    {
        if (static::$factory) {
            return static::createFromCustomFactory(static::$factory);
        }

        return Factory::create();
    }

    /**
     * 通过自定义工厂获取loop
     *
     * @param callable $factory
     * @return LoopInterface
     */
    protected static function createFromCustomFactory(callable $factory)
    {
        $loop = call_user_func($factory);

        if (!$loop instanceof LoopInterface) {
            throw new \LogicException(
                sprintf(
                    'The GlobalLoop factory must return an instance of LoopInterface but returned %s.',
                    is_object($loop) ? get_class($loop) : gettype($loop)
                )
            );
        }

        return $loop;
    }

    /**
     * @param $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments = [])
    {
        $loop = static::get();

        if (!method_exists($loop, $name)) {
            throw new \BadMethodCallException("[{$name}] method not exists!");
        }

        return call_user_func_array([$loop, $name], $arguments);
    }
}