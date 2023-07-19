<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ReconController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth'], function () {
  Route::post('/login', [MerchantController::class, 'merchantAuthenticate']);
  Route::post('/re-auth', [MerchantController::class, 'merchantReAuthenticate']);

  Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('logout', [MerchantController::class, 'logout']);
  });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
  Route::group(['prefix' => 'dashboard'], function () {
    Route::get('summary', [DashboardController::class, 'getDashboardSummary']);
    Route::post('chart/summary', [DashboardController::class, 'getChartData']);
  });
  Route::group(['prefix' => 'payin'], function () {
    Route::post('/', [TransactionController::class, 'getTransaction']);
    Route::post('refund', [TransactionController::class, 'refundTransaction']);
    Route::post('resend/webhook', [TransactionController::class, 'resendTransactionWebhook']);
    Route::post('detail', [TransactionController::class, 'getTransactionDetails']);
  });
  Route::group(['prefix' => 'payout'], function () {
    Route::post('/', [PayoutController::class, 'getPayout']);
    Route::post('single/request', [PayoutController::class, 'createPayoutRequest']);
    Route::post('request/approve', [PayoutController::class, 'approvedPayoutRequest']);
    Route::post('request/cancel', [PayoutController::class, 'cancelPayoutRequest']);
    Route::post('resend/webhook', [PayoutController::class, 'resendPayoutWebhook']);
    Route::post('detail', [PayoutController::class, 'getPayoutDetails']);
  });
  Route::group(['prefix' => 'refund'], function () {
    Route::post('/', [RefundController::class, 'getRefund']);
    Route::post('detail', [RefundController::class, 'getRefundDetail']);
    Route::post('resend/webhook', [RefundController::class, 'resendRefundWebhook']);
  });
  Route::group(['prefix' => 'report'], function () {
    Route::post('add', [ReportController::class, 'addReport']);
    Route::post('get', [ReportController::class, 'getReports']);
    Route::post('download', [ReportController::class, 'downloadReport']);
  });
  Route::group(['prefix' => 'setting'], function () {
    Route::get('detail', [MerchantController::class, 'getSettingDetail']);
    Route::post('update/webhook', [MerchantController::class, 'updateWebhook']);
    Route::post('update/configuration', [MerchantController::class, 'updateConfiguration']);
    Route::post('change-password', [MerchantController::class, 'merchantChangePassword']);
    Route::post('gauth/enable', [MerchantController::class, 'enableGAuth']);
    Route::post('gauth/disable', [MerchantController::class, 'disableGAuth']);
    Route::post('gauth/enable/verify', [MerchantController::class, 'verifyToEnableGAuth']);
  });
  Route::post('/view/payin/status', [ReconController::class, "transactionRecon"]);
  Route::post('/transaction/recon/action', [ReconController::class, "transactionReconAction"]);
});
