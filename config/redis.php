<?php
//redis
use think\facade\Env;
return [
    'host'       => Env::get('redis.host', '127.0.0.1'),
    'port'       => 6379,
    'timeout'    => 0,
    'expire'     => 0,
    'password'=>Env::get('redis.password', '')
];
