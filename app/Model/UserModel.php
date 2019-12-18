<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
use Hyperf\DbConnection\Db;

class UserModel extends Model
{

    public function getUserByName($username)
    {
        return Db::table('user')->where('UserName', $username)->first();
    }

    public function getUserById($userId)
    {
        return Db::table('user')->where('id', $userId)->first();
    }

    public function getAvataById($userId)
    {
        $user = Db::table('user')->select('Avatar')->where('id', $userId)->first();
        return $user->Avatar ?? '';
    }

    public function createUser($userData)
    {
        return Db::table('user')->insert($userData);
    }

    public function updateUser($userId, $userData)
    {
        return Db::table('user')->where('id', $userId)->update($userData);
    }
    
}
