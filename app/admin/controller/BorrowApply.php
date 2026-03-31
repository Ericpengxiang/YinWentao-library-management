<?php
declare(strict_types=1);
namespace app\admin\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class BorrowApply extends BaseController
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

        $query = Db::name('borrow_apply')->alias('a')
            ->leftJoin('readers r', 'a.reader_id = r.id')
            ->leftJoin('books b', 'a.book_id = b.id')
            ->field('a.*, r.name as reader_name, r.card_no, b.title as book_title, b.stock');

        if ($status !== '' && $status !== null) {
            $query->where('a.status', (int)$status);
        }

        $total = (clone $query)->count();
        $list = $query->order('a.id', 'desc')->page($page, $limit)->select()->toArray();

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
    }

    // 审核通过：自动创建借阅记录
    public function approve()
    {
        $id = (int)$this->request->post('id', 0);
        $remark = trim((string)$this->request->post('remark', ''));
        $adminId = Session::get('admin_id');

        $apply = Db::name('borrow_apply')->find($id);
        if (!$apply || $apply['status'] != 0) {
            return json(['code' => 1, 'msg' => '申请不存在或已处理', 'data' => new \stdClass()]);
        }

        $book = Db::name('books')->find($apply['book_id']);
        if (!$book || $book['stock'] <= 0) {
            return json(['code' => 1, 'msg' => '图书库存不足，无法批准', 'data' => new \stdClass()]);
        }

        Db::startTrans();
        try {
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+' . $apply['want_days'] . ' day'));

            Db::name('borrow')->insert([
                'book_id'     => $apply['book_id'],
                'reader_id'   => $apply['reader_id'],
                'admin_id'    => $adminId,
                'borrow_date' => $borrowDate,
                'due_date'    => $dueDate,
                'status'      => 0,
                'remark'      => $remark,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            Db::name('books')->where('id', $apply['book_id'])->dec('stock', 1)->update();
            Db::name('readers')->where('id', $apply['reader_id'])->inc('borrow_count', 1)->update();
            Db::name('borrow_apply')->where('id', $id)->update([
                'status'       => 1,
                'admin_id'     => $adminId,
                'admin_remark' => $remark,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
            return json(['code' => 0, 'msg' => '已批准，借阅记录已创建', 'data' => new \stdClass()]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '操作失败：' . $e->getMessage(), 'data' => new \stdClass()]);
        }
    }

    // 拒绝申请
    public function reject()
    {
        $id = (int)$this->request->post('id', 0);
        $remark = trim((string)$this->request->post('remark', ''));
        $adminId = Session::get('admin_id');

        $apply = Db::name('borrow_apply')->find($id);
        if (!$apply || $apply['status'] != 0) {
            return json(['code' => 1, 'msg' => '申请不存在或已处理', 'data' => new \stdClass()]);
        }

        Db::name('borrow_apply')->where('id', $id)->update([
            'status'       => 2,
            'admin_id'     => $adminId,
            'admin_remark' => $remark,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '已拒绝', 'data' => new \stdClass()]);
    }
}
