<?php

namespace App\Exports;

use App\Models\DealerPayout;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DealerPayoutExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    public function __construct($from_date, $todate, $filterByDealer, $filterByStatus)
    {
        $this->from_date = $from_date;
        $this->todate = $todate;
        $this->filterByDealer = $filterByDealer;
        $this->filterByStatus = $filterByStatus;
    }

    public function headings(): array
    {
        return [
            'Ordrer Date',
            'Order Code',
            'Dealer Name',
            'Distributor Margin',
            'Status'
        ];
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $op = DealerPayout::getDealerPay($this->from_date, $this->todate, $this->filterByDealer, $this->filterByStatus);
        return collect($op);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}
