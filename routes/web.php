<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ManualCOntroller;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ManuallController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\NarrationController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\AccountsHeadController;
use App\Http\Controllers\SalesOfficerController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\InwardgatepassController;
use App\Http\Controllers\ProductBookingController;
use App\Http\Controllers\WarehouseStockController;
use App\Http\Controllers\ReportLockController;

// shift to new repo
// onilne deployed hogaya hai                                   
/*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "web" middleware group. Make something great!
    |
    */

Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

Route::get('System/Reports', [HomeController::class, 'System_Reports'])->name('System.Reports')->middleware('permission:System Reports');
Route::get('/category-products/{id}', [HomeController::class, 'categoryProducts']);

// Route::get('/adminpage', [HomeController::class, 'adminpage'])->middleware(['auth','admin'])->name('adminpage');

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    route::get('/category', [CategoryController::class, 'index'])->name('Category.home')->middleware('permission:Category');
    Route::get('/category/delete/{id}', [CategoryController::class, 'delete'])->name('delete.category');
    route::post('/category/stote', [CategoryController::class, 'store'])->name("store.category");

    route::get('/Brand', [BrandController::class, 'index'])->name('Brand.home')->middleware('permission:Brands');
    Route::get('/Brand/delete/{id}', [BrandController::class, 'delete'])->name('delete.Brand');
    route::post('/Brand/stote', [BrandController::class, 'store'])->name("store.Brand");

    route::get('/Unit', [UnitController::class, 'index'])->name('Unit.home');
    Route::get('/Unit/delete/{id}', [UnitController::class, 'delete'])->name('delete.Unit');
    route::post('/Unit/stote', [UnitController::class, 'store'])->name("store.Unit");

    route::get('/subcategory', [SubcategoryController::class, 'index'])->name('subcategory.home')->middleware('permission:Sub Category');
    Route::get('/subcategory/delete/{id}', [SubcategoryController::class, 'delete'])->name('delete.subcategory');
    route::post('/subcategory/stote', [SubcategoryController::class, 'store'])->name("store.subcategory");

    Route::get('/Product', [ProductController::class, 'product'])->name('product')->middleware('permission:Products');
    // Route::get('/Product', [ProductController::class, 'product'])->name('product')->middleware('permission:View Product');
    Route::get('/create_prodcut', [ProductController::class, 'view_store'])->name('store');
    // Route::get('/create_prodcut', [ProductController::class, 'view_store'])->name('store')->middleware('permission:Create Product');
    Route::post('/store-product', [ProductController::class, 'store_product'])->name('store-product');
    Route::put('/product/update/{id}', [ProductController::class, 'update'])->name('product.update');
    Route::get('/get-subcategories/{category_id}', [ProductController::class, 'getSubcategories'])->name('fetch-subcategories');
    Route::get('/generate-barcode-image', [ProductController::class, 'generateBarcode'])->name('generate-barcode-image');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::get('/barcode/{id}', [ProductController::class, 'barcode'])->name('product.barcode');

    Route::prefix('discount')->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->name('discount.index')->middleware('permission:Discount Products');
        Route::get('/create', [DiscountController::class, 'create'])->name('discount.create');
        Route::post('/store', [DiscountController::class, 'store'])->name('discount.store');
        Route::post('/toggle-status/{id}', [DiscountController::class, 'toggleStatus'])->name('discount.toggleStatus');
        Route::get('/barcode/{id}', [DiscountController::class, 'barcode'])->name('discount.barcode');
    });




    // Customer Routes


    //Cutomer create 
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('permission:Customer');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers/store', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/edit/{id}', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::get('/customers/delete/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::put('customers/{id}', [CustomerController::class, 'update'])->name('customers.update');


    // New
    Route::get('/customers/inactive', [CustomerController::class, 'inactiveCustomers'])->name('customers.inactive');
    Route::get('/customers/inactive/{id}', [CustomerController::class, 'markInactive'])->name('customers.markInactive');
    Route::get('customers/toggle-status/{id}', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');
    Route::get('/customers/ledger', [CustomerController::class, 'customer_ledger'])->name('customers.ledger');
    Route::get('/customer/payments', [CustomerController::class, 'customer_payments'])->name('customer.payments');
    Route::post('/customer/payments', [CustomerController::class, 'store_customer_payment'])->name('customer.payments.store');
    Route::get('/customer/payments/{id}/edit', [CustomerController::class, 'edit_customer_payment'])->name('customer.payments.edit');
    Route::put('/customer/payments/{id}', [CustomerController::class, 'update_customer_payment'])->name('customer.payments.update');
    // web.php
    Route::get('/customer/ledger/{id}', [CustomerController::class, 'getCustomerLedger']);
    Route::delete('/customer-payments/{id}', [CustomerController::class, 'destroy_payment'])->name('customer.payments.destroy');
    Route::get('customer-payments/receipt/{id}', [CustomerController::class, 'customer_payment_receipt'])->name('customer.payments.receipt');

    Route::get('/customer/charges', [CustomerController::class, 'customer_charges'])->name('customer.charges');
    Route::post('/customer/charges', [CustomerController::class, 'store_customer_charge'])->name('customer.charges.store');
    Route::get('/customer/charges/delete/{id}', [CustomerController::class, 'destroy_customer_charge'])->name('customer.charges.destroy');


    // Vendor Routes
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors')->middleware('permission:Vendor');
    Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');
    Route::get('/vendor/delete/{id}', [VendorController::class, 'delete'])->name('vendor.delete');
    Route::get('/vendors-ledger', [VendorController::class, 'vendors_ledger'])->name('vendors-ledger');
    Route::get('/vendor/payments', [VendorController::class, 'vendor_payments'])->name('vendor.payments');
    Route::post('/vendor/payments', [VendorController::class, 'store_vendor_payment'])->name('vendor.payments.store');
    Route::get('/vendor/payments/{id}/edit', [VendorController::class, 'edit_vendor_payment'])->name('vendor.payments.edit');
    Route::put('/vendor/payments/{id}', [VendorController::class, 'update_vendor_payment'])->name('vendor.payments.update');
    Route::get('/vendor/payment/delete/{id}', [VendorController::class, 'destroy_payment'])->name('vendor.payment.delete');
    Route::get('/vendor/bilties', [VendorController::class, 'vendor_bilties'])->name('vendor.bilties');
    Route::post('/vendor/bilties', [VendorController::class, 'store_vendor_bilty'])->name('vendor.bilties.store');
    Route::get('vendor/payment-receipt/{id}', [VendorController::class, 'printReceipt'])->name('vendor.payment.receipt');

    // Warehouse Routes
    Route::get('/warehouse', [WarehouseController::class, 'index'])->middleware('permission:List Warehouse');
    Route::post('/warehouse/store', [WarehouseController::class, 'store']);
    Route::get('/warehouse/delete/{id}', [WarehouseController::class, 'delete']);

    // Branches
    Route::resource('branch', BranchController::class)->names('branch')->only(['index', 'store']);
    Route::get('/branch/delete/{id}', [BranchController::class, 'delete'])->name('branch.delete');

    // Roles
    Route::resource('roles', RoleController::class)->names('roles')->only(['index', 'store']);
    Route::get('/roles/delete/{id}', [RoleController::class, 'delete'])->name('roles.delete');
    Route::post('/admin/roles/update-permission', [RoleController::class, 'updatePermissions'])->name('roles.update.permission');


    // Permissions
    Route::resource('permissions', PermissionController::class)->names('permissions')->only(['index', 'store']);
    Route::get('/permissions/delete/{id}', [PermissionController::class, 'delete'])->name('permission.delete');

    // Users
    Route::resource('users', UserController::class)->names('users')->only(['index', 'store']);
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');
    Route::post('/admin/users/update-roles', [UserController::class, 'updateRoles'])->name('users.update.roles');
    Route::post('/users/opening-balance', [UserController::class, 'storeOpeningBalance'])->name('users.store_opening_balance');
    // Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])->name('users.update.roles');

    // Zone
    Route::get('zone', [ZoneController::class, 'index'])->name('zone.index')->middleware('permission:Zone');
    Route::post('zones/store', [ZoneController::class, 'store'])->name('zone.store');
    Route::get('zones/edit/{id}', [ZoneController::class, 'edit'])->name('zone.edit');
    Route::get('zones/delete/{id}', [ZoneController::class, 'destroy'])->name('zone.delete');

    //Sales Officer
    Route::get('sales-officers', [SalesOfficerController::class, 'index'])->name('sales.officer.index')->middleware('permission:Sales Officer');
    Route::post('sales-officers/store', [SalesOfficerController::class, 'store'])->name('sales-officer.store');
    Route::get('sales-officers/edit/{id}', [SalesOfficerController::class, 'edit'])->name('sales.officer.edit');
    Route::delete('sales-officers/{id}', [SalesOfficerController::class, 'destroy'])->name('sales-officer.delete');


    // products

    route::get('/Purchase', [PurchaseController::class, 'index'])->name('Purchase.home')->middleware('permission:Purchase');
    route::get('/add/Purchase', [PurchaseController::class, 'add_purchase'])->name('add_purchase');
    route::post('/Purchase/stote', [PurchaseController::class, 'store'])->name("store.Purchase");
    Route::get('/purchase/{id}/edit', [PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/purchase/{id}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');
    Route::get('/search-products', [ProductController::class, 'searchProducts'])->name('search-products');
    Route::get('/purchase/{id}/invoice', [PurchaseController::class, 'Invoice'])->name('purchase.invoice');


    Route::get('/purchasereturn/{id}/invoice', [PurchaseController::class, 'ReturnInvoice'])->name('purchasereturn.invoice');

    // Inward Gatepass Routes
    Route::get('/InwardGatepass', [InwardgatepassController::class, 'index'])->name('InwardGatepass.home')->middleware('permission:List Inwards');
    Route::get('/add/InwardGatepass', [InwardgatepassController::class, 'create'])->name('add_inwardgatepass')->middleware('permission:Create Inward Gatepass');
    Route::post('/InwardGatepass/store', [InwardgatepassController::class, 'store'])->name("store.InwardGatepass");
    Route::get('/InwardGatepas/{id}', [InwardgatepassController::class, 'show'])->name('InwardGatepass.show');
    Route::get('/InwardGatepasinv/{id}', [InwardgatepassController::class, 'show_inv'])->name('InwardGatepass.inv');
    Route::delete('/inward-gatepass/{id}', [InwardGatepassController::class, 'destroy'])->name('InwardGatepass.destroy');
    
    // Notifications
    Route::get('/notifications/inward/check', [InwardgatepassController::class, 'checkNewInwards'])->name('notifications.check_inwards');
    Route::post('/notifications/inward/mark-notified', [InwardgatepassController::class, 'markInwardsNotified'])->name('notifications.mark_inwards');

    // Stock Transfer Notifications
    Route::get('/notifications/transfer/check', [StockTransferController::class, 'checkNewTransfers'])->name('notifications.check_transfers');
    Route::post('/notifications/transfer/mark-notified', [StockTransferController::class, 'markTransfersNotified'])->name('notifications.mark_transfers');

    Route::get('inward-gatepass/{id}/add-details', [InwardGatepassController::class, 'addDetails'])
        ->name('InwardGatepass.addDetails');

    Route::get('/search-product-by-barcode', [InwardGatepassController::class, 'searchByBarcode'])
        ->name('search-product-by-barcode');

    Route::post('inward-gatepass/{id}/store-details', [InwardGatepassController::class, 'storeDetails'])
        ->name('InwardGatepass.storeDetails');

    // edit/update/delete abhi comment kiye hue hain
    Route::get('/InwardGatepass/{id}/edit', [InwardgatepassController::class, 'edit'])->name('InwardGatepass.edit');
    Route::put('/InwardGatepass/{id}', [InwardgatepassController::class, 'update'])->name('InwardGatepass.update');
    Route::get('/inward-gatepass/{id}/pdf', [InwardgatepassController::class, 'pdf'])->name('InwardGatepass.pdf');


    Route::delete('/InwardGatepass/{id}', [InwardgatepassController::class, 'destroy'])->name('InwardGatepass.destroy');
    // Products search
    Route::get('/search-products', [InwardgatepassController::class, 'searchProducts'])->name('search-products');


    // Show Add Bill Form
    Route::get('inward-gatepass/{id}/add-bill', [PurchaseController::class, 'addBill'])->name('add_bill');
    // Store Bill
    Route::post('inward-gatepass/{id}/store-bill', [PurchaseController::class, 'store_inwardbill'])->name('store.bill');
    // Edit Bill Form
    Route::get('inward-gatepass/{id}/edit-bill', [PurchaseController::class, 'editBill'])->name('edit_bill');
    // Update Bill
    Route::post('inward-gatepass/{id}/update-bill', [PurchaseController::class, 'update_inwardbill'])->name('update.bill');
    // Purchase Return Routes
    Route::get('purchase/return', [PurchaseController::class, 'purchaseReturnIndex'])->name('purchase.return.index')->middleware('permission:Purchase Return');

    Route::get('purchase/return/{id}', [PurchaseController::class, 'showReturnForm'])->name('purchase.return.show');
    Route::post('purchase/return/store', [PurchaseController::class, 'storeReturn'])->name('purchase.return.store');

    // Route::get('/fetch-product', [PurchaseController::class, 'fetchProduct'])->name('item.search');
    // Route::post('/fetch-item-details', [PurchaseController::class, 'fetchItemDetails']);
    // Route::get('/Purchase/create', function () {
    //     return view('admin_panel.purchase.add_purchase');
    // });
    // Route::get('/get-items-by-category/{categoryId}', [PurchaseController::class, 'getItemsByCategory'])->name('get-items-by-category');
    // Route::get('/get-product-details/{productName}', [ProductController::class, 'getProductDetails'])->name('get-product-details');

    // Route::get('booking/system', [SaleController::class,'booking-system'])->name('booking.index');
    Route::get('sale', [SaleController::class, 'index'])->name('sale.index')->middleware('permission:Sales');
    Route::get('sale/create', [SaleController::class, 'addsale'])->name('sale.add');
    // Route::get('/products/search', [SaleController::class, 'searchProducts'])->name('products.search');
    Route::get('/search-product-name', [SaleController::class, 'searchpname'])->name('search-product-name');
    Route::get('/get-all-products-for-search', [SaleController::class, 'getAllProductsForSearch'])->name('get-all-products-for-search');
    Route::post('/sales/store', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{id}/return', [SaleController::class, 'saleretun'])->name('sales.return.create');
    Route::post('/sales-return/store', [SaleController::class, 'storeSaleReturn'])->name('sales.return.store');
    Route::get('/sale-returns', [App\Http\Controllers\SaleController::class, 'salereturnview'])->name('sale.returns.index')->middleware('permission:Sale Return');
    Route::get('/sales/{id}/invoice', [SaleController::class, 'saleinvoice'])->name('sales.invoice');
    Route::get('/sales/{id}/edit', [SaleController::class, 'saleedit'])->name('sales.edit');
    Route::put('/sales/{id}', [SaleController::class, 'updatesale'])->name('sales.update');
    Route::get('/sales/{id}/dc', [SaleController::class, 'saledc'])->name('sales.dc');
    Route::get('/sales/{id}/recepit', [SaleController::class, 'salerecepit'])->name('sales.recepit');
    Route::get('/sale-return/invoice/{id}', [SaleController::class, 'retrninvoice'])->name('saleReturn.invoice');

    // booking system

    Route::get('bookings', [ProductBookingController::class, 'index'])->name('bookings.index')->middleware('permission:Bookings');
    Route::get('bookings/create', [ProductBookingController::class, 'create'])->name('bookings.create');
    Route::post('bookings/store', [ProductBookingController::class, 'store'])->name('bookings.store');
    Route::get('booking/receipt/{id}', [ProductBookingController::class, 'receipt'])->name('booking.receipt');
    Route::get('/sales/from-booking/{id}', [SaleController::class, 'convertFromBooking'])->name('sales.from.booking');
    Route::delete('bookings/{id}', [ProductBookingController::class, 'destroy'])->name('bookings.destroy');

    // web.php
    Route::get('/warehouse-stock-quantity', [StockTransferController::class, 'getStockQuantity'])->name('warehouse.stock.quantity');
    Route::get('/warehouse-stock-receipt/{id?}', [StockTransferController::class, 'receipt'])->name('recipt.warehouse');

    // narratiions
    Route::get('/get-customers-by-type', [CustomerController::class, 'getByType']);
    Route::resource('warehouse_stocks', WarehouseStockController::class)->middleware('permission:Warehouse Stock');
    Route::resource('stock_transfers', StockTransferController::class)->middleware('permission:Stock Transfer');

    Route::resource('narrations', NarrationController::class)->only(['index', 'store', 'destroy']);
    Route::get('vouchers/{type}', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('vouchers/store', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/get-vendor-balance/{id}', [VendorController::class, 'getVendorBalance']);

    // reporting routes 
    Route::get('report/lock', [ReportLockController::class, 'showLockForm'])->name('report.lock.form');
    Route::post('report/unlock', [ReportLockController::class, 'unlock'])->name('report.unlock');

    Route::get('/report/item-stock', [ReportingController::class, 'item_stock_report'])->name('report.item_stock')->middleware('permission:Item Stock Report');
    Route::post('/report/item-stock-fetch', [ReportingController::class, 'fetchItemStock'])->name('report.item_stock.fetch');

    Route::get('report/purchase', [ReportingController::class, 'purchase_report'])->name('report.purchase')->middleware('permission:Purchase Report');
    Route::post('report/purchase/fetch', [ReportingController::class, 'fetchPurchaseReport'])->name('report.purchase.fetch');

    Route::get('report/sale', [ReportingController::class, 'sale_report'])->name('report.sale')->middleware(['permission:Sale Report', 'lock.report']);
    Route::get('report/sale/fetch', [ReportingController::class, 'fetchsaleReport'])->name('report.sale.fetch')->middleware(['permission:Sale Report', 'lock.report']);

    Route::get('report/niaz', [ReportingController::class, 'niaz_report'])->name('report.niaz')->middleware(['permission:Sale Report', 'lock.report']);
    Route::get('report/niaz/fetch', [ReportingController::class, 'fetchNiazReport'])->name('report.niaz.fetch')->middleware(['permission:Sale Report', 'lock.report']);

    Route::get('report/sale-bonus', [ReportingController::class, 'sale_bonus_report'])->name('report.sale.bonus')->middleware(['permission:Sale Report', 'lock.report']);
    Route::get('report/sale-bonus/fetch', [ReportingController::class, 'fetchSaleBonusReport'])->name('report.sale.bonus.fetch')->middleware(['permission:Sale Report', 'lock.report']);


    Route::get('report/sale/category', [ReportingController::class, 'sale_report_category'])->name('report.sale.category')->middleware(['permission:Sale Report', 'lock.report']);
    Route::get('report/sale/category/fetch', [ReportingController::class, 'fetchsalecategoryReport'])->name('report.sale.category.fetch')->middleware(['permission:Sale Report', 'lock.report']);


    Route::get('report/customer/ledger', [ReportingController::class, 'customer_ledger_report'])->name('report.customer.ledger')->middleware('permission:Customer Ledger');
    Route::get('report/customer-ledger/fetch', [ReportingController::class, 'fetch_customer_ledger'])->name('report.customer.ledger.fetch');

    Route::get('report/vendor/ledger', [ReportingController::class, 'vendor_ledger_report'])->name('report.vendor.ledger')->middleware('permission:Vendor Ledger');
    Route::get('report/vendor-ledger/fetch', [ReportingController::class, 'fetch_vendor_ledger'])->name('report.vendor.ledger.fetch');

    Route::get('report/expense/vocher', [ReportingController::class, 'expense_vocher'])->name('expense.vocher');
    Route::get('/expense-voucher/ajax', [ReportingController::class, 'expenseVoucherAjax'])->name('expense.voucher.ajax');

    // Vochers work

    Route::get('/view_all', [AccountsHeadController::class, 'index'])->name('view_all')->middleware('permission:Char Of Accounts');
    Route::delete('/coa/account/{id}', [AccountsHeadController::class, 'destroy'])
    ->name('coa.account.delete');

    Route::resource('narrations', NarrationController::class)->only(['index', 'store', 'destroy'])->middleware('permission:Narrations');
    Route::get('/getPartyList', [NarrationController::class, 'getPartyList'])->name('party.list');
    Route::get('/get-customer/{id}', [NarrationController::class, 'getCustomerData'])->name('customers.show');
    Route::get('/get-accounts-by-head/{headId}', [NarrationController::class, 'getAccountsByHead']);

    Route::get('/all-recepit-vochers', [VoucherController::class, 'all_recepit_vochers'])->name('all-recepit-vochers')->middleware('permission:Receipts Voucher');
    Route::get('/recepit-vochers', [VoucherController::class, 'recepit_vochers'])->name('recepit-vochers');
    Route::post('/recepit/vochers/stote', [VoucherController::class, 'store_rec_vochers'])->name('recepit.vochers.store');
    Route::get('/receipt-voucher/print/{id}', [VoucherController::class, 'print'])->name('receiptVoucher.print');


    Route::get('/Payment-vochers', [VoucherController::class, 'Payment_vochers'])->name('Payment-vochers');
    route::post('/Payment/vochers/stote', [VoucherController::class, 'store_Pay_vochers'])->name('Payment.vochers.store');
    Route::get('/all-Payment-vochers', [VoucherController::class, 'all_Payment_vochers'])->name('all-Payment-vochers')->middleware('permission:Payment Voucher');
    Route::get('/Payment-voucher/print/{id}', [VoucherController::class, 'Paymentprint'])->name('PaymentVoucher.print');


    Route::get('/expense-vochers', [VoucherController::class, 'expense_vochers'])->name('expense-vochers');
    route::post('/expense/vochers/stote', [VoucherController::class, 'store_expense_vochers'])->name('expense.vochers.store');
    Route::get('/all-expense-vochers', [VoucherController::class, 'all_expense_vochers'])->name('all-expense-vochers')->middleware('permission:Expense Voucher');
    Route::get('/expense-voucher/edit/{id}', [VoucherController::class, 'edit_expense_voucher'])->name('expense.voucher.edit');
    Route::put('/expense-voucher/update/{id}', [VoucherController::class, 'update_expense_voucher'])->name('expense.voucher.update');
    Route::get('/expense-voucher/print/{id}', [VoucherController::class, 'expenseprint'])->name('expenseVoucher.print');


    Route::get('cashbook', [ReportingController::class, 'cashbook'])->name('cashbook');


    Route::prefix('coa')->group(function () {
        Route::get('/', [AccountsHeadController::class, 'index'])->name('coa.index');
        Route::post('/head', [AccountsHeadController::class, 'storeHead'])->name('coa.head.store');
        Route::post('/account', [AccountsHeadController::class, 'storeAccount'])->name('coa.account.store');
    });

    route::post('/subcategory/manual', [ManuallController::class, 'subcategory'])->name("manual.subcategory");
    route::post('/Brand/manual', [ManuallController::class, 'brand'])->name("manual.Brand");
    route::post('/category/manual', [ManuallController::class, 'category'])->name("manual.category");
    route::post('/Unit/manual', [ManuallController::class, 'unit'])->name("manual.Unit");
    route::post('/Brand/manual', [ManuallController::class, 'brand'])->name("manual.Brand");
});
require __DIR__ . '/auth.php';
require __DIR__ . '/hr.php';
