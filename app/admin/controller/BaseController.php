<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\BaseController as AppBaseController;

/**
 * 后台控制器基类：注入登录中间件
 */
abstract class BaseController extends AppBaseController
{
    /**
     * 中间件
     * @var array
     */
    protected $middleware = [
        \app\admin\middleware\Auth::class,
    ];
}
