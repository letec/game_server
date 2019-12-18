<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class GameAction extends BaseAction
{

    private $user;

    private $gameClass;

    public function action($fd, $data, $user)
    {
        $this->user = $user;
        $userStatus = $this->redis->get("USER_STATUS_{$user->id}");
        $userStatus = is_string($userStatus) ? json_decode($userStatus, TRUE) : [];

        if ( ! isset($userStatus['ROOM']) == '')
        {
            return ['result'=>FALSE, 'message'=>'您不在一个对局中!', 'data'=>NULL];
        }

        $table = $his->redis->get($userStatus['ROOM']);
        $table = json_decode((string) $table, TRUE);
        if ( ! isset($table['USERS']))
        {
            $this->redis->del("USER_STATUS_{$user->id}");
            return ['result'=>FALSE, 'message'=>'参数错误!', 'data'=>NULL];
        }

        $className = ucfirst($table['gameCode']);
        $this->gameClass = new $className();

        switch ($data['data']['GAME_ACTION']) 
        {
            case 'READY':
                return $this->gameClass->ready($fd, $data);
            case 'MOVE':
                return $this->gameClass->move($fd, $data);
            default:
                break;
        }
    }


}