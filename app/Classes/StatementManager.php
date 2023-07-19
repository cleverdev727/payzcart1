<?php

namespace App\Classes;

use App\Models\MerchantBalance;
use Illuminate\Support\Facades\Log;

class StatementManager
{

    private $merchantBalance;

    public function __construct(MerchantBalance $merchantBalance)
    {
        $this->merchantBalance = $merchantBalance;
    }

    public function getStatement($merchantId, $filterData, $limit, $pageNo)
    {
        try {
            $data = $this->merchantBalance->getStatement($merchantId, $filterData, $limit, $pageNo);
            if(isset($data)) {
                $result['status'] = true;
                $result['message'] = 'Statement Details Retrieve successfully';
                $result['current_page'] = $data->currentPage();
                $result['last_page'] = $data->lastPage();
                $result['is_last_page'] = !$data->hasMorePages();
                $result['total_item'] = $data->total();
                $result['current_item_count'] = $data->count();
                $result['data'] = $data->items();
                return response()->json($result)->setStatusCode(200);
            }
            $error['status'] = false;
            $error['message'] = "Statement Not found";
            return response()->json($error)->setStatusCode(400);
        } catch (\Exception $ex) {
            $error['status'] = false;
            $error['message'] = "Error while getting Statement";
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
}
