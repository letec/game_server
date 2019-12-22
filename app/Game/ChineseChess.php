<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class ChineseChess extends BaseAction
{

    private function chessPanelInit($users)
    {
        $matrix = [];
        for ($x=1; $x <= 10; $x++) 
        { 
            for ($y=1; $y <= 9; $y++) 
            { 
                $matrix[$x][$y] = NULL;
            }
        }
        $matrix[10][1] = ['type'=>'chariot', 'color'=>'red'];
        $matrix[10][2] = ['type'=>'knight', 'color'=>'red'];
        $matrix[10][3] = ['type'=>'elephant', 'color'=>'red'];
        $matrix[10][4] = ['type'=>'guard', 'color'=>'red'];
        $matrix[10][5] = ['type'=>'king', 'color'=>'red'];
        $matrix[10][6] = ['type'=>'guard', 'color'=>'red'];
        $matrix[10][7] = ['type'=>'elephant', 'color'=>'red'];
        $matrix[10][8] = ['type'=>'knight', 'color'=>'red'];
        $matrix[10][9] = ['type'=>'chariot', 'color'=>'red'];

        $matrix[8][2] = ['type'=>'gunner', 'color'=>'red'];
        $matrix[8][8] = ['type'=>'gunner', 'color'=>'red'];

        $matrix[7][1] = ['type'=>'gunner', 'color'=>'red'];
        $matrix[7][3] = ['type'=>'gunner', 'color'=>'red'];
        $matrix[7][5] = ['type'=>'gunner', 'color'=>'red'];
        $matrix[7][7] = ['type'=>'gunner', 'color'=>'red'];
        $matrix[7][9] = ['type'=>'gunner', 'color'=>'red'];

        $matrix[4][1] = ['type'=>'gunner', 'color'=>'black'];
        $matrix[4][3] = ['type'=>'gunner', 'color'=>'black'];
        $matrix[4][5] = ['type'=>'gunner', 'color'=>'black'];
        $matrix[4][7] = ['type'=>'gunner', 'color'=>'black'];
        $matrix[4][9] = ['type'=>'gunner', 'color'=>'black'];

        $matrix[3][2] = ['type'=>'gunner', 'color'=>'black'];
        $matrix[3][8] = ['type'=>'gunner', 'color'=>'black'];

        $matrix[1][1] = ['type'=>'chariot', 'color'=>'black'];
        $matrix[1][2] = ['type'=>'knight', 'color'=>'black'];
        $matrix[1][3] = ['type'=>'elephant', 'color'=>'black'];
        $matrix[1][4] = ['type'=>'guard', 'color'=>'black'];
        $matrix[1][5] = ['type'=>'king', 'color'=>'black'];
        $matrix[1][6] = ['type'=>'guard', 'color'=>'black'];
        $matrix[1][7] = ['type'=>'elephant', 'color'=>'black'];
        $matrix[1][8] = ['type'=>'knight', 'color'=>'black'];
        $matrix[1][9] = ['type'=>'chariot', 'color'=>'black'];

        return $matrix;
    } 

    public function ready($fd, $data, $userStatus, $table)
    {
        if ($table['STATUS'] != 0)
        {
            return ['result'=>FALSE, 'message'=>'游戏已经开始!', 'data'=>['ACTION'=>'INFO']];
        }
        $targetStatus = $table['USERS'][$userStatus['SEAT']-1]['status'] == 1 ? 0 : 1;
        $table['USERS'][$userStatus['SEAT']-1]['status'] = $targetStatus;
        
        if ($table['USERS'][0]['status'] == 1 && $table['USERS'][1]['status'] == 1)
        {
            $table['USERS'][0]['status'] = 2;
            $table['USERS'][1]['status'] = 2;
            $flag = mt_rand(0, 1);
            $table['USERS'][0]['color'] = $flag == 0 ? 'blackChess' : 'redChess';
            $table['USERS'][1]['color'] = $table['USERS'][0]['color'] == 'blackChess' ? 'redChess' : 'blackChess';
            $table['USERS'][0]['USED_TIME'] = 0;
            $table['USERS'][1]['USED_TIME'] = 0;
            $table['STATUS'] = 1;
            $table['GAMING_DATA'] = [
                'TOTAL_TIME'  => 40*60,
                'CHESS_PANEL' => $this->chessPanelInit($table['USERS']),
                'TURN'        => $flag == 1 ? $table['USERS'][0]['username'] : $table['USERS'][1]['username']
            ];
            $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'START_GAME']];
        }
        else 
        {
            $data = ['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'READY']];
        }

        $this->redis->setex($userStatus['ROOM'], 24*60*60*30, json_encode($table));
        $fds = [];
        foreach ($table['USERS'] as $k => $v)
        {
            if ($v['fd'] != '') $fds[] = $v['fd'];
            $table['USERS'][$k]['avatar'] = $this->UserModel->getAvataById($v['userId']) ?? '2019070315.jpg';
        }
        $data['data']['table'] = $table;
        return ['fds'=>$fds, 'data'=>$data];
    }

    public function move($fd, $data, $userStatus, $table)
    {

    }



}