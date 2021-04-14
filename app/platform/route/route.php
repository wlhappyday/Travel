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
//賬戶中心
Route::group('account', function () {
    Route::post('personal', 'Account/personal');/*个人信息*/
    Route::post('enterprise', 'Account/enterprise');/*个人信息*/
    Route::post('personal_save', 'Account/personalsave');/*个人信息保存*/
    Route::post('enterprise_save', 'Account/enterprisesave');/*企业信息保存*/
    Route::post('password', 'Account/password');/*企业信息保存*/
});

?>