<?php


namespace App\Classes;


use App\Auth\CustomUserProvider;
use App\Models\MerchantDetails;
use App\Models\Payout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MerchantManager
{

    private $merchantDetails;

    public function __construct(MerchantDetails $merchantDetails)
    {
        $this->merchantDetails = $merchantDetails;
    }

    public function merchantAuthenticate($username, $password)
    {
        try {
            $data = $this->merchantDetails->getMerchantDetail($username);


            if (isset($data)) {
                if (Hash::check($password, $data->password)) {
                    $token = $data->createToken($data->merchant_email)->plainTextToken;
                    $userdata = array(
                        'email' => $data->merchant_email,
                        'fullName' => $data->merchant_name,
                        'accountStatus' => $data->account_status,
                        'isPayoutEnable' => $data->isPayoutEnable,
                    );

                    if ($data->is_password_temp) {
                        $res['status'] = true;
                        $res['is_allowed_change_password'] = true;
                        $res['message'] = "Login Success";
                        $res['userData'] = $userdata;
                        $res['accessToken'] = $token;
                        DashboardUtils::LogDB("AUTHENTICATION", "Change Password With Temp Password");
                        return response()->json($res)->setStatusCode(200);
                    }
                    $userObject = new CustomUserProvider();
                    $userObject->merchantId = $data->merchant_id;
                    $userObject->email = $data->merchant_email;
                    $userObject->fullName = $data->merchant_name;
                    $userObject->accountStatus = $data->account_status;
                    $userObject->isDashboardPayoutEnable = $data->is_dashboard_payout_enable;
                    $userObject->isPayoutEnable = $data->is_payout_enable;
                    Auth::login($userObject);

                    if (Auth::check()) {
                        $res['status'] = true;
                        $res['is_allowed_change_password'] = false;
                        $res['message'] = "Login Success";
                        $res['accessToken'] = $token;
                        $res['userData'] = $userdata;
                        DashboardUtils::LogDB("AUTHENTICATION", "Dashboard Login Success");
                        return response()->json($res)->setStatusCode(200);
                    }
                }
            }
            $res['status'] = false;
            $res['is_allowed_change_password'] = false;
            $res['message'] = "Login Failed";
            DashboardUtils::LogDB("AUTHENTICATION", "Dashboard Login Failed");
            return response()->json($res)->setStatusCode(400);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            $error['status'] = false;
            $error['is_allowed_change_password'] = false;
            $error['message'] = "Login Failed";
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function merchantReAuthenticate($username, $password, $new_password)
    {
        try {
            $data = $this->merchantDetails->getMerchantDetail($username);
            if (isset($data)) {
                if (Hash::check($password, $data->password)) {
                    $newPassword = Hash::make($new_password);
                    if ($this->merchantDetails->updatePassword($username, $newPassword)) {
                        $userObject = new CustomUserProvider();
                        $userObject->merchantId = $data->merchant_id;
                        $userObject->email = $data->merchant_email;
                        $userObject->fullName = $data->merchant_name;
                        $userObject->accountStatus = $data->account_status;
                        $userObject->isDashboardPayoutEnable = $data->is_dashboard_payout_enable;
                        $userObject->isPayoutEnable = $data->is_payout_enable;
                        Auth::login($userObject);
                        if (Auth::check()) {
                            $error['status'] = true;
                            $error['is_allowed_change_password'] = false;
                            $error['message'] = "Login Success";
                            DashboardUtils::LogDB("AUTHENTICATION", "Dashboard Login Success");
                            return response()->json($error)->setStatusCode(200);
                        }
                    }
                }
            }
            $error['status'] = false;
            $error['is_allowed_change_password'] = false;
            $error['message'] = "Login Failed";
            DashboardUtils::LogDB("AUTHENTICATION", "Dashboard Login Failed");
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            $error['status'] = false;
            $error['is_allowed_change_password'] = false;
            $error['message'] = "Login Failed";
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function merchantChangePassword($merchantId, $old_password, $new_password)
    {
        try {
            $data = $this->merchantDetails->getMerchantDetailById($merchantId);
            if (isset($data)) {
                if (Hash::check($old_password, $data->password)) {
                    $newPassword = Hash::make($new_password);
                    if ($this->merchantDetails->changePassword($merchantId, $newPassword)) {
                        DashboardUtils::LogDB("PASSWORD_CHANGE", "Password Change Success");
                        $error['status'] = true;
                        $error['message'] = "Password Change Success";
                        return response()->json($error)->setStatusCode(200);
                    }
                }
            }
            $error['status'] = false;
            $error['message'] = "Password Change Failed";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            $error['status'] = false;
            $error['message'] = "Error while change password";
            return response()->json($error)->setStatusCode(400);
        }
    }

    public function getSettingDetail($merchantId)
    {
        try {
            $txnData = $this->merchantDetails->getSettingDetail($merchantId);
            if (isset($txnData) && !empty($txnData)) {
                $result['status'] = true;
                $result['message'] = 'Setting Details Retrieve successfully';
                $result['data'] = $txnData;
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Setting Detail Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting Transaction";
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

    public function updateConfiguration($merchantId, $payoutDelayedTime, $autoApprovedPayout)
    {
        try {
            if ($autoApprovedPayout) {
                if ((new Payout())->getPendingApprovePayout($merchantId)) {
                    $result['status'] = false;
                    $result['message'] = 'Update Configuration Failed, Please approved payout first';
                    return response()->json($result)->setStatusCode(400);
                }
            }
            $update_status = $this->merchantDetails->updateConfiguration($merchantId, $payoutDelayedTime, $autoApprovedPayout);
            if ($update_status) {
                DashboardUtils::LogDB("PAYOUT_CONFIGURATION", "Payout Configuration Success, Delay_TIME: {$payoutDelayedTime}, IS_AUTO_APPROVED: {$autoApprovedPayout}");
                $result['status'] = true;
                $result['message'] = 'Update Configuration successfully';
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Update Configuration Failed";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            $error['status'] = false;
            $error['message'] = "Update Configuration Failed";
            return response()->json($error)->setStatusCode(500);
        }
    }

    public function updateWebhook($merchantId, $paymentWebhook, $payoutWebhook)
    {
        try {
            $update_status = $this->merchantDetails->updateWebhook($merchantId, $paymentWebhook, $payoutWebhook);
            if ($update_status) {
                DashboardUtils::LogDB("WEBHOOK_UPDATE", "Webhook Update Success, Payin:{$paymentWebhook}, Payout: {$payoutWebhook}");
                $result['status'] = true;
                $result['message'] = 'Update Webhook successfully';
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Update Webhook Failed";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            $error['status'] = false;
            $error['message'] = "Update Webhook Failed";
            return response()->json($error)->setStatusCode(500);
        }
    }

}
