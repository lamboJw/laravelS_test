<?php


namespace App\Services;


use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface
{
    private $table;

    public function __construct()
    {
        $this->table = app('swoole')->websocketTable;
    }

    public function onOpen(Server $server, Request $request)
    {
        $this->table->set('fd' . $request->fd, ['fd' => $request->fd]);
        $server->push($request->fd, $this->output(1, '服务端连接成功'));
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $data = json_decode($frame->data, true);
        if ($data['event'] == 'msg') {
            $this->broadcast($server, $frame->fd, $data['msg']);
        } elseif ($data['event'] == 'init') {
            $this->table->set('fd' . $frame->fd, ['nickname' => $data['msg']]);
            $this->broadcast($server, $frame->fd, '进入聊天室');
        } elseif ($data['event'] == 'reconnect'){
            $this->table->set('fd' . $frame->fd, ['fd' => $frame->fd, 'nickname' => $data['msg']]);
            $this->broadcast($server, $frame->fd, '重新连接');
        } elseif ($data['event'] == 'heartbeat'){
            $this->output();
        }
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $this->broadcast($server, $fd, '断开连接');
        $this->table->del('fd' . $fd);
    }

    private function output($ret = 1, $msg = 'success', $data = [])
    {
        return json_encode(compact('ret', 'msg', 'data'));
    }

    private function msg($nickname, $msg)
    {
        return $this->output(1, 'success', ['nickname' => $nickname, 'message' => $msg]);
    }

    private function broadcast($server, $self_fd, $msg){
        $nickname = $this->table->get('fd' . $self_fd, 'nickname');
        foreach ($this->table as $key => $value) {
            if ($server->exist($value['fd']) && $value['fd'] != $self_fd) {
                $server->push($value['fd'], $this->msg($nickname, $msg));
            }
        }
        $server->push($self_fd, $this->msg('自己', $msg));
    }
}
