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
                fwrite($newStream, "HTTP/1.0 200 OK\r\nContent-Length: 11\r\n\r\nHello world");
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

// 监听并绑定8000端口
$serverSocket = stream_socket_server(
    "tcp://0.0.0.0:8000",
    $errorCode,
    $errorMessage,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

// 设置非阻塞
stream_set_blocking($serverSocket, false);

Ant\Coroutine\GlobalLoop::addReadStream($serverSocket, function ($stream) {
    Ant\Coroutine\Task::start(function () use ($stream) {
        $newStream = @stream_socket_accept($stream);
        stream_set_blocking($newStream, false);

        try {
            // 切换上下文,等到流读完时切换回当前上下文
            echo (yield \Ant\Coroutine\asyncRead($newStream, 8192));

            // 沉睡50ms后
            yield Ant\Coroutine\sleep(0.05);

            // 切换上下文,写入完成后,切换回当前上下文
            yield \Ant\Coroutine\asyncWrite($newStream, "HTTP/1.0 200 OK\r\nContent-Length: 11\r\n\r\nHello world");
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        } finally {
            // 断开连接
            fclose($newStream);
        }
    });
});
```

### Todo
* Fork当前任务
* 协程上下文,参考Koa中间件功能
* 异步客户端 (redis, mysql, http)
* defer,当协程完成时,触发defer(资源回收机制)
* 保存任务上下文,每当触发事件时,clone一个进行触发