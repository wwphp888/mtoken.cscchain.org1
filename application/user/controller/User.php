<?php
/**
 * Created by PhpStorm.
 * User: pengjiang
 * Date: 2019/5/21
 * Time: 11:07
 */

namespace app\user\controller;

use app\common\controller\UserBase;
use think\Db;

class User extends UserBase
{

    /**
     * 设置初始支付密码 ok
     */
    public function setPayPassword()
    {
        $trade_pwd = Db::name("members")->where("id", $this->uid)->value("trade_pwd");
        if (!empty($trade_pwd)) {
            return $this->error(lang('transaction_password_1'));
        }
        $tradepwd   = $this->data["tradepwd"];
        $retradepwd = $this->data["retradepwd"];

        if (preg_match("/^\d{6}$/", $tradepwd) == 0)
            return $this->error(lang('transaction_password_2'));

        if ($retradepwd != $tradepwd)
            return $this->error(lang('transaction_password_3'));

        $tradepwd = cmf_password($tradepwd);

        if (Db::name("members")->where("id", $this->uid)->update(["trade_pwd" => $tradepwd]) != false)
        {
            return $this->success(lang('set_1'));
        } else {
            return $this->success(lang('set_0'));
        }
    }

    /*
     * 修改交易密码
     * */
    public function forgetTradePassword()
    {
        $mobile = trim($this->data['mobile']);// 手机号
        $lang = $this->lang;// 语言
        // 获取用户记录
        $user = Db::name("members")->where("mobile", $mobile)->find();

        if (empty($user))
            return $this->error(lang('members_1'));

        if ($user['times'] >= 5 && $user['freeze_time'] > time())
        {
            $freeze_time = date("Y-m-d H:i:s", $user['freeze_time']);
            return $this->error(lang('password_3') . $freeze_time . '！');
        }

        if ($user['times'] >= 5 && $user['freeze_time'] <= time()) {
            Db::name("members")
                ->where("id", $user['id'])
                ->update(["times" => 0]);
        }

        //判断手机验证码
        $code = smsCode($mobile, "forget", $this->project);

        if ($this->data['code'] != $code) {
            // 用户输入密码的次数自增 1
            Db::name("members")->where("id", $user['id'])->setInc("times", 1);

            if ($user['times'] + 1 >= 5)
            {
                // 更新密码冻结时间
                Db::name("members")->where("id", $user['id'])->update("freeze_time", (time() + 3600));
            }

            return $this->error(lang('phone_verification'));
        }

        //验证密码
        $is_pass = isPasswords($this->data['password'], $this->data['rePassword']);

        if (!$is_pass)
            return $this->error(lang('transaction_password_3'));

        if (preg_match("/^\d{6}$/", $this->data['password']) == 0)
            return $this->error(lang('transaction_password_2'));

        //加密密码
        $password = cmf_password(trim($this->data['password']));

        if ($user['trade_pwd'] == $password)
            return $this->success(lang('transaction_password_4'));

        $res = Db::name("members")
                ->where("mobile", $mobile)
                ->update([
                    'trade_pwd' => $password,
                    "times" => 0 // 密码错误次数重置为 0
                ]);
        if ($res)
            return $this->success(lang('transaction_password_4'));

        return $this->error(lang('transaction_password_5'));
    }// forgetTradePassword() end


    //修改登录密码
    public function updatePassword()
    {
        // 获取用户记录
        $login_pwd = Db::name("members")
            ->where("id", $this->uid)
            ->value("login_pwd");

        $oldpassword    = $this->data['oldpassword'];   // 旧密码
        $password       = $this->data["password"];      // 新密码
        $rePassword     = $this->data["rePassword"];    // 新密码重复

        if (!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/', $this->data['password']))
            return $this->error(lang('password_1'));

        if ($password != $rePassword)
            return $this->error(lang('password_and_confirmation_password'));

        $oldpassword    = cmf_password($oldpassword);
        $password       = cmf_password($password);

        if ($oldpassword != $login_pwd)
            return $this->error(lang('password_2'));

        if ($oldpassword == $password)
            return $this->success(lang('set_1'));

        $bool = Db::name("members")
                    ->where("id", $this->uid)
                    ->update([
                        "login_pwd" => $password
                    ]);

        if ($bool) {
            return $this->success(lang('set_1'));
        } else {
            return $this->success(lang('set_0'));
        }
    }// updatePassword() end
}