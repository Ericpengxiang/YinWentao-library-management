<?php
declare(strict_types=1);
namespace app\reader\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Book extends BaseController
{
    public function index()
    {
        $keyword = trim((string)$this->request->param('keyword', ''));
        $categoryId = (int)$this->request->param('category_id', 0);
        $page = max(1, (int)$this->request->param('page', 1));
        $limit = 12;

        $query = Db::name('books')->alias('b')
            ->leftJoin('category c', 'b.category_id = c.id')
            ->where('b.status', 1)
            ->field('b.*, c.name as category_name');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $k = '%' . $keyword . '%';
                $q->where('b.title', 'like', $k)
                  ->whereOr('b.author', 'like', $k)
                  ->whereOr('b.isbn', 'like', $k);
            });
        }
        if ($categoryId) {
            $query->where('b.category_id', $categoryId);
        }

        $total = (clone $query)->count();
        $books = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();
        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        $totalPages = (int)ceil($total / $limit);

        View::assign(compact('books', 'categories', 'keyword', 'categoryId', 'page', 'total', 'totalPages', 'limit'));
        return View::fetch();
    }

    public function apply()
    {
        $bookId = (int)$this->request->post('book_id', 0);
        $wantDays = (int)$this->request->post('want_days', 14);
        $readerId = Session::get('reader_id');

        if (!$bookId) return json(['code' => 1, 'msg' => '参数错误', 'data' => new \stdClass()]);

        $book = Db::name('books')->where('id', $bookId)->where('status', 1)->find();
        if (!$book) return json(['code' => 1, 'msg' => '图书不存在或已下架', 'data' => new \stdClass()]);
        if ($book['stock'] <= 0) return json(['code' => 1, 'msg' => '该图书库存不足，暂无法借阅', 'data' => new \stdClass()]);

        // 检查是否已有待审核或已借阅的申请
        $existing = Db::name('borrow_apply')
            ->where('reader_id', $readerId)
            ->where('book_id', $bookId)
            ->where('status', 0)
            ->find();
        if ($existing) return json(['code' => 1, 'msg' => '您已提交过该图书的借阅申请，请等待审核', 'data' => new \stdClass()]);

        $alreadyBorrowing = Db::name('borrow')
            ->where('reader_id', $readerId)
            ->where('book_id', $bookId)
            ->where('status', 0)
            ->find();
        if ($alreadyBorrowing) return json(['code' => 1, 'msg' => '您当前已借阅该图书', 'data' => new \stdClass()]);

        // 检查借阅上限
        $reader = Db::name('readers')->find($readerId);
        $currentCount = Db::name('borrow')->where('reader_id', $readerId)->where('status', 0)->count();
        if ($currentCount >= $reader['max_borrow']) {
            return json(['code' => 1, 'msg' => '您已达到最大借阅数量（' . $reader['max_borrow'] . '本），请先归还后再申请', 'data' => new \stdClass()]);
        }

        Db::name('borrow_apply')->insert([
            'reader_id'  => $readerId,
            'book_id'    => $bookId,
            'apply_date' => date('Y-m-d'),
            'want_days'  => max(7, min(30, $wantDays)),
            'status'     => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '借阅申请已提交，请等待管理员审核', 'data' => new \stdClass()]);
    }
}
