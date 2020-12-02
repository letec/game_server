<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Utils\Context;
use Hyperf\View\RenderInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Model\UserModel;

class HallController extends AbstractController
{

    protected $container;
    
    protected $redis;

    public function __construct()
    {
        $this->UserModel = new UserModel();
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
    }

    public function getHall(ResponseInterface $response)
    {
        $gameCode = $this->request->input('gameCode', '');
        $allGame = config('game.allGame');
        if ( ! isset($allGame[$gameCode]))
        {
            return $response->json(['result'=>FALSE, 'message'=>'参数错误!', 'data'=>NULL]);
        }
        $result = $this->redis->keys("HALL_{$gameCode}_TABLE_*");
        sort($result);
        $list = [];
        foreach ($result as $k => $v)
        {
            $item = $this->redis->get($v);
            $temp = is_string($item) ? json_decode($item, TRUE) : [];
            foreach ($temp['USERS'] as $key => $value) 
            {
                if ($value['userId'] != '')
                {
                    $a = $this->UserModel->getAvataById($value['userId']);
                    $temp['USERS'][$key]['avatar'] = $a ?? '2019070315.jpg';
                }
            }
            $list[] = $temp;
        }
        return $response->json(['result'=>TRUE, 'message'=>'', 'data'=>['tables'=>$list]]);
    }

    public function seatDown(ResponseInterface $response)
    {
        $user = $this->request->USER;
        $gameCode = $this->request->input('gameCode', '');
        $tableID = $this->request->input('tableID', '');
        $seat = $this->request->input('seat', '');
        $allGame = config('game.allGame');
                
        $tableKey = "HALL_{$gameCode}_TABLE_{$tableID}"; 
        $table = $this->redis->get($tableKey);

        $userStatus = $this->redis->get("USER_STATUS_{$user->id}");
        $userStatus = is_string($userStatus) ? json_decode($userStatus, TRUE) : [];
        if ( ! empty($userStatus['SEAT']) && ! empty($userStatus['ROOM']))
        {
            return $response->json(['result'=>FALSE, 'message'=>'您还在一个位置上!', 'data'=>NULL]);
        }
        
        if ( ! isset($allGame[$gameCode]))
        {
            return $response->json(['result'=>FALSE, 'message'=>'游戏代号错误!', 'data'=>NULL]);
        }

        if ( ! $table)
        {
            return $response->json(['result'=>FALSE, 'message'=>'房间ID错误!', 'data'=>NULL]);
        }

        $table = json_decode($table, TRUE);

        if ( ! isset($table['USERS'][$seat-1]['userId']))
        {
            return $response->json(['result'=>FALSE, 'message'=>'座位错误!', 'data'=>NULL]);
        }

        if ($table['USERS'][$seat-1]['userId'] != '')
        {
            return $response->json(['result'=>FALSE, 'message'=>'已经有人坐下了!', 'data'=>NULL]);
        }

        return $response->json(['result'=>TRUE, 'message'=>'', 'data'=>NULL]);
    }

}