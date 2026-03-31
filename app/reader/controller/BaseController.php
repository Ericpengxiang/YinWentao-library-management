<?php
declare(strict_types=1);
namespace app\reader\controller;

use think\facade\Session;
use think\facade\View;

class BaseController extends \app\BaseController
{
    protected function initialize()
    {
        $readerId = Session::get('reader_id');
        if ($readerId) {
            View::assign([
                'reader_id'   => $readerId,
                'reader_name' => Session::get('reader_name'),
                'card_no'     => Session::get('reader_card'),
            ]);
        }
    }
}
