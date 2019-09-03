<?php

namespace app\user\controller;

use app\common\controller\Base;
use app\user\service\User;
use system\Curl;
use think\Db;
use think\facade\Env;

class Index extends Base
{
    public $url = '127.0.0.1:10002';

    public function register()
    {
        $mobile = trim($this->data['mobile']??'');// 手机号
        $lang   = '';// 语言

        // 语言预设
        if (!empty($this->data['lang']))
        {
            $lang = strip_tags($this->data['lang']);
        }

        //限流
        $register_time = cache("register_time");

        if ($register_time)
        {
            return $this->error(lang("registration_fast"));
        }
        cache("register_time", 1, 2);

        // 调用了 api\common.php\isMobile()，正则匹配手机号
        $is_mobile = isMobile($mobile);

        //判断手机号格式
        if (!$is_mobile)
        {
            return $this->error(lang("phone_number_error"));
        }

        // 手机号码长度验证
        if (strlen($mobile)>20 || strlen($mobile)<6)
        {
            return $this->error(lang("phone_number_error"));
        }

        $this->project  = Env::get("project");
        $project        = $this->project;

        // 从 redis 缓存中获取验证码
        $code = smsCode($mobile, "reg", $project);
        /*help_test_logs([
            '接收到的验证码-'.$this->data['code'],
            '从 redis 获取的验证码-'.$code
        ]);*/

        //判断手机验证码
        if ($this->data['code'] != $code)
        {
            return $this->error(lang("phone_verification"));
        }

        // 获取用户记录
        $is_exist = Db::name("members")
                        ->where("mobile", $mobile)
                        ->field("id")
                        ->find();

        //判断手机号是否存在
        if (!empty($is_exist))
        {
            return $this->error(lang("username_existed"));
        }
        // 匹配密码格式
        if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/',$this->data['password']))
        {
            return $this->error(lang('password_4'));
        }

        //验证密码 application/common.php/isPasswords()
        $is_pass = isPasswords($this->data['password'], $this->data['rePassword']);
        // 校验两次输入的密码是否相同
        if (!$is_pass)
        {
            return $this->error(lang("password_and_confirmation_password"));
        }
        //注册
        $data = [
            'number'        => getNumber(),// ?
            'create_time'   => time(),//  注册时间
            'mobile'        => $mobile,// 手机号
        ];

        $data['login_pwd']      = cmf_password($this->data['password']);//登录密码加密
        $trade_pwd              = $this->data['trade_pwd']??'';// 交易密码
        $needTradePwdProject    = ["WSEC"];

        if (in_array($this->project, $needTradePwdProject))
        {
            if (empty($trade_pwd) || strlen($trade_pwd) < 6)
            {
                return $this->error(lang("transaction_password"));
            }
        }
        //交易密码
        if (!empty($trade_pwd)) {
            if ($trade_pwd != $this->data['re_trade_pwd'])
            {
                return $this->error(lang("re_transaction_password"));
            }

            $data['trade_pwd'] = cmf_password($this->data['trade_pwd']);
        }

        // pid 是邀请码 members.pid
        // pid 按需求改成手机号
        if (preg_match('/^[0-9]*$/', $this->data['pid']) || strlen($mobile)<20 || strlen($mobile)>6)
        {
            // 这段代码没看懂业务
            $path = Db::name("members")
                        ->where('mobile', $this->data['pid'])
                        ->field('relation,is_extend,id')
                        ->find();

            if (!empty($path))
            {
                // 检查用户是否有推广权 1：才有推广权
                if ($path['is_extend'] != 1)
                {
                    return $this->error(lang("promotion_rights"));
                }

                $data['relation'] = !empty($path['relation']) ? $path['id'] . '-' . $path['relation'] : $path['id'] . '-0';
                $data['pid']      = $path['id'];
            } else {
                return $this->error(lang("referee_doesn"));
            }

        } else {
            return $this->error(lang('members_2'));
        }

        $data['project'] = $project;

        // 写入用户注册信息
        $res = Db::name("members")->insert($data);

        if (empty($res))
        {
            return $this->error(lang("sign_error"));
        }

        $curl   = new Curl();
        $param  = ['mobile' => $mobile];

        $url = $this->url.'/api.php/block/suntoken/create_account';

        $cc = $curl->post($url, $param);
        $this->success(lang("sign_ok"));
    }// register() end

    /*
     * 测试接口
     * */
    /*public function test()
    {
        $param  = ['mobile' => '15070410521'];
        $url    = '161.117.193.112:20002/api.php/block/suntoken/create_account';
        $curl   = new Curl();
        $cc     = $curl->post($url, $param);

        die($cc);
        return $cc;die();
    }*/// test() end

