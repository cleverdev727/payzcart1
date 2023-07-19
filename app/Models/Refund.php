<?php


namespace App\Models;


use App\Classes\DashboardUtils;
use App\Classes\Utils\RefundStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Refund extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_refund';
    protected $primaryKey = 'refund_id';
    public $incrementing = false;

    protected $appends = ["refund_date_ind"];

    public function getRefundDateIndAttribute() {
        $originalDate = $this->attributes['created_at'];
        return Carbon::parse($originalDate, "UTC")->setTimezone("Asia/Kolkata")->format("d/m/y H:i:s");
    }

    public function getRefund($merchantId, $filterData, $limit, $pageNo)
    {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);

            if(isset($filterData) && !empty($filterData)) {
                if(isset($filterData['refund_id']) && !empty($filterData['refund_id'])) {
                    $data->where('refund_id', $filterData['refund_id']);
                }
                if(isset($filterData['transaction_id']) && !empty($filterData['transaction_id'])) {
                    $data->where('transaction_id', $filterData['transaction_id']);
                }
                if(isset($filterData['refund_amount']) && !empty($filterData['refund_amount'])) {
                    $data->where('refund_amount', $filterData['refund_amount']);
                }
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $data->whereBetween('created_at', [$filterData['start_date'], $filterData['end_date']]);
                }
            }
            Paginator::currentPageResolver(function () use ($pageNo) {
                return $pageNo;
            });

            $data->select([
                'refund_id',
                'transaction_id',
                'refund_amount',
                'refund_status',
                'refund_type',
                'bank_rrn',
                'is_webhook_call',
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

    public function getRefundDetail($merchantId, $refund_id)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where('refund_id', $refund_id)
                ->select([
                    'refund_id',
                    'transaction_id',
                    'refund_amount',
                    'refund_status',
                    'refund_type',
                    'bank_rrn',
                    'refund_reason',
                    'response_message',
                    'pg_refund_accept_date',
                    'user_credit_expected_date',
                    'is_webhook_call',
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
                    ->where("refund_status", RefundStatus::SUCCESS)
                    ->whereBetween("created_at", [$today_start_date, $today_end_date])
                    ->select([
                        DB::raw("sum(refund_amount) as refund_amount"),
                        DB::raw("count(1) as refund"),
                    ])
                    ->first();
            } else{
                $data = $this->where("merchant_id", $merchantId)
                    ->where("refund_status", RefundStatus::SUCCESS)
                    ->select([
                        DB::raw("sum(refund_amount) as refund_amount"),
                        DB::raw("count(1) as refund"),
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

    public function resendRefundWebhook($merchantId, $refund_id)
    {
        try {
            $this->where("merchant_id", $merchantId)->where("refund_id", $refund_id)->update([
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

    public function getRefundAmountByTransaction($merchantId, $transaction_id)
    {
        try {
            return $this->where("merchant_id", $merchantId)->where("transaction_id", $transaction_id)->sum("refund_amount");
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return -1;
        }
    }

    public function addRefund($merchant_id, $refundId, $transaction_id, $amount, $currency, $pg_name, $meta_id, $refundType, $reason)
    {
        try {
            $this->refund_id = $refundId;
            $this->merchant_id = $merchant_id;
            $this->transaction_id = $transaction_id;
            $this->refund_amount = $amount;
            $this->refund_currency = $currency;
            $this->refund_status = "Processing";
            $this->internal_status = "NEW";
            $this->refund_type = $refundType;
            $this->pg_name = $pg_name;
            $this->meta_id = $meta_id;
            $this->refund_reason = $reason;
            $this->processed_by = "MERCHANT_DASHBOARD";
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

    public function getRefundForReport($merchantId, $requestData)
    {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);
            if(strcmp($requestData['status'], "All") !== 0) {
                $data->where('refund_status', $requestData['status']);
            }
            $data->whereBetween('created_at', [$requestData['start_date'], $requestData['end_date']]);
            $data->select([
                'refund_id',
                'transaction_id',
                'merchant_id',
                'refund_amount',
                'refund_status',
                'refund_type',
                'bank_rrn',
                'refund_reason',
                'response_message',
                'processed_by',
                'created_at'
            ]);
            $data->orderBy('created_at', 'desc');
            $res = $data->get();
            if($data->count() > 0){
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

    public function getTotalRefundAmount($merchantId)
    {
        try {
            return $this->where("merchant_id", $merchantId)
                ->where("refund_status", RefundStatus::SUCCESS)
                ->sum("refund_amount");
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
