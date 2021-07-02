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
    Route::get('poster', 'Product/poster');
    Route::get('posterimg', 'Product/posterimg');
    Route::get('previewlist', 'Product/preview_list');
    Route::get('posterdetail', 'Product/poster_detail');
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
    Route::get('download', 'Order/download');//订单详情


});

//賬戶中心
Route::group('account', function () {
    Route::get('personal', 'Account/personal');/*个人信息*/
    Route::get('enterprise', 'Account/enterprise');/*个人信息*/
    Route::post('personal_save', 'Account/personalsave');/*个人信息保存*/
    Route::post('enterprise_save', 'Account/enterprisesave');/*企业信息保存*/
    Route::post('password', 'Account/password');/*企业信息保存*/
    Route::get('Balancerecords', 'Account/Balancerecords');/*个人信息*/
    Route::get('puseruser', 'Account/puseruser');/*个人信息*/

});

//小程序管理
Route::group('applets', function () {
    Route::get('templatemessagelist', 'Applets/templatemessage_list');/*模板消息列表*/
    Route::post('templatemessage', 'Applets/templatemessage');/*模板消息添加修改*/


    Route::get('carousellist', 'Applets/carousel_list');/*轮播图*/
    Route::get('carouseldetail', 'Applets/carouseldetail');/*轮播图*/
    Route::post('carousel', 'Applets/carousel');/*轮播图添加/修改*/
    Route::post('carouseldel', 'Applets/carousel_del');/*轮播图删除*/

    Route::get('homenavigationlist', 'Applets/homenavigation_list');/*导航图标列表*/
    Route::post('homenavigationtype', 'Applets/homenavigation_type');/*导航图标列表*/
    Route::get('homenavigationdetail', 'Applets/homenavigation_detail');/*导航图标详情*/
    Route::post('homenavigation', 'Applets/homenavigation');/*导航图标添加/修改*/
    Route::post('homenavigationdel', 'Applets/homenavigation_del');/*导航图标删除*/

    Route::get('magic', 'Applets/magic');/*图片魔方*/
    Route::post('magicdo', 'Applets/magic_do');/*图片魔方peizhi*/
    Route::get('magicdetail', 'Applets/magic_detail');/*图片魔方*/
    Route::post('magicdelete', 'Applets/magic_delete');/*图片魔方*/
    Route::get('page', 'Applets/page');/*图片魔方*/
    Route::get('product', 'Applets/product');/*图片魔方*/
    Route::get('my', 'Applets/my');/*图片魔方*/
    Route::post('mydo', 'Applets/my_do');/*图片魔方*/

    Route::get('navigationlist', 'Applets/navigationlist');/*底部导航栏列表*/
    Route::get('navigationdetail', 'Applets/navigation_detail');/*底部导航栏詳情*/
    Route::post('navigation', 'Applets/navigation');/*底部导航修改*/
    Route::post('navigations', 'Applets/navigations');/*导航栏修改*/

    Route::post('mytype', 'Applets/my_type');/*导航栏修改*/
    Route::get('mydetail', 'Applets/my_detail');/*底部导航栏列表*/

    Route::post('notice', 'Applets/notices');/*导航栏修改*/


});
//分销
Route::group('distribution', function () {
    Route::get('userlist', 'Distribution/userlist');/*分销商列表*/
    Route::POST('isDistcenter', 'Distribution/isDistcenter');/*审核分销商*/
    Route::POST('distcenterPrice', 'Distribution/distcenterPrice');/*修改分销商比例*/
});

?>