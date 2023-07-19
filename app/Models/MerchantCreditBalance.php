<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class MerchantCreditBalance extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_merchant_credit_balance';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    public function getTotalCreditAmount($merchantId)
    {
        try {
            return $this->where("merchant_id", $merchantId)->sum("credit_amount");
        } catch (QueryException $ex) {
            report($ex);
            return 0;
        }
    }

}
