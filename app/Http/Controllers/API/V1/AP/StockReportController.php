<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Helpers\GlobalHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Http\Requests\PhotoPrintRequest;
use App\Models\GstPercentage;
use App\Models\Service;
use App\Models\Category;
use App\Models\ProductVariant;
use File;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;


class StockReportController extends Controller
{
    public function stockreport_list(Request $request)
    {
        try {
            Log::channel("stockreports")->info('** started the stockreports list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';
            $Status = ($request->filterByStatus) ? $request->filterByStatus : '[]';
            $all = $request->all;


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'created_on' => 'product.created_on',
                'product_id' => 'product.product_id',
                'service_id' => 'service.service_id',
                'service_name' => 'service.service_name',
                'product_name' => 'product.product_name',
                'variant_details' => 'product_variant.variant_attributes',
                'mrp' => 'product_variant.mrp',
                'selling_price' => 'product_variant.selling_price',
                'quantity' => 'product_variant.quantity',
                // 'type_of_stock' => 'product.type_of_stock',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "ASC");
            $column_search = array('product.created_on', 'product.product_id', 'service.service_id', 'service.service_name', 'product.product_name', 'product_variant.variant_details', 'product_variant.mrp', 'product_variant.selling_price', 'product_variant.quantity');

            $stockreportss = ProductVariant::leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')
                ->leftjoin('service', 'service.service_id', '=', 'product.service_id')
                ->select('product.*', 'product_variant.label','product.service_id','service.service_name', 'product_variant.quantity', 'product_variant.variant_attributes', 'product_variant.mrp', 'product_variant.selling_price', 'product_variant.status')
                ->whereIn('service.service_id', [4, 5])->where('product.status', 1);

            if ($Status != '[]') {
                $Status = json_decode($Status, true);   
                if ($Status == [1]) {
                    $stockreportss->where('product_variant.quantity', '>', 0);
                }
                if ($Status == [0]) {
                    $stockreportss->where('product_variant.quantity', "<=", 0);
                }
                // if ($Status == [2]) {
                //     $stockreportss->where('product_variant.quantity', '!=', 2);
                // }
            }


            $stockreportss->where(function ($query) use ($searchval, $column_search, $stockreportss) {
                $i = 0;
                if ($searchval) {
                    foreach ($column_search as $item) {
                        if ($i === 0) {
                            $query->where(($item), 'LIKE', "%{$searchval}%");
                        } else {
                            $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                        }
                        $i++;
                    }
                }
            });
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $stockreportss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $stockreportss->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $stockreportss->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $stockreportss->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByServiceType) && $filterByServiceType != ' ' && $filterByServiceType != 'all') {
                $filterByServiceType = json_decode($filterByServiceType, true);
                $stockreportss->whereIn('service.service_id', $filterByServiceType);
            }
            // if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
            //     $filterByStatus = json_decode($filterByStatus, true);
            //     $stockreportss->where('product_variant.status', $filterByStatus);
            // }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $stockreportss->where('product_variant.quantity', $filterByStatus);
            }

            // if ($TypeOfStock == [1]) {
            //     $stockreportss->where('product_variant.quantity','>',0);
            // }
            // if ($TypeOfStock == [0]) {
            //     $stockreportss->where('product_variant.quantity',"<=",0);
            // }

            $count = $stockreportss->count();

