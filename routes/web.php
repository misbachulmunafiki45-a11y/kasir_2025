<?php

use Illuminate\Support\Facades\Route;

//route home
Route::get('/', function () {
    return \Inertia\Inertia::render('Auth/Login');
})->middleware('guest');

//prefix "apps"
Route::prefix('apps')->group(function() {

    //middleware "auth"
    Route::group(['middleware' => ['auth']], function () {

        //route dashboard
        Route::get('dashboard', App\Http\Controllers\Apps\DashboardController::class)->name('apps.dashboard');

        //route permissions
        Route::get('/permissions', [\App\Http\Controllers\Apps\PermissionController::class, 'index'])->name('apps.permissions.index');

        //route resource roles
        Route::resource('/roles', \App\Http\Controllers\Apps\RoleController::class, ['as' => 'apps']);

        //route resource users
        Route::resource('/users', \App\Http\Controllers\Apps\UserController::class, ['as' => 'apps']);

        //route resource categories
        Route::resource('/categories', \App\Http\Controllers\Apps\CategoryController::class, ['as' => 'apps']);

        //route resource products
        Route::resource('/products', \App\Http\Controllers\Apps\ProductController::class, ['as' => 'apps']);

        //route stocks
        Route::get('/stocks', [\App\Http\Controllers\Apps\StockController::class, 'index'])->name('apps.stocks.index');
Route::put('/stocks/{product}', [\App\Http\Controllers\Apps\StockController::class, 'update'])->name('apps.stocks.update');
Route::post('/stocks/{product}/add', [\App\Http\Controllers\Apps\StockController::class, 'add'])->name('apps.stocks.add');

        //route resource customers
        Route::resource('/customers', \App\Http\Controllers\Apps\CustomerController::class, ['as' => 'apps']);

        //route transaction
        Route::get('/transactions', [\App\Http\Controllers\Apps\TransactionController::class, 'index'])->name('apps.transactions.index');

        //route transaction searchProduct
        Route::post('/transactions/searchProduct', [\App\Http\Controllers\Apps\TransactionController::class, 'searchProduct'])->name('apps.transactions.searchProduct');

        //route transaction addToCart
        Route::post('/transactions/addToCart', [\App\Http\Controllers\Apps\TransactionController::class, 'addToCart'])->name('apps.transactions.addToCart');

        //route transaction destroyCart
        Route::post('/transactions/destroyCart', [\App\Http\Controllers\Apps\TransactionController::class, 'destroyCart'])->name('apps.transactions.destroyCart');

        //route transaction store
        Route::post('/transactions/store', [\App\Http\Controllers\Apps\TransactionController::class, 'store'])->name('apps.transactions.store');

        //route transaction print
        Route::get('/transactions/print', [\App\Http\Controllers\Apps\TransactionController::class, 'print'])->name('apps.transactions.print');

        //route sales index
        Route::get('/sales', [\App\Http\Controllers\Apps\SaleController::class, 'index'])->name('apps.sales.index');

        //route sales filter
        Route::get('/sales/filter', [\App\Http\Controllers\Apps\SaleController::class, 'filter'])->name('apps.sales.filter');

        //route sales export excel
        Route::get('/sales/export', [\App\Http\Controllers\Apps\SaleController::class, 'export'])->name('apps.sales.export');

        //route sales print pdf
        Route::get('/sales/pdf', [\App\Http\Controllers\Apps\SaleController::class, 'pdf'])->name('apps.sales.pdf');

        //route profits index
        Route::get('/profits', [\App\Http\Controllers\Apps\ProfitController::class, 'index'])->name('apps.profits.index');

        //route profits filter
        Route::get('/profits/filter', [\App\Http\Controllers\Apps\ProfitController::class, 'filter'])->name('apps.profits.filter');

        //route profits export
        Route::get('/profits/export', [\App\Http\Controllers\Apps\ProfitController::class, 'export'])->name('apps.profits.export');

        //route profits pdf
        Route::get('/profits/pdf', [\App\Http\Controllers\Apps\ProfitController::class, 'pdf'])->name('apps.profits.pdf');

        //route stocks index already defined via resource elsewhere
        // add filter route for stocks (date range)
        Route::get('/stocks/filter', [\App\Http\Controllers\Apps\StockController::class, 'filter'])->name('apps.stocks.filter');

        //route stocks export excel
        Route::get('/stocks/export', [\App\Http\Controllers\Apps\StockController::class, 'export'])->name('apps.stocks.export');

        //route stocks pdf
        Route::get('/stocks/pdf', [\App\Http\Controllers\Apps\StockController::class, 'pdf'])->name('apps.stocks.pdf');
    });
});
