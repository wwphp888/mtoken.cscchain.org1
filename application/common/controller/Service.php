<?php
/**服务基类
 * Created by PhpStorm.
 * User: pengjiang
 * Date: 2019/5/14
 * Time: 13:02
 */

namespace app\common\controller;

class Service extends Base
{
    //密钥
    const appSecret = 'hiwa&$%ehkipo@asqw';
    //加密方式
    protected static $sign_method = 'sha256';

    public function initialize()
    {
        parent::initialize();

        //发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"
        $time = $this->data['timetamp'];
        $time = strtotime($time);
        $time ?: $this->error("时间戳不存在");

        $sign = $this->data['sign'];
        $sign ?: $this->error("签名不存在");
        if ($this->getSign($this->data) != $sign) {
            $this->error("签名不匹配");
        }
        //清除sign与时间
        unset($this->data['sign']);
        unset($this->data['timestamp']);
    }

    protected function getSign($params)
    {
        unset($params['sign']);
        ksort($params);
        $tmps = array();
        foreach ($params as $k => $v) {
            $tmps[] = $k . $v;
        }
        $string = implode('', $tmps) . self::appSecret;
        return strtolower(hash(self::$sign_method, $string));
    }
}