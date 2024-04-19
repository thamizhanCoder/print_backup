<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class RatingReviewReportController extends Controller
{

    public function product_getcall_rating()
    {
        $get_product = Product::where('product.service_id', 5)->where('is_publish',1)->select('product_id','product_name')->get();

        if (!empty($get_product)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Product listed successfully'),
                    'data' => $get_product
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => []
                ]
            );
        }
    }
    public function rating_report_list(Request $request)
    {
        try {
            Log::channel("ratingreport")->info('** started the rating report list method **');
         
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByProductname = ($request->filterByProductname) ? $request->filterByProductname : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $rating = Rating::leftjoin('customer','customer.customer_id','=','rating_review.customer_id')
            ->leftjoin('product','product.product_id','=','rating_review.product_id')
            ->select('rating_review.*', DB::raw('ROW_NUMBER() OVER (ORDER BY rating_review_id DESC) AS SrNo'),'product.product_name','customer.customer_first_name','customer.customer_last_name','customer.mobile_no');
                          
            if (!empty($from_date)) {
                $rating->where(function ($query) use ($from_date) {
                    $query->whereDate('rating_review.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $rating->where(function ($query) use ($to_date) {
                    $query->whereDate('rating_review.created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByProductname) && $filterByProductname != '[]' && $filterByProductname != 'all') {
                $filterByProductname = json_decode($filterByProductname, true);
                $rating->whereIn('product.product_id', $filterByProductname);
            }

            if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $rating->whereIn('rating_review.status', $filterByStatus);
            }

            $count = $rating->count();

            Log::channel("ratingreport")->info("request value :: $from_date :: $to_date ");
            $rating->orderBy('rating_review_id', 'DESC');
            $rating = $rating->get();
            $final = [];
            $count = $rating->count();
            if ($count > 0) {
                foreach ($rating as $value) {
                    $ary = [];
                    $ary['s_no'] = $value['SrNo'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                    $ary['product_id'] = $value['product_id'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";
                    $ary['rating'] = $value['rating'] ?? "-";
                    $ary['review'] = $value['review'] ?? "-";
                    if($value['status']==1)
                    {
                        $ary['status']="UN PUBLISHED";
                    }
                    if($value['status']==2)
                    {
                        $ary['status']="PUBLISHED";
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ratingreport")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Rating & review report listed successfully'),
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
            Log::channel("ratingreport")->error('** start the rating report list error method **');
            Log::channel("ratingreport")->error($exception);
            Log::channel("ratingreport")->error('** end the rating report list error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    function rating_list_report_Excel(Request $request)
    {
        Log::channel("ratingreport")->info('** started the admin rating report list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByProductname = ($request->filterByProductname) ? $request->filterByProductname : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        
        $rating = Rating::leftjoin('customer','customer.customer_id','=','rating_review.customer_id')
        ->leftjoin('product','product.product_id','=','rating_review.product_id')
        ->select('rating_review.*', DB::raw('ROW_NUMBER() OVER (ORDER BY rating_review_id DESC) AS SrNo'),'product.product_name','customer.customer_first_name','customer.customer_last_name','customer.mobile_no');
      
            if (!empty($from_date)) {
                $rating->where(function ($query) use ($from_date) {
                    $query->whereDate('rating_review.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $rating->where(function ($query) use ($to_date) {
                    $query->whereDate('rating_review.created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByProductname) && $filterByProductname != '[]' && $filterByProductname != 'all') {
                $filterByProductname = json_decode($filterByProductname, true);
                $rating->whereIn('product.product_id', $filterByProductname);
            }

            if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $rating->whereIn('rating_review.status', $filterByStatus);
            }
            
        $rating = $rating->get();
        $count = count($rating);

        $s = 1;
        if ($count > 0) {
            $overll = [];

            foreach ($rating as $value) {
                $ary = [];
                $ary['s_no'] = $value['SrNo'];
                $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                $ary['product_name'] = $value['product_name'] ?? "-";
                $ary['rating'] = $value['rating'] ?? "-";
                $ary['review'] = $value['review'] ?? "-";
                if($value['status']==1)
                {
                    $ary['status']="UN PUBLISHED";
                }
                if($value['status']==2)
                {
                    $ary['status']="PUBLISHED";
                }
                $overll[] = $ary;
            }
            $s++;
            $excel_report_title = "Rating Review Report";
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
            $sheet->setCellValue('A1', 'S No');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Customer Name');
            $sheet->setCellValue('D1', 'Mobile No');
            $sheet->setCellValue('E1', 'Product Name');
            $sheet->setCellValue('F1', 'Ratings');
            $sheet->setCellValue('G1', 'Reviews');
            $sheet->setCellValue('H1', 'Status');
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
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }
            //retrieve  table data
            $overll[] = array('', '', '', '');
            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "rating-report-data.xls";
            $fullpath = storage_path() . '/app/rating_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/rating_reportrating-report-data.xls'), "rating_report.xls");
        }
    }

}
