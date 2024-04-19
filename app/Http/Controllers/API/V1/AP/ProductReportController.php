<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ProductReportController extends Controller
{
    public function product_report_list(Request $request)
    {
        try {
            Log::channel("productreport")->info('** started the product report list method **');

            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';

            $product = Product::where('product.status', 1)
                ->leftJoin('product_variant', function ($leftJoin) {
                    $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                        ->where('product_variant.set_as_default', 1);
                })
                ->leftjoin('service', 'service.service_id', '=', 'product.service_id')
                ->select('product.*', DB::raw('ROW_NUMBER() OVER (ORDER BY product_id DESC) AS SrNo'), 'service.service_name', 'product_variant.mrp as mrp_of_variant', 'product_variant.selling_price as selling_price_of_variant', 'product_variant.quantity as quantity_variant')
                ->groupby('product.product_id');

            if (!empty($from_date)) {
                $product->where(function ($query) use ($from_date) {
                    $query->whereDate('product.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $product->where(function ($query) use ($to_date) {
                    $query->whereDate('product.created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByServiceType) && $filterByServiceType != '[]' &&$filterByServiceType != 'all') {
                $filterByServiceType = json_decode($filterByServiceType, true);
                $product->whereIn('product.service_id', $filterByServiceType);
            }

            if (!empty($filterByStatus) &&  $filterByStatus != '[]'&& $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $product->whereIn('product.is_publish', $filterByStatus);
            }

            $count = $product->count();


            Log::channel("productreport")->info("request value :: $from_date :: $to_date ");
            $product->orderBy('product_id', 'DESC');
            $product = $product->get();
            $final = [];
            $count = $product->count();
            if ($count > 0) {
                foreach ($product as $value) {
                    $ary = [];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['service_id'] = $value['service_id'] ?? "-";
                    $ary['service_type'] = $value['service_name'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";

                    if ($value['service_id'] == 1) {
                        $ary['quantity'] =  "-";
                        $ary['mrp'] = $value['mrp'] ?? "-";
                        $ary['selling_price'] = $value['selling_price'] ?? "-";
                        $ary['first_copy_selling_price'] = "-";
                        $ary['additional_copy_selling_price'] = "-";
                    }

                    if ($value['service_id'] == 2) {
                        $ary['quantity'] =  "-";
                        $ary['mrp'] = $value['mrp'] ?? "-";
                        $ary['selling_price'] = "-";
                        $ary['first_copy_selling_price'] = $value['first_copy_selling_price'] ?? "-";
                        $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price']?? "-";
                    }

                    if ($value['service_id'] == 3) {
                        $ary['quantity'] =  "-";
                        $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                        $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                        $ary['first_copy_selling_price'] = "-";
                        $ary['additional_copy_selling_price'] = "-";
                    }

                    if ($value['service_id'] == 4) {
                        $ary['quantity'] = $value['quantity_variant'] ?? "-";
                        $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                        $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                        $ary['first_copy_selling_price'] = "-";
                        $ary['additional_copy_selling_price'] = "-";
                    }

                    if ($value['service_id'] == 5) {
                        $ary['quantity'] = $value['quantity_variant'] ?? "-";
                        $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                        $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                        $ary['first_copy_selling_price'] = "-";
                        $ary['additional_copy_selling_price'] = "-";
                    }

                    if ($value['service_id'] == 6) {
                        $ary['quantity'] = "-";
                        $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                        $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                        $ary['first_copy_selling_price'] = "-";
                        $ary['additional_copy_selling_price'] = "-";
                    }

                    if ($value['is_publish'] == 1) {
                        $ary['status'] = "PUBLISHED";
                    }

                    if ($value['is_publish'] == 2) {
                        $ary['status'] = "UN PUBLISHED";
                    }

                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("productreport")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Product report listed Successfully'),
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("productreport")->error('** start the product report list error method **');
            Log::channel("productreport")->error($exception);
            Log::channel("productreport")->error('** end the product report list error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    function product_list_report_Excel(Request $request)
    {
        Log::channel("productreport")->info('** started the admin product report list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';

        $product = Product::where('product.status', 1)
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', 1);
            })
            ->leftjoin('service', 'service.service_id', '=', 'product.service_id')
            ->select('product.*', DB::raw('ROW_NUMBER() OVER (ORDER BY product_id Desc) AS SrNo'), 'service.service_name', 'product_variant.mrp as mrp_of_variant', 'product_variant.selling_price as selling_price_of_variant', 'product_variant.quantity as quantity_variant')
            ->groupby('product.product_id');

        if (!empty($from_date)) {
            $product->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $product->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByServiceType) && $filterByServiceType != '[]' &&$filterByServiceType != 'all') {
            $filterByServiceType = json_decode($filterByServiceType, true);
            $product->whereIn('product.service_id', $filterByServiceType);
        }

        if (!empty($filterByStatus) &&  $filterByStatus != '[]'&& $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $product->whereIn('product.is_publish', $filterByStatus);
        }

        $product = $product->get();

        //Mrp total price
        $pr_mr = $product->pluck('mrp')->sum();
        $pv_mrp = $product->pluck('mrp_of_variant')->sum();
        $mrp = $pr_mr + $pv_mrp;

        //selling price total
        $pr_sp = $product->pluck('selling_price')->sum();
        $pv_sp = $product->pluck('selling_price_of_variant')->sum();

        $sp = $pr_sp + $pv_sp;


        $count = count($product);

        $s = 1;
        if ($count > 0) {
            $overll = [];

            foreach ($product as $value) {
                $ary = [];
                
                $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['product_id'] = $value['product_code'] ?? "-";
                $ary['service_type'] = $value['service_name'] ?? "-";
                $ary['product_name'] = $value['product_name'] ?? "-";
                if ($value['service_id'] == 1) {
                    $ary['quantity'] =  "-";
                    $ary['mrp'] = $value['mrp'] ?? "-";
                    $ary['selling_price'] = $value['selling_price'] ?? "-";
                    $ary['first_copy_selling_price'] = "-";
                    $ary['additional_copy_selling_price'] = "-";
                }

                if ($value['service_id'] == 2) {
                    $ary['quantity'] =  "-";
                    $ary['mrp'] = $value['mrp'] ?? "-";
                    $ary['selling_price'] =  "-";
                    $ary['first_copy_selling_price'] = $value['first_copy_selling_price'] ?? "-";
                    $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price']?? "-";
                }

                if ($value['service_id'] == 3) {
                    $ary['quantity'] =  "-";
                    $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                    $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                    $ary['first_copy_selling_price'] = "-";
                    $ary['additional_copy_selling_price'] = "-";
                }

                if ($value['service_id'] == 4) {
                    $ary['quantity'] = $value['quantity_variant'] ?? "-";
                    if($value['quantity_variant']==0)
                    {
                        $ary['quantity'] = '0';
                    }
                    $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                    $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                    $ary['first_copy_selling_price'] = "-";
                    $ary['additional_copy_selling_price'] = "-";
                }

                if ($value['service_id'] == 5) {
                    $ary['quantity'] = $value['quantity_variant'] ?? "-";
                    if($value['quantity_variant'] == 0)
                    {
                        $ary['quantity'] = '0';
                    }
                    $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                    $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                    $ary['first_copy_selling_price'] = "-";
                    $ary['additional_copy_selling_price'] = "-";
                }

                if ($value['service_id'] == 6) {
                    $ary['quantity'] = "-";
                    $ary['mrp'] = $value['mrp_of_variant'] ?? "-";
                    $ary['selling_price'] = $value['selling_price_of_variant'] ?? "-";
                    $ary['first_copy_selling_price'] = "-";
                    $ary['additional_copy_selling_price'] = "-";
                }

                if ($value['is_publish'] == 1) {
                    $ary['status'] = "PUBLISHED";
                }

                if ($value['is_publish'] == 2) {
                    $ary['status'] = "UN PUBLISHED";
                }
                $overll[] = $ary;
            }
            $s++;
            $excel_report_title = "Product Report";
            $spreadsheet = new Spreadsheet();
            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Employee List")
                ->setSubject("Employee List")
                ->setDescription("Employee List")
                ->setKeywords("Employee List")
                ->setCategory("Employee List");
            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technogenesis");
            $spreadsheet->setActiveSheetIndex(0);
            $sheet = $spreadsheet->getActiveSheet();
            //name the worksheet
            $sheet->setTitle($excel_report_title);
            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Product ID');
            $sheet->setCellValue('C1', 'Service Type');
            $sheet->setCellValue('D1', 'Product Name');
            $sheet->setCellValue('E1', 'Quantity');
            $sheet->setCellValue('F1', 'MRP(₹)');
            $sheet->setCellValue('G1', 'Selling Price(₹)');
            $sheet->setCellValue('H1', 'First Copy Selling Price(₹)');
            $sheet->setCellValue('I1', 'Additional Copy Selling Price(₹)');
            $sheet->setCellValue('J1', 'Status');
            

            $i = 2;
            $fcs = 0;
            $acs = 0;
            foreach ($product as $location) {
                $sheet->setCellValue('A' . $i, $location['Date']);
                $sheet->setCellValue('B' . $i, $location['Product ID']);
                $sheet->setCellValue('C' . $i, $location['Service Type']);
                $sheet->setCellValue('D' . $i, $location['Product Name']);
                $sheet->setCellValue('E' . $i, $location['Quantity']);
                $sheet->setCellValue('F' . $i, $location['MRP(₹)']);
                $sheet->setCellValue('G' . $i, $location['Selling Price(₹)']);
                $sheet->setCellValue('H' . $i, $location['First Copy Selling Price(₹)']);
                $sheet->setCellValue('I' . $i, $location['Additional Copy Selling Price(₹)']);
                $sheet->setCellValue('J' . $i, $location['Status']);
                $sheet->getStyle("F")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("G")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("H")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("I")->getNumberFormat()->setFormatCode('0.00');
                $fcs = $fcs + $location['first_copy_selling_price'];
                $acs = $acs + $location['additional_copy_selling_price'];
                $i++;
            }
            //here your $i val already incremented in foreach() loop
            $sheet->setCellValue('E' . $i, "Total Amount (₹)")->getStyle('H' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('F' . $i,  $mrp)->getStyle('I' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('G' . $i,  $sp)->getStyle('J' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('H' . $i,  $fcs)->getStyle('H' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('I' . $i,  $acs)->getStyle('I' . $i, "Total")->getFont()->setBold( true );
            $conditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional1->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN);
            $conditional1->addCondition('0');
            $conditional1->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            $conditional1->getStyle()->getFont()->setBold(true);
            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('B2')->getConditionalStyles();
            $conditional1->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            //make the font become bold
            $conditional1->getStyle('A2')->getFont()->setBold(true);
            $conditional1->getStyle('A1')->getFont()->setBold(true);
            $conditional1->getStyle('B1')->getFont()->setBold(true);
            $conditional1->getStyle('C3')->getFont()->setBold(true);
            $conditional1->getStyle('D3')->getFont()->setBold(true);
            $conditional1->getStyle('E3')->getFont()->setBold(true);
            $conditional1->getStyle('F3')->getFont()->setBold(true);
            $conditional1->getStyle('A3')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');
            //make the font become bold
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }
            //retrieve  table data
            $overll[] = array('', '', '', '');
            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "product-report-data.xls";
            $fullpath = storage_path() . '/app/product_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/product_reportproduct-report-data.xls'), "product_report.xls");
        }
    }
}
