<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * 读者模型
 */
class Reader extends Model
{
    protected $name = 'readers';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';
    protected $updateTime = false;

    public function borrows()
    {
        return $this->hasMany(Borrow::class, 'reader_id');
    }
}
