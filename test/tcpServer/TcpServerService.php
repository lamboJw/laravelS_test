<?php
namespace swoole;

abstract class TcpServerService
{
    //最大子进程数
    private int $max_process = 3;
    //子进程id数组
    private array $pids = [];
    //网络套接字
    private $socket;
    //主进程ID
    private int $mpid;


    //主进程业务
    public function run()
    {

        $process = new Process(function () {

            $this->mpid = posix_getpid();
            echo date("Y-m-d") . "  启动主进程，进程ID：{$this->mpid}\n";
            //stream_socket_server创建一个指定地址的套接字，errno和errstr表明系统级错误
            $this->socket = stream_socket_server('tcp://127.0.0.1:9503', $errno, $errstr);
            if (!$this->socket) {
                exit("创建套接字错误：{$errno} -> {$errstr}");
            }
            //启动子进程
            for ($i = 0; $i < $this->max_process; $i++) {
                $this->startWorkerProcess();
            }

            echo '等待子进程启动...';

            while (true) {
                $ret = Process::wait(false);    //获取已结束子进程信息
                if ($ret) {
                    echo date("Y-m-d") . "  子进程{$ret['pid']}已结束，启动新子进程";
                    $key = array_search($ret['pid'], $this->pids);
                    if ($key) {
                        unset($this->pids[$key]);
                    }
                    $this->startWorkerProcess();
                    print_r($this->pids);
                }
                sleep(1);
            }
            //主进程非阻塞的情况下，需要注册信号SIGCHLD对已结束子进程进行回收
            /*Process::signal(SIGCHLD, function ($sig) {
                //Process::wait回收已结束子进程，blocking指定是否阻塞等待。此处必须为false
                while ($ret = Process::wait(false)) {
                    if ($ret) {
                        echo date("Y-m-d") . "  子进程{$ret['pid']}已结束，启动新子进程";
                        $key = array_search($ret['pid'], $this->pids);
                        if ($key) {
                            unset($this->pids[$key]);
                        }
                    }
                }
            });*/
        }, false, false);
        //以守护进程执行
        Process::daemon();
        //start后定义的变量，子进程获取不了
        $process->start();
    }

    /**
     * 启动子进程
     */
    private function startWorkerProcess()
    {
        $process = new Process(function (Process $worder) {
            $this->acceptClient($worder);
        }, false, false);
        //启动子进程并获得进程ID
        $pid = $process->start();
        $this->pids[] = $pid;
        echo "子进程 {$pid} 启动\n";
    }

    /**
     * 等待客户端连接并处理
     * @param Process $worker
     */
    private function acceptClient(Process &$worker)
    {
        while (true) {
            $conn = stream_socket_accept($this->socket, -1);
            $this->onConnect($conn);
            //客户端传输的所有消息
            $full_msg = '';
            //缓冲区中的内容
            $buffer_msg = '';
            while (true) {
                $this->checkMainProcess($worker);
                //读取缓冲区内容
                $buffer_msg = fread($conn, 1024);
                if ($buffer_msg === false || $buffer_msg === '') {
                    //执行关闭进程前操作
                    $this->onClose($conn);
                    //读取消息完，退出当前循环，等待下一个客户端连接
                    break;
                }
                $pos = strpos($buffer_msg, "\n");   //消息完结标志
                if ($pos === false) {     //未读取到完结标志
                    $full_msg .= $buffer_msg;
                } else {  //读取完成
                    $full_msg .= trim(substr($buffer_msg, 0, $pos + 1));
                    $this->onMessage($conn, $full_msg);
                    if ($full_msg == "quit") {
                        echo "客户端发出退出指令\n";
                        fclose($conn);
                        break;
                    }
                    $full_msg = ''; // 清空消息，准备下一次接收
                }
            }
        }
    }

    /**
     * 判断主进程是否已退出，关闭子进程
     * @param Process $worker
     */
    private function checkMainProcess(Process &$worker)
    {
        //Process::kill发送一个信号给指定pid的进程，如果发送成功，则返回true，否则返回false。
        //signal_no默认是“SIGTERM”关闭进程，传“0”只检查进程是否存活，不做其他操作
        if (!Process::kill($this->mpid, 0)) {
            $worker->exit();
            echo "主进程已退出，退出子进程{$worker['pid']}\n";
        }
    }


    /**
     * 连接时执行逻辑
     * @param resource $conn 客户端连接
     * @return mixed
     */
    abstract public function onConnect($conn);

    /**
     * 接收消息执行逻辑
     * @param $conn
     * @param $msg
     * @return mixed
     */
    abstract public function onMessage($conn, $msg);

    /**
     * 断开连接执行逻辑
     * @param $conn
     * @return mixed
     */
    abstract public function onClose($conn);

}
