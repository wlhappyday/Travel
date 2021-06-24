<?php
use think\facade\Route;
//产品模块
Route::group('index', function () {
    Route::get('index', 'Index/index');
    Route::get('search', 'Index/search');
    Route::get('userinfo', 'Index/userinfo');

});
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::get('detail', 'Product/detail');
    Route::get('orderdetail', 'Product/orderdetail');
    Route::get('passenger', 'Product/passenger');
    Route::post('userinfoadd', 'Product/userinfoadd');
    Route::post('userinfodel', 'Product/userinfodel');
    Route::get('userinfodetail', 'Product/userinfodetail');
    Route::post('userinfoedit', 'Product/userinfoedit');
});
Route::group('order', function () {
    Route::post('orderadd', 'Order/orderadd');
    Route::get('orderlist', 'Order/orderlist');
});
?>