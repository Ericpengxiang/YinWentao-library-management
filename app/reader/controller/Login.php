<?php
declare(strict_types=1);
namespace app\reader\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Login extends BaseController
{
    public function index()
    {
        if (Session::get('reader_id')) {
            return redirect('/reader/index/index');
        }
        return View::fetch();
    }

    public function doLogin()
    {
        $username = trim((string)$this->request->post('username', ''));
        $password = trim((string)$this->request->post('password', ''));
        if (!$username || !$password) {
            return json(['code' => 1, 'msg' => '账号和密码不能为空', 'data' => new \stdClass()]);
        }
        $account = Db::name('reader_accounts')
            ->where('username', $username)
            ->where('status', 1)
            ->find();
        if (!$account || $account['password'] !== md5($password)) {
            return json(['code' => 1, 'msg' => '账号或密码错误', 'data' => new \stdClass()]);
        }
        $reader = Db::name('readers')->find($account['reader_id']);
        if (!$reader || $reader['status'] != 1) {
            return json(['code' => 1, 'msg' => '账号已被禁用，请联系管理员', 'data' => new \stdClass()]);
        }
        Db::name('reader_accounts')->where('id', $account['id'])->update(['last_login' => date('Y-m-d H:i:s')]);
        Session::set('reader_id', $reader['id']);
        Session::set('reader_name', $reader['name']);
        Session::set('reader_card', $reader['card_no']);
        return json(['code' => 0, 'msg' => '登录成功', 'data' => ['url' => '/reader/index/index']]);
    }

    public function logout()
    {
        Session::delete('reader_id');
        Session::delete('reader_name');
        Session::delete('reader_card');
        return redirect('/reader/login/index');
    }
}
