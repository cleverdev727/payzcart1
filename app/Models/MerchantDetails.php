<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class MerchantDetails extends Model
{
    use HasApiTokens;
    protected $connection = 'mysql';
    protected $table = 'tbl_merchant_details';
    protected $primaryKey = 'merchant_email';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        "is_password_temp" => "boolean",
        "is_payout_enable" => "boolean",
        "is_dashboard_payout_enable" => "boolean",
        "is_auto_approved_payout" => "boolean",
        "is_gauth_enabled" => "boolean",
    ];

    public function getMerchantDetail($merchantEmail)
    {
        try {
            $data = $this->where("merchant_email", $merchantEmail)->where("account_status", "!=", "Suspended")->first();
            if (isset($data)) {
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

    public function updatePassword($merchantEmail, $newPassword)
    {
        try {
            if (
                $this->where("merchant_email", $merchantEmail)
                    ->where("account_status", "!=", "Suspended")
                    ->update([
                        "password" => $newPassword,
                        "is_password_temp" => false
                    ])
            ) {
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

    public function getMerchantDetailById($merchantId)
    {
        try {
            $data = $this->where("merchant_id", $merchantId)->where("account_status", "Approved")->first();
            if (isset($data)) {
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

    public function changePassword($merchantId, $newPassword)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "password" => $newPassword
                    ])
            ) {
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
    public function getSettingDetail($merchantId)
    {
        try {
            $txn = $this->where('merchant_id', $merchantId)
                ->select("payout_delayed_time", "is_auto_approved_payout", "webhook_url", "payout_webhook_url", "is_gauth_enabled")
                ->first();
            if (isset($txn)) {
                return $txn;
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
    public function updateConfiguration($merchantId, $payoutDelayedTime, $autoApprovedPayout)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "payout_delayed_time" => $payoutDelayedTime,
                        "is_auto_approved_payout" => $autoApprovedPayout,
                    ])
            ) {
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
    public function updateWebhook($merchantId, $paymentWebhook, $payoutWebhook)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "webhook_url" => $paymentWebhook,
                        "payout_webhook_url" => $payoutWebhook,
                    ])
            ) {
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

    public function checkGAuthSecretIsExist($gAuthSecretKey)
    {
        try {
            return $this->where("guath_secret", $gAuthSecretKey)->exists();
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return true;
        }
    }

    public function setMerchantGAuthSecret($merchantId, $gAuthSecretKey)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "guath_secret" => $gAuthSecretKey
                    ])
            ) {
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

    public function setMerchantGAuthAsActivated($merchantId)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "is_gauth_enabled" => true
                    ])
            ) {
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

    public function setMerchantGAuthAsDeactivate($merchantId)
    {
        try {
            if (
                $this->where("merchant_id", $merchantId)
                    ->update([
                        "is_gauth_enabled" => false,
                        "guath_secret" => null,
                    ])
            ) {
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

}
