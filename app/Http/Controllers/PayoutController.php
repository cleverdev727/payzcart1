<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\PayoutManager;
use App\Exceptions\UnAuthorizedRequest;
use App\Models\MerchantDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Razorpay\IFSC\IFSC;

class PayoutController extends Controller
{

    private $payoutManager;

    public function __construct(PayoutManager $payoutManager)
    {
        $this->payoutManager = $payoutManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getPayout(Request $request) {
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
        return $this->payoutManager->getPayout($merchantId, $filterData, $request->limit, $request->page_no);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getPayoutDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'payout_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->payoutManager->getPayoutDetails($merchantId, $request->payout_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function resendPayoutWebhook(Request $request) {
        $validator = Validator::make($request->all(), [
            'payout_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->payoutManager->resendPayoutWebhook($merchantId, $request->payout_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function approvedPayoutRequest(Request $request) {
        $validator = Validator::make($request->all(), [
            'payout_id' => 'required|string',
            'g_auth_otp' => 'nullable',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);

        if(isset($request->g_auth_otp)) {
            if(isset($merchantDetail)) {
                if($merchantDetail->is_gauth_enabled) {
                    if(DashboardUtils::validateOtp($merchantId, $request->g_auth_otp)) {
                        return $this->payoutManager->approvedPayoutRequest($merchantId, $request->payout_id);
                    } else {
                        return DashboardUtils::errorResponse("OTP Verification Failed");
                    }
                } else {
                    return $this->payoutManager->approvedPayoutRequest($merchantId, $request->payout_id);
                }
            } else {
                return DashboardUtils::errorResponse("Error while approve payout");
            }
        } else {
            if($merchantDetail->is_gauth_enabled) {
                return DashboardUtils::errorResponse("Verify Google Authenticator OTP First for approve payout", 400, [
                    'is_gauth_enable' => true
                ]);
            } else {
                return $this->payoutManager->approvedPayoutRequest($merchantId, $request->payout_id);
            }
        }
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function cancelPayoutRequest(Request $request) {
        $validator = Validator::make($request->all(), [
            'payout_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        return $this->payoutManager->cancelPayoutRequest($merchantId, $request->payout_id);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function createPayoutRequest(Request $request) {
        $validator = Validator::make($request->all(), [
            'payout_type' => 'required|string|in:IMPS,NEFT,RTGS',
            'payout_amount' => 'required|valid_amount',
            'payout_ref_id' => 'required|string|valid_reference_id',
            'customer_name' => 'required|string',
//            'customer_email' => 'required|string|customer_email',
//            'customer_mobile' => 'required|string|customer_mobile',
            'account_number' => 'required_if:payout_type,==,IMPS,NEFT,RTGS|valid_account_number',
            'ifsc_code' => 'required_if:payout_type,==,IMPS,NEFT,RTGS|valid_ifsc',
//            'vpa' => 'required_if:payout_type,==,UPI|valid_vpa',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }
        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);
        if(!isset($merchantDetail)) {
            return DashboardUtils::errorResponse("Invalid Request");
        }
        if(!$merchantDetail->is_payout_enable) {
            return DashboardUtils::errorResponse("Payout is not activate in your account");
        }
        if(!$merchantDetail->is_dashboard_payout_enable) {
            return DashboardUtils::errorResponse("Payout is not activate in your account");
        }

        $ifsc = trim(strtoupper($request->ifsc_code));
        $bankName = IFSC::getBankName($ifsc);


        $payoutId = "P".DashboardUtils::generateRandomNumber(10);
        $account_number = trim($request->account_number);
        return $this->payoutManager->createPayoutRequest(
            $merchantId,
            $payoutId,
            $request->payout_type,
            $request->payout_amount,
            $request->payout_ref_id,
            $request->customer_name,
            $account_number,
            $ifsc,
            $bankName
        );
    }


}
