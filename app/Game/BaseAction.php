<?php
declare(strict_types=1);

namespace App\Game;

use App\Controller\AbstractController;
use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;

class BaseAction
{
    protected $container;

    protected $redis;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
    }

}