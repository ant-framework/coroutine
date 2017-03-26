# Co stack
基于ReactPHP的携程堆栈模块,以同步的方式书写异步代码

### 演示
```php
include "vendor/autoload.php";

$loop = \Ant\Coroutine\GlobalLoop::get();

// 监听并绑定8000端口
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
// 设置非阻塞
stream_set_blocking($socket, false);

$loop->addReadStream($socket, function ($stream, $loop) {
    \Ant\Coroutine\Task::start(function () use ($stream, $loop) {
        $clientSocket = @stream_socket_accept($stream);
        stream_set_blocking($clientSocket, false);

        // 切换上下文,当流可读的时候切换回当前上下文
        yield \Ant\Coroutine\SysCall::waitForRead($clientSocket);

        echo fread($clientSocket, 8192);

        // 切换上下文,当流可写的时候切换回当前上下文
        yield \Ant\Coroutine\SysCall::waitForWrite($clientSocket);

        fwrite($clientSocket, "HTTP/1.0 200 OK\r\nContent-Length: 11\r\n\r\nHello world");

        // 断开连接
        fclose($clientSocket);
        \Ant\Coroutine\GlobalLoop::get()->removeStream($clientSocket);
    });
});

$loop->addPeriodicTimer(5, function () {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});
```