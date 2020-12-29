<?php
use Swoole\Process\Pool;

$workerNum = 5;
//创建5个进程的连接池
$pool = new Pool($workerNum);
$pool->on('WorkerStart', function ($pool, $workerID){
    echo "子进程#{$workerID}启动\n";
    $redis = new Redis();
    $redis->pconnect('127.0.0.1');
    $redis->select(1);
    $key = 'pool_test';
    while(true){
        $msg = $redis->brPop($key, 2);  //brPop:阻塞右弹出，当队列中没有内容时，会阻塞timeout秒，直到队列中有内容时弹出内容
        if($msg == null){   //一直等待到有内容
            continue;
        }
        echo "子进程#{$workerID}获取到队列信息：{$msg[1]}\n";
    }
});
$pool->on('WorkerStop', function ($pool, $workerID){
    echo "子进程#{$workerID}关闭\n";
});
$pool->start();
