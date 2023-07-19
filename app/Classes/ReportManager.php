<?php


namespace App\Classes;


use App\Classes\Utils\ReportStatus;
use App\Classes\Utils\ReportType;
use App\Classes\Utils\ReportUtils;
use App\Models\MerchantReport;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReportManager
{

    private $transaction;
    private $refund;
    private $payout;
    private $reportUtils;

    public function __construct()
    {
        $this->transaction = new Transaction();
        $this->refund = new Refund();
        $this->payout = new Payout();
        $this->reportUtils = new ReportUtils();
    }

    public function addReport($merchantId, $reportType, $filterStatus, $filterStartDate, $filterEndDate) {
        try {
            $reportId = strtoupper("DPZ".Str::random(25));
            $record = 0;

            $filterStartDate = DashboardUtils::TO_UTC(Carbon::parse($filterStartDate)->format("Y-m-d 00:00:00"));
            $filterEndDate = DashboardUtils::TO_UTC(Carbon::parse($filterEndDate)->format("Y-m-d 23:59:59"));

            switch ($reportType) {
                case ReportType::PAYIN:
                    $record = $this->transaction->getRecordForReport($merchantId, $filterStatus, $filterStartDate, $filterEndDate);
                    break;
                case ReportType::PAYOUT:
                    $record = $this->payout->getRecordForReport($merchantId, $filterStatus, $filterStartDate, $filterEndDate);
                    break;
                case ReportType::REFUND:
                    $record = $this->refund->getRecordForReport($merchantId, $filterStatus, $filterStartDate, $filterEndDate);
                    break;
            }

            if($record <= 0) {
                return DashboardUtils::errorResponse("Record Does not exist");
            }

            if((new MerchantReport())->addReport(
                $reportId,
                $merchantId,
                $reportType,
                $filterStatus,
                $filterStartDate,
                $filterEndDate,
                $record
            )) {
                DashboardUtils::merchantReportJob($reportId);
                return DashboardUtils::successResponse("Report in queue");
            }
            return DashboardUtils::errorResponse("Error while generate report");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while generate report");
        }
    }

    public function getReports($merchantId, $filterData, $pageNo, $limit) {
        try {
            $data = (new MerchantReport())->getReports($merchantId, $filterData, $pageNo, $limit);
            if(isset($data)) {
                $result['current_page'] = $data->currentPage();
                $result['last_page'] = $data->lastPage();
                $result['is_last_page'] = !$data->hasMorePages();
                $result['total_item'] = $data->total();
                $result['current_item_count'] = $data->count();
                $result['data'] = $data->items();
                return DashboardUtils::successResponse("Report Details Retrieve successfully", $result);
            }
            return DashboardUtils::errorResponse("report not found");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while create report request");
        }
    }

    public function downloadReport($merchantId, $reportId) {
        try {
            $reportFileName = (new MerchantReport())->where("merchant_id", $merchantId)->where("report_id", $reportId)->where("report_status", ReportStatus::SUCCESS)->first(["report_key"]);
            if(isset($reportFileName)) {
                $url = $this->reportUtils->getPreSignedUrl($reportFileName->report_key);
                if(isset($url)) {
                    return DashboardUtils::successResponse("Report Downloaded", ["report_url" => $url]);
                }
            }
            return DashboardUtils::errorResponse("Error while download report");
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return DashboardUtils::errorResponse("Error while download report");
        }
    }
}
