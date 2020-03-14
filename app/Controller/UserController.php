<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Utils\Context;
use Hyperf\View\RenderInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Model\UserModel;

class UserController extends AbstractController
{

    private $UserModel;

    protected $container;

    protected $redis;

    public function __construct()
    {
        $this->UserModel = new UserModel();
        $this->container = ApplicationContext::getContainer();
        $this->redis = $this->container->get(\Redis::class);
    }

    public function signIn(ResponseInterface $response)
    {
        $username = $this->request->input('username', '');
        $password = $this->request->input('password', '');
        
        $user = $this->UserModel->getUserByName($username);
        if ( ! $user)
        {
            return $response->json(['result'=>FALSE, 'message'=>'用户名不存在!', 'data'=>NULL]);
        }
        if ($user->PassWord == md5($user->Salt . $password . $username . $user->Salt))
        {
            $oid = md5($username . uniqid() . time());
            $this->redis->setex('ONLINE_' . $oid, 15*60, json_encode($user));
            $this->redis->setex('USER_STATUS_' . $user->id, 15*60, json_encode([]));
            return $response->json(['result'=>TRUE, 'message'=>'登录成功!', 'data'=>['oid'=>$oid]]);
        }
        return $response->json(['result'=>FALSE, 'message'=>'密码错误!', 'data'=>NULL]);
    }

    public function signUp(ResponseInterface $response)
    {
        $username = $this->request->input('username', '');
        $password = $this->request->input('password', '');
        $confirmPwd = $this->request->input('confirmPwd', '');

        $vCode = $this->request->input('vCode', '');
        $vcodeID = $this->request->input('vcodeID', ''); 

        if ( ! preg_match("/^[a-zA-Z0-9]{5,16}$/", $username))
        {
            return $response->json(['result'=>FALSE, 'message'=>'用户名不合法!', 'data'=>NULL]);
        }
        if ( ! preg_match("/^[a-zA-Z0-9]{5,16}$/", $password))
        {
            return $response->json(['result'=>FALSE, 'message'=>'密码不合法!', 'data'=>NULL]);
        }
        if ($confirmPwd != $password)
        {
            return $response->json(['result'=>FALSE, 'message'=>'两次输入的密码不一致!', 'data'=>NULL]);
        }
    
        $email = $this->request->input('email', '');

        if ($email != '' && ! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", $password))
        {
            return $response->json(['result'=>FALSE, 'message'=>'邮箱格式不正确!', 'data'=>NULL]);
        }

        $verify = $this->redis->get('captchaId_' . $vcodeID);
        $this->redis->del('captchaId_' . $vcodeID);
        if ($verify != $vCode)
        {
            return $response->json(['result'=>FALSE, 'message'=>'验证码不正确!', 'data'=>NULL]);
        }

        $user = $this->UserModel->getUserByName($username);
        if ($user)
        {
            return $response->json(['result'=>FALSE, 'message'=>'该用户已经存在!', 'data'=>NULL]);
        }

        $salt = mt_rand(10000, 99999);
        $createTime = time();

        $result = $this->UserModel->createUser([
            'UserName'   => $username,
            'PassWord'   => md5($salt . $password . $username . $salt),
            'Email'      => $email,
            'Salt'       => $salt,
            'CreateTime' => $createTime
        ]);

        if ( ! $result)
        {
            return $response->json(['result'=>FALSE, 'message'=>'注册失败,请稍后再试!', 'data'=>NULL]);
        }

        return $response->json(['result'=>TRUE, 'message'=>'注册成功!', 'data'=>NULL]);
    }

    public function intoHall(ResponseInterface $response)
    {
        $gameCode = $this->request->input('gameCode', '');
        $allGame = config('game.allGame');
        if ( ! isset($allGame[$gameCode]))
        {
            return $response->json(['result'=>FALSE, 'message'=>'参数错误!', 'data'=>NULL]);
        }
        return $response->json(['result'=>TRUE, 'message'=>'', 'data'=>NULL]);
    }

    public function info(ResponseInterface $response)
    {
        $user = $this->request->USER;
        unset($user->PassWord);
        unset($user->Salt);
        return $response->json(['result'=>TRUE, 'message'=>'', 'data'=>$user]);
    }

    public function update(ResponseInterface $response)
    {
        $user = $this->request->USER;
        $username = $user->UserName;

        $oid = $this->request->input('oid', '');
        $email = $this->request->input('email', '');
        $oldPassword = $this->request->input('oldPassword', '');
        $newPassword = $this->request->input('newPassword', '');
        $confirmPassword = $this->request->input('confirmPassword', '');

        $avatar = $this->request->input('avatar', '');

        $updateData = [];
        if ($newPassword != '')
        {
            if ($newPassword != $confirmPassword)
            {
                return $response->json(['result'=>FALSE, 'message'=>'两次输入的密码不一致!', 'data'=>NULL]);
            }
            if ($user->PassWord != md5($user->Salt . $oldPassword . $username . $user->Salt))
            {
                return $response->json(['result'=>FALSE, 'message'=>'旧密码不正确!', 'data'=>NULL]);
            }
            $updateData['Salt'] = mt_rand(10000, 99999);
            $updateData['PassWord'] = md5($updateData['Salt'] . $newPassword . $username . $updateData['Salt']);
        }
        $avatar != '' && $updateData['Avatar'] = $avatar;
        $email != '' && $updateData['Email'] = $email;

        $flag = $this->UserModel->updateUser($user->id, $updateData);
        if ( ! $flag)
        {
            return $response->json(['result'=>FALSE, 'message'=>'修改失败,请稍后重试!', 'data'=>NULL]);
        }
        $user = $this->UserModel->getUserById($user->id);
        $this->redis->set('ONLINE_' . $oid, json_encode($user));
        $this->redis->expire('ONLINE_' . $oid, 15*60);
        return $response->json(['result'=>TRUE, 'message'=>'修改成功!', 'data'=>NULL]);
    }

    public function quit()
    {
        $oid = $this->request->input('oid', '');
        $this->redis->del('ONLINE_' . $oid);
    }


}
