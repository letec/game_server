<?php

declare (strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Redis;

use Hyperf\Redis\Pool\PoolFactory;
class RedisProxy_9e48b63abf8ea038444bdacf3766393d extends RedisProxy
{
    use \Hyperf\Di\Aop\ProxyTrait;
    protected $poolName;
    public function __construct(PoolFactory $factory, string $pool)
    {
        Redis::__construct($factory);
        $this->poolName = $pool;
    }
    public function __call($name, $arguments)
    {
        $__function__ = __FUNCTION__;
        $__method__ = __METHOD__;
        return self::__proxyCall(RedisProxy::class, __FUNCTION__, self::getParamsMap(RedisProxy::class, __FUNCTION__, func_get_args()), function ($name, $arguments) use($__function__, $__method__) {
            return parent::__call($name, $arguments);
        });
    }
}