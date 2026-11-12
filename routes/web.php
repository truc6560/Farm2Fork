<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountProvisionController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\PlantingZoneLookupController;
use App\Http\Controllers\Admin\RegistrationApprovalController;
use App\Http\Controllers\Admin\ThresholdController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Chain\ChainAlertController;
use App\Http\Controllers\Chain\ChainColdStorageController;
use App\Http\Controllers\Chain\ChainDashboardController;
use App\Http\Controllers\Chain\ChainLotController;
use App\Http\Controllers\Chain\ChainReportController;
use App\Http\Controllers\Chain\ChainTransportController;
use App\Http\Controllers\Chain\VehicleController;
use App\Http\Controllers\Chain\PartnerController;
use App\Http\Controllers\Chain\ChainSeasonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\DeliveryController;
use App\Http\Controllers\Driver\JourneyController;
use App\Http\Controllers\Driver\TripController;
use App\Http\Controllers\Producer\FarmingLogController;
use App\Http\Controllers\Producer\HarvestLotController;
use App\Http\Controllers\Producer\PlantingZoneController;
use App\Http\Controllers\Producer\SeasonController;
use App\Http\Controllers\Processing\InboundReceiptController;
use App\Http\Controllers\Processing\PackagingController;
use App\Http\Controllers\Processing\ProcessingBatchController;
use App\Http\Controllers\QC\InspectionTicketController;
use App\Http\Controllers\QC\PendingInspectionController;
use App\Http\Controllers\TraceabilityController;
use App\Http\Controllers\Warehouse\InventoryController;
use App\Http\Controllers\Warehouse\MonitoringController;
use App\Http\Controllers\Warehouse\OperationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('auth.login.form');
});

// Public Consumer Traceability (GS1 Digital Link & QR Scanner)
Route::get('/trace/{code}', [TraceabilityController::class, 'show'])->name('trace.show');
Route::get('/trace/01/{gtin}/10/{lotCode}', [TraceabilityController::class, 'showGs1'])->name('trace.gs1');
Route::get('/01/{gtin}/10/{lotCode}', [TraceabilityController::class, 'showGs1']);
Route::get('/00/{sscc}', [TraceabilityController::class, 'showSscc'])->name('trace.sscc');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('auth.login.form');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login.submit');

    // Quên & Đặt lại mật khẩu
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('auth.password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('auth.password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('auth.password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('auth.password.update');
});

// Chấp nhận lời mời thành viên (Odoo style) - Public Route
Route::get('/invitations/accept/{token}', [AcceptInvitationController::class, 'show'])->name('invitations.accept');
Route::post('/invitations/accept/{token}', [AcceptInvitationController::class, 'accept'])
    ->middleware('throttle:10,1')
    ->name('invitations.submit');

