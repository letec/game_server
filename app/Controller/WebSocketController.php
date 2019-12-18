<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use App\Game\UserAction;
use App\Game\GameAction;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{

    protected $redis;

    protected $container;

    protected $UserAction;
    protected $GameAction;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
        $this->UserAction = new UserAction();
        $this->GameAction = new GameAction();
    }

    private function checkOnline($data, $fd)
    {
        $oid = isset($data['oid']) ? $data['oid'] : '';
        $user = $this->redis->get('ONLINE_' . $oid);
        if ( ! $user)
        {
            $this->redis->del('ONLINE_' . $oid);
            return FALSE;
        }
        $this->redis->expire('ONLINE_' . $oid, 15*60);
        $fdUser = $this->redis->get("SWOOLE_FD_{$fd}");
        if ( ! $fdUser)
        {
            $this->redis->set("SWOOLE_FD_{$fd}", $user);
        }
        $this->redis->expire("SWOOLE_FD_{$fd}", 15*60);
        return json_decode($user);
    }

    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $data = json_decode($frame->data, TRUE);
        $user = $this->checkOnline($data, $frame->fd);

        if ( ! $user)
        {
            $server->push($frame->fd, json_encode(['result'=>FALSE, 'message'=>'OFFLINE', 'data'=>['ACTION'=>'OFFLINE']]));
            $server->close($frame->fd);
            return;
        }
        $action = isset($data['action']) ? $data['action'] : '';
        $result = NULL;
        switch ($action) 
        {
            case 'SEATDOWN':
                $result = $this->UserAction->action($frame->fd, $data, $user);
                break;
            case 'GAME_ACTION':
                $result = $this->GameAction->action($frame->fd, $data, $user);
                break;
            default:
                $server->push($frame->fd, json_encode(['result'=>FALSE, 'message'=>"WRONG_ACTION: {$action}", 'data'=>['ACTION'=>'WRONG_ACTION']]));
                return;
        }
        if (isset($result['fds']))
        {
            foreach ($result['fds'] as $fd)
            {
                $server->push($fd, json_encode($result['data']));
            }
        }
        else if ($result !== NULL)
        {
            $server->push($frame->fd, json_encode($result));
        }
        return;
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        $result = $this->UserAction->cleanUserStatus($fd);
        foreach ($result['fds'] as $fd)
        {
            $server->push($fd, json_encode($result['data']));
        }
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $server->push($request->fd, json_encode(['result'=>TRUE, 'message'=>'CONNECTED', 'data'=>['ACTION'=>'CONNECTED']]));
    }

}