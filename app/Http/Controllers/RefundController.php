<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\RefundManager;
use App\Exceptions\UnAuthorizedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{

    private $refundManager;

    public function __construct(RefundManager $refundManager)
    {

        $this->refundManager = $refundManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getRefund(Request $request) {
        $validator = Validator::make($request->all(), [
            'filter_data' => 'nullable|array',
            'page_no' => 'required',
            'limit' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $filterData = DashboardUtils::parseFilterData($request->filter_data);
        return $this->refundManager->getRefund($merchantId, $filterData, $request->limit, $request->page_no);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getRefundDetail(Request $request) {
        $validator = Validator::make($request->all(), [
            'refund_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->refundManager->getRefundDetail($merchantId, $request->refund_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function resendRefundWebhook(Request $request) {
        $validator = Validator::make($request->all(), [
            'refund_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->refundManager->resendRefundWebhook($merchantId, $request->refund_id);
    }
}
