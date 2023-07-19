<?php


namespace App\Export;


use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Refund implements FromCollection,ShouldAutoSize,WithHeadings
{
    use Exportable;

    private $filterData;
    private $reportId;
    private $emailId;
    private $transaction;
    private $merchantId;

    public function __construct($merchantId, $filterData, $reportId)
    {
        $this->filterData = $filterData;
        $this->reportId = $reportId;
        $this->merchantId = $merchantId;
    }


    public function collection()
    {
        $result = (new \App\Models\Refund())->getRefundForReport($this->merchantId, $this->filterData);
        if(isset($result) && !empty($result)){
            $collection = collect();
                foreach ($result as $value) {
                    $collection[] = array(
                        "Refund Id"  => $value->refund_id,
                        "Transaction Id"  => $value->transaction_id,
                        "Merchant Id"  => $value->merchant_id,
                        "Amount"  => $value->refund_amount,
                        "Fees"  => $value->payout_fees,
                        "Refund Type"  => $value->refund_status,
                        "Status"  => $value->refund_status,
                        "UTR"  => $value->bank_rrn,
                        "Message"  => $value->response_message,
                        "Process By"  => $value->processed_by,
                        "Refund Date"  => $value->refund_date_ind
                    );
                }
            if ($collection) {
                return $collection;
            }
        }
        return null;
    }

    public function headings(): array
    {
        return [
            "Refund Id",
            "Transaction Id",
            "Merchant Id",
            "Amount (INR)",
            "Fees (INR)",
            "Refund Type",
            "Status",
            "UTR",
            "Message",
            "Process By",
            "Refund Date"
        ];
    }
}
