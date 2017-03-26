<?php
namespace Ant\Coroutine;

use React\EventLoop\Timer\Timer;

/**
 * @param $interval
 * @param callable $callback
 * @return \React\EventLoop\Timer\TimerInterface
 */
function addTimer($interval, callable $callback)
{
    return GlobalLoop::get()->addTimer($interval, $callback);
}

/**
 * @param $interval
 * @param callable $callback
 * @return \React\EventLoop\Timer\TimerInterface
 */
function addPeriodicTimer($interval, callable $callback)
{
    return GlobalLoop::get()->addPeriodicTimer($interval, $callback);
}

/**
 * @param Timer $timer
 * @return bool
 */
function isTimerActive(Timer $timer)
{
    return GlobalLoop::get()->isTimerActive($timer);
}

/**
 * @param Timer $timer
 */
function cancelTimer(Timer $timer)
{
    GlobalLoop::get()->cancelTimer($timer);
}

/**
 * @param callable $callback
 */
function futureTick(callable $callback)
{
    GlobalLoop::get()->futureTick($callback);
}

/**
 * @param callable $callback
 */
function nextTick(callable $callback)
{
    GlobalLoop::get()->nextTick($callback);
}

/**
 * @param $stream
 * @param callable $listener
 */
function addReadStream($stream, callable $listener)
{
    GlobalLoop::get()->addReadStream($stream, $listener);
}

/**
 * @param $stream
 * @param callable $listener
 */
function addWriteStream($stream, callable $listener)
{
    GlobalLoop::get()->addWriteStream($stream, $listener);
}

/**
 * @param $stream
 */
function removeReadStream($stream)
{
    GlobalLoop::get()->removeReadStream($stream);
}

/**
 * @param $stream
 */
function removeWriteStream($stream)
{
    GlobalLoop::get()->removeWriteStream($stream);
}

/**
 * @param $stream
 */
function removeStream($stream)
{
    GlobalLoop::get()->removeStream($stream);
}