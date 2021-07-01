<?php
use think\facade\Route;
//产品模块
Route::group('index', function () {
    Route::POST('index', 'Index/index');
    Route::get('search', 'Index/search');
    Route::get('userinfo', 'Index/userinfo');
    Route::POST('tabBar', 'Index/tabBar');

});
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::post('detail', 'Product/detail');
    Route::get('orderdetail', 'Product/orderdetail');
    Route::get('passenger', 'Product/passenger');
    Route::post('userinfoadd', 'Product/userinfoadd');
    Route::post('userinfodel', 'Product/userinfodel');
    Route::get('userinfodetail', 'Product/userinfodetail');
    Route::post('userinfoedit', 'Product/userinfoedit');
    Route::post('collection', 'Product/collection');
    Route::get('posterlist', 'Product/poster_list');
    Route::get('posterdetail', 'Product/poster_detail');
    Route::get('details', 'Product/details');
    Route::get('collection', 'Product/collectionlist');
    Route::post('collectiondelete', 'Product/collectiondelete');
    Route::get('footprint', 'Product/footprint');
    Route::get('productclass', 'Product/product_class');

});
Route::group('order', function () {
    Route::post('orderadd', 'Order/orderadd');
    Route::get('orderlist', 'Order/orderlist');
     Route::get('orderdetail', 'Order/orderdetail');
});
?>