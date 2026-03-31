<?php
declare(strict_types=1);
namespace app\reader\middleware;

use Closure;
use think\Request;
use think\facade\Session;

class Auth
{
    public function handle(Request $request, Closure $next)
    {
        $controller = strtolower((string)$request->controller());
        if ($controller === 'login') {
            return $next($request);
        }
        if (!Session::get('reader_id')) {
            if ($request->isAjax()) {
                return json(['code' => 1, 'msg' => '请先登录', 'data' => new \stdClass()]);
            }
            return redirect('/reader/login/index');
        }
        return $next($request);
    }
}
