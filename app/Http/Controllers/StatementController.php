<?php

namespace App\Http\Controllers;

use App\Classes\DashboardUtils;
use App\Classes\StatementManager;
use App\Exceptions\UnAuthorizedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StatementController extends Controller
{
    private $statementManager;

    public function __construct(StatementManager $statementManager)
    {
        $this->statementManager = $statementManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getStatement(Request $request) {
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
        return $this->statementManager->getStatement($merchantId, $request->filter_data, $request->limit, $request->page_no);
    }
}
