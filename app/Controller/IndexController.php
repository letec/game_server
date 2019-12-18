<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\View\RenderInterface;
use App\Libraries\ValidateCode;

class IndexController extends AbstractController
{

    protected $container;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
    }

    public function index(RenderInterface $render)
    {
        return $render->render('index', ['name' => 'Hyperf']);
    }

    public function captcha(ResponseInterface $response)
    {
        $ValidateCode = new ValidateCode();
        $code = $ValidateCode->doimg();
        $codeId = uniqid((string) mt_rand(1, 999)) . uniqid((string) mt_rand(999, 9999));
        $vcode = $code['vcode'];

        $redis = $this->container->get(\Redis::class);
        $redis->setex('captchaId_' . $codeId, 60, $vcode);

        $data = ['img'=>$code['codeImg'], 'codeId'=>$codeId];
        return $response->json(['result'=>TRUE, 'message'=>'', 'data'=>$data]);
    }

    public function test()
    {
        echo 132;
    }

}
