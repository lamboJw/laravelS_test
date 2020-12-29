<?php
/**
 * 进程间管道同步通信
 */
use Swoole\Process;

$main_process = new Process(function (Process $child_process) {
    $cmd = $child_process->read();
    ob_start();
    echo "接收到主进程输入的命令：{$cmd}\n";    //一定要在ob_start()后执行echo，才能继续执行后面的代码，否则echo后就直接返回内容
    passthru($cmd);
    $ret = ob_get_clean();
    $ret .= "\n 子进程pid：{$child_process->pid}\n";
    $child_process->write($ret);
    $child_process->exit(0);
}, true, true);
$main_process->start();
$main_process->write('php -v');
$msg = $main_process->read();
echo "主进程接收到子进程返回结果：\n{$msg}";
