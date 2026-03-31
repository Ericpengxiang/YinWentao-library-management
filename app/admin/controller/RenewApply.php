<?php
declare(strict_types=1);
namespace app\admin\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class RenewApply extends BaseController
{
    public function index()
    {
        return View::fetch();
    }

    public function listJson()
    {
        $page = max(1, (int)$this->request->param('page', 1));
        $limit = 15;
        $status = $this->request->param('status', '');

        $query = Db::name('renew_apply')->alias('ra')
            ->leftJoin('borrow b', 'ra.borrow_id = b.id')
            ->leftJoin('readers r', 'ra.reader_id = r.id')
            ->leftJoin('books bk', 'b.book_id = bk.id')
            ->field('ra.*, r.name as reader_name, r.card_no, bk.title as book_title, b.due_date as old_due_date');

        if ($status !== '' && $status !== null) {
            $query->where('ra.status', (int)$status);
        }

        $total = (clone $query)->count();
        $list = $query->order('ra.id', 'desc')->page($page, $limit)->select()->toArray();

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
    }

    public function approve()
    {
        $id = (int)$this->request->post('id', 0);
        $remark = trim((string)$this->request->post('remark', ''));
        $adminId = Session::get('admin_id');

        $apply = Db::name('renew_apply')->find($id);
        if (!$apply || $apply['status'] != 0) {
            return json(['code' => 1, 'msg' => '申请不存在或已处理', 'data' => new \stdClass()]);
        }

        $borrow = Db::name('borrow')->find($apply['borrow_id']);
        if (!$borrow || $borrow['status'] != 0) {
            return json(['code' => 1, 'msg' => '借阅记录不存在或已归还', 'data' => new \stdClass()]);
        }

        $newDueDate = date('Y-m-d', strtotime($borrow['due_date'] . ' +' . $apply['renew_days'] . ' day'));

        Db::startTrans();
        try {
            Db::name('borrow')->where('id', $apply['borrow_id'])->update(['due_date' => $newDueDate]);
            Db::name('renew_apply')->where('id', $id)->update([
                'status'       => 1,
                'admin_id'     => $adminId,
                'admin_remark' => $remark,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
            return json(['code' => 0, 'msg' => '续借已批准，还书日期已延至 ' . $newDueDate, 'data' => new \stdClass()]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '操作失败', 'data' => new \stdClass()]);
        }
    }

    public function reject()
    {
        $id = (int)$this->request->post('id', 0);
        $remark = trim((string)$this->request->post('remark', ''));
        $adminId = Session::get('admin_id');

        $apply = Db::name('renew_apply')->find($id);
        if (!$apply || $apply['status'] != 0) {
            return json(['code' => 1, 'msg' => '申请不存在或已处理', 'data' => new \stdClass()]);
        }

        Db::name('renew_apply')->where('id', $id)->update([
            'status'       => 2,
            'admin_id'     => $adminId,
            'admin_remark' => $remark,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '已拒绝续借申请', 'data' => new \stdClass()]);
    }
}
