<?php


namespace App\Classes;


use App\Classes\Utils\PayoutType;
use App\Models\MerchantDetails;
use App\Models\Payout;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PayoutManager
{

    private $payout;
    private $merchant;
    private $transactions;

    public function __construct(Payout $payout, MerchantDetails $merchant, Transaction $transactions)
    {
        $this->payout = $payout;
        $this->merchant = $merchant;
        $this->transactions = $transactions;
    }

    public function getPayout($merchantId, $filterData, $limit, $page_no)
    {
        try {
            $payoutSummary = $this->payout->getPayoutSummary($filterData,$merchantId);

            $data = $this->payout->getPayout($merchantId, $filterData, $limit, $page_no);
            if(isset($data)) {

                $result['status'] = true;
                $result['message'] = 'Transaction Details Retrieve successfully';
                $result['current_page'] = $data->currentPage();
                $result['last_page'] = $data->lastPage();
                $result['is_last_page'] = !$data->hasMorePages();
                $result['total_item'] = $data->total();
                $result['current_item_count'] = $data->count();
                $result['data'] = $data->items();
                $result['summary'] = $payoutSummary;

                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "payout Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting Payout";
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

    public function getPayoutDetails($merchantId, $payout_id)
    {
        try {
            $data = $this->payout->getPayoutDetails($merchantId, $payout_id);
            if(isset($data)) {
                $result['status'] = true;
                $result['message'] = 'Details Retrieve successfully';
                $result['data'] = base64_encode(view("components.widget.payout-details")->with("data", $data)->render());
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "payout Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting Payout";
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

    public function resendPayoutWebhook($merchantId, $payout_id)
    {
        try {
            if($this->payout->resendPayoutWebhook($merchantId, $payout_id)) {
                $result['status'] = true;
                $result['message'] = 'Webhook Resend Success';
                DashboardUtils::LogDB("PAYOUT", "Payout Webhook Resend, PayoutID: {$payout_id}");
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

    public function createPayoutRequest(
        $merchantId,
        $payoutId,
        $payout_type,
        $payout_amount,
        $payout_ref_id,
        $customer_name,
        $account_number,
        $ifsc_code,
        $bankName
    )
    {
        try {

            $merchantDetail = $this->merchant->getMerchantDetailById($merchantId);
            if(!isset($merchantDetail)) {
                return DashboardUtils::errorResponse("Invalid Request");
            }

            if(!$merchantDetail->is_payout_enable) {
                return DashboardUtils::errorResponse("Payout is not enabled");
            }

            $payoutFees = $this->calculatePayoutFees($payout_amount, $merchantDetail->payout_fees);
            $associateFees = $this->calculatePayoutFees($payout_amount, $merchantDetail->payout_associate_fees);
            $totalPayout = floatval($payout_amount) + floatval($payoutFees);

            if(!$this->checkIsBalanceAvailable($merchantId, $totalPayout)) {
                return DashboardUtils::errorResponse("Insufficient Balance");
            }

            if(
                strcmp($payout_type, PayoutType::UPI) === 0 ||
                strcmp($payout_type, PayoutType::PAYTM) === 0
            ) {
                $account_number = null;
                $ifsc_code = null;
                $bankName = null;
            }

            if($this->payout->createPayoutRequest(
                $merchantId,
                $payoutId,
                $payout_type,
                $payout_amount,
                $payoutFees,
                $associateFees,
                $payout_ref_id,
                $customer_name,
                $account_number,
                $ifsc_code,
                $bankName
            )) {
                $result['status'] = true;
                $result['message'] = 'Payout Request Created';
                DashboardUtils::LogDB("PAYOUT", "Payout Request Created, PayoutID: {$payoutId}, PayoutAmount: {$payout_amount}");
                return response()->json($result)->setStatusCode(200);
            }
            return DashboardUtils::errorResponse("Payout Request Failed");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while Create Payout Request");
        }
    }

    private function calculatePayoutFees($payoutAmount, $merchantPayoutFees)
    {
        $payoutAmount = floatval($payoutAmount);
        $merchantPayoutFees = floatval($merchantPayoutFees);
        return round(($payoutAmount * $merchantPayoutFees) / 100,3);
    }

    private function checkIsBalanceAvailable($merchantId, $totalPayout)
    {
        try {
            $availableBalance = DashboardUtils::getMerchantBalanceForPayout($merchantId);
            if($totalPayout > $availableBalance) {
                return false;
            }
            return true;
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function approvedPayoutRequest($merchantId, $payout_id)
    {
        try {
            $payoutDetail = $this->payout->getPayoutDetails($merchantId, $payout_id);
            if(isset($payoutDetail)) {
                $merchantDetail = $this->merchant->getMerchantDetailById($merchantId);
                if(isset($merchantDetail)) {
                    $merchantPayoutDetailTime = $merchantDetail->payout_delayed_time;
                    $currentDate = Carbon::now();
                    $payoutCreatedDate = Carbon::parse($payoutDetail->created_at);
                    $difference = $currentDate->diffInMinutes($payoutCreatedDate);
                    if($difference < $merchantPayoutDetailTime) {
                        return DashboardUtils::errorResponse("Payout request is in cooling period");
                    }
                    if($this->payout->approvedPayoutRequest($merchantId, $payout_id)) {
                        DashboardUtils::LogDB("PAYOUT", "Payout Request Approved, PayoutID: {$payout_id}");
                        return DashboardUtils::successResponse("Payout Request Approved");
                    }
                }
            }
            return DashboardUtils::errorResponse("Invalid Operation");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while approve payout request");
        }
    }

    public function cancelPayoutRequest($merchantId, $payout_id)
    {
        try {
            $payoutDetail = $this->payout->getPayoutDetails($merchantId, $payout_id);
            if(isset($payoutDetail)) {
                $merchantDetail = $this->merchant->getMerchantDetailById($merchantId);
                if(isset($merchantDetail)) {
                    $merchantPayoutDetailTime = $merchantDetail->payout_delayed_time;
                    $merchantPayoutDetailTime = $merchantDetail->payout_delayed_time;
                    $currentDate = Carbon::now();
                    $payoutCreatedDate = Carbon::parse($payoutDetail->created_at);
                    $difference = $currentDate->diffInMinutes($payoutCreatedDate);
                    if($difference > $merchantPayoutDetailTime) {
                        return DashboardUtils::errorResponse("Payout request cooling period is over, you can't cancel this request");
                    }
                    if($this->payout->cancelPayoutRequest($merchantId, $payout_id)) {
                        DashboardUtils::LogDB("PAYOUT", "Payout Request Cancelled, PayoutID: {$payout_id}");
                        return DashboardUtils::successResponse("Payout request is cancelled");
                    }
                }

            }
            return DashboardUtils::errorResponse("Invalid Operation");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while cancel payout request");
        }
    }

    public function getTodayPayoutSummary($merchantId)
    {
        try {
            $data = $this->payout->getTodayPayoutSummary($merchantId);
            if(isset($data)) {
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
    public function getPayoutSummary($merchantId)
    {
        try {
            $data = $this->payout->getSummaryData($merchantId);
            if(isset($data)) {
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

    public function getPendingPayoutSummary($merchantId)
    {
        try {
            $data = $this->payout->getPendingPayoutSummary($merchantId);
            if(isset($data)) {
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
