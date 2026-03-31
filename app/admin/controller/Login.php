<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\BaseController;
use app\admin\model\Admin;
use think\facade\Session;
use think\facade\View;

/**
 * 后台登录与退出
 */
class Login extends BaseController
{
    /**
     * 登录页不使用 Auth 中间件（基类默认无中间件，此处显式声明）
     * @var array
     */
    protected $middleware = [];

    /**
     * 登录页
     */
    public function index()
    {
        if (Session::get('admin_id')) {
            return redirect('/admin/index/index');
        }
        return View::fetch();
    }

    /**
     * 提交登录（MD5 校验）
     */
    public function doLogin()
    {
        $username = trim((string) $this->request->post('username', ''));
        $password = (string) $this->request->post('password', '');

        if ($username === '' || $password === '') {
            return json(['code' => 1, 'msg' => '请输入账号和密码', 'data' => new \stdClass()]);
        }

        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            return json(['code' => 1, 'msg' => '账号或密码错误', 'data' => new \stdClass()]);
        }

        if (md5($password) !== $admin->password) {
            return json(['code' => 1, 'msg' => '账号或密码错误', 'data' => new \stdClass()]);
        }

        Session::set('admin_id', (int) $admin->id);
        Session::set('admin_name', (string) ($admin->real_name ?: $admin->username));

        $admin->last_login = date('Y-m-d H:i:s');
        $admin->save();

        return json(['code' => 0, 'msg' => '登录成功', 'data' => ['url' => '/admin/index/index']]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::clear();
        return redirect('/admin/login/index');
    }
}
