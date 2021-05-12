<?php
use think\facade\Route;
//产品模块
Route::group('index', function () {
    Route::get('index', 'Index/index');
});
?>