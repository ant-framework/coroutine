# Co stack
基于ReactPHP的携程堆栈模块,以同步的方式书写异步代码

### 使用ReactPHP异步回调模式

```php
include "vendor/autoload.php";

$loop = \React\EventLoop\Factory::create();
// 监听并绑定8000端口
$socket = stream_socket_server(
    "tcp://0.0.0.0:8000",
    $errorCode,
    $errorMessage,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

// 设置非阻塞
stream_set_blocking($socket, false);

// 等待新连接到来
$loop->addReadStream($socket, function ($stream, $loop) {
    $newStream = @stream_socket_accept($stream);
    stream_set_blocking($newStream, false);
    // 等待新连接可读
    $loop->addReadStream($newStream, function ($newStream, $loop) {
        echo fread($newStream, 8192);
        // 等待50ms后响应
        $loop->addTimer(0.05, function () use ($loop, $newStream) {
            // 等待新连接可写
            $loop->addWriteStream($newStream, function ($newStream, $loop) {
                fwrite($newStream, "HTTP/1.1 200 OK\r\nContent-Length: 11\r\n\r\nHello world");
                // 断开连接
                fclose($newStream);
                $loop->removeStream($newStream);
            });
        });
    });
});

$loop->run();
```
### 使用Coroutine

* 在使用Coroutine的情况下,代码是以同步的方式书写的,但是运行模式是以异步的方式运行,在遇到IO时,程序会避开等待,去执行其他任务,等到IO完成后,切换回之前的上下文

```php
include "vendor/autoload.php";

$loop = Ant\Coroutine\GlobalLoop::get();
// 监听并绑定8000端口
$socket = stream_socket_server(
    "tcp://0.0.0.0:8000",
    $errorCode,
    $errorMessage,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

// 设置非阻塞
stream_set_blocking($socket, false);

$loop->addReadStream($socket, function ($stream) {
    Ant\Coroutine\Task::start(function () use ($stream) {
        $newStream = @stream_socket_accept($stream);
        stream_set_blocking($newStream, false);

        // 切换上下文,当流可读的时候切换回当前上下文
        yield Ant\Coroutine\waitForRead($newStream);
        echo fread($newStream, 8192);

        // 沉睡50ms后再响应
        yield Ant\Coroutine\sleep(0.05);

        // 切换上下文,当流可写的时候切换回当前上下文
        yield Ant\Coroutine\waitForWrite($newStream);
        fwrite($newStream, "HTTP/1.1 200 OK\r\nContent-Length: 11\r\n\r\nHello world");

        // 断开连接
        fclose($newStream);
    });
});
```