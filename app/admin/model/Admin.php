<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    protected $name = 'admin';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';
    protected $updateTime = false;
}
