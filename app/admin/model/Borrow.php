<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * 借阅记录模型
 */
class Borrow extends Model
{
    protected $name = 'borrow';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';
    protected $updateTime = false;

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function reader()
    {
        return $this->belongsTo(Reader::class, 'reader_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
