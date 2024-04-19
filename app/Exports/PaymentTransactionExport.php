<?php

namespace App\Exports;

use App\Models\PaymentTransactionReport;
use App\Models\Product;
use App\Models\SalesReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentTransactionExport implements FromCollection,WithHeadings,WithStyles
{

    use Exportable;

    
    public function __construct($from_date,$to_date,$typeCus,$dealer,$status)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->typeCus = $typeCus;
        $this->dealer = $dealer;
        $this->status = $status;
    }

    public function headings():array{
        if($this->typeCus==''){
            return[
                'Order Date',
                'Order Code',
                'Customer Name',
                'Type Of Customer',
                'Order Amount',
                'Distributor Margin',
                'Payment Status'
            ];
        }
        if($this->typeCus=='all'){
            return[
                'Order Date',
                'Order Code',
                'Customer Name',
                'Type Of Customer',
                'Order Amount',
                'Distributor Margin',
                'Payment Status'
            ];
        }
        if($this->typeCus == 1 && $this->dealer != '[]'){
            return[
                'Order Date',
                'Order Code',
                'Customer Name',
                'Dealer Name',
                'Order Amount',
                'Distributor Margin',
                'Payment Status'
            ];
        }
        if($this->typeCus == 2){
            return[
                'Order Date',
                'Order Code',
                'Customer Name',
                'Order Amount',
                'Distributor Margin',
                'Payment Status'
            ];
        }
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $op = PaymentTransactionReport::getPayment($this->from_date,$this->to_date,$this->typeCus,$this->dealer,$this->status);
        
        return collect($op);
    }

    public function styles(Worksheet $sheet)
{
    return [
       // Style the first row as bold text.
       1    => ['font' => ['bold' => true]],
       // Styling a specific cell by coordinate.
    //    'B2' => ['font' => ['italic' => true]],

       // Styling an entire column.
    //    'C'  => ['font' => ['size' => 16]],
    ];
}
}
