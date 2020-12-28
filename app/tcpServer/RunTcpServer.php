<?php
/**
 * 直接cli执行该文件，启动多进程tcp服务器
 */
require "TcpServer.php";

$server = new TcpServer();
$server->run();
