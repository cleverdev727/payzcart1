<?php


namespace App\Http\Controllers;


use App\Classes\DashboardUtils;
use App\Classes\ReportManager;
use App\Exceptions\UnAuthorizedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{

    private $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function addReport(Request $request) {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:PAYIN,REFUND,PAYOUT',
            'status' => 'required|string',
            'start_date' => 'required|string',
            'end_date' => 'required|string',
        ]);

        if ($validator->fails()) {
            return DashboardUtils::errorResponse($validator->errors()->first());
        }
        $merchantId = DashboardUtils::merchantId();
        return $this->reportManager->addReport(
            $merchantId,
            $request->report_type,
            $request->status,
            $request->start_date,
            $request->end_date
        );
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function getReports(Request $request) {
        $validator = Validator::make($request->all(), [
            'filter_data' => 'nullable',
            'page_no' => 'required',
            'limit' => 'required',
        ]);

        if ($validator->fails()) {
            return DashboardUtils::errorResponse($validator->errors()->first());
        }
        $merchantId = DashboardUtils::merchantId();
        $filterData = DashboardUtils::parseFilterData($request->filter_data);
        return $this->reportManager->getReports(
            $merchantId,
            $filterData,
            $request->page_no,
            $request->limit
        );
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function downloadReport(Request $request) {
        $validator = Validator::make($request->all(), [
            'report_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return DashboardUtils::errorResponse($validator->errors()->first());
        }
        $merchantId = DashboardUtils::merchantId();
        return $this->reportManager->downloadReport(
            $merchantId,
            $request->report_id
        );
    }
}
