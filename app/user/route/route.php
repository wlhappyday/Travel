<?php
use think\facade\Route;
//产品模块
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::post('productrelatioedit', 'Product/product_relatio_edit');
    Route::get('productrelatiolist', 'Product/product_relatio_list');
    Route::post('productuseradd', 'Product/product_user_add');
    Route::post('productstatus', 'Product/product_status');
    Route::post('hot', 'Product/hot');
    Route::get('listdetails', 'Product/list_details');
    Route::post('disassociate', 'Product/disassociate');
    Route::get('platformdetails', 'Product/platform_details');
});
Route::group('systems', function () {
    Route::get('index', 'Systems/index');
    Route::get('config', 'Systems/config');
    Route::post('configdo', 'Systems/config_do');

});
Route::group('order', function () {
    Route::get('productlist', 'Order/product_list');
    Route::post('orderadd', 'Order/order_add');
    Route::get('orderlist', 'Order/order_list');
    Route::get('orderdetail', 'Order/order_detail');//订单详情
    Route::post('ordercouponprice', 'Order/ordercouponprice');//订单详情
});

//賬戶中心
Route::group('account', function () {
    Route::get('personal', 'Account/personal');/*个人信息*/
    Route::get('enterprise', 'Account/enterprise');/*个人信息*/
    Route::post('personal_save', 'Account/personalsave');/*个人信息保存*/
    Route::post('enterprise_save', 'Account/enterprisesave');/*企业信息保存*/
    Route::post('password', 'Account/password');/*企业信息保存*/
    Route::get('Balancerecords', 'Account/Balancerecords');/*个人信息*/

});

?>