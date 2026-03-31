<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\model\Book as BookModel;
use app\admin\model\Borrow as BorrowModel;
use app\admin\model\Reader as ReaderModel;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

/**
 * 借阅与还书
 */
class Borrow extends BaseController
{
    /**
     * 借阅管理页
     */
    public function index()
    {
        return View::fetch();
    }

    /**
     * 列表数据（Tab：all / borrowing / overdue / returned + 关键词）
     */
    public function listJson()
    {
        $page = max(1, (int) $this->request->param('page', 1));
        $limit = 10;
        $tab = (string) $this->request->param('tab', 'all');
        $keyword = trim((string) $this->request->param('keyword', ''));

        $today = date('Y-m-d');

        $query = Db::name('borrow')->alias('br')
            ->leftJoin('books b', 'br.book_id = b.id')
            ->leftJoin('readers r', 'br.reader_id = r.id')
            ->field('br.*, b.title AS book_title, b.isbn, r.name AS reader_name, r.card_no');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $k = '%' . $keyword . '%';
                $q->where('b.title', 'like', $k)
                    ->whereOr('b.isbn', 'like', $k)
                    ->whereOr('r.name', 'like', $k)
                    ->whereOr('r.card_no', 'like', $k);
            });
        }

        if ($tab === 'borrowing') {
            $query->where('br.status', 0);
        } elseif ($tab === 'overdue') {
            $query->where('br.status', 0)->where('br.due_date', '<', $today);
        } elseif ($tab === 'returned') {
            $query->where('br.status', 1);
        }

        $total = (int) (clone $query)->count();
        $list = $query->order('br.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $rows = [];
        foreach ($list as $row) {
            $isOverdue = ((int) $row['status'] === 0 && $row['due_date'] < $today);
            $rows[] = [
                'id'          => (int) $row['id'],
                'book_id'     => (int) $row['book_id'],
                'reader_id'   => (int) $row['reader_id'],
                'book_title'  => $row['book_title'],
                'isbn'        => $row['isbn'],
                'reader_name' => $row['reader_name'],
                'card_no'     => $row['card_no'],
                'borrow_date' => $row['borrow_date'],
                'due_date'    => $row['due_date'],
                'return_date' => $row['return_date'],
                'status'      => (int) $row['status'],
                'remark'      => $row['remark'],
                'is_overdue'  => $isOverdue ? 1 : 0,
            ];
        }

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $rows]);
    }

    /**
     * 借书：弹窗内搜索图书（防抖由前端控制）
     */
    public function searchBooks()
    {
        $q = trim((string) $this->request->param('q', ''));
        if ($q === '') {
            return json(['code' => 0, 'msg' => '', 'data' => []]);
        }
        $list = BookModel::where('status', 1)
            ->where(function ($query) use ($q) {
                $k = '%' . $q . '%';
                $query->where('title', 'like', $k)
                    ->whereOr('isbn', 'like', $k)
                    ->whereOr('author', 'like', $k);
            })
            ->field('id,isbn,title,author,available')
            ->limit(20)
            ->select();

        $data = [];
        foreach ($list as $b) {
            $data[] = [
                'id'        => $b->id,
                'isbn'      => $b->isbn,
                'title'     => $b->title,
                'author'    => $b->author,
                'available' => (int) $b->available,
            ];
        }
        return json(['code' => 0, 'msg' => '', 'data' => $data]);
    }

    /**
     * 搜索读者
     */
    public function searchReaders()
    {
        $q = trim((string) $this->request->param('q', ''));
        if ($q === '') {
            return json(['code' => 0, 'msg' => '', 'data' => []]);
        }
        $list = ReaderModel::where('status', 1)
            ->where(function ($query) use ($q) {
                $k = '%' . $q . '%';
                $query->where('name', 'like', $k)
                    ->whereOr('card_no', 'like', $k)
                    ->whereOr('phone', 'like', $k);
            })
            ->field('id,card_no,name,phone,borrow_count,max_borrow')
            ->limit(20)
            ->select();

        $data = [];
        foreach ($list as $r) {
            $data[] = [
                'id'           => $r->id,
                'card_no'      => $r->card_no,
                'name'         => $r->name,
                'phone'        => $r->phone,
                'borrow_count' => (int) $r->borrow_count,
                'max_borrow'   => (int) $r->max_borrow,
            ];
        }
        return json(['code' => 0, 'msg' => '', 'data' => $data]);
    }

    /**
     * 办理借书（事务）
     */
    public function borrowBook()
    {
        $bookId = (int) $this->request->post('book_id', 0);
        $readerId = (int) $this->request->post('reader_id', 0);
        $adminId = (int) Session::get('admin_id');

        if ($bookId <= 0 || $readerId <= 0) {
            return json(['code' => 1, 'msg' => '请选择图书和读者', 'data' => new \stdClass()]);
        }

        try {
            Db::transaction(function () use ($bookId, $readerId, $adminId) {
                $book = BookModel::lock(true)->find($bookId);
                if (!$book || (int) $book->status !== 1) {
                    throw new \RuntimeException('图书不存在或已下架');
                }
                if ((int) $book->available < 1) {
                    throw new \RuntimeException('库存不足，无法借阅');
                }

                $reader = ReaderModel::lock(true)->find($readerId);
                if (!$reader || (int) $reader->status !== 1) {
                    throw new \RuntimeException('读者不存在或已禁用');
                }

                $currentBorrow = (int) BorrowModel::where('reader_id', $readerId)
                    ->where('status', 0)
                    ->count();
                if ($currentBorrow >= (int) $reader->max_borrow) {
                    throw new \RuntimeException('已达到借阅上限');
                }

                $dup = BorrowModel::where('book_id', $bookId)
                    ->where('reader_id', $readerId)
                    ->where('status', 0)
                    ->find();
                if ($dup) {
                    throw new \RuntimeException('该读者已借阅本书且未归还');
                }

                $borrowDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+30 days'));

                $borrow = new BorrowModel();
                $borrow->save([
                    'book_id'     => $bookId,
                    'reader_id'   => $readerId,
                    'admin_id'    => $adminId,
                    'borrow_date' => $borrowDate,
                    'due_date'    => $dueDate,
                    'return_date' => null,
                    'status'      => 0,
                    'remark'      => '',
                ]);

                $book->available = (int) $book->available - 1;
                $book->save();

                $reader->borrow_count = (int) $reader->borrow_count + 1;
                $reader->save();
            });
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage(), 'data' => new \stdClass()]);
        }

        return json(['code' => 0, 'msg' => '借书成功', 'data' => new \stdClass()]);
    }

    /**
     * 办理还书（事务，AJAX）
     */
    public function returnBook()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误', 'data' => new \stdClass()]);
        }

        try {
            Db::transaction(function () use ($id) {
                $rec = BorrowModel::lock(true)->find($id);
                if (!$rec) {
                    throw new \RuntimeException('借阅记录不存在');
                }
                if ((int) $rec->status === 1) {
                    throw new \RuntimeException('该记录已归还');
                }

                $book = BookModel::lock(true)->find($rec->book_id);
                $reader = ReaderModel::lock(true)->find($rec->reader_id);

                $rec->status = 1;
                $rec->return_date = date('Y-m-d');
                $rec->save();

                if ($book) {
                    $book->available = (int) $book->available + 1;
                    $book->save();
                }
                if ($reader && (int) $reader->borrow_count > 0) {
                    $reader->borrow_count = (int) $reader->borrow_count - 1;
                    $reader->save();
                }
            });
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage(), 'data' => new \stdClass()]);
        }

        return json(['code' => 0, 'msg' => '还书成功', 'data' => new \stdClass()]);
    }
}
