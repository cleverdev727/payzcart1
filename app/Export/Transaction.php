<?php


namespace App\Export;



use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Transaction implements FromCollection,ShouldAutoSize,WithHeadings
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
            "Payment Date",
            "Transaction Id",
            "Order Id",
            "Payment Amount (INR)",
            "Associate Fees (INR)",
            "Fees (INR)",
            "Status",
            "UTR",
            "Payment Method",
            "UDF1",
            "UDF2",
            "UDF3",
            "UDF4",
            "UDF5",
            "Customer IP"

        ];
    }
}
