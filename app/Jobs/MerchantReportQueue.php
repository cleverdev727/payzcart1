<?php

namespace App\Jobs;
use App\Classes\DashboardUtils;
use App\Classes\Utils\DownloadLimit;
use App\Classes\Utils\ReportResponse;
use App\Classes\Utils\ReportStatus;
use App\Classes\Utils\ReportType;
use App\Classes\Utils\ReportUtils;
use App\Export\Payout;
use App\Export\Refund;
use App\Export\Transaction;
use App\Models\MerchantReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MerchantReportQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $reportBatchId;

    public function __construct($report_id)
    {
        $this->reportBatchId = $report_id;
    }

    public function handle()
    {
        try {
            $reportDetails = (new MerchantReport())->getPendingReportForQueueProcess($this->reportBatchId);
            if(isset($reportDetails)) {
                (new MerchantReport())->markAsProcessing($reportDetails->report_id);
                $filterData = [
                    "status" => $reportDetails->filter_status,
                    "start_date" => DashboardUtils::TO_UTC(Carbon::parse($reportDetails->filter_start_date)->format("Y-m-d 00:00:00")),
                    "end_date" => DashboardUtils::TO_UTC(Carbon::parse($reportDetails->filter_end_date)->format("Y-m-d 23:59:59")),
                ];
                switch ($reportDetails->report_type) {
                    case ReportType::PAYIN:
                        $this->transactionBackUp($reportDetails, $filterData);
                        break;
                    case ReportType::PAYOUT:
                        $this->payoutBackUp($reportDetails, $filterData);
                        break;
                    case ReportType::REFUND:
                        break;
                }
            }

        } catch (\Exception $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);

            $reportResponse = new ReportResponse();
            $reportResponse->reportMessage = "Internal Server Error {$ex->getMessage()}";
            $reportResponse->reportId = $this->reportBatchId;
            (new MerchantReport())->markAsFailed($reportResponse);
        }
    }

    private function transactionBackUp($reportDetails, $filterData)
    {
        try {
            $transactionObj = new Collection();
                $_transactionObj    = (new ReportUtils())->transactionToCollection($reportDetails->merchant_id, $filterData);
                $transactionObj     = $transactionObj->merge($_transactionObj);

            $fileName = (new ReportUtils())->getFileName($filterData, $reportDetails->report_type);
            $reportResponse = (new ReportUtils())->storeFile((new Transaction($transactionObj)), $fileName, $reportDetails);
            $this->processReportResponse($reportResponse);

        }catch (\Exception $ex){
            Log::error(__CLASS__.'::'.__FUNCTION__.' Exception', [
                'error_message' => $ex->getMessage(),
                'error_at_line' => $ex->getLine(),
                'error_file' => $ex->getFile()
            ]);
        }
    }

    private function payoutBackUp($reportDetails, $filterData)
    {
        try {
            $transactionObj = new Collection();
            $_transactionObj    = (new ReportUtils())->payoutToCollection($reportDetails->merchant_id, $filterData);
            $transactionObj     = $transactionObj->merge($_transactionObj);
            $fileName = (new ReportUtils())->getFileName($filterData, $reportDetails->report_type);
            $reportResponse = (new ReportUtils())->storeFile((new Payout($transactionObj)), $fileName, $reportDetails);
            $this->processReportResponse($reportResponse);

        }catch (\Exception $ex){
            Log::error(__CLASS__.'::'.__FUNCTION__.' Exception', [
                'error_message' => $ex->getMessage(),
                'error_at_line' => $ex->getLine(),
                'error_file' => $ex->getFile()
            ]);
        }
    }

    private function processReportResponse(ReportResponse $reportResponse) {
        try {
            if(strcmp($reportResponse->reportStatus, ReportStatus::SUCCESS) === 0) {
                (new MerchantReport())->markAsSuccess($reportResponse);
            } else {
                (new MerchantReport())->markAsFailed($reportResponse);
            }
        } catch (\Exception $ex) {
            $reportResponse->reportMessage = "Internal Server Error";
            (new MerchantReport())->markAsFailed($reportResponse);
        }
    }

    public function fail($exception = null)
    {
        $reportResponse = new ReportResponse();
        $reportResponse->reportMessage = "Internal Server Error";
        $reportResponse->reportId = $this->reportBatchId;
        (new MerchantReport())->markAsFailed($reportResponse);
    }
}
