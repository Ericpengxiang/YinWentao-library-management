<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\model\Borrow as BorrowModel;
use app\admin\model\Reader as ReaderModel;
use think\exception\ValidateException;
use think\facade\View;

/**
 * 读者管理
 */
class Reader extends BaseController
{
    /**
     * 读者列表
     */
    public function index()
    {
        return View::fetch();
    }

    /**
     * Layui 表格数据
     */
    public function listJson()
    {
        $page = max(1, (int) $this->request->param('page', 1));
        $limit = 12;
        $keyword = trim((string) $this->request->param('keyword', ''));
        $status = $this->request->param('status', '');

        $query = ReaderModel::alias('r');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $k = '%' . $keyword . '%';
                $q->where('r.name', 'like', $k)
                    ->whereOr('r.card_no', 'like', $k)
                    ->whereOr('r.phone', 'like', $k);
            });
        }
        if ($status !== '' && $status !== null) {
            $query->where('r.status', (int) $status);
        }

        $total = (clone $query)->count();
        $list = $query->order('r.id', 'desc')
            ->page($page, $limit)
            ->select();

        $rows = [];
        foreach ($list as $item) {
            // 当前借阅数（实时统计未归还）
            $currentBorrow = (int) BorrowModel::where('reader_id', $item->id)
                ->where('status', 0)
                ->count();

            $rows[] = [
                'id'              => $item->id,
                'card_no'         => $item->card_no,
                'name'            => $item->name,
                'gender'          => (int) $item->gender,
                'phone'           => $item->phone,
                'email'           => $item->email,
                'class_name'      => $item->class_name,
                'max_borrow'      => (int) $item->max_borrow,
                'borrow_count'    => (int) $item->borrow_count,
                'current_borrow'  => $currentBorrow,
                'status'          => (int) $item->status,
                'created_at'      => $item->created_at,
            ];
        }

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $rows]);
    }

    /**
     * 添加页
     */
    public function add()
    {
        return View::fetch();
    }

    /**
     * 生成借书证号：R + 7 位数字（按当前最大 id）
     */
    protected function generateCardNo(): string
    {
        $maxId = (int) ReaderModel::max('id');
        $next = $maxId + 1;
        return 'R' . str_pad((string) $next, 7, '0', STR_PAD_LEFT);
    }

    /**
     * 保存新增
     */
    public function saveAdd()
    {
        $data = $this->request->only([
            'name', 'gender', 'phone', 'email', 'class_name', 'max_borrow',
        ]);

        try {
            $this->validate($data, \app\admin\validate\ReaderValidate::class . '.add');
        } catch (ValidateException $e) {
            return json(['code' => 1, 'msg' => $e->getError(), 'data' => new \stdClass()]);
        }

        $cardNo = $this->generateCardNo();
        if (ReaderModel::where('card_no', $cardNo)->find()) {
            // 极端情况重试一次
            $cardNo = $this->generateCardNo() . 'X';
        }

        $reader = new ReaderModel();
        $reader->save([
            'card_no'      => $cardNo,
            'name'         => $data['name'],
            'gender'       => (int) $data['gender'],
            'phone'        => $data['phone'],
            'email'        => (string) ($data['email'] ?? ''),
            'class_name'   => $data['class_name'],
            'max_borrow'   => (int) $data['max_borrow'],
            'borrow_count' => 0,
            'status'       => 1,
        ]);

        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['card_no' => $cardNo]]);
    }

    /**
     * 启用/禁用（AJAX）
     */
    public function toggleStatus()
    {
        $id = (int) $this->request->post('id', 0);
        $reader = ReaderModel::find($id);
        if (!$reader) {
            return json(['code' => 1, 'msg' => '读者不存在', 'data' => new \stdClass()]);
        }
        $reader->status = $reader->status ? 0 : 1;
        $reader->save();
        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['status' => (int) $reader->status],
        ]);
    }
}
