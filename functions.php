<?php

if (!function_exists("waitForRead")) {
    function waitForRead($stream)
    {
        return \Ant\Coroutine\SysCall::waitForRead($stream);
    }
}

if (!function_exists("waitForWrite")) {
    function waitForWrite($stream)
    {
        return \Ant\Coroutine\SysCall::waitForWrite($stream);
    }
}

if (!function_exists("killed")) {
    function killed()
    {
        return \Ant\Coroutine\SysCall::killed();
    }
}