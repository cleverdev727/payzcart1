<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\MerchantManager;
use App\Exceptions\UnAuthorizedRequest;
use App\Models\MerchantDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    private $merchantManager;

    public function __construct(MerchantManager $merchantManager)
    {
        $this->merchantManager = $merchantManager;
    }

    public function merchantAuthenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }
        return $this->merchantManager->merchantAuthenticate($request->username, $request->password);
    }

    public function merchantReAuthenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'new_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        return $this->merchantManager->merchantReAuthenticate($request->username, $request->password, $request->new_password);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function merchantChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required|same:new_password'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }
        $merchantId = DashboardUtils::merchantId();
        return $this->merchantManager->merchantChangePassword($merchantId, $request->old_password, $request->new_password);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getSettingDetail()
    {
        $merchantId = DashboardUtils::merchantId();
        return $this->merchantManager->getSettingDetail($merchantId);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function updateConfiguration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payout_delayed_time' => 'required|min:1|max:10',
            'auto_approved_payout' => 'required|bool'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }
        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);
        if (!isset($merchantDetail)) {
            return DashboardUtils::errorResponse("Invalid Request");
        }
        if (!$merchantDetail->is_payout_enable) {
            return DashboardUtils::errorResponse("Payout is not activate in your account");
        }
        return $this->merchantManager->updateConfiguration($merchantId, $request->payout_delayed_time, $request->auto_approved_payout);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function updateWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_webhook' => 'required',
            'payout_webhook' => 'nullable'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }
        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);
        $payoutWebhook = $request->get("payout_webhook");
        if (!isset($merchantDetail)) {
            return DashboardUtils::errorResponse("Invalid Request");
        }
        if (isset($payoutWebhook)) {
            if (!$merchantDetail->is_payout_enable) {
                return DashboardUtils::errorResponse("Payout is not activate in your account");
            }
        }

        return $this->merchantManager->updateWebhook($merchantId, $request->payment_webhook, $payoutWebhook);
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function enableGAuth()
    {
        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);

        if ($merchantDetail->is_gauth_enabled) {
            return DashboardUtils::errorResponse("Google Authenticator already activated in your account");
        }

        $google2fa = app('pragmarx.google2fa');
        $gAuthSecretKey = $google2fa->generateSecretKey();

        if ((new MerchantDetails())->checkGAuthSecretIsExist($gAuthSecretKey)) {
            return DashboardUtils::errorResponse("Error while activate Google Authenticator app, Please try after sometime");
        }

        if ((new MerchantDetails())->setMerchantGAuthSecret($merchantId, $gAuthSecretKey)) {
            $holder = DashboardUtils::isKeePays() ? "KEEPAYS" : "DIGIPAYZONE";
            // $qrCode = (new Google2FA())->getQRCodeUrl($merchantDetail->merchant_name, $holder, $gAuthSecretKey);
            // $qrCodeImg = "https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=$qrCode&chld=L|1&choe=UTF-8";
//            return DashboardUtils::successResponse("Please Verify your Google Authenticator", [
//                "qr_code" => $qrCodeImg
//            ]);
        }
        return DashboardUtils::errorResponse("Error while activate Google Authenticator app, Please try after sometime");
    }

    public function verifyToEnableGAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'g_auth_otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);

        //        if((new Google2FA())->verify($request->g_auth_otp, $merchantDetail->guath_secret)) {
//            if((new MerchantDetails())->setMerchantGAuthAsActivated($merchantId)) {
//                DashboardUtils::LogDB("ACCOUNT", "Google Authenticator Activated");
//                return DashboardUtils::successResponse("Your Google Authenticator activated successfully");
//            } else {
//                return DashboardUtils::errorResponse("Error while activate Google Authenticator app, Please try after sometime");
//            }
//        }
        return DashboardUtils::errorResponse("Invalid OTP");
    }

    public function disableGAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'g_auth_otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $merchantDetail = (new MerchantDetails())->getMerchantDetailById($merchantId);

        //        if((new Google2FA())->verify($request->g_auth_otp, $merchantDetail->guath_secret)) {
//            if((new MerchantDetails())->setMerchantGAuthAsDeactivate($merchantId)) {
//                DashboardUtils::LogDB("ACCOUNT", "Google Authenticator Deactivated");
//                return DashboardUtils::successResponse("Your Google Authenticator deactivate successfully");
//            } else {
//                return DashboardUtils::errorResponse("Error while deactivate Google Authenticator app, Please try after sometime");
//            }
//        }
        return DashboardUtils::errorResponse("Invalid OTP");
    }

    public function logout(Request $request)
    {
        DashboardUtils::LogDB("AUTHENTICATION", "Dashboard Logout Success");
        $request->user()->tokens()->delete();
    }
}
