<?php


namespace App\Services;


use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService  implements WebSocketHandlerInterface
{
    public function __construct()
    {

    }

    public function onOpen(Server $server, Request $request)
    {
        Log::info("客户端{$request->fd}连接成功");
        $server->push($request->fd, json_encode(['data'=>'服务端连接成功']));
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $arr = ['data'=>$frame->data, 'opcode'=>$frame->opcode, 'finish'=>$frame->finish];
        $server->push($frame->fd, json_encode($arr));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        Log::info("客户端{$fd}断开连接");
    }
}
