<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use system\Redis;
use \think\facade\Env;

// 应用公共文件
if (!function_exists('isMobile')) {
    /**
     * @annotate 判断手机号格式
     * @author 江枫
     * @email 635449961@qq.com
     * @url:www.cloudcmf.com
     * @time 2019-5-13
     */
    function isMobile($mobile)
    {
        if (empty($mobile)) {
            return false;
        }

        if (!preg_match("/^[0-9][0-9]*$/", $mobile)) {
            return false;
        }
        return true;
    }
}

/**
 * CMF密码加密方法
 * @param string $pw 要加密的原始密码
 * @param string $authCode 加密字符串
 * @return string
 */
function cmf_password($pw, $authCode = '')
{
    if (empty($authCode)) {
        $authCode = config('database.authcode');
    }
    $result = "###" . md5(md5($authCode . $pw));
    return $result;
}

//检查密码
function isPasswords($password, $repassword)
{
    $password   = trim($password);
    $repassword = trim($repassword);
    if (strlen($password) < 6) {
        return false;
    }
    if ($password != $repassword) {
        return false;
    }
    return true;
}

function getNumber()
{
    $number = rand(100000000, 999999999);
    $flag   = db('members')->where('number', $number)->find();
    if ($flag)
    {
        return getNumber();
    }
    return $number;
}


function getRandChar($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

function get_image_url($image)
{
    return $image;
}

if (!function_exists('redis')) {
    /**
     * @annotate redis操作
     * @author 江枫
     * @email 635449961@qq.com
     * @url:www.cloudcmf.com
     * @time
     */
    function redis($options = [])
    {
        return Redis::instance($options);
    }
}
if (!function_exists('smsCode')) {
    /**
     * @annotate 判断手机号格式
     * @author 江枫
     * @email 635449961@qq.com
     * @url:www.cloudcmf.com
     * @time 2019-5-13
     */
    function smsCode($mobile, $type = 'base', $project = '')
    {
        $redis = \redis();
        if ($project) {
            $redis->setKey($project);
        }
        $key = "code:" . $type . ":" . $mobile;

        return $redis->get($key);
    }
}

function help_p($val = null)
{
    if (empty($val)) {
        echo '<pre>';
        var_dump($val);
        echo '</pre>';
    } else {
        echo '<pre>';
        print_r($val);
        echo '</pre>';
    }
}// help_p() end

function help_test_logs($var = [])
{
    \think\Db::name('test_logs')->insert([
        'content' => json_encode($var, JSON_UNESCAPED_UNICODE)
    ]);
}// help_test_logs
