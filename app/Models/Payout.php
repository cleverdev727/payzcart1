<?php


namespace App\Models;


use App\Classes\DashboardUtils;
use App\Classes\Utils\DownloadLimit;
use App\Classes\Utils\PaymentStatus;
use App\Classes\Utils\PayoutStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Payout extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_payout';
    protected $primaryKey = 'payout_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        "is_approved" => "boolean"
    ];

    protected $appends = [
        "payout_date_ind",
        "payout_approved_date_ind",
    ];

    public function getPayoutDateIndAttribute() {
        $createdAtOriginal = $this->created_at;
        if(isset($createdAtOriginal)) {
            return Carbon::parse($createdAtOriginal, "UTC")->setTimezone("Asia/Kolkata")->format("d/m/y H:i:s");
        }
        return $createdAtOriginal;
    }

    public function getPayoutApprovedDateIndAttribute() {
        $originalDate = $this->attributesapproved_at;
        if(isset($originalDate)) {
            return Carbon::parse($originalDate, "UTC")->setTimezone("Asia/Kolkata")->format("d/m/y H:i:s");
        }
        return $originalDate;
    }

    public function getPayoutStatusAttribute() {
        $originalData = $this->attributes['payout_status'];
        if(strcmp($originalData, "LOWBAL") === 0) {
            return "Pending";
        }
        return $originalData;
    }

    public function getPgResponseMsgAttribute() {
        $originalData = $this->attributes['pg_response_msg'];
        if(isset($originalData)) {
            $payoutStatus = $this->attributes['payout_status'];
            if(strcmp($payoutStatus, PayoutStatus::SUCCESS) === 0 || strcmp($payoutStatus, PayoutStatus::FAILED) === 0) {
                return $originalData;
            }
        }

        return "-";
    }

    //total_amount payout_fees associate_fees
    public function getTotalAmountAttribute() {
        $payoutStatus = $this->attributes["payout_status"];
        $payoutAmount = $this->attributes["payout_amount"];
        $originalData = $this->attributes['total_amount'];
        if ($originalData) {
            return floatval(round($originalData, 3));
        }
        return $payoutAmount;
    }
    public function getPayoutFeesAttribute() {
        $payoutStatus = $this->attributes["payout_status"];
        $originalData = $this->attributes['payout_fees'];
        if(isset($originalData)) {
            return floatval(round($originalData, 3));
        }
        return 0;
    }
    public function getAssociateFeesAttribute() {
        $payoutStatus = $this->attributes["payout_status"];
        $originalData = $this->attributes['associate_fees'];
        if (isset($originalData)) {
            return floatval(round($originalData, 3));
        }
        return 0;
    }

    public function getPayout($merchantId, $filterData, $limit, $page_no)
    {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);
            if(isset($filterData) && !empty($filterData)) {

                if(isset($filterData["payout_id"]) && !empty(isset($filterData["payout_id"]))) {
                    $data->where("payout_id", $filterData["payout_id"]);
                }

                if(isset($filterData["ref_id"]) && !empty(isset($filterData["ref_id"]))) {
                    $data->where("merchant_ref_id", $filterData["ref_id"]);
                }

                if(isset($filterData["bank_rrn"]) && !empty(isset($filterData["bank_rrn"]))) {
                    $data->where("bank_rrn", $filterData["bank_rrn"]);
                }

                if(isset($filterData["account_no"]) && !empty(isset($filterData["account_no"]))) {
                    $data->where("bank_account", $filterData["account_no"]);
                }

                if(isset($filterData["ifsc_code"]) && !empty(isset($filterData["ifsc_code"]))) {
                    $data->where("ifsc_code", $filterData["ifsc_code"]);
                }

                if(isset($filterData["customer_email"]) && !empty(isset($filterData["customer_email"]))) {
                    $data->where("customer_email", $filterData["customer_email"]);
                }

                if(isset($filterData["mobile_no"]) && !empty(isset($filterData["mobile_no"]))) {
                    $data->where("customer_mobile", $filterData["mobile_no"]);
                }

                if(isset($filterData["payout_amount"]) && !empty(isset($filterData["payout_amount"]))) {
                    $data->where("payout_amount", $filterData["payout_amount"]);
                }

                if(isset($filterData["status"]) && !empty(isset($filterData["status"])) && strcmp($filterData['status'], "All") !== 0) {
                    $data->where("payout_status", $filterData["status"]);
                }

                if(isset($filterData["udf1"]) && !empty(isset($filterData["udf1"]))) {
                    $data->where("udf1", $filterData["udf1"]);
                }

                if(isset($filterData["udf2"]) && !empty(isset($filterData["udf2"]))) {
                    $data->where("udf2", $filterData["udf2"]);
                }

                if(isset($filterData["udf3"]) && !empty(isset($filterData["udf3"]))) {
                    $data->where("udf3", $filterData["udf3"]);
                }

                if(isset($filterData["udf4"]) && !empty(isset($filterData["udf4"]))) {
                    $data->where("udf4", $filterData["udf4"]);
                }

                if(isset($filterData["udf5"]) && !empty(isset($filterData["udf5"]))) {
                    $data->where("udf5", $filterData["udf5"]);
                }
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $data->whereBetween('created_at', [$filterData['start_date'], $filterData['end_date']]);
                }
            }
            Paginator::currentPageResolver(function () use ($page_no) {
                return $page_no;
            });

            $data->select([
                'payout_id',
                'merchant_ref_id as ref_id',
                'payout_amount',
                'payout_fees',
                'total_amount',
                'associate_fees',
                'payout_type',
                'account_holder_name',
                'bank_account',
                'ifsc_code',
                'customer_name',
                'customer_email',
                'customer_mobile',
                'account_holder_name',
                'payout_status',
                'bank_rrn',
                'pg_response_msg',
                'is_webhook_called',
                'is_approved',
                'approved_at',
                'created_at'
            ]);
            $data->orderBy('created_at', 'desc');
            if($data->count() > 0){
                return $data->paginate($limit);
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }


    public function getPayoutSummary($filterData,$merchantId)
    {
        try {
            $payout = $this->newQuery();
            $payout->where("merchant_id", $merchantId);
            if(isset($filterData) && !empty($filterData)) {

                if(isset($filterData["payout_id"]) && !empty(isset($filterData["payout_id"]))) {
                    $payout->where("payout_id", $filterData["payout_id"]);
                }

                if(isset($filterData["ref_id"]) && !empty(isset($filterData["ref_id"]))) {
                    $payout->where("merchant_ref_id", $filterData["ref_id"]);
                }

                if(isset($filterData["bank_rrn"]) && !empty(isset($filterData["bank_rrn"]))) {
                    $payout->where("bank_rrn", $filterData["bank_rrn"]);
                }

                if(isset($filterData["account_no"]) && !empty(isset($filterData["account_no"]))) {
                    $payout->where("bank_account", $filterData["account_no"]);
                }

                if(isset($filterData["ifsc_code"]) && !empty(isset($filterData["ifsc_code"]))) {
                    $payout->where("ifsc_code", $filterData["ifsc_code"]);
                }

                if(isset($filterData["customer_email"]) && !empty(isset($filterData["customer_email"]))) {
                    $payout->where("customer_email", $filterData["customer_email"]);
                }

                if(isset($filterData["mobile_no"]) && !empty(isset($filterData["mobile_no"]))) {
                    $payout->where("customer_mobile", $filterData["mobile_no"]);
                }

                if(isset($filterData["payout_amount"]) && !empty(isset($filterData["payout_amount"]))) {
                    $payout->where("payout_amount", $filterData["payout_amount"]);
                }

                if(isset($filterData["status"]) && !empty(isset($filterData["status"])) && strcmp($filterData['status'], "All") !== 0) {
                    $payout->where("payout_status", $filterData["status"]);
                }

                if(isset($filterData["udf1"]) && !empty(isset($filterData["udf1"]))) {
                    $payout->where("udf1", $filterData["udf1"]);
                }

                if(isset($filterData["udf2"]) && !empty(isset($filterData["udf2"]))) {
                    $payout->where("udf2", $filterData["udf2"]);
                }

                if(isset($filterData["udf3"]) && !empty(isset($filterData["udf3"]))) {
                    $payout->where("udf3", $filterData["udf3"]);
                }

                if(isset($filterData["udf4"]) && !empty(isset($filterData["udf4"]))) {
                    $payout->where("udf4", $filterData["udf4"]);
                }

                if(isset($filterData["udf5"]) && !empty(isset($filterData["udf5"]))) {
                    $payout->where("udf5", $filterData["udf5"]);
                }
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $payout->whereBetween('created_at', [$filterData['start_date'], $filterData['end_date']]);
                }
            }
            $payout->select([
                DB::raw("COUNT(*) as total_payout"),
                DB::raw("SUM(payout_amount) as payout_amount"),
                DB::raw("SUM(IF(payout_status!='Failed',payout_fees,0)) as total_payout_fees"),
                DB::raw("SUM(IF(payout_status!='Failed',associate_fees,0)) as total_associate_fees"),
                DB::raw("SUM(IF(payout_status!='Failed',total_amount,0)) as total_payout_amount"),
            ]);
            $result = $payout->first();
            if(isset($result)){
                return $result;
            }
            return null;
        } catch (QueryException $ex) {
            Log::error('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function getPayoutDetails($merchantId, $payout_id)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where("payout_id", $payout_id)
                ->select([
                    'payout_id',
                    'merchant_ref_id as ref_id',
                    'payout_amount',
                    'payout_fees',
                    'payout_type',
                    'customer_name',
                    'customer_email',
                    'customer_mobile',
                    'account_holder_name',
                    'bank_account',
                    'ifsc_code',
                    'vpa_address',
                    'bank_name',
                    'payout_status',
                    'bank_rrn',
                    'is_webhook_called',
                    'udf1',
                    'udf2',
                    'udf3',
                    'udf4',
                    'udf5',
                    'approved_at',
                    'created_at'
                ])
                ->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function getSummaryData($merchantId, $today_start_date = null, $today_end_date = null)
    {
        try {
            $data = null;
            if(isset($today_start_date) && isset($today_end_date)) {
                $data = $this->where("merchant_id", $merchantId)
                    ->where("payout_status", "!=","Failed")
                    ->whereBetween("created_at", [$today_start_date, $today_end_date])
                    ->select([
                        DB::raw("sum(payout_amount + payout_fees) as payout_amount"),
                        DB::raw("count(1) as payout"),
                    ])
                    ->first();
            } else{
                $data = $this->where("merchant_id", $merchantId)
                    ->where("payout_status", "!=","Failed")
                    ->select([
                        DB::raw("sum(payout_amount + payout_fees) as payout_amount"),
                        DB::raw("count(1) as payout"),
                    ])
                    ->first();
            }

            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function getPendingSummaryData(string $merchantId)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where("payout_status", "Pending")
                ->select([
                    DB::raw("sum(payout_amount + payout_fees) as payout_amount"),
                    DB::raw("count(1) as payout"),
                ])
                ->first();

            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function resendPayoutWebhook($merchantId, $payout_id)
    {
        try {
            $this->where("merchant_id", $merchantId)->where("payout_id", $payout_id)->update([
                "is_webhook_called" => 0
            ]);
            return true;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function getChartData($merchantId, $startDate, $endDate)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where("payout_status", "!=", "Failed")
                ->whereBetween("created_at", [$startDate, $endDate])
                ->select([
                    DB::raw("sum(payout_amount) as total_amount"),
                    DB::raw("count(1) as total"),
                    DB::raw("DATE(created_at) as date"),
                    "created_at"
                ])
                ->groupBy(DB::raw("DATE(created_at)"))
                ->get()->toArray();
            if(sizeof($data) > 0) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function createPayoutRequest(
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
    )
    {
        try {
            $this->payout_id = $payoutId;
            $this->merchant_ref_id = $payout_ref_id;
            $this->merchant_id = $merchantId;
            $this->payout_amount = $payout_amount;
            $this->payout_fees = $payoutFees;
            $this->total_amount = floatval($payout_amount) + floatval($payoutFees) + floatval($associateFees);
            $this->associate_fees = $associateFees;
            $this->payout_currency = "INR";
            $this->payout_type = $payout_type;
            $this->customer_name = $customer_name;
            $this->account_holder_name = $customer_name;
            $this->bank_account = $account_number;
            $this->ifsc_code = $ifsc_code;
            $this->bank_name = $bankName;
            $this->payout_status = PayoutStatus::INITIALIZED;
            $this->pxn_date = Carbon::now()->toDateString();
            $this->is_approved = 1;
            $this->approved_at = Carbon::now()->format("Y-m-d H:i:s");
            $this->payout_by = "MERCHANT_DASHBOARD";
            $this->customer_email = "customer@db.com";
            $this->customer_name = "Customer";
            $this->customer_mobile = "0000000000";
            if($this->save()) {
                return true;
            }
            return false;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function getTotalPayoutAmount($merchantId)
    {
        try {
            return $this->where("merchant_id", $merchantId)
                ->where("payout_status", "!=", PayoutStatus::FAILED)
                ->sum("total_amount");
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return 0;
        }
    }

    public function getPendingApprovePayout($merchantId)
    {
        try {
            if($this->where("merchant_id", $merchantId)->where("is_approved", false)->where("payout_status", PayoutStatus::INITIALIZED)->exists()) {
                return true;
            }
            return false;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
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
            if(
                $this->where("merchant_id", $merchantId)
                    ->where("payout_id", $payout_id)
                    ->where("payout_status", PayoutStatus::INITIALIZED)
                    ->where("is_approved", false)
                    ->update([
                        "is_approved" => true,
                        "approved_at" => Carbon::now()
                    ])
            ) {
                return true;
            }
            return false;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function cancelPayoutRequest($merchantId, $payout_id)
    {
        try {
            if(
            $this->where("merchant_id", $merchantId)
                ->where("payout_id", $payout_id)
                ->where("payout_status", PayoutStatus::INITIALIZED)
                ->where("is_approved", false)
                ->update([
                    "payout_status" => PayoutStatus::CANCELLED
                ])
            ) {
                return true;
            }
            return false;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function getRecordForReport($merchantId, $filterStatus, $filterStartDate, $filterEndDate)
    {
        try {
            $txn = $this->newQuery();
            $txn->where("merchant_id", $merchantId);
            if(strcmp($filterStatus, "All") !== 0) {
                if(strcmp($filterStatus, "Pending") === 0) {
                    $txn->where(function ($q) {
                        $q->where('payout_status', PayoutStatus::PENDING);
                        $q->orWhere('payout_status', PayoutStatus::LOWBAL);
                    });
                } else {
                    $txn->where('payout_status', $filterStatus);
                }
            }
            $txn->whereBetween('created_at', [$filterStartDate, $filterEndDate]);
            return $txn->count();
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return 0;
        }
    }

    public function getPayoutForReport($merchantId, $requestData)
    {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);
            if(strcmp($requestData['status'], "All") !== 0) {
                if(strcmp($requestData['status'], "Pending") === 0) {
                    $data->where(function ($q) use ($requestData) {
                        $q->where('payout_status', PayoutStatus::PENDING);
                        $q->orWhere('payout_status', PayoutStatus::LOWBAL);
                    });
                } else {
                    $data->where('payout_status', $requestData['status']);
                }
            }
            $data->whereBetween('created_at', [$requestData['start_date'], $requestData['end_date']]);
            $data->select([
                'payout_id',
                'merchant_ref_id',
                'merchant_id',
                'payout_amount',
                'payout_fees',
                'payout_type',
                'customer_email',
                'customer_mobile',
                'customer_name',
                'bank_account',
                'ifsc_code',
                'vpa_address',
                'bank_name',
                'payout_status',
                'bank_rrn',
                'pg_response_msg',
                'payout_by',
                'approved_at',
                'udf1',
                'udf2',
                'udf3',
                'udf4',
                'udf5',
                'customer_ip',
                'created_at'
            ]);

            $res = $data->orderBy('created_at', 'desc')->get();
            if($data->count()){
                return $res;
            }
            return $res;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function getTodayPayoutSummary($merchantId)
    {
        try {
            $startDate = Carbon::now("Asia/Kolkata")->format("Y-m-d 00:00:00");
            $endDate = Carbon::now("Asia/Kolkata")->format("Y-m-d 23:59:59");

            $startDate = Carbon::parse($startDate, "Asia/Kolkata")->setTimezone("UTC")->format("Y-m-d H:i:s ");
            $endDate = Carbon::parse($endDate, "Asia/Kolkata")->setTimezone("UTC")->format("Y-m-d H:i:s ");

            $data = $this->where("merchant_id", $merchantId)
                ->where(function ($q) {
                    $q->where("payout_status", "!=", PayoutStatus::FAILED);
                    $q->Where("payout_status", "!=", PayoutStatus::CANCELLED);
                })
                ->whereBetween("created_at", [$startDate, $endDate])
                ->select(
                    DB::raw("COUNT(1) as total"),
                    DB::raw("SUM(payout_amount) as amount")
                )
                ->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::error('Error while executing SQL Query', [
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
            $data = $this->where("merchant_id", $merchantId)
                ->where(function ($q) {
                    $q->where("payout_status", PayoutStatus::INITIALIZED);
                    $q->orWhere("payout_status", PayoutStatus::PROCESSING);
                    $q->orWhere("payout_status", PayoutStatus::PENDING);
                    $q->orWhere("payout_status", PayoutStatus::LOWBAL);
                })
                ->select(
                    DB::raw("COUNT(1) as total"),
                    DB::raw("SUM(payout_amount) as amount")
                )
                ->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
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
