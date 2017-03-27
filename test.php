<?php
include "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

// 监听8000端口
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
// 设置非阻塞
stream_set_blocking($socket, false);

$loop->addReadStream($socket, function ($stream, React\EventLoop\LoopInterface $loop) {
    $task = new \Ant\Coroutine\Task($loop, function () use ($stream, $loop) {
        $newSocket = @stream_socket_accept($stream);
        stream_set_blocking($newSocket, false);

        // todo fix bug,定时的时间小于当前时间,于是立刻触发定时器回调,原因未知
        echo microtime(true),PHP_EOL;
        // 沉睡1秒钟
        yield \Ant\Coroutine\SysCall::sleep(1);
        echo microtime(true);

        // 切换上下文,当流可读的时候切换回当前上下文
        yield waitForRead($newSocket);

        fwrite($newSocket, "HTTP/1.0 200 OK\r\nContent-Length: 11\r\n\r\nHello world\n");
        fclose($newSocket);
        $loop->removeStream($newSocket);
    });

    $task->run();
});

//$loop->addPeriodicTimer(5, function (\React\EventLoop\Timer\Timer $timer) {
//    $memory = memory_get_usage() / 1024;
//    $formatted = number_format($memory, 3).'K';
//    echo "Current memory usage: {$formatted}\n";
//});

$loop->run();
