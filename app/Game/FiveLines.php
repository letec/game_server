<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class FiveLines extends BaseAction
{

    private function chessPanelInit($users)
    {
        $matrix = [];
        for ($x=1; $x <= 12; $x++) 
        { 
            for ($y=1; $y <= 12; $y++) 
            { 
                $matrix[$x][$y] = '';
            }
        }
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
            $table['USERS'][0]['color'] = $flag == 0 ? 'blackChess' : 'whiteChess';
            $table['USERS'][1]['color'] = $table['USERS'][0]['color'] == 'blackChess' ? 'whiteChess' : 'blackChess';
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

    public function move($fd, $data, $user, $userStatus, $table)
    {

        if ($table['GAMING_DATA']['TURN'] != $user->UserName)
        {
            return ['result'=>FALSE, 'message'=>"没轮到您!", 'data'=>['ACTION'=>'MOVE', 'table'=>$table]];
        }

        $targetCol = $data['data']['targetCol'];
        $targetRow = $data['data']['targetRow'];
        $selectedCol = $data['data']['selectedCol'];
        $selectedRow = $data['data']['selectedRow'];
        $color = '';
        foreach ($table['USERS'] as $k => $v)
        {
            if ($v['fd'] != '') $fds[] = $v['fd'];
            $table['USERS'][$k]['avatar'] = $this->UserModel->getAvataById($v['userId']) ?? '2019070315.jpg';
            if ($v['userId'] == $user->id) 
            {
                $color = $v['color'];
            }
        }
        if ($color == 'blackChess')
        {
            $targetCol = $targetCol * -1 + 10;
            $targetRow = $targetRow * -1 + 11;
            $selectedCol = $selectedCol * -1 + 10;
            $selectedRow = $selectedRow * -1 + 11;
        }

        $matrix = $table['GAMING_DATA']['CHESS_PANEL'];
        if ( ! isset($matrix[$selectedRow][$selectedCol]) || ! isset($matrix[$targetRow][$targetCol]))
        {
            $position = "s:{$selectedRow},{$selectedCol} t:{$targetRow},{$targetCol}";
            return ['result'=>FALSE, 'message'=>"坐标错误!{$position}", 'data'=>['ACTION'=>'MOVE', 'table'=>$table]];
        }

        $selectedObject = $matrix[$selectedRow][$selectedCol];
        $targetObject = $matrix[$targetRow][$targetCol];
        if ( ! isset($selectedObject['color']) || $selectedObject['color'] != $color)
        {
            return ['result'=>FALSE, 'message'=>"不能使用对方的子!", 'data'=>['ACTION'=>'MOVE', 'table'=>$table]];
        }

        $check = $this->checkMoveTarget($matrix, $targetObject, $targetRow, $targetCol, $color);
        if ($check['result'] === TRUE)
        {
            // game_over
            if ($check['LineFive'])
            {
                return $this->gameOver($fds, $user->UserName, $table, $userStatus['ROOM']);
            }
            else 
            {
                $matrix[$targetRow][$targetCol] = $selectedObject;
                $matrix[$selectedRow][$selectedCol] = '';
                $table['GAMING_DATA']['CHESS_PANEL'] = $matrix;
                if ($table['USERS'][0]['username'] == $table['GAMING_DATA']['TURN'])
                {
                    $table['GAMING_DATA']['TURN'] = $table['USERS'][1]['username'];
                }
                else
                {
                    $table['GAMING_DATA']['TURN'] = $table['USERS'][0]['username'];
                }
                $this->redis->setex($userStatus['ROOM'], 24*60*60*30, json_encode($table));
                return ['fds'=>$fds, 'data'=>['result'=>TRUE, 'message'=>'', 'data'=>['ACTION'=>'MOVE', 'table'=>$table]]];
            }
        }

        return ['result'=>FALSE, 'message'=>'不能下这一步棋', 'data'=>['ACTION'=>'MOVE', 'table'=>$table]];
    }

    private function gameOver($fds, $username, $table, $room)
    {
        $table['STATUS'] = 0;
        $table['USERS'][0]['status'] = 0;
        $table['USERS'][1]['status'] = 0;
        $table['GAMING_DATA']['CHESS_PANEL'] = $this->chessPanelInit($table['USERS']);
        $table['GAMING_DATA']['TURN'] = '';
        $this->redis->setex($room, 24*60*60*30, json_encode($table));
        return ['fds'=>$fds, 'data'=>['result'=>TRUE, 'message'=>'游戏结束', 'data'=>['ACTION'=>'GAME_OVER', 'WIN'=>$username, 'table'=>$table]]];
    }

    private function checkMoveTarget($matrix, $targetObject, $targetRow, $targetCol, $color)
    {
        
        return ['result'=>FALSE, 'LineFive'=>$LineFive];
    }

    public function chat($fd, $data, $user, $userStatus, $table)
    {
        $fds = [];
        $avatar = $this->UserModel->getAvataById($user->id);
        foreach ($table['USERS'] as $k => $v)
        {
            if ($v['fd'] != '') $fds[] = $v['fd'];
        }
        $content = mb_substr($data['data']['content'], 0, 30);
        $resp = ['ACTION'=>'CHAT', 'content'=>$content, 'from'=>$user->UserName, 'avatar'=>$avatar, 'time'=>date('Y-m-d H:i:s')];
        return ['fds'=>$fds, 'data'=>['result'=>TRUE, 'message'=>'', 'data'=>$resp]];
    }

    public function giveUp($fd, $data, $user, $userStatus, $table)
    {
        foreach ($table['USERS'] as $k => $v)
        {
            if ($v['fd'] != '') $fds[] = $v['fd'];
        }
        $username = $user->UserName == $table['USERS'][0]['username'] ? $table['USERS'][1]['username'] : $table['USERS'][0]['username'];
        return $this->gameOver($fds, $username, $table, $userStatus['ROOM']);
    }

}