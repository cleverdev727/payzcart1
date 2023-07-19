<?php


namespace App\Classes;


use App\Classes\Utils\PayoutStatus;
use App\Exceptions\UnAuthorizedRequest;
use App\Jobs\MerchantReportQueue;
use App\Models\MerchantCreditBalance;
use App\Models\MerchantDashboardLogs;
use App\Models\MerchantDetails;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardUtils
{
    use DispatchesJobs;

    public static function parseFilterData($filterData)
    {
        if (isset($filterData)) {
            if (isset($filterData['start_date']) && !empty($filterData['start_date']) && isset($filterData['end_date']) && !empty($filterData['end_date'])) {
                $filterData['start_date'] = DashboardUtils::TO_UTC((Carbon::parse($filterData['start_date'])->format("Y-m-d 00:00:00")));
                $filterData['end_date'] = DashboardUtils::TO_UTC(Carbon::parse($filterData['end_date'])->format("Y-m-d 23:59:59"));
            }
        }
        return $filterData;
    }

    public static function isKeePays()
    {
        return strcmp($_SERVER['HTTP_HOST'], "app.keepays.com") === 0
            || strcmp($_SERVER['HTTP_HOST'], "app1.keepays.com:8001") === 0;
    }

    public static function validateOtp($merchantId, $gAuthOtp)
    {
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);
        if (isset($merchantDetail)) {
            if ($merchantDetail->is_gauth_enabled) {
                if ((new Google2FA())->verify($gAuthOtp, $merchantDetail->guath_secret)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public static function merchantId()
    {
        if (Auth::check()) {
            return Auth::user()->merchant_id;
        } else {
            throw new UnAuthorizedRequest("Unauthorized");
        }
    }

    public static function TO_UTC($utcDate, $format = "Y-m-d H:i:s")
    {
        if (isset($utcDate) && !empty($utcDate)) {
            return Carbon::parse($utcDate, "Asia/Kolkata")->setTimezone("UTC")->format($format);
        }
        return "";
    }

    public static function LogDB($type, $action)
    {
        $userData=Auth::user();
        if(isset($userData)) {
            $merchantId = Auth::user()->merchant_id;
            $ip = request()->ip();
            $userAgent = request()->userAgent();
            (new MerchantDashboardLogs())->addLog($merchantId, $type, $action, $ip, $userAgent);
        }
    }

    public static function generateRefundId()
    {
        return "refund_D" . Str::random(16);
    }

    public static function findKeyByValue($array, $key, $value, $returnKeyValue)
    {
        $search_key = array_search($value, array_column($array, $key));
        if (gettype($search_key) !== "boolean") {
            return floatval($array[$search_key][$returnKeyValue]);
        }
        return 0;
    }

    public static function putMerchantCountData($merchantId, $merchantData, $minute = 60)
    {
        return Cache::put($merchantId, json_encode($merchantData), Carbon::now()->addMinutes($minute));
    }

    public static function getMerchantCountData($merchantId)
    {
        return Cache::get($merchantId);
    }

    public static function generateRandomNumber($length = 18): string
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function errorResponse($message, $status_code = 400, $data = null)
    {
        return response()->json([
            "status" => false,
            "message" => $message,
            "data" => $data,
        ])->setStatusCode($status_code);
    }

    public static function successResponse($message, $data = null)
    {
        return response()->json([
            "status" => true,
            "message" => $message,
            "data" => $data,
        ])->setStatusCode(200);
    }

    public static function merchantReportJob($reportId)
    {
        try {
            $job = (new MerchantReportQueue($reportId))
                ->onQueue('merchant_report_queue_v1');
            dispatch($job);
        } catch (\Exception $ex) {
            Log::critical('Job Error', ['merchantReportJob' => $ex->getMessage()]);
        }
    }

    //    static public function merchantBalance($merchant_id) {
//        try {
////            self::UpdateMerchantBalance($merchant_id,false);
//            $merchantBalance = (new MerchantBalance())->getBalance($merchant_id);
//            $availableBalance = 0;
//            if(isset($merchantBalance)) {
//                $payinAmount = floatval($merchantBalance->payin_amount);
//                $payoutAmount = floatval($merchantBalance->payout_amount);
//                $refundAmount = floatval($merchantBalance->refund_amount);
//                $availableBalance = $payinAmount - ($payoutAmount + $refundAmount);
//            }
//            return floatval($availableBalance);
//        } catch (\Exception $ex) {
//            Log::debug('Error', [
//                'class' => __CLASS__,
//                'function' => __METHOD__,
//                'file' => $ex->getFile(),
//                'line_no' => $ex->getLine(),
//                'error_message' => $ex->getMessage(),
//            ]);
//            return 0;
//        }
//    }
    public static function getMerchantBalanceForPayout($merchantId)
    {
        try {
            $merchantDetails = (new MerchantDetails())->getMerchantDetailById($merchantId);

            $totalCollection = 0;

            if ($merchantDetails->is_settlement_enable) {
                $totalCollection = (new Transaction())->getTotalPayableAmount($merchantId);
            }

            $totalCredit = (new MerchantCreditBalance())->getTotalCreditAmount($merchantId);
            $totalRefund = (new Refund())->getTotalRefundAmount($merchantId);

            $totalBalance = floatval(($totalCollection + $totalCredit) - $totalRefund);

            $totalWithdrawal = (new Payout())->where("merchant_id", $merchantId)->where(function ($q) {
                $q->where("payout_status", PayoutStatus::SUCCESS);
                $q->orwhere("payout_status", PayoutStatus::INITIALIZED);
                $q->orwhere("payout_status", PayoutStatus::LOWBAL);
                $q->orWhere("payout_status", PayoutStatus::PENDING);
                $q->orWhere("payout_status", PayoutStatus::PROCESSING);
            })->sum("total_amount");

            $totalBalance = floatval($totalBalance - $totalWithdrawal);
            return round($totalBalance, 2);
        } catch (\Exception $ex) {
            return 0;
        }
    }

    //    public static function UpdateMerchantBalance($merchantId,$isconsole=false)
//    {
//        try {
//            $dt = \Illuminate\Support\Carbon::now()->toDateString();
//            if($isconsole) {
//                echo "\n" . $dt;
//            }
//            //production
//
//            //    $resall= DB::connection('mysqlread')->table('tbl_transaction')->where('merchant_id',$merchantId)->where('payment_status',PaymentStatus::SUCCESS)->where('txn_date',$dt)->sum('payable_amount');
//            $resall1= DB::connection('mysqlread')
//                ->table('tbl_payout')
//                ->where('merchant_id',$merchantId)
//                ->where(function ($q) {
//                    $q->where('payout_status', '<>', PayoutStatus::FAILED);
//                    $q->Where('payout_status', '<>', PayoutStatus::CANCELLED);
//                })
//                ->where('pxn_date',$dt)
//                ->sum('total_amount');
//            $resall2= DB::connection('mysqlread')->table('tbl_refund')
//                ->where('merchant_id',$merchantId)
//                ->where('refund_status','<>',RefundStatus::FAILED)
//                ->where('ref_date',$dt)
//                ->sum('refund_amount');
//
//
//            //testing just for ssum
//            // $resall = DB::table('tbl_transaction')->where('merchant_id',$merchantId)->where('txn_date', $dt)->sum('payable_amount');
//            // $resall1 = DB::table('tbl_payout')->where('merchant_id', $merchantId)->where('pxn_date', $dt)->sum('total_amount');
//            //  dd($resall1);
//            //  $resall2 = DB::table('tbl_refund')->where('merchant_id',$merchantId)->where('ref_date', $dt)->sum('refund_amount');
//            if($isconsole) {
//                echo "\n" . $dt . " " . $merchantId . " " . $resall1 . " " . $resall2;
//            }
//            DB::table('tbl_balance')->updateOrInsert([
//                'merchant_id' => $merchantId,
//                'pay_date' => $dt
//            ], [
//                'payout' => $resall1,
//                'refund' => $resall2
//            ]);
//        } catch (\Exception $ex) {
//            Log::debug('Error', [
//                'class' => __CLASS__,
//                'function' => __METHOD__,
//                'file' => $ex->getFile(),
//                'line_no' => $ex->getLine(),
//                'error_message' => $ex->getMessage(),
//            ]);
////            throw $ex; // ToDo:: TBD
//            //throw new InvalidTransactionException("ACCOUNT_NOT_APPROVED", "Your account not approved", "Your account not approved", 400);
//        }
//    }
    public static function renderPayoutAlert()
    {
        try {
            $alertData = [];
            $merchantId = DashboardUtils::merchantId();
            $merchantDetails = (new MerchantDetails())->getMerchantDetailById($merchantId);
            if ($merchantDetails->payout_alert_limit > 0) {
                $availableBalance = DashboardUtils::getMerchantBalanceForPayout($merchantId);
                if (floatval($availableBalance) < floatval($merchantDetails->payout_alert_limit)) {
                    $alertData[] = [
                        "alert_title" => "Low Balance Alert",
                        "alert" => "Your Payout Balance is ₹ $availableBalance, Please Load Balance immediately to avoid service disruption",
                        "alert_type" => "alert-primary",
                    ];
                }
            }
            if (sizeof($alertData) > 0) {
                return $alertData;
            }
            return null;
        } catch (\Exception $ex) {
            return null;
        }
    }
}
