<?php

use Hyperf\Utils\ApplicationContext;

class Init
{

    private $allGame;

    private $container; 

    private $redis; 

    public function __construct()
    {
        $this->allGame = config('game.allGame');

        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);

        foreach ($this->allGame as $key => $game) 
        {
            $redisKey = "HALL_{$key}";
            $result = $this->redis->keys($redisKey.'*');
            $result && $this->redis->del($result);
            for ($t=1; $t<=$game['tableNumber']; $t++)
            {
                $temp = ['gameCode'=>$key, 'TABLE_PANEL'=>[], 'USERS'=>[], 'STATUS'=>0];
                for ($i=1; $i<=$game['tableUserNumber']; $i++) {
                    $temp["USERS"][] = [
                        'seatId'   => $i,
                        'userId'   => '',
                        'username' => '',
                        'status'   => '',
                        'fd'       => '',
                    ];
                }
                $this->redis->setex("HALL_{$key}_TABLE_{$t}", 24*60*60*30, json_encode($temp));
            }
        }
    }

}

new Init();