Route::middleware(['auth', 'active.account'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');

    Route::get('/change-password', [ChangePasswordController::class, 'show'])
        ->name('auth.change-password.form');
    Route::put('/change-password', [ChangePasswordController::class, 'update'])
        ->name('auth.change-password.update');

    Route::middleware('force.change.password')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('admin/registrations')
            ->name('admin.registrations.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [RegistrationApprovalController::class, 'index'])->name('index');
                Route::get('/{applicationCode}', [RegistrationApprovalController::class, 'show'])->name('show');
                Route::post('/{applicationCode}/verify-contact', [RegistrationApprovalController::class, 'verifyContact'])->name('verify-contact');
                Route::post('/{applicationCode}/approve', [RegistrationApprovalController::class, 'approve'])->name('approve');
                Route::post('/{applicationCode}/reject', [RegistrationApprovalController::class, 'reject'])->name('reject');
                Route::post('/{applicationCode}/request-supplement', [RegistrationApprovalController::class, 'requestSupplement'])->name('request-supplement');
                Route::post('/{applicationCode}/provision-account', [AccountProvisionController::class, 'provision'])->name('provision-account');
                Route::get('/{applicationCode}/documents/{docId}', [RegistrationApprovalController::class, 'downloadDocument'])->name('documents.download');
            });

        Route::prefix('admin/members')
            ->name('admin.members.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [MemberController::class, 'index'])->name('index');
                Route::post('/invite', [MemberController::class, 'store'])->name('invite');
                Route::post('/invitations/{id}/resend', [MemberController::class, 'resend'])->name('invitations.resend');
                Route::post('/invitations/{id}/revoke', [MemberController::class, 'revoke'])->name('invitations.revoke');
                Route::post('/invitations/{id}/link', [MemberController::class, 'getLink'])->name('invitations.link');
            });

        Route::prefix('admin/planting-zones')
            ->name('admin.planting-zones.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [PlantingZoneLookupController::class, 'index'])->name('index');
                Route::get('/{id}', [PlantingZoneLookupController::class, 'show'])->whereNumber('id')->name('show');
            });

        Route::prefix('admin/accounts')
            ->name('admin.accounts.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [AccountController::class, 'index'])->name('index');
                Route::post('/{id}/lock', [AccountController::class, 'lock'])->name('lock');
                Route::post('/{id}/unlock', [AccountController::class, 'unlock'])->name('unlock');
                Route::post('/{id}/role', [AccountController::class, 'changeRole'])->name('change-role');
            });

        Route::prefix('admin/thresholds')
            ->name('admin.thresholds.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [ThresholdController::class, 'index'])->name('index');
                Route::post('/', [ThresholdController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [ThresholdController::class, 'edit'])->name('edit');
                Route::put('/{id}', [ThresholdController::class, 'update'])->name('update');
                Route::post('/{id}/toggle', [ThresholdController::class, 'toggle'])->name('toggle');
            });

        Route::prefix('admin/warehouses')
            ->name('admin.warehouses.')
            ->middleware('admin.role')
            ->group(function () {
                Route::get('/', [WarehouseController::class, 'index'])->name('index');
                Route::get('/create', [WarehouseController::class, 'create'])->name('create');
                Route::post('/', [WarehouseController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [WarehouseController::class, 'edit'])->name('edit');
                Route::put('/{id}', [WarehouseController::class, 'update'])->name('update');
                Route::post('/{id}', [WarehouseController::class, 'update'])->name('update.post');
            });

        Route::prefix('producer')
            ->name('producer.')
            ->middleware('role:NGUOI_SAN_XUAT')
            ->group(function () {
                Route::get('planting-zones/{id}/certificate', [PlantingZoneController::class, 'downloadCertificate'])->name('planting-zones.certificate');
                Route::resource('planting-zones', PlantingZoneController::class);

                Route::post('seasons/{season}/complete', [SeasonController::class, 'complete'])->name('seasons.complete');
                Route::resource('seasons', SeasonController::class)->except(['destroy']);

                Route::resource('farming-logs', FarmingLogController::class);

                Route::resource('lots', HarvestLotController::class)->only(['index', 'create', 'store', 'show']);
            });

        Route::prefix('processing')
            ->name('processing.')
            ->middleware('role:NV_SO_CHE_DONG_GOI')
            ->group(function () {
                Route::get('inbound', [InboundReceiptController::class, 'index'])->name('inbound.index');
                Route::get('inbound/{id}', [InboundReceiptController::class, 'show'])->name('inbound.show');
                Route::post('inbound/{id}/receive', [InboundReceiptController::class, 'receive'])->name('inbound.receive');

                Route::resource('batches', ProcessingBatchController::class)->only(['index', 'create', 'store', 'show']);

                Route::get('packaging/suggest-sscc', [PackagingController::class, 'suggestSscc'])->name('packaging.suggest-sscc');
                Route::get('packaging/labels/print', [PackagingController::class, 'printLabels'])->name('packaging.print-labels');
                Route::resource('packaging', PackagingController::class)->only(['index', 'create', 'store', 'show']);
            });

        Route::prefix('qc')
            ->name('qc.')
            ->middleware('role:QC')
            ->group(function () {
                Route::get('pending', [PendingInspectionController::class, 'index'])->name('pending.index');
                Route::resource('inspections', InspectionTicketController::class)->only(['index', 'create', 'store', 'show']);
                Route::post('inspections/{id}/remedy', [InspectionTicketController::class, 'remedy'])->name('inspections.remedy');
            });

        Route::prefix('warehouse')
            ->name('warehouse.')
            ->middleware('role:NV_KHO_LANH')
            ->group(function () {
                // 1. Inbound & Outbound Operations
                Route::get('operations', [OperationController::class, 'index'])->name('operations.index');
                Route::post('operations/receive', [OperationController::class, 'receive'])->name('operations.receive');
                Route::post('operations/receive-bulk', [OperationController::class, 'receiveBulk'])->name('operations.receive-bulk');
                Route::post('operations/dispatch', [OperationController::class, 'dispatch'])->name('operations.dispatch');
                Route::post('operations/dispatch-bulk', [OperationController::class, 'dispatchBulk'])->name('operations.dispatch-bulk');

                // 2. Inventory & Warehouse Stock Balance
                Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
                Route::get('inventory/{id}', [InventoryController::class, 'show'])->name('inventory.show');

                // 3. Cold Chain & Temperature Monitoring
                Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
                Route::post('monitoring/alerts/{id}/resolve', [MonitoringController::class, 'resolveAlert'])->name('monitoring.resolve-alert');
            });

        Route::prefix('driver')
            ->name('driver.')
            ->middleware('role:TAI_XE')
            ->group(function () {
                // 1. Chuyến vận chuyển
                Route::get('trips', [TripController::class, 'index'])->name('trips.index');
                Route::get('trips/{id}', [TripController::class, 'show'])->name('trips.show');
                Route::post('trips/{id}/start', [TripController::class, 'startTrip'])->name('trips.start');
                Route::post('trips/{id}/complete', [TripController::class, 'completeTrip'])->name('trips.complete');

                // 2. Hành trình & Sự kiện lộ trình
                Route::get('journey', [JourneyController::class, 'index'])->name('journey.index');
                Route::post('journey/event', [JourneyController::class, 'storeEvent'])->name('journey.store-event');

                // 3. Giao nhận hàng hóa
                Route::get('delivery', [DeliveryController::class, 'index'])->name('delivery.index');
                Route::get('delivery/{id}', [DeliveryController::class, 'show'])->name('delivery.show');
                Route::post('delivery/{id}/confirm', [DeliveryController::class, 'confirmDelivery'])->name('delivery.confirm');
            });

        Route::prefix('chain')
            ->name('chain.')
            ->middleware('role:QUAN_LY_CHUOI,QUAN_TRI_VIEN')
            ->group(function () {
                // 1. Dashboard chuỗi
                Route::get('dashboard', [ChainDashboardController::class, 'index'])->name('dashboard');

                // 2. Giám sát lô hàng xuyên suốt chuỗi
                Route::get('lots', [ChainLotController::class, 'index'])->name('lots.index');
                Route::get('lots/{id}', [ChainLotController::class, 'show'])->name('lots.show');

                // 3. Giám sát kho lạnh & IoT
                Route::get('cold-storage', [ChainColdStorageController::class, 'index'])->name('cold-storage.index');

                // 4. Giám sát vận chuyển & Giao nhận
                Route::get('transport', [ChainTransportController::class, 'index'])->name('transport.index');
                Route::get('transport/create', [ChainTransportController::class, 'create'])->name('transport.create');
                Route::post('transport', [ChainTransportController::class, 'store'])->name('transport.store');

                // 5. Trung tâm cảnh báo rủi ro
                Route::get('alerts', [ChainAlertController::class, 'index'])->name('alerts.index');

                // 6. Báo cáo quản trị & Xuất dữ liệu CSV
                Route::get('reports', [ChainReportController::class, 'index'])->name('reports.index');
                Route::get('reports/export-csv', [ChainReportController::class, 'exportCsv'])->name('reports.export');

                // Quản lý vòng đời mùa vụ
                Route::get('seasons', [ChainSeasonController::class, 'index'])->name('seasons.index');
                Route::get('seasons/create', [ChainSeasonController::class, 'create'])->name('seasons.create');
                Route::post('seasons', [ChainSeasonController::class, 'store'])->name('seasons.store');
                Route::get('seasons/{season}', [ChainSeasonController::class, 'show'])->name('seasons.show');
                Route::get('seasons/{season}/edit', [ChainSeasonController::class, 'edit'])->name('seasons.edit');
                Route::put('seasons/{season}', [ChainSeasonController::class, 'update'])->name('seasons.update');
                Route::delete('seasons/{season}', [ChainSeasonController::class, 'destroy'])->name('seasons.destroy');
                Route::post('seasons/{season}/start-harvest', [ChainSeasonController::class, 'startHarvest'])->name('seasons.start-harvest');
                Route::post('seasons/{season}/complete', [ChainSeasonController::class, 'complete'])->name('seasons.complete');
                Route::post('seasons/{season}/reopen', [ChainSeasonController::class, 'reopen'])->name('seasons.reopen');
                Route::post('seasons/{season}/cancel', [ChainSeasonController::class, 'cancel'])->name('seasons.cancel');

                // 7. Quản lý phương tiện vận chuyển
                Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
                Route::get('vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
                Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
                Route::get('vehicles/{id}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
                Route::put('vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update');

                // 8. Quản lý đối tác nhận hàng
                Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
                Route::get('partners/create', [PartnerController::class, 'create'])->name('partners.create');
                Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
                Route::get('partners/{id}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
                Route::put('partners/{id}', [PartnerController::class, 'update'])->name('partners.update');
            });
    });
});

// Storage fallback route for file previews
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*')->name('storage.local');
