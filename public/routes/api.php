<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminRequestController;
use App\Http\Controllers\ApproveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoreConfigs\BannerController;
use App\Http\Controllers\BookingHistoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\CoreConfigs\SubjectController;
use App\Http\Controllers\CoreConfigsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SidebarController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserCartController;
use App\Http\Controllers\UserController;

use App\Http\Middleware\CheckManageProduct;
use App\Http\Middleware\CheckManageUser;
use App\Http\Middleware\CheckManageApprove;
use App\Http\Middleware\CheckManageRequest;
use App\Http\Middleware\CheckManageWebSetting;
use App\Http\Middleware\CheckManageApproveOrRequest;

// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:' . config('sss.throttle_limit'));
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:' . config('sss.throttle_limit'));
Route::post('/login-with-microsoft', [AuthController::class, 'loginWithMicrosoft'])->middleware('throttle:' . config('sss.throttle_limit'));
Route::post('/login-with-google', [AuthController::class, 'loginWithGoogle'])->middleware('throttle:' . config('sss.throttle_limit'));

Route::group(['middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')]], function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);
});



///Route Product
Route::group([
    'prefix'     => '/products',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/', [ProductController::class, 'getProduct']);
    Route::post('/', [ProductController::class, 'createProduct']);
    Route::get('/{id}', [ProductController::class, 'getProductById']);
    Route::put('/{id}', [ProductController::class, 'updateProduct']);
    Route::delete('/{id}', [ProductController::class, 'deleteProduct']);

    Route::get('/stock/{id}', [ProductController::class, 'getProductHistory']);
    Route::put('/stock/{id} ', [ProductController::class, 'updateProductStock']);

    Route::get('/option/tag', [ProductController::class, 'getOptionTag']);
    Route::get('/option/type', [ProductController::class, 'getOptionType']);
    Route::get('/option/category', [ProductController::class, 'getOptionCategory']);
    Route::get('/option/subject', [ProductController::class, 'getOptionSubject']);
    Route::get('/option/teacher', [ProductController::class, 'getOptionTeacher']);

    Route::get('/export/xlsx', [ProductController::class, 'exportProductXlsx']);
});



///Route Category
Route::group([
    'prefix'     => '/categories',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/get', [CategoryController::class, 'getCategory']);
    Route::get('/get/{id}', [CategoryController::class, 'getCategoryById']);
    Route::post('/create', [CategoryController::class, 'createCategory']);
    Route::post('/update', [CategoryController::class, 'updateCategory']);
    Route::delete('/delete/{id}', [CategoryController::class, 'deleteCategory']);
});



///Route Type
Route::group([
    'prefix'     => '/types',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/get', [TypeController::class, 'getType']);
    Route::get('/get/{id}', [TypeController::class, 'getTypeById']);
    Route::post('/create', [TypeController::class, 'createType']);
    Route::post('/update', [TypeController::class, 'updateType']);
    Route::delete('/delete/{id}', [TypeController::class, 'deleteType']);
});



///Route Tag
Route::group([
    'prefix'     => '/tags',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/get', [TagController::class, 'getTag']);
    Route::get('/get/{id}', [TagController::class, 'getTagById']);
    Route::post('/create', [TagController::class, 'createTag']);
    Route::post('/update', [TagController::class, 'updateTag']);
    Route::delete('/delete/{id}', [TagController::class, 'deleteTag']);
});



///Route User manager
Route::group([
    'prefix'     => '/user-manager',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/get', [UserController::class, 'getUser']);
    Route::get('/get/{id}', [UserController::class, 'getUserById']);
    Route::post('/create', [UserController::class, 'createUser']);
    Route::post('/update', [UserController::class, 'updateUser']);
    Route::delete('/delete/{id}', [UserController::class, 'deleteUser']);
});



///Route Role manager
Route::group([
    'prefix'     => '/role-manager',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/get', [RoleController::class, 'getRole']);
    Route::get('/get/{id}', [RoleController::class, 'getRoleById']);
    Route::post('/create', [RoleController::class, 'createRole']);
    Route::post('/update', [RoleController::class, 'updateRole']);
    Route::delete('/delete/{id}', [RoleController::class, 'deleteRole']);

    Route::get('/permission/get', [PermissionController::class, 'getPermissions']);
});



///Route Core Configs
Route::group([
    'prefix'     => '/core-configs',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/', [CoreConfigsController::class, 'getCoreConfigs']);
    Route::get('/{code}', [CoreConfigsController::class, 'getCoreConfigsByCode']);
    Route::post('/', [CoreConfigsController::class, 'createCoreConfigs']);
    Route::put('/{code}', [CoreConfigsController::class, 'updateCoreConfigs']);
    Route::delete('/{code}', [CoreConfigsController::class, 'deleteCoreConfigs']);
});



///Route Banner
Route::group([
    'prefix'     => '/banner',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/', [BannerController::class, 'getBanner']);
    Route::post('/', [BannerController::class, 'createBanner']);
    Route::get('/{id}', [BannerController::class, 'getBannerById']);
    Route::put('/{id}', [BannerController::class, 'updateBanner']);
    Route::delete('/{id}', [BannerController::class, 'deleteBanner']);
});



