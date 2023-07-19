<?php


namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantBalance extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_balance';
    protected $primaryKey = 'merchant_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $appends = ["update_date_ind"];

    public function getUpdateDateIndAttribute() {
        $originalDate = $this->updated_at;
        if(isset($originalDate)) {
            return Carbon::parse($originalDate, "UTC")->setTimezone("Asia/Kolkata")->format("d/m/y H:i:s");
        }
        return "";
    }


    public function getStatement($merchantId, $filterData, $limit, $pageNo) {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);
            if(isset($filterData) && !empty($filterData)) {
                if(isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                    $data->where("pay_date", ">=", $filterData['start_date']);
                    $data->where("pay_date", "<=", $filterData['end_date']);
                }
            }
            Paginator::currentPageResolver(function () use ($pageNo) {
                return $pageNo;
            });

            $data->select([
                'pay_date',
                'open_balance',
                'payin',
                'payout',
                'refund',
                'un_settled',
                'closing_balance',
                'updated_at'
            ]);
            $data->orderBy('pay_date', 'desc');
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

    public function getChartData($merchantId, $startData, $endDate)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->where("pay_date", ">=", $startData)
                ->where("pay_date", "<=", $endDate)
                ->select(
                    DB::raw("pay_date"),
                    DB::raw("SUM(payin) as payin_amount"),
                    DB::raw("SUM(payout) as payout_amount"),
                    DB::raw("SUM(payout) as payout_amount"),
                    DB::raw("SUM(refund) as refund_amount")
                )
                ->groupBy("pay_date")
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

    public function getSummary($merchantId)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->select(
                    DB::raw("SUM(payin) as payin_amount"),
                    DB::raw("SUM(payout) as payout_amount"),
                    DB::raw("SUM(refund) as refund_amount"),
                    DB::raw("SUM(un_settled) as un_settled_amount"),
                    DB::raw("SUM(settled) as settled_amount"),
                )
                ->first()->toArray();
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

    public function getBalance($merchantId) {
        try {
            $data = $this->where("merchant_id", $merchantId)
                ->select(
                    DB::raw("SUM(payin) as payin_amount"),
                    DB::raw("SUM(payout) as payout_amount"),
                    DB::raw("SUM(refund) as refund_amount")
                )
                ->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            report($ex);
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
