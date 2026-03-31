<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\model\Book as BookModel;
use app\admin\model\Borrow as BorrowModel;
use app\admin\model\Reader as ReaderModel;
use think\facade\Db;
use think\facade\View;

/**
 * 统计首页
 */
class Index extends BaseController
{
    /**
     * 控制台首页
     */
    public function index()
    {
        // 图书总数
        $bookTotal = (int) BookModel::count();
        // 读者总数
        $readerTotal = (int) ReaderModel::count();
        // 借阅中
        $borrowing = (int) BorrowModel::where('status', 0)->count();
        // 超期未还
        $today = date('Y-m-d');
        $overdue = (int) BorrowModel::where('status', 0)
            ->where('due_date', '<', $today)
            ->count();

        // 分类占比（饼图），按 category_id 分组避免 ONLY_FULL_GROUP_BY 问题
        $catRows = Db::name('books')->alias('b')
            ->leftJoin('category c', 'b.category_id = c.id')
            ->fieldRaw('COALESCE(MAX(c.name), \'未分类\') AS name, COUNT(*) AS cnt')
            ->group('b.category_id')
            ->select()
            ->toArray();

        $pieData = [];
        foreach ($catRows as $row) {
            $pieData[] = ['name' => $row['name'], 'value' => (int) $row['cnt']];
        }

        // 近 7 日借阅趋势（按借阅日期统计新增借阅笔数）
        $trendLabels = [];
        $trendValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' day'));
            $trendLabels[] = $d;
            $trendValues[] = (int) BorrowModel::where('borrow_date', $d)->count();
        }

        // 最近 5 条借阅
        $recentBorrows = BorrowModel::with(['book', 'reader'])
            ->order('id', 'desc')
            ->limit(5)
            ->select();

        // 3 天内即将到期（借阅中且 due_date 在 [今天, 今天+3]）
        $dueEnd = date('Y-m-d', strtotime('+3 day'));
        $dueSoon = BorrowModel::with(['book', 'reader'])
            ->where('status', 0)
            ->whereBetween('due_date', [$today, $dueEnd])
            ->order('due_date', 'asc')
            ->limit(20)
            ->select();

        View::assign([
            'bookTotal'     => $bookTotal,
            'readerTotal'   => $readerTotal,
            'borrowing'     => $borrowing,
            'overdue'       => $overdue,
            'pieJson'       => json_encode($pieData, JSON_UNESCAPED_UNICODE),
            'trendLabels'   => json_encode($trendLabels, JSON_UNESCAPED_UNICODE),
            'trendValues'   => json_encode($trendValues, JSON_UNESCAPED_UNICODE),
            'recentBorrows' => $recentBorrows,
            'dueSoon'       => $dueSoon,
        ]);

        return View::fetch();
    }
}
