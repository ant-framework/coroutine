<?php
namespace Ant\Coroutine;


/**
 * Class IOCall
 * @package Ant\Coroutine
 */
class IOCall
{
    /**
     * @param $stream
     * @param $buffer
     * @return SysCall
     */
    public static function write($stream, $buffer)
    {
        return new SysCall(function (Task $task) use ($stream, $buffer) {
            GlobalLoop::addWriteStream($stream, function ($stream) use ($task, $buffer) {
                $sent = @fwrite($stream, $buffer);

                if ($sent === 0 || $sent === false) {
                    GlobalLoop::removeWriteStream($stream);
                    $error = static::createLastError("Send failed");
                    $task->throwException($error);
                    return;
                }

                GlobalLoop::removeWriteStream($stream);
                $task->resume($sent);
            });

            return Signal::TASK_WAIT;
        });
    }

    /**
     * @param $stream
     * @param $length
     * @return SysCall
     */
    public static function read($stream, $length)
    {
        return new SysCall(function (Task $task) use ($stream, $length) {
            GlobalLoop::addReadStream($stream, function ($stream) use ($task, $length) {
                $chunk = fread($stream, $length);

                if ($chunk === '' || $chunk === false) {
                    GlobalLoop::removeReadStream($stream);
                    $error = static::createLastError("Read failed");
                    $task->throwException($error);
                    return;
                }

                GlobalLoop::removeReadStream($stream);
                $task->resume($chunk);
            });

            return Signal::TASK_WAIT;
        });
    }

    /**
     * @param string $defaultMessage
     * @return \ErrorException|\RuntimeException
     */
    protected static function createLastError($defaultMessage = '')
    {
        $error = error_get_last();

        if ($error === null) {
            return new \RuntimeException($defaultMessage);
        }

        return new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );
    }
}