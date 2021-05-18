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