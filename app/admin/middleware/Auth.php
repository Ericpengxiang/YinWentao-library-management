<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use Closure;
use think\Request;
use think\facade\Session;

/**
 * 后台登录校验中间件：未登录跳转登录页或返回 JSON
 */
class Auth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $controller = strtolower((string) $request->controller());
        // 登录相关控制器放行
        if ($controller === 'login') {
            return $next($request);
        }

        if (!Session::get('admin_id')) {
            if ($request->isAjax()) {
                return json(['code' => 1, 'msg' => '请先登录', 'data' => new \stdClass()]);
            }
            return redirect('/admin/login/index');
        }

        return $next($request);
    }
}
