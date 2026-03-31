<?php
// 全局中间件定义文件
// +----------------------------------------------------------------------

return [
    // Session初始化（后台需 Session 登录态）
    \think\middleware\SessionInit::class,
];
