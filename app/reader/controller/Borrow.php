<?php
declare(strict_types=1);
namespace app\reader\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Borrow extends BaseController
{
    // 我的借阅记录
    public function index()
    {
        $readerId = Session::get('reader_id');
        $tab = $this->request->param('tab', 'current');
        $page = max(1, (int)$this->request->param('page', 1));
        $limit = 10;
        $today = date('Y-m-d');

        $query = Db::name('borrow')->alias('b')
            ->leftJoin('books bk', 'b.book_id = bk.id')
            ->where('b.reader_id', $readerId)
            ->field('b.*, bk.title as book_title, bk.author as book_author, bk.cover');

        if ($tab === 'current') {
            $query->where('b.status', 0);
        } elseif ($tab === 'returned') {
            $query->where('b.status', 1);
        }

        $total = (clone $query)->count();
        $list = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();
        $totalPages = (int)ceil($total / $limit);

        // 我的借阅申请
        $applyList = Db::name('borrow_apply')->alias('a')
            ->leftJoin('books bk', 'a.book_id = bk.id')
            ->where('a.reader_id', $readerId)
            ->field('a.*, bk.title as book_title')
            ->order('a.id', 'desc')
            ->limit(20)
            ->select()->toArray();

        // 我的续借申请
        $renewList = Db::name('renew_apply')->alias('r')
            ->leftJoin('borrow b', 'r.borrow_id = b.id')
            ->leftJoin('books bk', 'b.book_id = bk.id')
            ->where('r.reader_id', $readerId)
            ->field('r.*, bk.title as book_title, b.due_date as old_due_date')
            ->order('r.id', 'desc')
            ->limit(20)
            ->select()->toArray();

        View::assign(compact('list', 'tab', 'page', 'total', 'totalPages', 'today', 'applyList', 'renewList'));
        return View::fetch();
    }

    // 提交续借申请
    public function renew()
    {
        $borrowId = (int)$this->request->post('borrow_id', 0);
        $renewDays = (int)$this->request->post('renew_days', 14);
        $readerId = Session::get('reader_id');

        $borrow = Db::name('borrow')->where('id', $borrowId)->where('reader_id', $readerId)->where('status', 0)->find();
        if (!$borrow) return json(['code' => 1, 'msg' => '借阅记录不存在', 'data' => new \stdClass()]);

        // 检查是否已有待审核的续借申请
        $existing = Db::name('renew_apply')->where('borrow_id', $borrowId)->where('status', 0)->find();
        if ($existing) return json(['code' => 1, 'msg' => '已提交过续借申请，请等待审核', 'data' => new \stdClass()]);

        Db::name('renew_apply')->insert([
            'borrow_id'  => $borrowId,
            'reader_id'  => $readerId,
            'renew_days' => max(7, min(30, $renewDays)),
            'status'     => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '续借申请已提交，请等待管理员审核', 'data' => new \stdClass()]);
    }
}
