<?php
declare(strict_types=1);
namespace app\admin\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Notice extends BaseController
{
    public function index()
    {
        return View::fetch();
    }

    public function listJson()
    {
        $page = max(1, (int)$this->request->param('page', 1));
        $limit = 15;
        $total = Db::name('notices')->count();
        $list = Db::name('notices')->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
    }

    public function save()
    {
        $id = (int)$this->request->post('id', 0);
        $title = trim((string)$this->request->post('title', ''));
        $content = trim((string)$this->request->post('content', ''));
        $status = (int)$this->request->post('status', 1);
        $adminId = Session::get('admin_id');

        if (!$title || !$content) {
            return json(['code' => 1, 'msg' => '标题和内容不能为空', 'data' => new \stdClass()]);
        }

        $data = ['title' => $title, 'content' => $content, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) {
            Db::name('notices')->where('id', $id)->update($data);
        } else {
            $data['admin_id'] = $adminId;
            $data['created_at'] = date('Y-m-d H:i:s');
            Db::name('notices')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功', 'data' => new \stdClass()]);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        Db::name('notices')->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功', 'data' => new \stdClass()]);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $notice = Db::name('notices')->find($id);
        return json(['code' => 0, 'msg' => '', 'data' => $notice ?: new \stdClass()]);
    }
}
