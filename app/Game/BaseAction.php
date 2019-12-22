<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;
use App\Model\UserModel;

class BaseAction
{
    protected $container;

    protected $redis;

    protected $UerModel;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
        $this->UserModel = new UserModel();
    }

}