<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * 图书模型
 */
class Book extends Model
{
    protected $name = 'books';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';
    protected $updateTime = false;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class, 'book_id');
    }
}
