<?php

namespace app\user\service;
/**
 * Created by PhpStorm.
 * User: pengjiang
 * Date: 2019/5/13
 * Time: 19:24
 */
class User
{
    const TOKEN_SALT = 'sdflldf@mgdsnlg';
    const TOKEN_EXPIRE_IN = 604800;

    // 生成令牌
    public static function generateToken()
    {
        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        $tokenSalt = self::TOKEN_SALT;
        return md5($randChar . $timestamp . $tokenSalt);
    }

    /** 设置token
     * @param $uid
     */
    public static function setToken($uid, $project = '')
    {
        $redis = redis();
        $redis->setKey($project);

        $token = self::generateToken();
        //保存id对应的key
        $key = $redis->get("members:token:id:" . $uid);
        if ($key) {
            //移除
            $redis->rm($key);
        }
        //设置redis
        $result = $redis->set("members:" . $token, $uid, self::TOKEN_EXPIRE_IN);
        if (!$result) {
            return false;
        }
        $key = "members:" . $token;
        //保存id对应的key
        $redis->set("members:token:id:" . $uid, $key);
        return $token;
    }
}