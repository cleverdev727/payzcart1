<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class MerchantDashboardLogs extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_merchant_dashboard_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    public function addLog($merchantId, $actionType, $action, $ip, $userAgent) {
        try {
            $this->merchant_id = $merchantId;
            $this->action_type = $actionType;
            $this->action = $action;
            $this->request_ip = $ip;
            $this->user_agent = $userAgent;

            if(!$this->save()) {
                Log::error('Error Add Merchant Dashboard Log', [
                    'class' => __CLASS__,
                    'function' => __METHOD__,
                    'merchant_id' => $merchantId,
                    'action_type' => $actionType,
                    'action' => $action,
                    'request_ip' => $ip,
                    'user_agent' => $userAgent,
                    'error_message' => "Merchant Log not added",
                ]);
            }
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
        }
    }
}
