<?php

use think\facade\Route;
Route::group('product', function () {
    Route::get('list', 'Product/list');
    Route::post('relation', 'Product/relation');
    Route::get('relation_products', 'Product/relationproducts');
});

?>