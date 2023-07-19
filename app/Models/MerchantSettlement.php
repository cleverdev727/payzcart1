<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class MerchantSettlement extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_merchant_settlement';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    public function getTotalSettledAmount($merchantId)
    {
        try {
            return $this->where("merchant_id", $merchantId)->sum("settled_amount");
        } catch (QueryException $ex) {
            report($ex);
            return 0;
        }
    }

}
