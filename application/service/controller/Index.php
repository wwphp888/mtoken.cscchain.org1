<?php

namespace app\service\controller;

use app\common\model\Members;
use app\common\controller\Service;

class Index extends Service
{
    public function getUserInfo()
    {
        $uid = $this->data['uid'];
        $data = Members::where('id', $uid)->find();
        if ($data) {
            $this->success("成功", $data);
        } else {
            $this->error("失败");
        }
    }

    /**
     * 检查手机号
     */
    public function checkMobile()
    {

        $mobile = $this->data['mobile'];
        $data = Members::where('mobile', $mobile)->value("id");
        if ($data) {
            $this->success("手机号存在");
        } else {
            $this->error("手机号不存在");
        }
    }
}
