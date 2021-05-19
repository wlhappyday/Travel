<?php
use think\facade\Route;
//产品模块
Route::group('index', function () {
    Route::get('index', 'Index/index');
    Route::get('search', 'Index/search');
});
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::get('detail', 'Product/detail');
    Route::get('orderdetail', 'Product/orderdetail');
    Route::get('userInfo', 'Product/userInfo');
    Route::post('userinfoadd', 'Product/userinfoadd');
});
Route::group('order', function () {
    Route::post('orderadd', 'Order/orderadd');
    Route::get('orderlist', 'Order/orderlist');


});
?>