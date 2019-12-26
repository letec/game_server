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
                $matrix[$x][$y] = '';
            }
        }
        $matrix[10][1] = ['type'=>'chariot', 'color'=>'redChess'];
        $matrix[10][2] = ['type'=>'knight', 'color'=>'redChess'];
        $matrix[10][3] = ['type'=>'elephant', 'color'=>'redChess'];
        $matrix[10][4] = ['type'=>'guard', 'color'=>'redChess'];
        $matrix[10][5] = ['type'=>'king', 'color'=>'redChess'];
        $matrix[10][6] = ['type'=>'guard', 'color'=>'redChess'];
        $matrix[10][7] = ['type'=>'elephant', 'color'=>'redChess'];
        $matrix[10][8] = ['type'=>'knight', 'color'=>'redChess'];
        $matrix[10][9] = ['type'=>'chariot', 'color'=>'redChess'];

        $matrix[8][2] = ['type'=>'gunner', 'color'=>'redChess'];
        $matrix[8][8] = ['type'=>'gunner', 'color'=>'redChess'];

        $matrix[7][1] = ['type'=>'soldier', 'color'=>'redChess'];
        $matrix[7][3] = ['type'=>'soldier', 'color'=>'redChess'];
        $matrix[7][5] = ['type'=>'soldier', 'color'=>'redChess'];
        $matrix[7][7] = ['type'=>'soldier', 'color'=>'redChess'];
        $matrix[7][9] = ['type'=>'soldier', 'color'=>'redChess'];

        $matrix[4][1] = ['type'=>'soldier', 'color'=>'blackChess'];
        $matrix[4][3] = ['type'=>'soldier', 'color'=>'blackChess'];
        $matrix[4][5] = ['type'=>'soldier', 'color'=>'blackChess'];
        $matrix[4][7] = ['type'=>'soldier', 'color'=>'blackChess'];
        $matrix[4][9] = ['type'=>'soldier', 'color'=>'blackChess'];

        $matrix[3][2] = ['type'=>'gunner', 'color'=>'blackChess'];
        $matrix[3][8] = ['type'=>'gunner', 'color'=>'blackChess'];

        $matrix[1][1] = ['type'=>'chariot', 'color'=>'blackChess'];
        $matrix[1][2] = ['type'=>'knight', 'color'=>'blackChess'];
        $matrix[1][3] = ['type'=>'elephant', 'color'=>'blackChess'];
        $matrix[1][4] = ['type'=>'guard', 'color'=>'blackChess'];
        $matrix[1][5] = ['type'=>'king', 'color'=>'blackChess'];
        $matrix[1][6] = ['type'=>'guard', 'color'=>'blackChess'];
        $matrix[1][7] = ['type'=>'elephant', 'color'=>'blackChess'];
        $matrix[1][8] = ['type'=>'knight', 'color'=>'blackChess'];
        $matrix[1][9] = ['type'=>'chariot', 'color'=>'blackChess'];

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

        $check = $this->checkMoveTarget($matrix, $selectedObject, $targetObject, $selectedRow, $selectedCol, $targetRow, $targetCol, $color);
        if ($check['result'] === TRUE)
        {
            // game_over
            if ($check['kingKilled'])
            {
                $table['STATUS'] = 0;
                $table['USERS'][0]['status'] = 0;
                $table['USERS'][1]['status'] = 0;
                $table['GAMING_DATA']['CHESS_PANEL'] = $this->chessPanelInit($table['USERS']);
                $table['GAMING_DATA']['TURN'] = '';
                $this->redis->setex($userStatus['ROOM'], 24*60*60*30, json_encode($table));
                return ['fds'=>$fds, 'data'=>['result'=>TRUE, 'message'=>'游戏结束', 'data'=>['ACTION'=>'GAME_OVER', 'WIN'=>$user->UserName, 'table'=>$table]]];
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

    private function checkMoveTarget($matrix, $selectedObject, $targetObject, $selectedRow, $selectedCol, $targetRow, $targetCol, $color)
    {
        // no move detected
        if ($selectedRow == $targetRow && $selectedCol == $targetCol)
        {
            return ['result'=>FALSE, 'kingKilled'=>FALSE];
        }
        // can't eat self's chess
        if (isset($matrix[$targetRow][$targetCol]['color']) && $matrix[$targetRow][$targetCol]['color'] == $selectedObject['color'])
        {
            return ['result'=>FALSE, 'kingKilled'=>FALSE];
        }

        $kingKilled = (isset($matrix[$targetRow][$targetCol]['type']) && $matrix[$targetRow][$targetCol]['type'] == 'king') ? TRUE : FALSE;
        switch ($selectedObject['type']) 
        {
            // 车
            case 'chariot':
                if ($selectedCol == $targetCol)
                {
                    $colDiffer = $selectedCol - $targetCol;
                    if ($colDiffer < 0) 
                    {
                        for ($col=$selectedCol; $col<$targetCol; $col++) 
                        { 
                            if ($matrix[$selectedRow][$col] != '' && $col != $targetCol)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                    else 
                    {
                        for ($col=$selectedCol; $col>$targetCol; $col--) 
                        { 
                            if ($matrix[$selectedRow][$col] != '' && $col != $targetCol)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                }
                else if ($selectedRow == $targetRow)
                {
                    $rowDiffer = $selectedRow - $targetRow;
                    if ($rowDiffer < 0) 
                    {
                        for ($row=$selectedRow; $row < $targetRow; $row++) 
                        { 
                            if ($matrix[$row][$selectedCol] != '' && $row != $targetRow)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                    else
                    {
                        for ($row=$selectedRow; $row > $targetRow; $row--) 
                        { 
                            if ($matrix[$row][$selectedCol] != '' && $row != $targetRow)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                }
                break;
            // 马
            case 'knight':
                $possibleTarget = [
                    ['row'=>$selectedRow-2, 'col'=>$selectedCol-1],
                    ['row'=>$selectedRow-2, 'col'=>$selectedCol+1],
                    ['row'=>$selectedRow-1, 'col'=>$selectedCol+2],
                    ['row'=>$selectedRow+1, 'col'=>$selectedCol+2],
                    ['row'=>$selectedRow+2, 'col'=>$selectedCol+1],
                    ['row'=>$selectedRow+2, 'col'=>$selectedCol-1],
                    ['row'=>$selectedRow+1, 'col'=>$selectedCol-2],
                    ['row'=>$selectedRow-1, 'col'=>$selectedCol-2],
                ];
                foreach ($possibleTarget as $k => $v) 
                {
                    if ( ! isset($matrix[$v['row']][$v['col']]))
                    {
                        continue;
                    }
                    if ($v['row'] == $targetRow && $v['col'] == $targetCol)
                    {
                        switch ($k) {
                            case 0:
                            case 1:
                                if ($matrix[$selectedRow-1][$selectedCol] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 2:
                            case 3:
                                if ($matrix[$selectedRow][$selectedCol+1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 4:
                            case 5:
                                if ($matrix[$selectedRow+1][$selectedCol] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 6:
                            case 7:
                                if ($matrix[$selectedRow][$selectedCol-1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }
                break;
            // 象
            case 'elephant':
                if (($color == 'redChess' && $targetRow < 6) || ($color == 'blackChess' && $targetRow > 5))
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                $possibleTarget = [
                    ['row'=>$selectedRow-2, 'col'=>$selectedCol-2],
                    ['row'=>$selectedRow-2, 'col'=>$selectedCol+2],
                    ['row'=>$selectedRow+2, 'col'=>$selectedCol+2],
                    ['row'=>$selectedRow+2, 'col'=>$selectedCol-2],
                ];
                foreach ($possibleTarget as $k => $v) 
                {
                    if ( ! isset($matrix[$v['row']][$v['col']]))
                    {
                        continue;
                    }
                    if ($v['row'] == $targetRow && $v['col'] == $targetCol)
                    {
                        switch ($k) {
                            case 0:
                                if ($matrix[$selectedRow-1][$selectedCol-1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 1:
                                if ($matrix[$selectedRow-1][$selectedCol+1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 2:
                                if ($matrix[$selectedRow+1][$selectedCol+1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            case 3:
                                if ($matrix[$selectedRow+1][$selectedCol-1] == '')
                                {
                                    return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }
                break;
            // 士
            case 'guard':
                if ($targetCol < 4 || $targetCol > 6)
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                if (($color == 'redChess' && $targetRow < 8) || ($color == 'blackChess' && $targetRow > 3))
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                $possibleTarget = [
                    ['row'=>$selectedRow-1, 'col'=>$selectedCol-1],
                    ['row'=>$selectedRow-1, 'col'=>$selectedCol+1],
                    ['row'=>$selectedRow+1, 'col'=>$selectedCol+1],
                    ['row'=>$selectedRow+1, 'col'=>$selectedCol-1],
                ];
                foreach ($possibleTarget as $k => $v) 
                {
                    if ( ! isset($matrix[$v['row']][$v['col']]))
                    {
                        continue;
                    }
                    if ($v['row'] == $targetRow && $v['col'] == $targetCol)
                    {
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                }
                break;
            // 帅
            case 'king':
                if ($targetCol < 4 || $targetCol > 6)
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                if (($color == 'redChess' && $targetRow < 8) || ($color == 'blackChess' && $targetRow > 3))
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                $possibleTarget = [
                    ['row'=>$selectedRow+1, 'col'=>$selectedCol],
                    ['row'=>$selectedRow, 'col'=>$selectedCol+1],
                    ['row'=>$selectedRow-1, 'col'=>$selectedCol],
                    ['row'=>$selectedRow, 'col'=>$selectedCol-1],
                ];
                foreach ($possibleTarget as $k => $v) 
                {
                    if ( ! isset($matrix[$v['row']][$v['col']]))
                    {
                        continue;
                    }
                    if ($v['row'] == $targetRow && $v['col'] == $targetCol)
                    {
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                }
                break;
            // 炮
            case 'gunner':
                $piece = 0;
                if ($selectedRow == $targetRow)
                {
                    $colDiffer = $selectedCol - $targetCol;
                    if ($colDiffer < 0)
                    {
                        for ($col=$selectedCol; $col<=$targetCol; $col++)
                        {
                            if ($piece > 1)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                            else if ($matrix[$selectedRow][$col] != '' && ! in_array($col, [$selectedCol, $targetCol]))
                            {
                                $piece ++;
                            }
                            if ($targetCol == $col && $piece > 0 && $matrix[$targetRow][$col] == '')
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                    }
                    else
                    {
                        for ($col=$selectedCol; $col>=$targetCol; $col--) 
                        { 
                            if ($piece > 1)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                            else if ($matrix[$selectedRow][$col] != '' && ! in_array($col, [$selectedCol, $targetCol]))
                            {
                                $piece ++;
                            }
                            if ($targetCol == $col && $piece > 0 && $matrix[$targetRow][$col] == '')
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                    }
                }
                else if ($selectedCol == $targetCol)
                {
                    $rowDiffer = $selectedRow - $targetRow;
                    if ($rowDiffer < 0) 
                    {
                        for ($row=$selectedRow; $row<=$targetRow; $row++) 
                        { 
                            if ($piece > 1)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                            else if ($matrix[$row][$selectedCol] != '' && ! in_array($row, [$selectedRow, $targetRow]))
                            {
                                $piece ++;
                            }
                            if ($targetRow == $row && $piece > 0 && $matrix[$targetRow][$targetCol] == '')
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                    }
                    else
                    {
                        for ($row=$selectedRow; $row>=$targetRow; $row--) 
                        { 
                            if ($piece > 1)
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                            else if ($matrix[$row][$selectedCol] != '' && ! in_array($row, [$selectedRow, $targetRow]))
                            {
                                $piece ++;
                            }
                            if ($targetRow == $row && $piece > 0 && $matrix[$targetRow][$targetCol] == '')
                            {
                                return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                            }
                        }
                    }
                }
                if ($piece < 1 && $matrix[$targetRow][$targetCol] != '')
                {
                    return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
                }
                return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
            // 兵
            case 'soldier':
                $possibleTarget = [];
                if ($color == 'redChess')
                {
                    $possibleTarget[] = ['row'=>$selectedRow-1, 'col'=>$selectedCol];
                }
                else
                {
                    $possibleTarget[] = ['row'=>$selectedRow+1, 'col'=>$selectedCol];
                }
                if (($color == 'redChess' && $selectedRow < 6) || ($color == 'blackChess' && $selectedRow > 5))
                {
                    $possibleTarget[] = ['row'=>$selectedRow, 'col'=>$selectedCol+1];
                    $possibleTarget[] = ['row'=>$selectedRow, 'col'=>$selectedCol-1];
                }
                foreach ($possibleTarget as $k => $v) 
                {
                    if ( ! isset($matrix[$v['row']][$v['col']]))
                    {
                        continue;
                    }
                    if ($v['row'] == $targetRow && $v['col'] == $targetCol)
                    {
                        return ['result'=>TRUE, 'kingKilled'=>$kingKilled];
                    }
                }
                break;
            default:
                break;
        }
        return ['result'=>FALSE, 'kingKilled'=>$kingKilled];
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



}