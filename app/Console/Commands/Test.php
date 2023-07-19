<?php

namespace App\Console\Commands;

use App\Classes\DashboardUtils;
use App\Classes\Utils\ReportType;
use App\Classes\Utils\ReportUtils;
use App\Export\Payout;
use App\Export\Transaction;
use App\Models\MerchantReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Test extends Command
{

    protected $signature = 'test';
    protected $description = 'Command description';
    public function handle()
    {
        try {
            $reportDetails = (new MerchantReport())->getPendingReportForQueueProcess("DPZWCZJ6CJ5AIKHJMZ6URXS8X2JM");
            if(isset($reportDetails)) {
                //(new MerchantReport())->markAsProcessing($reportDetails->report_id);

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
        }
    }

    private function transactionBackUp($reportDetails, $filterData)
    {
        try {
            $transactionObj = new Collection();
                $_transactionObj    = (new ReportUtils())->transactionToCollection($reportDetails->merchant_id, $filterData, $offset);
                $transactionObj     = $transactionObj->merge($_transactionObj);
            $fileName = (new ReportUtils())->getFileName($filterData, $reportDetails->report_type);
            $reportResponse = (new ReportUtils())->storeFile((new Transaction($transactionObj)), $fileName, $reportDetails);
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


            $payoutObj = new Collection();

                $_payoutObj = (new ReportUtils())->payoutToCollection($reportDetails->merchant_id, $filterData);
                $payoutObj  = $payoutObj->merge($_payoutObj);

            $fileName = (new ReportUtils())->getFileName($filterData, $reportDetails->report_type);
            $reportResponse = (new ReportUtils())->storeFile((new Payout($payoutObj)), $fileName, $reportDetails);


        }catch (\Exception $ex){
            Log::error(__CLASS__.'::'.__FUNCTION__.' Exception', [
                'error_message' => $ex->getMessage(),
                'error_at_line' => $ex->getLine(),
                'error_file' => $ex->getFile()
            ]);
        }
    }
}
