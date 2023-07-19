<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\PayoutManager;
use App\Classes\RefundManager;
use App\Classes\TransactionManager;
use App\Exceptions\UnAuthorizedRequest;
use App\Models\MerchantBalance;
use App\Models\MerchantCreditBalance;
use App\Models\MerchantSettlement;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{

    private $transactionManager;
    /**
     * @var MerchantBalance
     */
    private $merchantBalance;
    /**
     * @var PayoutManager
     */
    private $payoutManager;
    /**
     * @var RefundManager
     */
    private $refundManager;

    public function __construct(
        TransactionManager $transactionManager,
        MerchantBalance $merchantBalance,
        PayoutManager $payoutManager,
        RefundManager $refundManager
    )
    {
        $this->transactionManager = $transactionManager;
        $this->merchantBalance = $merchantBalance;
        $this->payoutManager = $payoutManager;
        $this->refundManager = $refundManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getDashboardSummary(Request $request) {
        $merchantId = DashboardUtils::merchantId();

        $countData = [
            "transaction" => null,
            "payout" => null,
            "refund" => null
        ];


        $totalPayableAmount = (new Transaction())->getTotalPayableAmount($merchantId);
        $totalPayoutAmount = (new Payout())->getTotalPayoutAmount($merchantId);
        $totalRefundAmount = (new Refund())->getTotalRefundAmount($merchantId);



        $totalWithdrawal =   floatval($totalPayoutAmount);

        $totalRefund = floatval($totalRefundAmount);

        $settledBalance = (new MerchantSettlement())->getTotalSettledAmount($merchantId);
        $totalBalance = $totalPayableAmount;
        $unsettledBalance = $totalPayableAmount - $settledBalance;

        $availableBalance = DashboardUtils::getMerchantBalanceForPayout($merchantId);

        $totalCredit = (new MerchantCreditBalance())->getTotalCreditAmount($merchantId);

        $today_success_txn = 0;
        $today_success_txn_amount = 0;
        $today_payout = 0;
        $today_payout_amount = 0;
        $total_pending_payout = 0;
        $total_pending_payout_amount = 0;
        $total_refund_txn = 0;
        $total_refund_txn_amount = 0;

        $todayTxnSummary = $this->transactionManager->todayTransactionSummary($merchantId);

        if(isset($todayTxnSummary)) {
            $today_success_txn = $todayTxnSummary->total;
            $today_success_txn_amount = floatval($todayTxnSummary->amount);
        }
        $payoutSummary = $this->payoutManager->getPayoutSummary($merchantId);
        if(isset($payoutSummary)) {
            //$totalWithdrawal = floatval($payoutSummary->amount);
        }

        $todayPayoutSummary = $this->payoutManager->getTodayPayoutSummary($merchantId);
        if(isset($todayPayoutSummary)) {
            $today_payout = $todayPayoutSummary->total;
            $today_payout_amount = floatval($todayPayoutSummary->amount);
        }

        $pendingPayoutSummary = $this->payoutManager->getPendingPayoutSummary($merchantId);
        if(isset($pendingPayoutSummary)) {
            $total_pending_payout = $pendingPayoutSummary->total;
            $total_pending_payout_amount = floatval($pendingPayoutSummary->amount);
        }

        $refundSummary = $this->refundManager->getSummary($merchantId);
        if(isset($refundSummary)) {
            $total_refund_txn = $refundSummary['total_refund'];
            $total_refund_txn_amount = floatval($refundSummary['total_refund_amount']);
        }

        $countData = [
            "TOTAL_BALANCE" => $totalBalance,
            "TOTAL_WITHDRAWAL" => $totalWithdrawal,
            "REMAINING_BALANCE" => $availableBalance,
            "SETTLED_BALANCE" => $settledBalance,
            "UNSETTLED_BALANCE" => $unsettledBalance,
            "LOAD_BALANCE" => $totalCredit,
            "TODAY_SUCCESS_TXN" => $today_success_txn,
            "TODAY_SUCCESS_TXN_AMOUNT" => $today_success_txn_amount,
            "TODAY_PAYOUT" => $today_payout,
            "TODAY_PAYOUT_AMOUNT" => $today_payout_amount,
            "PENDING_PAYOUT" => $total_pending_payout,
            "PENDING_PAYOUT_AMOUNT" => $total_pending_payout_amount,
            "TOTAL_REFUND" => $total_refund_txn,
            "TOTAL_REFUND_AMOUNT" => $total_refund_txn_amount,
        ];


        return response()->json([
            "status" => true,
            "message" => "data",
            "data" => $countData
        ])->setStatusCode(200);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getChartData(Request $request) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|string',
            'end_date' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->transactionManager->getChartData($merchantId, $request->start_date, $request->end_date);

    }
}
