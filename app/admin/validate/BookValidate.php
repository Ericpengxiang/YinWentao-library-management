<?php
declare (strict_types = 1);

namespace app\admin\validate;

use think\Validate;

/**
 * 图书表单验证
 */
class BookValidate extends Validate
{
    protected $rule = [
        'isbn'         => 'require|max:30',
        'title'        => 'require|max:200',
        'author'       => 'require|max:100',
        'publisher'    => 'require|max:100',
        'category_id'  => 'require|integer|gt:0',
        'total'        => 'require|integer|egt:0',
        'available'    => 'integer|egt:0',
        'price'        => 'require|number|egt:0',
        'publish_date' => 'date',
        'description'  => 'max:65535',
        'status'       => 'in:0,1',
    ];

    protected $message = [
        'isbn.require'        => '请填写 ISBN',
        'title.require'       => '请填写书名',
        'author.require'      => '请填写作者',
        'publisher.require'   => '请填写出版社',
        'category_id.require' => '请选择分类',
        'total.require'       => '请填写库存总数',
        'price.require'       => '请填写定价',
    ];

    protected $scene = [
        'add'  => ['isbn', 'title', 'author', 'publisher', 'category_id', 'total', 'available', 'price', 'publish_date', 'description', 'status'],
        'edit' => ['isbn', 'title', 'author', 'publisher', 'category_id', 'total', 'available', 'price', 'publish_date', 'description', 'status'],
    ];
}