///Route Subject
Route::group([
    'prefix'     => '/subject',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/', [SubjectController::class, 'getSubject']);
    Route::post('/', [SubjectController::class, 'createSubject']);
    Route::get('/{id}', [SubjectController::class, 'getSubjectById']);
    Route::put('/{id}', [SubjectController::class, 'updateSubject']);
    Route::delete('/{id}', [SubjectController::class, 'deleteSubject']);
});



/// Category api
Route::group([
    'prefix'     => '/category-page',
    // 'middleware' => ['throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/product/{cate}', [CategoryPageController::class, 'getProductByCategory']);
});



/// Homepage api
Route::group([
    'prefix'     => '/home-page',
    // 'middleware' => ['throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/category', [HomeController::class, 'CategorySelector']);
    Route::get('/product', [HomeController::class, 'AllProduct']);
    Route::get('/banner', [HomeController::class, 'getBanner']);
});



/// Cart api
Route::group([
    'prefix'     => '/user-cart',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::post('/cart', [UserCartController::class, 'addCart']);
    Route::get('/get-cart', [UserCartController::class, 'getCart']);
    Route::post('/update-cart', [UserCartController::class, 'updateCart']);

    Route::get('/subject-select', [UserCartController::class, 'getSubjectSelect']);
});



/// Booking api
Route::group([
    'prefix'     => '/booking',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/teacher-select', [UserController::class, 'getTeacher']);
    Route::post('/save-booking', [BookingHistoryController::class, 'saveBooking']);
    Route::get('/history/all', [BookingHistoryController::class, 'userBookingHistoryAll']);
    Route::get('/history/borrow', [BookingHistoryController::class, 'userBookingHistoryBorrow']);
    Route::get('/history/return', [BookingHistoryController::class, 'userBookingHistoryReturned']);
    Route::get('/history/{id}', [BookingHistoryController::class, 'userBookingHistoryById']);

    Route::put('/pickup/{id}', [BookingHistoryController::class, 'confirmPickup']);
    Route::put('/return/{id}', [BookingHistoryController::class, 'confirmReturn']);
});



/// Notification api
Route::group([
    'prefix'     => '/noti',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/read', [NotificationController::class, 'getNotificationReaded']);
    Route::get('/unread', [NotificationController::class, 'getNotificationUnread']);
    Route::get('/make-as-read', [NotificationController::class, 'makeAsRead']);
});



/// Approve api
Route::group([
    'prefix'     => '/approve',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/pending-list', [ApproveController::class, 'approveListPending']);
    Route::get('/approved-list', [ApproveController::class, 'approveListApproved']);
    Route::post('/product-stock', [ApproveController::class, 'checkProductStock']);
    Route::get('/get/{id}', [ApproveController::class, 'approveById']);

    Route::post('/approved', [ApproveController::class, 'approve']);
    Route::post('/reject', [ApproveController::class, 'reject']);
});



/// Admin Request api
Route::group([
    'prefix'     => '/request',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/all', [AdminRequestController::class, 'requestAll']);
    Route::get('/get/{id}', [AdminRequestController::class, 'requestById']);

    Route::put('/packed/{id}', [AdminRequestController::class, 'requestPacked']);
    Route::post('/confirm/{id}', [AdminRequestController::class, 'requestConfirm']);
    Route::post('/incomplete/{id}', [AdminRequestController::class, 'requestIncomplete']);
    Route::put('/save-change/{id}', [AdminRequestController::class, 'requestSaveChange']);
    Route::post('/confirm-pack-list', [AdminRequestController::class, 'requestConfirmPackList']);

    Route::get('/print/preview', [AdminRequestController::class, 'PreviewPrint']);

    Route::post('/extend-date', [AdminRequestController::class, 'extendDate']);
    Route::post('/change-status', [AdminRequestController::class, 'changeStatus']);
});



/// Dashboard api
Route::group([
    'prefix'     => '/dashboard',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/status-report', [DashboardController::class, 'StatusCount']);
    Route::get('/most-product-booking', [DashboardController::class, 'MostProductBooking']);
    Route::get('/most-category-booking', [DashboardController::class, 'MostCategoryBooking']);
    Route::get('/less-out-stock', [DashboardController::class, 'LessOrOutOfStock']);
    Route::get('/product-lost', [DashboardController::class, 'ProductLost']);
    Route::get('/product-inuse', [DashboardController::class, 'ProductInuse']);
    Route::get('/booking-with-status-now', [DashboardController::class, 'BookingWithStatusNow']);
    Route::get('/booking-with-status-overdue', [DashboardController::class, 'BookingOverDue']);
    Route::get('/booking-approve-by-teacher', [DashboardController::class, 'TeacherApproveReject']);
});



/// Admin Page api
Route::group([
    'prefix'     => '/sidebar',
    // 'middleware' => ['auth:sanctum', 'throttle:' . config('sss.throttle_limit')],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('/count-booking-status-admin', [SidebarController::class, 'BookingStatusCountAdmin']);
    Route::get('/count-booking-status-teacher', [SidebarController::class, 'BookingStatusCountTeacher']);
});
