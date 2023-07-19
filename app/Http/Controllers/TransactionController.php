<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\TransactionManager;
use App\Exceptions\UnAuthorizedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{

    private $transactionManager;

    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getTransaction(Request $request)
    {
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
        return $this->transactionManager->getTransaction($merchantId, $filterData, $request->limit, $request->page_no);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getTransactionDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->transactionManager->getTransactionDetails($merchantId, $request->transaction_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function resendTransactionWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->transactionManager->resendTransactionWebhook($merchantId, $request->transaction_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function refundTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'amount' => 'required|string',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->transactionManager->refundTransaction($merchantId, $request->transaction_id, $request->amount, $request->reason);
    }

}
