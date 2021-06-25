<?php
use think\facade\Route;
//产品模块
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::post('details', 'Product/details');
    Route::post('relation', 'Product/relation');
    Route::get('relation_products', 'Product/relationproducts');
    Route::post('disassociate', 'Product/disassociate');
    Route::post('platformdetails', 'Product/platform_details');
    Route::post('edit', 'Product/edit');
    Route::get('reviewlist', 'Product/review_list');
    Route::post('reviewstate', 'Product/review_state');
    Route::get('previewlist', 'Product/preview_list');
    Route::post('productstate', 'Product/product_state');

});
//用户模块
Route::group('user', function () {
    Route::post('list', 'User/list');
    Route::post('create', 'User/create');
    Route::post('delete', 'User/delete');
    Route::post('userrate', 'User/userrate');

});
//賬戶中心
Route::group('account', function () {
    Route::post('personal', 'Account/personal');/*个人信息*/
    Route::post('enterprise', 'Account/enterprise');/*个人信息*/
    Route::post('personal_save', 'Account/personalsave');/*个人信息保存*/
    Route::post('enterprise_save', 'Account/enterprisesave');/*企业信息保存*/
    Route::post('password', 'Account/password');/*企业信息保存*/
    Route::get('Balancerecords', 'Account/Balancerecords');/*个人信息*/
    Route::get('signinLog', 'Account/signinLog');/*登录日志*/
    Route::get('operationLog', 'Account/operationLog');/*操作日志*/
});

//用户模块
Route::group('systems', function () {
    Route::get('list', 'Systems/list');
});

//订单
Route::group('order', function () {
    Route::get('list', 'Order/list');
    Route::get('detail', 'Order/Detail');
});
?>