<?php


namespace App\Models;


use App\Classes\Utils\DownloadLimit;
use App\Classes\Utils\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_transaction';
    protected $primaryKey = 'transaction_id';
    public $incrementing = false;

    protected $appends = [
        "transaction_date_ind"
    ];

    public function getTransactionDateIndAttribute() {
        $originalDate = $this->created_at;
        if(isset($originalDate)){
            return Carbon::parse($originalDate, "UTC")->setTimezone("Asia/Kolkata")->format("d/m/y H:i:s");
        }
        return null;

    }

    public function getAssociateFeesAttribute() {
        $paymentStatus = $this->attributes['payment_status'];
        $originalData = $this->attributes['associate_fees'];
        if(strcmp($paymentStatus, PaymentStatus::SUCCESS) === 0) {
            if(isset($originalData)) {
                return round($originalData, 3);
            }
        }
        return 0;
    }

    public function getPgFeesAttribute() {
        $paymentStatus = $this->attributes['payment_status'];
        $originalData = $this->attributes['pg_fees'];
        if(strcmp($paymentStatus, PaymentStatus::SUCCESS) === 0) {
            if(isset($originalData)) {
                return round($originalData, 3);
            }
        }
        return 0;
    }

    public function getPayableAmountAttribute() {
        $paymentStatus = $this->attributes['payment_status'];
        $originalData = $this->attributes['payable_amount'];
        if(strcmp($paymentStatus, PaymentStatus::SUCCESS) === 0) {
            if(isset($originalData)) {
                return round($originalData, 3);
            }
        }
        return 0;
    }

    public function getTransaction($merchantId, $filterData, $limit, $pageNo)
    {
        try {
            $txn = $this->newQuery();
            $txn->where("merchant_id", $merchantId);
            if(isset($filterData) && sizeof($filterData) > 0) {

                if(isset($filterData['transaction_id']) && !empty($filterData['transaction_id'])) {
                    $txn->where('transaction_id', $filterData['transaction_id']);
                }
                if(isset($filterData['order_id']) && !empty($filterData['order_id'])) {
                    $txn->where('merchant_order_id', $filterData['order_id']);
                }
                if(isset($filterData['customer_email']) && !empty($filterData['customer_email'])) {
                    $txn->where('customer_email', $filterData['customer_email']);
                }
                if(isset($filterData['customer_mobile']) && !empty($filterData['customer_mobile'])) {
                    $txn->where('customer_mobile', $filterData['customer_mobile']);
                }
                if(isset($filterData['payment_amount']) && !empty($filterData['payment_amount'])) {
                    $txn->where('payment_amount', $filterData['payment_amount']);
                }

                if(isset($filterData['status']) && !empty($filterData['status'])) {
                    if(strcmp($filterData['status'], "All") !== 0){
                        $txn->where('payment_status', $filterData['status']);
                    }
                }

                if(isset($filterData['bank_rrn']) && !empty($filterData['bank_rrn'])) {
                    $txn->where('bank_rrn', $filterData['bank_rrn']);
                }
                if(isset($filterData['udf1']) && !empty($filterData['udf1'])) {
                    $txn->where('udf1', $filterData['udf1']);
                }
                if(isset($filterData['udf2']) && !empty($filterData['udf2'])) {
                    $txn->where('udf2', $filterData['udf2']);
                }
                if(isset($filterData['udf3']) && !empty($filterData['udf3'])) {
                    $txn->where('udf3', $filterData['udf3']);
                }
                if(isset($filterData['udf4']) && !empty($filterData['udf4'])) {
                    $txn->where('udf4', $filterData['udf4']);
                }
                if(isset($filterData['udf5']) && !empty($filterData['udf5'])) {
                    $txn->where('udf5', $filterData['udf5']);
                }
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $txn->whereBetween('created_at', [$filterData['start_date'], $filterData['end_date']]);
                }

            }

            Paginator::currentPageResolver(function () use ($pageNo) {
                return $pageNo;
            });

            $txn->select([
                'transaction_id',
                'merchant_order_id',
                'customer_email',
                'customer_mobile',
                'customer_id',
                'customer_email',
                'customer_mobile',
                'payment_amount',
                'pg_fees',
                'pg_name',
                'associate_fees',
                'payable_amount',
                'payment_status',
                'payment_method',
                'pg_res_msg',
                'bank_rrn',
                'is_webhook_call',
                'created_at'
            ]);
            $txn->orderBy('created_at', 'desc');
            if($txn->count() > 0){
                return $txn->paginate($limit);
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

    public function getTransactionSummary($filterData,$merchantId) {
        try {
            $transactions = $this->newQuery();
            $transactions->where("merchant_id", $merchantId);
            if(isset($filterData) && sizeof($filterData) > 0) {

                if(isset($filterData['transaction_id']) && !empty($filterData['transaction_id'])) {
                    $transactions->where('transaction_id', $filterData['transaction_id']);
                }
                if(isset($filterData['order_id']) && !empty($filterData['order_id'])) {
                    $transactions->where('merchant_order_id', $filterData['order_id']);
                }
                if(isset($filterData['customer_email']) && !empty($filterData['customer_email'])) {
                    $transactions->where('customer_email', $filterData['customer_email']);
                }
                if(isset($filterData['customer_mobile']) && !empty($filterData['customer_mobile'])) {
                    $transactions->where('customer_mobile', $filterData['customer_mobile']);
                }
                if(isset($filterData['payment_amount']) && !empty($filterData['payment_amount'])) {
                    $transactions->where('payment_amount', $filterData['payment_amount']);
                }

                if(isset($filterData['status']) && !empty($filterData['status'])) {
                    if(strcmp($filterData['status'], "All") !== 0){
                        $transactions->where('payment_status', $filterData['status']);
                    }
                }

                if(isset($filterData['bank_rrn']) && !empty($filterData['bank_rrn'])) {
                    $transactions->where('bank_rrn', $filterData['bank_rrn']);
                }
                if(isset($filterData['udf1']) && !empty($filterData['udf1'])) {
                    $transactions->where('udf1', $filterData['udf1']);
                }
                if(isset($filterData['udf2']) && !empty($filterData['udf2'])) {
                    $transactions->where('udf2', $filterData['udf2']);
                }
                if(isset($filterData['udf3']) && !empty($filterData['udf3'])) {
                    $transactions->where('udf3', $filterData['udf3']);
                }
                if(isset($filterData['udf4']) && !empty($filterData['udf4'])) {
                    $transactions->where('udf4', $filterData['udf4']);
                }
                if(isset($filterData['udf5']) && !empty($filterData['udf5'])) {
                    $transactions->where('udf5', $filterData['udf5']);
                }
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $transactions->whereBetween('created_at', [$filterData['start_date'], $filterData['end_date']]);
                }

            }
             $transactions->select([
                DB::raw("COUNT(*) as total_txn"),
                DB::raw("SUM(payment_amount) as total_payment_amount"),
                DB::raw("SUM(IF(payment_status='Success',pg_fees,0)) as total_pg_fees"),
                DB::raw("SUM(IF(payment_status='Success',associate_fees,0)) as total_associate_fees"),
                DB::raw("SUM(IF(payment_status='Success',payable_amount,0)) as total_payable_amount"),
            ]);
            $result = $transactions->first();
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

    public function getTransactionDetails($merchantId, $transaction_id)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where('transaction_id', $transaction_id)
                ->select([
                    'transaction_id',
                    'merchant_order_id',
                    'customer_email',
                    'customer_mobile',
                    'customer_name',
                    'payment_amount',
                    'payment_status',
                    'payment_method',
                    'pg_fees',
                    'pg_res_msg',
                    'bank_rrn',
                    'is_webhook_call',
                    'udf1',
                    'udf2',
                    'udf3',
                    'udf4',
                    'udf5',
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

    public function getSummaryData($merchantId, $today_start_date = null, $today_end_date= null)
    {
        try {
            $data = null;
            if(isset($today_start_date) && isset($today_end_date)) {
                $data = $this->where("merchant_id", $merchantId)
                    ->where(function ($q) {
                        $q->where("payment_status", "Success");
                        $q->orWhere("payment_status", "Full Refund");
                        $q->orWhere("payment_status", "Partial Refund");
                    })
                    ->whereBetween("created_at", [$today_start_date, $today_end_date])
                    ->select([
                        DB::raw("sum(payment_amount + pg_fees) as today_transaction_amount"),
                        DB::raw("count(1) as today_transaction"),
                    ])
                    ->first();
            } else{
                $data = $this->where("merchant_id", $merchantId)
                    ->where(function ($q) {
                        $q->where("payment_status", "Success");
                        $q->orWhere("payment_status", "Full Refund");
                        $q->orWhere("payment_status", "Partial Refund");
                    })
                    ->select([
                        DB::raw("sum(payment_amount + pg_fees) as today_transaction_amount"),
                        DB::raw("count(1) as today_transaction"),
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

    public function resendTransactionWebhook($merchantId, $transaction_id)
    {
        try {
            $this->where("merchant_id", $merchantId)->where("transaction_id", $transaction_id)->update([
                "is_webhook_call" => 0
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

    public function getTransactionDetailsForRefund($merchantId, $transaction_id)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where("transaction_id", $transaction_id)
                ->where(function ($q) {
                    $q->where("payment_status", "Success");
                    $q->orWhere("payment_status", "Full Refund");
                    $q->orWhere("payment_status", "Partial Refund");
                })
                ->first(["transaction_id", "payment_amount", "pg_name", "meta_id", "currency"]);
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

    public function markAsRefund($merchantId, $transaction_id, $refundType)
    {
        try {
            $this->where("merchant_id", $merchantId)
                ->where("transaction_id", $transaction_id)
                ->update([
                    "payment_status" => $refundType
                ]);
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
        }
    }

    public function getChartData($merchantId, $startDate, $endDate)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                    ->where(function ($q) {
                        $q->where("payment_status", "Success");
                        $q->orWhere("payment_status", "Full Refund");
                        $q->orWhere("payment_status", "Partial Refund");
                    })
                    ->whereBetween("created_at", [$startDate, $endDate])
                    ->select([
                        DB::raw("sum(payment_amount) as total_amount"),
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

    public function getSuccessTransactionAmount($merchantId)
    {
        try {
            return $this->where('merchant_id', $merchantId)
                ->where(function ($q) {
                    $q->where("payment_status", PaymentStatus::SUCCESS);
                    $q->where("payment_status", PaymentStatus::PARTIAL_REFUND);
                    $q->where("payment_status", PaymentStatus::FULL_REFUND);
                })
                ->sum("payment_amount","pg_fees");
        }catch (QueryException $ex){
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

    public function getTransactionForReport($merchantId, $requestData)
    {
        try {
            $txn = $this->newQuery();
            $txn->where("merchant_id", $merchantId);
            if(strcmp($requestData['status'], "All") !== 0) {
                $txn->where('payment_status', $requestData['status']);
            }
            $txn->whereBetween('created_at', [$requestData['start_date'], $requestData['end_date']]);
            $txn->select([
                'transaction_id',
                'merchant_order_id',
                'merchant_id',
                'customer_email',
                'customer_mobile',
                'payment_amount',
                'pg_fees',
                'associate_fees',
                'payment_status',
                'payment_method',
                'bank_rrn',
                'udf1',
                'udf2',
                'udf3',
                'udf4',
                'udf5',
                'customer_ip',
                'created_at'
            ]);

            $res = $txn->orderBy('created_at', 'desc')->get();

            if($txn->count()){
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

    public function getRecordForReport($merchantId, $filterStatus, $filterStartDate, $filterEndDate)
    {
        try {
            $txn = $this->newQuery();
            $txn->where("merchant_id", $merchantId);
            if(strcmp($filterStatus, "All") !== 0) {
                $txn->where('payment_status', $filterStatus);
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

    public function getUnsettledTransactionSummary($merchantId)
    {
        try {
            $payable_amount = $this->where("merchant_id", $merchantId)->where("is_settled", 0)->where("payment_status", PaymentStatus::SUCCESS)->sum("payable_amount");
            return $payable_amount;
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
    public function getSuccessTransactionSummary($merchantId)
    {
        try {
            $payable_amount = $this->where("merchant_id", $merchantId)->where("payment_status", PaymentStatus::SUCCESS)->sum("payment_amount");
            return $payable_amount;
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

    public function todayTransactionSummary($merchantId)
    {
        try {
            $startDate = Carbon::now("Asia/Kolkata")->format("Y-m-d 00:00:00");
            $endDate = Carbon::now("Asia/Kolkata")->format("Y-m-d 23:59:59");

            $startDate = Carbon::parse($startDate, "Asia/Kolkata")->setTimezone("UTC")->format("Y-m-d H:i:s");
            $endDate = Carbon::parse($endDate, "Asia/Kolkata")->setTimezone("UTC")->format("Y-m-d H:i:s");

            $data = $this->where("merchant_id", $merchantId)
                ->where("payment_status", PaymentStatus::SUCCESS)
                ->whereBetween("created_at", [$startDate, $endDate])
                ->select(
                    DB::raw("COUNT(1) as total"),
                    DB::raw("SUM(payment_amount) as amount")
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

    public function getTransactionDetailsForRecon($merchantId, $transaction_id)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)->where("transaction_id", $transaction_id)->first(["txn_token"]);
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

    public function getTotalPayableAmount($merchantId)
    {
        try {
            return $this->where("merchant_id", $merchantId)
                ->where("payment_status", PaymentStatus::SUCCESS)
                ->sum("payable_amount");
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

}
