<?php


namespace App\Classes\Utils;

use Illuminate\Support\Facades\Log;
use Razorpay\IFSC\IFSC;

define('URL_FORMAT',
    '/^(https):\/\/'.                                         // protocol
    '(([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+'.         // username
    '(:([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+)?'.      // password
    '@)?(?#'.                                                  // auth requires @
    ')((([a-z0-9]\.|[a-z0-9][a-z0-9-]*[a-z0-9]\.)*'.                      // domain segments AND
    '[a-z][a-z0-9-]*[a-z0-9]'.                                 // top level domain  OR
    '|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'.
    '(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])'.                 // IP address
    ')(:\d+)?'.                                                // port
    ')(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'. // path
    '(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)'.      // query string
    '?)?)?'.                                                   // path and query string optional
    '(#([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)?'.      // fragment
    '$/i');

class ValidationUtils
{

    public function validateIFSC($attribute, $value, $parameters, $validator) {
        $payoutType = request()->get("payout_type");
        if(
            strcmp($payoutType, PayoutType::IMPS) === 0 ||
            strcmp($payoutType, PayoutType::NEFT) === 0 ||
            strcmp($payoutType, PayoutType::RTGS) === 0
        ) {
            return IFSC::validate(strtoupper($value));
        }
        return true;
    }
    public function validateCustomerEmail($attribute, $value, $parameters, $validator) {
        return preg_match("/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,15})(\]?)$/", $value);
    }
    public function validateCustomerMobile($attribute, $value, $parameters, $validator) {
        return preg_match("/^\+?[0-9]{7,15}$/", $value);
    }
    public function validateAmount($attribute, $value, $parameters, $validator) {
        return preg_match("/^\d+(\.\d{1,2})?$/", $value) && floatval($value) >= 1;
    }
    public function validateVpa($attribute, $value, $parameters, $validator) {
        $payoutType = request()->get("payout_type");
        if(strcmp($payoutType, PayoutType::UPI) === 0) {
            return preg_match("/^[\w.-]+@[\w.-]+$/", $value);
        }
        return true;
    }
    public function validateAccountNumber($attribute, $value, $parameters, $validator) {
        $payoutType = request()->get("payout_type");
        if(
            strcmp($payoutType, PayoutType::IMPS) === 0 ||
            strcmp($payoutType, PayoutType::NEFT) === 0 ||
            strcmp($payoutType, PayoutType::RTGS) === 0
        ) {
            $value = trim($value);
            Log::info((strlen($value) < 9 || strlen($value) > 18). strlen($value));
            return !(strlen($value) < 9 || strlen($value) > 18);
        }
        return true;
    }
    public function validateURL($attribute, $value, $parameters, $validator) {
        return preg_match(URL_FORMAT, $value);
    }
    public function validateMerchantReferenceId($attribute, $value, $parameters, $validator) {
        return strlen($value) <= 50;
    }
    public function validateUDF($attribute, $value, $parameters, $validator) {
        if(isset($value)) {
            return strlen($value) <= 200;
        }
        return true;
    }

}
