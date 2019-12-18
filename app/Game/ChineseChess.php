<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class ChineseChess extends BaseAction
{

    public function ready($fd, $data, $userStatus, $table)
    {
        if ($table['STATUS'] != 0)
        {
            return ['result'=>FALSE, 'message'=>'游戏已经开始!', 'data'=>NULL];
        }
        $targetStatus = $table['USERS'][$userStatus['SEAT']-1]['status'] == 1 ? 0 : 1;
        $table['USERS'][$userStatus['SEAT']-1]['status'] = $targetStatus;
        $this->redis->setex($userStatus['ROOM'], 24*60*60*30, json_encode($table));
        $fds = [];
        foreach ($table['USERS'] as $v)
        {
            if ($v['fd'] != '') $fds[] = $v['fd'];
        }

        $table = $his->redis->get($userStatus['ROOM']);
        $table = json_decode((string) $table, TRUE);

        foreach ($table['USERS'] as $k => $v)
        {
            $table['USERS'][$k]['avatar'] = $this->UserModel->getAvataById($value['userId']) ?? '2019070315.jpg';
        }

        if ($table['USERS'][0]['status'] == 1 && $table['USERS'][0]['status'] == 1)
        {
            $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'TABLE_UPDATE', 'table'=>$table]];
        }
        else 
        {
            $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'TABLE_UPDATE', 'table'=>$table]];
        }
        return ['fds'=>$fds, 'data'=>$data];
    }

    public function move($fd, $data, $userStatus, $table)
    {

    }



}