<?php

namespace app\user\validate;

/**
 * Created by PhpStorm.
 * User: pengjiang
 * Date: 2019/5/14
 * Time: 10:00
 */
use think\Validate;

class Members extends Validate
{
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
    }

    protected $rule = [
        'mobile' => 'mobile',
        'password' => 'length:6,32',
        'rePassword'=>'require|confirm:password',
        'project' => 'require',
    ];
    protected $message = [
        'mobile.mobile' => '{%phone_number_error}',
        'password.length' => '{%phone_number_error}',
        'rePassword.require'=>'{%phone_number_error}',
        'project.require' => '{%phone_number_error}',
    ];
}