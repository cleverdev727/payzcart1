<?php


namespace App\Export;


use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Payout implements FromCollection,ShouldAutoSize,WithHeadings
{
    use Exportable;

    private $collectionData;

    public function __construct($collectionData)
    {
        $this->collectionData = $collectionData;
    }

    public function collection()
    {
        return $this->collectionData;
    }

    public function headings(): array
    {
        return [
            "Payout Id",
            "Reference Id",
            "Merchant Id",
            "Amount (INR)",
            "Fees (INR)",
            "Payout Type",
            "Customer Name",
            "Customer Email",
            "Customer Mobile",
            "Bank Account",
            "IFSC",
            "Bank Name",
            "UPI",
            "Status",
            "UTR",
            "Message",
            "Payout By",
            "Approved At",
            "UDF1",
            "UDF2",
            "UDF3",
            "UDF4",
            "UDF5",
            "Customer IP",
            "Payout Date"
        ];
    }
}
