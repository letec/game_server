<?php

declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class UserAction extends BaseAction
{

    private $user;


    public function __construct()
    {
        parent::__construct();
    }

    public function action($fd, $data, $user)
    {
        $this->user = $user;
        switch ($data['action']) 
        {
            case 'SEATDOWN':
                return $this->seatDown($fd, $data);
            default:
                break;
        }
    }

    private function seatDown($fd, $data)
    {
        $user = $this->user;
        $gameCode = $data['gameCode'] ?? '';
        $tableID = $data['tableID'] ?? '';
        $seat = $data['seat'] ?? '';
        $allGame = config('game.allGame');

        $tableKey = "HALL_{$gameCode}_TABLE_{$tableID}"; 
        $table = $this->redis->get($tableKey);

        $userStatus = $this->redis->get("USER_STATUS_{$user->id}");
        $userStatus = is_string($userStatus) ? json_decode($userStatus, TRUE) : [];

        $response = ['ACTION'=>'SEATDOWN'];

        if ( ! isset($userStatus['SEAT']))
        {
            $userStatus = ['ROOM'=>$tableKey, 'SEAT'=>$seat];
        }
        else if ($userStatus['SEAT'] != '' && $userStatus['ROOM'] != '')
        {
            return ['result'=>FALSE, 'message'=>'您还在一个位置上!', 'data'=>$response];
        }
        
        if ( ! isset($allGame[$gameCode]))
        {
            return ['result'=>FALSE, 'message'=>'游戏代号错误!', 'data'=>$response];
        }

        if ( ! $table)
        {
            return ['result'=>FALSE, 'message'=>'房间ID错误!', 'data'=>$response];
        }

        $table = json_decode($table, TRUE);

        if ( ! isset($table['USERS'][$seat-1]['userId']))
        {
            return ['result'=>FALSE, 'message'=>'座位错误!', 'data'=>$response];
        }

        if ($table['USERS'][$seat-1]['userId'] != '')
        {
            return ['result'=>FALSE, 'message'=>'已经有人坐下了!', 'data'=>$response];
        }

        $table['USERS'][$seat-1]['userId'] = $user->id;
        $table['USERS'][$seat-1]['fd'] = $fd;
        $table['USERS'][$seat-1]['username'] = $user->UserName;
        $table['USERS'][$seat-1]['status'] = 0;

        $this->redis->setex($tableKey, 24*60*60*30, json_encode($table));

        $userStatus['ROOM'] = $tableKey;
        $userStatus['SEAT'] = $seat;

        $this->redis->setex("USER_STATUS_{$user->id}", 15*60, json_encode($userStatus));

        $fds = [];
        if (is_array($table['USERS']))
        {
            foreach ($table['USERS'] as $k => $v)
            {
                if ($v['fd'] != '') $fds[] = $v['fd'];
                $table['USERS'][$k]['avatar'] = $this->UserModel->getAvataById($v['userId']) ?? '2019070315.jpg';
            }
        }
        $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'TABLE_UPDATE', 'table'=>$table]];
        return ['fds'=>$fds, 'data'=>$data];
    }

    public function cleanUserStatus($fd)
    {
        $user = $this->redis->get("SWOOLE_FD_{$fd}");
        $user = $user ? json_decode($user) : FALSE;
        $table = NULL;
        $win = '';
        $leave = $user->UserName;
        if (isset($user->id))
        {
            $userStatus = $this->redis->get("USER_STATUS_{$user->id}");
            $userStatus = $userStatus ? json_decode($userStatus, TRUE) : ['ROOM'=>'', 'SEAT'=>''];
            if ($userStatus['SEAT'] != '' && $userStatus['ROOM'] != '')
            {
                $tableKey = $userStatus['ROOM']; 
                $table = json_decode($this->redis->get($tableKey), TRUE);
                foreach ($table['USERS'] as $k => $v) 
                {
                    if ($v['username'] != $user->id)
                    {
                        $win = $v['username'];
                    }
                    if ($userStatus['SEAT']-1 == $k)
                    {
                        foreach ($table['USERS'][$userStatus['SEAT']-1] as $x => $y)
                        {
                            $table['USERS'][$k][$x] = '';
                        }
                    }
                    else
                    {
                        $table['USERS'][$k]['userId'] != '' && $table['USERS'][$k]['status'] = 0;
                    }
                }
                if (isset($table['USERS'][$userStatus['SEAT']-1]))
                {
                    
                    $table['STATUS'] = 0;
                    $this->redis->setex($tableKey, 24*60*60*30, json_encode($table));
                }
            }
            $this->redis->del("USER_STATUS_{$user->id}");
        }
        $this->redis->del("SWOOLE_FD_{$fd}");

        $fds = [];
        if (isset($table['USERS']))
        {
            foreach ($table['USERS'] as $k => $v)
            {
                if ($v['fd'] != '') $fds[] = $v['fd'];
                $table['USERS'][$k]['avatar'] = $this->UserModel->getAvataById($v['userId']) ?? '2019070315.jpg';
            }
        }
        echo "SWOOLE_FD_{$fd} LEFT \n";
        $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'PLAYER_LEAVE', 'WIN'=>$win, 'LEAVE'=>$leave, 'table'=>$table]];
        return ['fds'=>$fds, 'data'=>$data];
    }



}