    /**
     * 用户登录
     */
    public function login()
    {
        $cc         = time();
        $mobile     = trim($this->data['mobile']);
        $password   = $this->data['password'];
        $model      = $this->data['model']??"";
        $code       = $this->data['code']??"";
        $lang       = '';

        if (!empty($this->data['lang']))
        {
            $lang = strip_tags($this->data['lang']);
        }

        // 手机号码验证
        if (preg_match('/^[0-9]*$/', $mobile)===false || strlen($mobile)>15)
        {
            return $this->error(lang("phone_number_error"));
        }

        // 获取用户记录
        $user_info = Db::name("members")
                        ->where('mobile', $mobile)
                        ->find();

        /*
         * $user_info['times'] 用户密码错误输入次数限制
         * $user_info['freeze_time'] 错误输入限制解限时间
         * */
        if($user_info['times'] >= 5 && $user_info['freeze_time'] > time())
        {
            $freeze_time = date("Y-m-d H:i:s", $user_info['freeze_time']);

            return $this->error(lang('password_3').$freeze_time.'！');
        }
        // 更新冻结时间
        if($user_info['times']>=5 && $user_info['freeze_time'] <= time())
        {
            Db::name("members")->where("id", $user_info['id'])->update(["times" => 0]);
        }
        // 密码校验
        if (empty($password))
        {
            return $this->error(lang("please_password"));
        }
        // 用户记录校验
        if (empty($user_info))
        {
            return $this->error(lang("invaild_username_password"));
        }

        //验证密码
        $check_password = cmf_password($password);

        if ($check_password != $user_info['login_pwd']) {
            return $this->error(lang("please_verify_your_password"));
        }
        //用户账号是否禁用，1-禁用；0-启用
        if ($user_info['is_disabled'] == 1)
        {
            return $this->error(lang("account_is_disabled"));
        }

        $this->project = Env::get("project");
        // 生成 token 并将 token 保存到 redis 里
        $token = User::setToken($user_info['id'], $this->project);

        // 更新用户的部分数据
        Db::name("members")
            ->where('id', $user_info['id'])
            ->update([
                'last_login' => time(),// 最近一次登录的时间戳
                'lang'       => $lang,// 语言
                'ip'         => $this->getip() //登录 IP
            ]);

        if (!empty($user_info['avatar']))
        {
            $user_info['avatar'] = get_image_url($user_info['avatar']);
        }

        $data = [
            'is_model'              =>1,
            'token'                 => $token,
            //'id'                    => $user_info['id'],
            'headUrl'               => $user_info['avatar'],
            'phone'                 => $user_info['mobile'],
            'headUrl'               => $user_info['avatar'],
            'receiverAddress'       => $user_info['address'],
            //'uid'                   => $user_info['id'],
            'isRealName'            => $user_info['is_smrz'],
            'wasCreateWallet'       => $user_info['address'] ? 1 : 0,
            'wasCreateTrasaction'   => $user_info['trade_pwd'] ? 1 : 0
        ];

        return $this->success(lang('success'), $data);
    }// login() end

    public function getip()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";

        return($ip);
    }

    /**获取区域
     * @param string $lang
     */
    public function getMobileArea()
    {
        cache("getMobileArea:" . $this->lang, null);
        $data = cache("getMobileArea:" . $this->lang);

        if (empty($data))
        {
            $mobiles = Db::name("mobile_area")->select();
            $data    = [];

            foreach ($mobiles as $k => $v) {
                if ($this->lang == 'en')
                {
                    $cc = [
                        "name" => $v['area_en'],
                        "num"  => $v['area_code']
                    ];
                } else {
                    $cc = [
                        "name" => $v['area_cn'],
                        "num"  => $v['area_code']
                    ];
                }

                $data[] = $cc;
            }

            cache("getMobileArea:" . $this->lang, $data);
        }
        return $this->success($data);
    }

    /**
     * 忘记密码
     */
    public function forgetPassword()
    {
        $mobile = trim($this->data['mobile']);

        $lang = $this->lang;// 语言
        $user = Db::name("members")->where("mobile", $mobile)->find();// 获取用户记录

        if (empty($user))
            return $this->error(lang('members_1'));

        if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/',$this->data['password']))
            return $this->error(lang('password_4'));

        $this->project = Env::get("project");
        $project = $this->project;

        // 手机号码验证
        if (preg_match('/^[0-9]*$/', $mobile)===false || strlen($mobile)>15)
        {
            return $this->error(lang("phone_number_error"));
        }

        //判断手机验证码
        $code = smsCode($mobile, "forget", $this->project);

        if ($this->data['code'] != $code)
            return $this->error(lang('phone_verification'));

        //验证密码
        $is_pass = isPasswords($this->data['password'], $this->data['rePassword']);
        if (!$is_pass)
            return $this->error(lang('password_and_confirmation_password'));

        //加密密码
        $password = cmf_password(trim($this->data['password']));
        // 更新用户登录密码
        $res = Db::name("members")
                    ->where("mobile", $mobile)
                    ->update([
                        'login_pwd' => $password
                    ]);

        if ($user['login_pwd'] == $password)
            return $this->success(lang('password_5'));

        if ($res)
            return $this->success(lang('password_5'));

        return $this->error(lang('password_6'));
    }
}