            if ($offset) {
                $offset = $offset * $limit;
                $stockreportss->offset($offset);
            }
            if ($limit) {
                $stockreportss->limit($limit);
            }
            Log::channel("stockreports")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $stockreportss->orderBy('product_id', 'ASC');
            $stockreportss = $stockreportss->get();
            $s = 1;
            if ($count > 0) {
                $final = [];
                foreach ($stockreportss as $value) {
                    $ary = [];
                    // $ary['s_no'] = $value['SrNo'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['service_type'] = $value['service_name'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";
                    // $ary['variant_details'] = json_decode($value['variant_attributes'], true) ?? "-";
                    $variantDetails = $this->variantDetails(json_decode($value['variant_attributes'], true)) ?? "-";

                    // $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                    if ($value['service_id'] == 5) {
                        $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                    } 
                        if ($value['service_id'] == 4) {
                            $primaryVariant = 'colour' ;
                            $ary['variant_details'] = "$primaryVariant: $value[label]" . ", " . implode(", ", $variantDetails);
                            // $ary['variant_details'] = "Primary Variant" .':'. $primaryVariant  . "." . implode(", ", $variantDetails) ?? "-"; 
                    }


                    $ary['mrp'] = $value['mrp'] ?? "-";
                    $ary['selling_price'] = $value['selling_price'] ?? "-";
                    $ary['quantity'] = $value['quantity'] ?? "-";
                    if ($value['quantity'] == 0) {
                        $ary['type_of_stock'] = "Out Of Stock";
                    } else {
                        $ary['type_of_stock'] = "In Stock";
                    }
                    // $ary['status'] = $value['status'] ?? "-";
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("stockreports")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Stock reports listed Successfully'),
                    'data' => $final,
                    'count' => $count,
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
            Log::channel("stockreports")->error($exception);
            Log::channel("stockreports")->error('** end the stockreports list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function variantDetails($variantDetails)
    {
        $resultArray = [];
        if (!empty($variantDetails)) {
            foreach ($variantDetails as $pd) {
                $resultArray[] = $pd['variant_type'] . ':' . $pd['value'];
            }
        }
        return $resultArray;
    }

    public function stockreport_excel(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $Status = ($request->filterByStatus) ? $request->filterByStatus : '[]';
        $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';
        // $TypeOfStock = ($request->filterByTypeOfStock) ? $request->filterByTypeOfStock : '[]';
        $all = $request->all;

        $stockreport = ProductVariant::leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')
            ->leftjoin('service', 'service.service_id', '=', 'product.service_id')
            ->select('product.*', 'product.service_id','product_variant.label','service.service_name', 'product_variant.quantity', 'product_variant.mrp', 'product_variant.variant_attributes', 'product_variant.selling_price', 'product_variant.status')
            ->whereIn('service.service_id', [4, 5])->where('product.status', 1);

        if ($Status != '[]') {
            $Status = json_decode($Status, true);
            if ($Status == [1]) {
                $stockreport->where('product_variant.quantity', '>', 0);
            }
            if ($Status == [0]) {
                $stockreport->where('product_variant.quantity', "<=", 0);
            }
            // if ($Status == [2]) {
            //     $stockreport->where('product_variant.quantity', '!=', 2);
            // }
        }


        if (!empty($from_date)) {
            $stockreport->where(function ($query) use ($from_date) {
                $query->whereDate('created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $stockreport->where(function ($query) use ($to_date) {
                $query->whereDate('created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $stockreport->where('status', $filterByStatus);
        }

        // if (!empty($filterByServiceType) && $filterByServiceType != ' ' && $filterByServiceType != 'all') {
        //     $filterByServiceType = json_decode($filterByServiceType, true);
        //     $stockreport->whereIn('service.service_id', $filterByServiceType);
        // }
        // if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
        //     $filterByStatus = json_decode($filterByStatus, true);
        //     $stockreport->where('product_variant.quantity', $filterByStatus);
        // }

        if (!empty($filterByServiceType) && $filterByServiceType != ' ' && $filterByServiceType != 'all') {
            $filterByServiceType = json_decode($filterByServiceType, true);
            $stockreport->whereIn('service.service_id', $filterByServiceType);
        }
        // if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
        //     $filterByStatus = json_decode($filterByStatus, true);
        //     $stockreport->where('product_variant.status', $filterByStatus);
        // }
        if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $stockreport->where('product_variant.quantity', $filterByStatus);
        }

        $stockreport = $stockreport->get();

        // $totalAmount_count = $stockreport->pluck('totalamount')->sum();

        $count = count($stockreport);

        $s = 1;
        if ($count > 0) {
            $overll = [];
            foreach ($stockreport as $value) {
                $ary = [];
                // $ary['s_no'] = $value['SrNo'];
                // $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['product_id'] = $value['product_code'] ?? "-";
                $ary['service_type'] = $value['service_name'] ?? "-";
                $ary['product_name'] = $value['product_name'] ?? "-";


                $variantDetails = $this->variantDetails(json_decode($value['variant_attributes'], true)) ?? "-";
                if ($value['variant_attributes'] != '[]') {
                    $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                } else {
                    $ary['variant_details'] = "-";
                }

                if ($value['service_id'] == 5) {
                    $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                } 
                    if ($value['service_id'] == 4) {
                        $primaryVariant = 'colour' ;
                        // $ary['variant_details'] = "primaryVariant:'colour'" . "." . implode(", ", $variantDetails);
                        // $ary['variant_details'] = "Primary Variant" .':'. $primaryVariant  . "." . implode(", ", $variantDetails) ?? "-"; 
                        $ary['variant_details'] = "$primaryVariant: $value[label]" . ", " . implode(", ", $variantDetails);
                    }

                // $ary['variant_details'] = json_decode($value['variant_attributes'], true) ?? "-";
                // $variantDetails = $this->variantDetails(json_decode($value['variant_attributes'], true)) ?? "-";
                // $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                $ary['mrp'] = $value['mrp'] ?? "-";
                $ary['selling_price'] = $value['selling_price'] ?? "-";
                $ary['quantity'] = $value['quantity'] ?? "-";
                if ($value['quantity'] == 0) {
                    $ary['type_of_stock'] = "Out Of Stock";
                } else {
                    $ary['type_of_stock'] = "In Stock";
                }
                $overll[] = $ary;
            }
            $s++;
            // return response()->json([
            //     'keyword' => 'success',
            //     'message' => 'Stock Reports listed successfully',
            //     'data' => $overll,
            // ]);
            $excel_report_title = "Stock Report";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Credit Accepted Data")
                ->setSubject("Credit Accepted Data")
                ->setDescription("Credit Accepted Data")
                ->setKeywords("Credit Accepted Data")
                ->setCategory("Credit Accepted Data");

            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technognesis");

            $spreadsheet->setActiveSheetIndex(0);

            $sheet = $spreadsheet->getActiveSheet();

            //name the worksheet
            $sheet->setTitle($excel_report_title);


            // $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('A1', 'Product ID');
            $sheet->setCellValue('B1', 'Service Type');
            $sheet->setCellValue('C1', 'Product Name');
            $sheet->setCellValue('D1', 'Variant Details');
            $sheet->setCellValue('E1', 'MRP(₹)');
            $sheet->setCellValue('F1', 'Selling Price(₹)');
            $sheet->setCellValue('G1', 'Quantity');
            $sheet->setCellValue('H1', 'Type Of Stock');

            $i = 2;
            $total = 0;
            $total2 = 0;
            foreach ($stockreport as $location) {
                // $sheet->setCellValue('A' . $i, $location['Date']);
                // $sheet->setCellValue('A' . $i, $location['Date']);
                $sheet->setCellValue('A' . $i, $location['Product ID']);
                $sheet->setCellValue('B' . $i, $location['Service Type']);
                $sheet->setCellValue('C' . $i, $location['Product Name']);
                $sheet->setCellValue('D' . $i, $location['Variant Details']);
                $sheet->setCellValue('E' . $i, $location['MRP(₹)']);
                $sheet->setCellValue('F' . $i, $location['Selling Price(₹)']);
                $sheet->setCellValue('G' . $i, $location['Quantity']);
                $sheet->setCellValue('H' . $i, $location['Type Of Stock']);
                $sheet->getStyle("E")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("F")->getNumberFormat()->setFormatCode('0.00');
                $total = $total + $location['mrp'];
                $total2 = $total2 + $location['selling_price'];
                $i++;
            }
            //here your $i val already incremented in foreach() loop
            $sheet->setCellValue('D' . $i, "Total Amount")->getStyle('D' . $i, "Total Amount")->getFont()->setBold(true);
            $sheet->setCellValue('E' . $i, $total)->getStyle('E' . $i, "Total Amount")->getFont()->setBold(true);
            $sheet->setCellValue('F' . $i, $total2)->getStyle('F' . $i, "Total Amount")->getFont()->setBold(true);


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
            $conditional1->getStyle('C1')->getFont()->setBold(true);
            $conditional1->getStyle('C3')->getFont()->setBold(true);
            $conditional1->getStyle('D3')->getFont()->setBold(true);
            $conditional1->getStyle('E3')->getFont()->setBold(true);
            $conditional1->getStyle('F3')->getFont()->setBold(true);
            $conditional1->getStyle('G1')->getFont()->setBold(true);
            $conditional1->getStyle('H1')->getFont()->setBold(true);
            // $conditional1->getStyle('I1')->getFont()->setBold(true);
            // $conditional1->getStyle('J1')->getFont()->setBold(true);
            $conditional1->getStyle('A3')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');

            //make the font become bold
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            // $sheet->getStyle('A1')->getFont()->setSize(16);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');

            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }

            //retrieve  table data
            $overll[] = array('', '',  '', '');

            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "stockreport-report-data.xls";
            $fullpath =  storage_path() . '/app/stockreport_report' . $file_name;
            // print_r($fullpath);exit;
            $writer->save($fullpath); // download file
            // return $file_name;
            // return response()->download($fullpath, "sales_report.xls");
            return response()->download(storage_path('app/stockreport_reportstockreport-report-data.xls'), "stockreport_reportstockreport-report-data.xls");
        }
    }

    public function stockreport_pdf(Request $request)
    {

        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        // $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';
        $Status = ($request->filterByStatus) ? $request->filterByStatus : '[]';
        $all = $request->all;


        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'product_id' => 'product.product_id',
            'service_id' => 'service.service_id',
            'service_name' => 'service.service_name',
            'product_name' => 'product.product_name',
            'variant_details' => 'product_variant.variant_attributes',
            'mrp' => 'product_variant.mrp',
            'selling_price' => 'product_variant.selling_price',
            'quantity' => 'product_variant.quantity',
            // 'type_of_stock' => 'product.type_of_stock',


        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('product.product_id', 'service.service_id', 'service.service_name', 'product.product_name', 'product_variant.variant_details', 'product_variant.mrp', 'product_variant.selling_price', 'product_variant.quantity');
        $stockreportss = ProductVariant::leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')
            ->leftjoin('service', 'service.service_id', '=', 'product.service_id')
            ->select('product.*','product.service_id','product_variant.label', 'service.service_name', 'product_variant.quantity', 'product_variant.mrp', 'product_variant.selling_price', 'product_variant.variant_attributes', 'product_variant.status')
            ->whereIn('service.service_id', [4, 5])->where('product.status', 1);

        if ($Status != '[]') {
            $Status = json_decode($Status, true);
            if ($Status == [1]) {
                $stockreportss->where('product_variant.quantity', '>', 0);
            }
            if ($Status == [0]) {
                $stockreportss->where('product_variant.quantity', "<=", 0);
            }
            // if ($Status == [2]) {
            //     $stockreportss->where('product_variant.quantity', '!=', 2);
            // }
        }


        $stockreportss->where(function ($query) use ($searchval, $column_search, $stockreportss) {
            $i = 0;
            if ($searchval) {
                foreach ($column_search as $item) {
                    if ($i === 0) {
                        $query->where(($item), 'LIKE', "%{$searchval}%");
                    } else {
                        $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                    }
                    $i++;
                }
            }
        });
        if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
            $stockreportss->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
            $stockreportss->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $stockreportss->where(function ($query) use ($from_date) {
                $query->whereDate('created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $stockreportss->where(function ($query) use ($to_date) {
                $query->whereDate('created_on', '<=', $to_date);
            });
        }

        // if (!empty($filterByStatus) && $filterByStatus != ' ') {
        //     $filterByStatus = json_decode($filterByStatus, true);
        //     $stockreportss->where('status', $filterByStatus);
        // }


        if (!empty($filterByServiceType) && $filterByServiceType != ' ' && $filterByServiceType != 'all') {
            $filterByServiceType = json_decode($filterByServiceType, true);
            $stockreportss->whereIn('service.service_id', $filterByServiceType);
        }
        // if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
        //     $filterByStatus = json_decode($filterByStatus, true);
        //     $stockreportss->where('product_variant.quantity', $filterByStatus);
        // }
        if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $stockreportss->where('product_variant.quantity', $filterByStatus);
        }

        $count = $stockreportss->count();

        //  $totalAmount = $stockreportss->pluck('totalamount')->sum();

        if ($offset) {
            $offset = $offset * $limit;
            $stockreportss->offset($offset);
        }
        if ($limit) {
            $stockreportss->limit($limit);
        }
        // Log::channel("stockreports")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
        $stockreportss->orderBy('product_id', 'DESC');
        $stockreportss = $stockreportss->get();

        $totalmrp = $stockreportss->pluck('mrp')->sum();
        $totalsellingprice = $stockreportss->pluck('selling_price')->sum();

        $count = count($stockreportss);

        if ($count > 0) {
            $overll = [];
            foreach ($stockreportss as $value) {
                $ary = [];
                // $ary['s_no'] = $value['SrNo'];
                // $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['product_code'] = $value['product_code'] ?? "-";
                $ary['service_name'] = $value['service_name'] ?? "-";
                $ary['product_name'] = $value['product_name'] ?? "-";
                $ary['variant_details'] = json_decode($value['variant_attributes'], true) ?? "-";
                $variantDetails = $this->variantDetails(json_decode($value['variant_attributes'], true)) ?? "-";
                if ($value['variant_attributes'] != '[]') {
                    $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                } else {
                    $ary['variant_details'] = "-";
                }

                if ($value['service_id'] == 5) {
                    $ary['variant_details'] = implode(", ", $variantDetails) ?? "-";
                } 
                    if ($value['service_id'] == 4) {
                        $primaryVariant = 'colour' ;
                        // $ary['variant_details'] = "primaryVariant:'colour'" . "." . implode(", ", $variantDetails);
                        // $ary['variant_details'] = "Primary Variant" .':'. $primaryVariant  . "." . implode(", ", $variantDetails) ?? "-"; 
                        $ary['variant_details'] = "$primaryVariant: $value[label]" . ", " . implode(", ", $variantDetails);
                    }


                $ary['mrp'] = $value['mrp'] ?? "-";
                $ary['selling_price'] = $value['selling_price'] ?? "-";
                $ary['quantity'] = $value['quantity'] ?? "-";
                if ($value['quantity'] == 0) {
                    $ary['type_of_stock'] = "Out Of Stock";
                } else {
                    $ary['type_of_stock'] = "In Stock";
                }
                $overll[] = $ary;
            }


            if (!empty($overll)) {

                $path = public_path() . "/stockreport";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "stockreport_" . time() . '.pdf';
                $location = public_path() . '/stockreport/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/stockreport', $overll)->with('overll', $overll)->with('req', $request)->with('totalmrp', $totalmrp)->with('totalsellingprice', $totalsellingprice)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                return response()->download($location, "stockreport.pdf");
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('message.no_data'),
                        'data' => []
                    ]
                );
            }
        }
    }
}