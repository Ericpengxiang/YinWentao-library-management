<?php
declare(strict_types=1);
namespace app\reader\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        $readerId = Session::get('reader_id');
        $reader = Db::name('readers')->find($readerId);

        // 当前借阅中
        $borrowing = Db::name('borrow')->alias('b')
            ->leftJoin('books bk', 'b.book_id = bk.id')
            ->where('b.reader_id', $readerId)
            ->where('b.status', 0)
            ->field('b.*, bk.title as book_title, bk.author as book_author, bk.isbn')
            ->order('b.id', 'desc')
            ->select()->toArray();

        // 超期数
        $today = date('Y-m-d');
        $overdueCount = 0;
        foreach ($borrowing as $b) {
            if ($b['due_date'] < $today) $overdueCount++;
        }

        // 待审核申请数
        $pendingApply = Db::name('borrow_apply')->where('reader_id', $readerId)->where('status', 0)->count();
        $pendingRenew = Db::name('renew_apply')->where('reader_id', $readerId)->where('status', 0)->count();

        // 最新公告
        $notices = Db::name('notices')->where('status', 1)->order('id', 'desc')->limit(3)->select()->toArray();

        // 历史借阅总数
        $historyTotal = Db::name('borrow')->where('reader_id', $readerId)->count();

        View::assign([
            'reader'       => $reader,
            'borrowing'    => $borrowing,
            'overdueCount' => $overdueCount,
            'pendingApply' => $pendingApply,
            'pendingRenew' => $pendingRenew,
            'notices'      => $notices,
            'historyTotal' => $historyTotal,
            'today'        => $today,
        ]);
        return View::fetch();
    }
}
