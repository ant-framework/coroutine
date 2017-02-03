# Co stack
因为PHP7以下不支持协程堆栈,所以用原生PHP实现了一遍协程堆栈

### 演示
```php
include "vendor/autoload.php";

function foo()
{
    yield 1;
    yield 2;
    yield 3;
    yield 4;
    yield 5;

    try{
        yield bar();
    }catch(\Exception $e) {
        yield $e->getMessage();
    }

    $stack = function(){
        yield 11;
        yield 12;
        yield 13;
        yield 14;
    };

    yield $stack();
    yield 15;
}

function bar()
{
    yield 6;
    yield 7;
    yield 8;
    yield 9;
    throw new Exception(10);
}

$task = new \Ant\Coroutine\Task(foo());

foreach ($task->each() as $key => $value) {
    echo $value,PHP_EOL;
}
```