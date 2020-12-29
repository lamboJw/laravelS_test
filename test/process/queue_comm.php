<?php
/**
 * 进程间使用消息队列通信
 */
use Swoole\Process;

$main_process = new Process(function (Process $child_process) {
    $cmd = $child_process->pop();
    echo "接收到主进程输入的命令：{$cmd}\n";
    ob_start();
    passthru($cmd);
    $ret = ob_get_clean();
    $ret .= "\n 子进程pid：{$child_process->pid}\n";
    $child_process->push($ret);
    $child_process->exit(0);
}, false, false);
//消息队列通信和管道通信不能共存
//useQueue使用消息队列进行进程间通信（win10子系统没有消息队列，所以不能使用）
//第一个参数为key，一定要是int类型，第二个是通信模式，2为争抢模式，那个子进程先读取到，就先消费，无法指定某个子进程
//消息队列不支持事件循环，因此引入了 \Swoole\Process::IPC_NOWAIT 表示以非阻塞模式进行通信
$main_process->useQueue(1, 2, Process::IPC_NOWAIT);
$main_process->push('php -v');
$msg = $main_process->pop();
echo "主进程接收到子进程返回结果：\n{$msg}";
$main_process->start();
Process::wait();    //要调用这段代码，否则子进程中的 push 或 pop 可能会报错
