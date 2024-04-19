<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromCollection,WithHeadings,WithStyles
{

    use Exportable;

    
    public function __construct($TypeOfStock,$filterByCategory)
    {
        $this->TypeOfStock = $TypeOfStock;
        $this->filterByCategory = $filterByCategory;
    }

    // public function headings():array{
    //     if($this->TypeOfStock!='[]'){
    //         return[
    //             'Product Code',
    //             'Product Name',
    //             'Category',
    //             'Quantity',
    //             'Stock status'
    //         ];
    //     }
    //     if($this->TypeOfStock ='[]'){
    //         return[
    //             'Product Code',
    //             'Product Name',
    //             'Category',
    //             'Quantity'
    //         ];
    //     }
    // }

    public function headings():array{
        if($this->TypeOfStock=='[2]'){
            return[
                'Product Code',
                'Product Name',
                'Category',
                'Quantity',
                'Stock status'
            ];
        }
        else{
            return[
                'Product Code',
                'Product Name',
                'Category',
                'Quantity'
            ];
        }
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $op = Product::getStock($this->TypeOfStock,$this->filterByCategory);
        
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
