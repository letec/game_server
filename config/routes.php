<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

use Hyperf\HttpServer\Router\Router;

Router::get('/', [App\Controller\IndexController::class, 'index']);

Router::get('/captcha', [App\Controller\IndexController::class, 'captcha']);

Router::post('/signin', [App\Controller\UserController::class, 'signIn']);

Router::post('/signup', [App\Controller\UserController::class, 'signUp']);

Router::addGroup("/",
    function () {
        Router::post('user/quit', 'App\Controller\UserController@quit');
        Router::post('user/intohall', 'App\Controller\UserController@intohall');
        Router::post('user/info', 'App\Controller\UserController@info');
        Router::post('user/update', 'App\Controller\UserController@update');
        Router::post('hall', 'App\Controller\HallController@getHall');
        Router::post('table/seatdown', 'App\Controller\HallController@seatDown');
    },
    ['middleware' => [App\MiddleWare\OnlineCheck::class]]
);


Router::addServer('ws', function () {
    Router::get('/', 'App\Controller\WebSocketController');
});