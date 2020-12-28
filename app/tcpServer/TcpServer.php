<?php
include "TcpServerService.php";
use swoole\TcpServerService;    //use只是表示使用这个类，但是实际上并没有引入。在框架中可以自动引入，但是原生PHP情况下，需要手动引入文件。

class TcpServer extends TcpServerService
{
    public function onConnect($conn)
    {
        echo "连接成功 -- 客户端： " . stream_socket_get_name($conn, true) . "\n";
    }

    public function onMessage($conn, $msg)
    {
        echo "接收信息 --" . $msg . "\n";
        fwrite($conn, "服务器接收到消息了： " . $msg . "\n");
    }

    public function onClose($conn)
    {
        echo "关闭连接 -- 客户端：" . stream_socket_get_name($conn, true) . "\n";
    }
}
