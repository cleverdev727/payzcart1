<?php


namespace App\Models;


use App\Classes\Utils\ReportResponse;
use App\Classes\Utils\ReportStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class MerchantReport extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tbl_merchant_report';
    protected $primaryKey = 'report_id';
    public $incrementing = false;

    protected $appends = [
        "report_date",
        "expiry_date_f",
        "report_status_f"
    ];

    public function getReportDateAttribute() {
        $originalDate = $this->attributes['created_at'];
        return Carbon::parse($originalDate, "UTC")->setTimezone("Asia/Kolkata")->format("M d, Y h:i:s A");
//        return Carbon::createFromFormat('Y-m-d H:i:s', $originalDate)->format("M d, Y h:i:s A");
    }


    public function getExpiryDateFAttribute() {
        $originalDate = $this->attributes['expiry_date'];
        if(isset($originalDate)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $originalDate)->format("M d, Y h:i:s A");
        }
        return null;
    }

    public function getReportStatusFAttribute() {
        $originalDate = $this->attributes['expiry_date'];
        $originalStatus = $this->attributes['report_status'];
        if(isset($originalDate)) {
            if(Carbon::parse($originalDate)->isPast()) {
                return "Expired";
            }
        }
        return $originalStatus;
    }

    public function addReport(
        $reportId,
        $merchantId,
        $reportType,
        $filterStatus,
        $filterStartDate,
        $filterEndDate,
        $record
    ) {
        try {
            $this->report_id = $reportId;
            $this->merchant_id = $merchantId;
            $this->report_type = $reportType;
            $this->filter_status = $filterStatus;
            $this->filter_start_date = $filterStartDate;
            $this->filter_end_date = $filterEndDate;
            $this->report_status = ReportStatus::PENDING;
            $this->record = $record;
            if($this->save()) {
                return true;
            }
            return false;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return false;
        }
    }

    public function getReports($merchantId, $filterData = null, $pageNo = 1, $limit = 10) {
        try {
            $data = $this->newQuery();
            $data->where("merchant_id", $merchantId);
            if(isset($filterData)) {
                if(isset($filterData['status']) && !empty(isset($filterData['status'])) && (strcmp($filterData['status'], "All") !== 0)) {
                    $data->where("report_status", $filterData['status']);
                }
                if(isset($filterData['type']) && !empty(isset($filterData['type'])) && (strcmp($filterData['status'], "All") !== 0)) {
                    $data->where("report_type", $filterData['type']);
                }
            }
            Paginator::currentPageResolver(function () use ($pageNo) {
                return $pageNo;
            });
            $data->orderBy('created_at', 'desc');
            if($data->count() > 0){
                return $data->paginate($limit);
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function getPendingReportForQueueProcess($report_id)
    {
        try {
            $data = $this->where("report_id", $report_id)->where("report_status", ReportStatus::PENDING)->orderBy("created_at", "asc")->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }
    public function getPendingReportForQueueProcessDirect()
    {
        try {
            $data = $this->where("report_status", ReportStatus::PENDING)->orderBy("created_at", "asc")->first();
            if(isset($data)) {
                return $data;
            }
            return null;
        } catch (QueryException $ex) {
            Log::debug('Error while executing SQL Query', [
                'class' => __CLASS__,
                'function' => __METHOD__,
                'file' => $ex->getFile(),
                'line_no' => $ex->getLine(),
                'error_message' => $ex->getMessage(),
            ]);
            return null;
        }
    }

    public function markAsProcessing($report_id)
    {
        try {
            $this->where("report_id", $report_id)->where("report_status", ReportStatus::PENDING)->update([
                "report_status" => ReportStatus::PROCESSING
            ]);
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

    public function markAsSuccess(ReportResponse $reportResponse)
    {
        try {
            $this->where("report_id", $reportResponse->reportId)->where("report_status", ReportStatus::PROCESSING)->update([
                "report_status" => ReportStatus::SUCCESS,
                "report_key" => $reportResponse->reportKey,
                "expiry_date" => $reportResponse->reportExpireAt,
                "record" => $reportResponse->reportRecord,
                "error_msg" => $reportResponse->reportMessage,
            ]);
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

    public function markAsFailed(ReportResponse $reportResponse)
    {
        try {
            $this->where("report_id", $reportResponse->reportId)->where("report_status", ReportStatus::PROCESSING)->update([
                "report_status" => ReportStatus::FAILED,
                "report_key" => $reportResponse->reportKey,
                "expiry_date" => $reportResponse->reportExpireAt,
                "record" => $reportResponse->reportRecord,
                "error_msg" => $reportResponse->reportMessage,
            ]);
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
