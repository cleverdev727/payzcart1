<?php


namespace App\Classes;


use App\Models\MerchantBalance;
use App\Models\Refund;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class TransactionManager
{

    private $transaction;
    private $refund;
    private $merchantBalance;

    public function __construct(Transaction $transaction, Refund $refund, MerchantBalance $merchantBalance)
    {
        $this->transaction = $transaction;
        $this->refund = $refund;
        $this->merchantBalance = $merchantBalance;
    }

    public function getTransaction($merchantId, $filterData, $limit, $pageNo)
    {
        try {
            $transactionsSummary = $this->transaction->getTransactionSummary($filterData, $merchantId);
            $txnData = $this->transaction->getTransaction($merchantId, $filterData, $limit, $pageNo);
            if (isset($txnData)) {

                $result['status'] = true;
                $result['message'] = 'Transaction Details Retrieve successfully';
                $result['current_page'] = $txnData->currentPage();
                $result['last_page'] = $txnData->lastPage();
                $result['is_last_page'] = !$txnData->hasMorePages();
                $result['total_item'] = $txnData->total();
                $result['current_item_count'] = $txnData->count();
                $result['data'] = $txnData->items();
                $result['summary'] = $transactionsSummary;
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Transaction  Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting Transaction";
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function getTransactionDetails($merchantId, $transaction_id)
    {
        try {
            $data = $this->transaction->getTransactionDetails($merchantId, $transaction_id);
            if (isset($data)) {
                $refundAmount = 0;
                if (
                    strcmp($data->payment_status, "Full Refund") ||
                    strcmp($data->payment_status, "Partial Refund")
                ) {
                    $refundAmount = $this->refund->getRefundAmountByTransaction($merchantId, $transaction_id);
                }
                $result['status'] = true;
                $result['message'] = 'data Retrieve successfully';
                $result['data'] = base64_encode(\view("components.widget.transaction-detail")->with("data", $data)->with("refundAmount", $refundAmount)->render());
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "data Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting data";
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function resendTransactionWebhook($merchantId, $transaction_id)
    {
        try {
            if ($this->transaction->resendTransactionWebhook($merchantId, $transaction_id)) {
                $result['status'] = true;
                $result['message'] = 'Webhook Resend Success';
                DashboardUtils::LogDB("PAYIN", "PAYIN Webhook Resend, PAYIN_ID: {$transaction_id}");
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Webhook Resend Failed";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while Resend Webhook";
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function refundTransaction($merchantId, $transaction_id, $amount, $reason)
    {
        try {
            $tData = $this->transaction->getTransactionDetailsForRefund($merchantId, $transaction_id);
            if (isset($tData)) {
                $rData = $this->refund->getRefundAmountByTransaction($merchantId, $transaction_id);
                if ($rData >= 0) {
                    $refundType = null;
                    $availableRefundAmount = floatval($tData->payment_amount) - floatval($rData->refund_amount);
                    if ($amount === $availableRefundAmount) {
                        $refundType = "Full Refund";
                    } elseif ($amount < $availableRefundAmount) {
                        $refundType = "Partial Refund";
                    } else {
                        $refundType = null;
                    }

                    if (isset($refundType)) {
                        $refundId = DashboardUtils::generateRefundId();
                        if ($this->refund->addRefund($merchantId, $refundId, $tData->transaction_id, $amount, $tData->currency, $tData->pg_name, $tData->meta_id, $refundType, $reason)) {
                            $this->transaction->markAsRefund($merchantId, $tData->transaction_id, $refundType);
                            $error['status'] = true;
                            $error['message'] = "Refund Request Received";
                            DashboardUtils::LogDB("PAYIN", "PAYIN Refund Request Received, ID: {$tData->transaction_id}, Refund Type: {$refundType}");
                            return response()->json($error)->setStatusCode(200);
                        }
                    }
                }
            }
            $error['status'] = false;
            $error['message'] = "Invalid Request";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while Refund";
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function getChartData($merchantId, $start_date, $end_date)
    {
        try {

            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);
            $dateObject = CarbonPeriod::between($start_date, $end_date);
            $chartCategories = [];
            $chartInSeriesAmount = [];
            $chartOutSeriesAmount = [];

            $txnData = $this->merchantBalance->getChartData($merchantId, $start_date->format("Y-m-d"), $end_date->format("Y-m-d"));
            foreach ($dateObject as $_dateObject) {
                $chartCategories[] = $_dateObject->format("M d, y");
                $chartInSeriesAmount[] = isset($txnData) ? DashboardUtils::findKeyByValue($txnData, "pay_date", $_dateObject->format("Y-m-d"), "payin_amount") : 0;
                $chartOutSeriesAmount[] = isset($txnData) ? DashboardUtils::findKeyByValue($txnData, "pay_date", $_dateObject->format("Y-m-d"), "payout_amount") : 0;
            }

            $countData = [
                "chartCategories" => $chartCategories,
                "chartInSeriesAmount" => $chartInSeriesAmount,
                "chartOutSeriesAmount" => $chartOutSeriesAmount
            ];

            return response()->json([
                "status" => true,
                "message" => "Data retrieved",
                "data" => $countData
            ]);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while retrieved Data";
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function getUnsettledTransactionSummary($merchantId)
    {
        try {
            return $this->transaction->getUnsettledTransactionSummary($merchantId);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return 0;
        }
    }
    public function getSuccessTransactionSummary($merchantId)
    {
        try {
            return $this->transaction->getSuccessTransactionSummary($merchantId);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return 0;
        }
    }

    public function todayTransactionSummary($merchantId)
    {
        try {
            $data = $this->transaction->todayTransactionSummary($merchantId);
            if (isset($data)) {
                return $data;
            }
            return null;
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

}
