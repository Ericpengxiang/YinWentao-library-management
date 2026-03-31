<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * 图书分类模型
 */
class Category extends Model
{
    protected $name = 'category';

    public function books()
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}
