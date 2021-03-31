<?php
declare (strict_types = 1);

namespace app\common\controller;

use think\facade\View;

class Index
{

    public function index(){

        return View::fetch('index');
    }
}
