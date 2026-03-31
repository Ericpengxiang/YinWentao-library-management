<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\model\Book as BookModel;
use app\admin\model\Borrow as BorrowModel;
use app\admin\model\Category;
use think\exception\ValidateException;
use think\facade\View;

/**
 * 图书管理
 */
class Book extends BaseController
{
    /**
     * 图书列表页
     */
    public function index()
    {
        $categories = Category::order('id', 'asc')->select();
        View::assign('categories', $categories);
        return View::fetch();
    }

    /**
     * Layui 表格数据
     */
    public function listJson()
    {
        $page = max(1, (int) $this->request->param('page', 1));
        $limit = 10;
        $keyword = trim((string) $this->request->param('keyword', ''));
        $categoryId = (int) $this->request->param('category_id', 0);

        $query = BookModel::alias('b')->with(['category']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $k = '%' . $keyword . '%';
                $q->where('b.title', 'like', $k)
                    ->whereOr('b.author', 'like', $k)
                    ->whereOr('b.isbn', 'like', $k);
            });
        }
        if ($categoryId > 0) {
            $query->where('b.category_id', $categoryId);
        }

        $total = (clone $query)->count();
        $list = $query->order('b.id', 'desc')
            ->page($page, $limit)
            ->select();

        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                'id'           => $item->id,
                'isbn'         => $item->isbn,
                'title'        => $item->title,
                'author'       => $item->author,
                'publisher'    => $item->publisher,
                'category_id'  => $item->category_id,
                'category_name'=> $item->category ? $item->category->name : '',
                'total'        => $item->total,
                'available'    => $item->available,
                'price'        => $item->price,
                'publish_date' => $item->publish_date,
                'status'       => (int) $item->status,
                'created_at'   => $item->created_at,
            ];
        }

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $rows]);
    }

    /**
     * 添加页
     */
    public function add()
    {
        $categories = Category::order('id', 'asc')->select();
        View::assign('categories', $categories);
        return View::fetch();
    }

    /**
     * 保存新增
     */
    public function saveAdd()
    {
        $data = $this->request->only([
            'isbn', 'title', 'author', 'publisher', 'category_id',
            'cover', 'total', 'available', 'price', 'publish_date', 'description', 'status',
        ]);
        try {
            $this->validate($data, \app\admin\validate\BookValidate::class . '.add');
        } catch (ValidateException $e) {
            return json(['code' => 1, 'msg' => $e->getError(), 'data' => new \stdClass()]);
        }

        $total = (int) ($data['total'] ?? 0);
        $avail = isset($data['available']) ? (int) $data['available'] : $total;
        if ($avail > $total) {
            $avail = $total;
        }

        $book = new BookModel();
        $book->save([
            'isbn'          => $data['isbn'],
            'title'         => $data['title'],
            'author'        => $data['author'],
            'publisher'     => $data['publisher'],
            'category_id'   => (int) $data['category_id'],
            'cover'         => (string) ($data['cover'] ?? ''),
            'total'         => $total,
            'available'     => $avail,
            'price'         => $data['price'],
            'publish_date'  => $data['publish_date'] ?: null,
            'description'   => (string) ($data['description'] ?? ''),
            'status'        => isset($data['status']) ? (int) $data['status'] : 1,
        ]);

        return json(['code' => 0, 'msg' => '添加成功', 'data' => new \stdClass()]);
    }

    /**
     * 编辑页
     */
    public function edit()
    {
        $id = (int) $this->request->param('id', 0);
        $book = BookModel::find($id);
        if (!$book) {
            return '图书不存在';
        }
        $categories = Category::order('id', 'asc')->select();
        View::assign(['book' => $book, 'categories' => $categories]);
        return View::fetch();
    }

    /**
     * 保存编辑
     */
    public function saveEdit()
    {
        $id = (int) $this->request->param('id', 0);
        $book = BookModel::find($id);
        if (!$book) {
            return json(['code' => 1, 'msg' => '图书不存在', 'data' => new \stdClass()]);
        }

        $data = $this->request->only([
            'isbn', 'title', 'author', 'publisher', 'category_id',
            'cover', 'total', 'available', 'price', 'publish_date', 'description', 'status',
        ]);

        try {
            $this->validate($data, \app\admin\validate\BookValidate::class . '.edit');
        } catch (ValidateException $e) {
            return json(['code' => 1, 'msg' => $e->getError(), 'data' => new \stdClass()]);
        }

        $total = (int) ($data['total'] ?? 0);
        $avail = isset($data['available']) ? (int) $data['available'] : $total;
        if ($avail > $total) {
            $avail = $total;
        }
        // 已借出 = total - available 不能为负
        $borrowed = $book->total - $book->available;
        if ($avail < $borrowed) {
            return json(['code' => 1, 'msg' => '可借册数不能小于已借出数量（' . $borrowed . '）', 'data' => new \stdClass()]);
        }

        $book->save([
            'isbn'          => $data['isbn'],
            'title'         => $data['title'],
            'author'        => $data['author'],
            'publisher'     => $data['publisher'],
            'category_id'   => (int) $data['category_id'],
            'cover'         => (string) ($data['cover'] ?? ''),
            'total'         => $total,
            'available'     => $avail,
            'price'         => $data['price'],
            'publish_date'  => $data['publish_date'] ?: null,
            'description'   => (string) ($data['description'] ?? ''),
            'status'        => isset($data['status']) ? (int) $data['status'] : 1,
        ]);

        return json(['code' => 0, 'msg' => '保存成功', 'data' => new \stdClass()]);
    }

    /**
     * 删除（存在未归还借阅则拒绝）
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $book = BookModel::find($id);
        if (!$book) {
            return json(['code' => 1, 'msg' => '图书不存在', 'data' => new \stdClass()]);
        }

        $cnt = BorrowModel::where('book_id', $id)->where('status', 0)->count();
        if ($cnt > 0) {
            return json(['code' => 1, 'msg' => '该书存在未归还记录，无法删除', 'data' => new \stdClass()]);
        }

        $book->delete();
        return json(['code' => 0, 'msg' => '已删除', 'data' => new \stdClass()]);
    }

    /**
     * 上架/下架
     */
    public function toggleStatus()
    {
        $id = (int) $this->request->post('id', 0);
        $book = BookModel::find($id);
        if (!$book) {
            return json(['code' => 1, 'msg' => '图书不存在', 'data' => new \stdClass()]);
        }
        $book->status = $book->status ? 0 : 1;
        $book->save();
        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['status' => (int) $book->status],
        ]);
    }
}
