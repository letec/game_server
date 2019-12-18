<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Utils\Context;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as ResponseHanddle;

class OnlineCheck implements MiddlewareInterface
{

    protected $redis;

    protected $container;

    protected $request;

    public function __construct(RequestInterface $request, ResponseHanddle $response)
    {
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
        $this->request = $request;
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() == 'OPTIONS') 
        {
            return $response;
        }

        $oid = $this->request->input('oid', '');
        $user = $this->redis->get('ONLINE_' . $oid);
        if ( ! $user)
        {
            $this->redis->del('ONLINE_' . $oid);
            return $this->response->json(['result'=>FALSE, 'message'=>'OFFLINE', 'data'=>[]]);
        }
        $this->redis->expire('ONLINE_' . $oid, 15*60);
        $this->request->USER = json_decode($user);
        return $handler->handle($request);
    }
}