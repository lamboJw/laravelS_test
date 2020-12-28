<?php
/**
 * 启动tcp客户端
 */
namespace Swoole;

go(function (){
    $client = new Coroutine\Client(SWOOLE_SOCK_TCP);
    if ($client->connect("127.0.0.1", 9503, 0.5)) {
        // 建立连接后发送内容
        $client->send("客户端发送消息\n");
        // 打印接收到的消息
        echo $client->recv();
        // 关闭连接
        $client->close();
    } else {
        echo "connect failed.";
    }
});
