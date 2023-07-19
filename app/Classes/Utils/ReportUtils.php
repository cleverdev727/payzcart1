<?php


namespace App\Classes\Utils;

use App\Models\Payout;
use App\Models\Transaction;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ReportUtils
{
    private $client;
    public function __construct()
    {
        $this->client = $this->config();
    }

    public function getFileName($requestData, $reportType)
    {
        $startData = Carbon::parse($requestData['start_date'])->format("dmY");
        $endData = Carbon::parse($requestData['end_date'])->format("dmY");
        $random = Str::random(10);
        return $reportType . "_" . $startData . "_" . $endData . $random . ".xlsx";
    }

    public function storeFile($collectionObject, $fileName, $reportDetails)
    {
        $fileName = "Dashboard/Merchant/{$reportDetails->report_type}/{$reportDetails->merchant_id}/$fileName";
        try {
            Excel::store(
                $collectionObject,
                $fileName,
                's3',
                ExcelWriter::XLSX
            );
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
        }


        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => "payzcart-dashboard-data-backup-agent",
            'Key' => $fileName
        ]);
        $reportResponse = new ReportResponse();
        $res = $this->client->createPresignedRequest($cmd, '+1440 minutes');
        $presignedUrl = (string) $res->getUri();

        if (isset($presignedUrl) && !empty($presignedUrl)) {
            $reportResponse->reportStatus = ReportStatus::SUCCESS;
            $reportResponse->reportMessage = "Report Generated";
            $reportResponse->reportRecord = $reportDetails->record;
            $reportResponse->reportId = $reportDetails->report_id;
            $reportResponse->reportKey = $fileName;
            $reportResponse->reportExpireAt = Carbon::now()->addDays(2);
        } else {
            $reportResponse->reportMessage = "Error while Generating report";
            $reportResponse->reportId = $reportDetails->report_id;
        }
        return $reportResponse;
    }

    public function getPreSignedUrl($fileName)
    {
        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => "payzcart-dashboard-data-backup-agent",
                'Key' => $fileName
            ]);
            $res = $this->client->createPresignedRequest($cmd, '+1440 minutes');
            $presignedUrl = (string) $res->getUri();
            if (isset($presignedUrl) && !empty($presignedUrl)) {
                return $presignedUrl;
            }
            return null;
        } catch (\Exception $ex) {
            Log::debug('Error in Exception', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    private function config()
    {
        return (new S3Client([
            'region' => 'ap-south-1',
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ],
        ]));
    }


    public function transactionToCollection($merchantId, $filterData)
    {
        try {
            $result = (new Transaction())->getTransactionForReport($merchantId, $filterData);
            if (isset($result) && !empty($result)) {
                $collection = collect();
                foreach ($result as $_result) {
                    $collection[] = array(
                        "Payment Date" => $_result->transaction_date_ind,
                        "Transaction Id" => $_result->transaction_id,
                        "Order Id" => $_result->merchant_order_id,
                        "Payment Amount (INR)" => $_result->payment_amount,
                        "Associate Fees (INR)" => $_result->associate_fees,
                        "Fees (INR)" => $_result->pg_fees,
                        "Status" => $_result->payment_status,
                        "UTR" => $_result->bank_rrn,
                        "Payment Method" => $_result->payment_method,
                        "UDF1" => $_result->udf1,
                        "UDF2" => $_result->udf2,
                        "UDF3" => $_result->udf3,
                        "UDF4" => $_result->udf4,
                        "UDF5" => $_result->udf5,
                        "Customer IP" => $_result->customer_ip,
                    );
                }
                if ($collection) {
                    return $collection;
                }
            }
            return null;
        } catch (\Exception $ex) {
            return null;
        }
    }

    public function payoutToCollection($merchantId, $filterData)
    {
        try {
            $result = (new Payout())->getPayoutForReport($merchantId, $filterData);
            if (isset($result) && !empty($result)) {
                $collection = collect();
                foreach ($result as $value) {
                    $collection[] = array(
                        "Payout Id" => $value->payout_id,
                        "Reference Id" => $value->merchant_ref_id,
                        "Merchant Id" => $value->merchant_id,
                        "Amount" => $value->payout_amount,
                        "Fees" => $value->payout_fees,
                        "Payout Type" => $value->payout_type,
                        "Customer Name" => $value->customer_name,
                        "Customer Email" => $value->customer_email,
                        "Customer Mobile" => $value->customer_mobile,
                        "Bank Account" => $value->bank_account,
                        "IFSC" => $value->ifsc_code,
                        "Bank Name" => $value->bank_name,
                        "UPI" => $value->vpa_address,
                        "Status" => $value->payout_status,
                        "UTR" => $value->bank_rrn,
                        "Message" => $value->pg_response_msg,
                        "Payout By" => $value->payout_by,
                        "Approved At" => $value->payout_approved_date_ind,
                        "UDF1" => $value->udf1,
                        "UDF2" => $value->udf2,
                        "UDF3" => $value->udf3,
                        "UDF4" => $value->udf4,
                        "UDF5" => $value->udf5,
                        "Customer IP" => $value->customer_ip,
                        "Payout Date" => $value->payout_date_ind,
                    );
                }
                if ($collection) {
                    return $collection;
                }
            }
            return null;
        } catch (\Exception $ex) {
            return null;
        }
    }

}