<?php


namespace App\Classes;


use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefundManager
{

    private $refund;

    public function __construct(Refund $refund)
    {
        $this->refund = $refund;
    }

    public function getRefund($merchantId, $filterData, $limit, $page_no)
    {
        try {
            $data = $this->refund->getRefund($merchantId, $filterData, $limit, $page_no);
            if(isset($data)) {
                $result['status'] = true;
                $result['message'] = 'data Retrieve successfully';
                $result['current_page'] = $data->currentPage();
                $result['last_page'] = $data->lastPage();
                $result['is_last_page'] = !$data->hasMorePages();
                $result['total_item'] = $data->total();
                $result['current_item_count'] = $data->count();
                $result['data'] = $data->items();
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

    public function getRefundDetail($merchantId, $refund_id)
    {
        try {
            $data = $this->refund->getRefundDetail($merchantId, $refund_id);
            if(isset($data)) {
                $result['status'] = true;
                $result['message'] = 'data Retrieve successfully';
                $result['data'] = base64_encode(view("components.widget.refund-details")->with("data", $data));
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

    public function getSummary($merchantId)
    {
        try {
            $today_start_date = Carbon::now()->setTimezone("Asia/Kolkata")->format("Y-m-d 00:00:00");
            $today_end_date = Carbon::now()->setTimezone("Asia/Kolkata")->format("Y-m-d 23:59:59");

            //$today_data = $this->refund->getSummaryData($merchantId, $today_start_date, $today_end_date);
            $today_data = null;
            $total_data = $this->refund->getSummaryData($merchantId);
            return [
//                "today_refund" => (isset($today_data)) ? ($today_data->refund ?? 0) : 0,
//                "today_refund_amount" => (isset($today_data)) ? (round(floatval($today_data->refund_amount), 2) ?? 0) : 0,
                "total_refund" => (isset($total_data)) ? ($total_data->refund ?? 0) : 0,
                "total_refund_amount" => (isset($total_data)) ? (round(floatval($total_data->refund_amount), 2) ?? 0) : 0
            ];
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return [
                "today_refund" => 0,
                "today_refund_amount" => 0,
                "total_refund" => 0,
                "total_refund_amount" => 0
            ];
        }
    }

    public function resendRefundWebhook($merchantId, $refund_id)
    {
        try {
            if($this->refund->resendRefundWebhook($merchantId, $refund_id)) {
                $result['status'] = true;
                $result['message'] = 'Webhook Resend Success';
                DashboardUtils::LogDB("REFUND", "Refund Webhook Resend, RefundId: {$refund_id}");
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

}
