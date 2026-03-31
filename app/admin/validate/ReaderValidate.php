<?php
declare (strict_types = 1);

namespace app\admin\validate;

use think\Validate;

/**
 * 读者表单验证
 */
class ReaderValidate extends Validate
{
    protected $rule = [
        'name'       => 'require|max:50',
        'gender'     => 'require|in:1,2',
        'phone'      => 'require|max:20',
        'email'      => 'max:100',
        'class_name' => 'require|max:100',
        'max_borrow' => 'require|integer|between:1,20',
    ];

    protected $message = [
        'name.require'       => '请填写姓名',
        'phone.require'      => '请填写手机号',
        'class_name.require' => '请填写班级',
        'email.email'        => '邮箱格式不正确',
    ];

    protected $scene = [
        'add' => ['name', 'gender', 'phone', 'email', 'class_name', 'max_borrow'],
    ];
}
