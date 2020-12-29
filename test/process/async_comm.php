<?php
/**
 * 进程间管道异步通信
 */
use Swoole\Process;

$main_process = new Process(function (Process $child_process) {
    swoole_event_add($child_process->pipe, function ($pipe) use ($child_process){
        $cmd = $child_process->read();
        ob_start();
        echo "接收到主进程输入的命令：{$cmd}\n";    //一定要在ob_start()后执行echo，才能继续执行后面的代码，否则echo后就直接返回内容
        passthru($cmd);
        $ret = ob_get_clean();
        $ret .= "\n 子进程pid：{$child_process->pid}\n";
        $child_process->write($ret);
        $child_process->exit(0);
    });
    $child_process->write("添加完管道事件后，直接输出，非阻塞");
}, true, true);
$main_process->start();
sleep(2);
$main_process->write('php -v');
$msg = $main_process->read();
echo "主进程接收到子进程返回结果：\n{$msg}";
$msg = $main_process->read();   //每次在子进程write，都要相应在主进程read，才能读取到write那次的内容，不会一次读取完
echo "主进程接收到子进程返回结果：\n{$msg}";
