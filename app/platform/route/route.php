<?php

use think\facade\Route;
//产品模块
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::post('relation', 'Product/relation');
    Route::get('relation_products', 'Product/relationproducts');
    Route::post('disassociate', 'Product/disassociate');
});
//用户模块
Route::group('user', function () {
    Route::post('list', 'User/list');
    Route::post('create', 'User/create');
    Route::post('save', 'User/save');
});

?>