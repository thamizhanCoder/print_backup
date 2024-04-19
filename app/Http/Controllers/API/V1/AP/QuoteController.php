<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\QuoteConvert;
use App\Events\QuoteReraiseApprovalEmployee;
use App\Events\QuoteReraiseRejectEmployee;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\QuoteCreateRequest;
use App\Http\Requests\QuoteReraisedCreateRequest;
use App\Http\Traits\QuoteTrait;
use App\Models\BulkOrderEnquiry;
use App\Models\BulkOrderEnquiryAssign;
use App\Models\BulkOrderTrackHistory;
use App\Models\BulkOrderEnquiryStatus;
use App\Models\BulkOrderQuote;
use App\Models\BulkOrderQuoteDetails;
use App\Models\BulkOrderQuoteStatus;
use App\Models\CompanyInfo;
use App\Models\Employee;
use App\Models\ProductCatalogue;
use App\Models\QuoteReasonHistory;
use App\Models\Service;
use App\Models\TermsAndConditions;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class QuoteController extends Controller
{
    use QuoteTrait;

    public function serviceGetAll(Request $request)
    {
        try {
            Log::channel("quote")->info('** start the serviceGetAll method **');
            $serviceDetails = Service::select('service_id', 'service_name')->get();
            if (!empty($serviceDetails)) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Service name listed successfully'),
                    'data'        => $serviceDetails

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Service name listed failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the serviceGetAll error method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the serviceGetAll error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteStatusGetAll(Request $request)
    {
        try {
            Log::channel("quote")->info('** start the quoteStatusGetAll method **');
            $quoteStatus = BulkOrderQuoteStatus::get();
            if (!empty($quoteStatus)) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Quote status listed successfully'),
                    'data'        => $quoteStatus

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Quote status listed failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the quoteStatusGetAll error method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the quoteStatusGetAll error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteCreate(QuoteCreateRequest $request)
    {
        try {
            Log::channel("quote")->info('** started the quote create method **');
            $quoteSaveDetailsMessage = "";
            $quote = new BulkOrderQuote();
            $quote->bulk_order_enquiry_id = $request->bulk_order_enquiry_id;
            $quote->quote_date = date('Y-m-d');
            $quote->billing_customer_first_name = $request->billing_customer_name;
            $quote->billing_mobile_number = $request->billing_mobile_number;
            $quote->billing_alt_mobile_number = $request->billing_alt_mobile_number;
            $quote->billing_email = $request->billing_email;
            $quote->billing_gst_no = $request->billing_gst_no;
            $quote->billing_pincode = $request->billing_pincode;
            $quote->billing_address_1 = $request->billing_address_1;
            $quote->billing_address_2 = $request->billing_address_2;
            $quote->billing_landmark = $request->billing_landmark;
            $quote->billing_state_id = $request->billing_state_id;
            $quote->billing_city_id = $request->billing_city_id;
            $quote->sub_total = $request->sub_total;
            $quote->delivery_charge = $request->delivery_charge;
            $quote->round_off = $request->round_off;
            $quote->grand_total = $request->grand_total;
            $quote->portal_type = 1;
            $quote->created_on = Server::getDateTime();
            $quote->created_by = JwtHelper::getSesUserId();

            if ($quote->save()) {

                $quote_code = 'QUOTE' . str_pad($quote->bulk_order_quote_id, 3, '0', STR_PAD_LEFT);
                $quote->quote_code = $quote_code;
                $quote->save();

                $quote_details_validation = $this->quoteDetailsValidation($request->quote_details);

                if ($quote_details_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $quote_details_validation, 'data' => []]);
                } else {

                    $quoteDetails = $request->quote_details;
                }

                if (!empty($quoteDetails)) {

                    $quoteSaveDetails = $this->quoteDetailsInsert($quoteDetails, $quote->bulk_order_quote_id, $request->delivery_charge);

                    if ($quoteSaveDetails == true) {

                        $quoteSaveDetailsMessage = "Quote details successfullly inserted in sub table";
                        Log::channel("quote")->info($quoteSaveDetailsMessage);
                    } else {

                        $quoteSaveDetailsMessage = "Quote detail sub table records not inserted correctly";
                        Log::channel("quote")->info($quoteSaveDetailsMessage);
                    }
                }

                //Update the enquiry table
                $updateStatusEnquiry = BulkOrderEnquiry::find($quote->bulk_order_enquiry_id);
                $updateStatusEnquiry->status = 8;
                $updateStatusEnquiry->updated_on = Server::getDateTime();
                $updateStatusEnquiry->updated_by = JwtHelper::getSesUserId();
                $updateStatusEnquiry->save();
                Log::channel("createOrder")->info("update the enquiry status save value :: $updateStatusEnquiry");

                //BulkOrderTrackHistory
                $updateStatusEnquiryHistory = new BulkOrderTrackHistory();
                $updateStatusEnquiryHistory->bulk_order_enquiry_id = $quote->bulk_order_enquiry_id;
                $updateStatusEnquiryHistory->status = 8;
                $updateStatusEnquiryHistory->portal_type = 1;
                $updateStatusEnquiryHistory->created_on = Server::getDateTime();
                $updateStatusEnquiryHistory->acl_user_id = JwtHelper::getSesUserId();
                $updateStatusEnquiryHistory->save();
                Log::channel("createOrder")->info("insert the enquiry history save value :: $updateStatusEnquiryHistory");

                Log::channel("quote")->info("quote main table save value :: $quote");
                Log::channel("quote")->info('** end the quote update method **');

                // log activity
                $desc =  $quote->quote_code . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Quote');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                //mail send

                $get_customer_details = BulkOrderEnquiry::where('bulk_order_enquiry_id', $quote->bulk_order_enquiry_id)->first();
                $totalQuantity = BulkOrderQuoteDetails::where('bulk_order_quote_id', $quote->bulk_order_quote_id)->sum('quantity');

                // if ($get_customer_details->email != null) {
                //     $mail_data = [];
                //     $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
                //     $mail_data['email'] = $get_customer_details->email;
                //     $mail_data['quote_code'] = $quote_code;
                //     $mail_data['enquiry_code'] = $get_customer_details->enquiry_code;
                //     $mail_data['quantity'] = $totalQuantity;
                //     if ($get_customer_details->email != '') {
                //         event(new QuoteConvert($mail_data));
                //     }
                // }
                $send_customer_email = $this->quoteCreateSendEmailPdf($request, $quote->quote_code, $quote->bulk_order_enquiry_id, $quote->bulk_order_quote_id);

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Quote created successfully'),
                    'data'        => $quote,
                    'quoteSaveDetailsMessage' => $quoteSaveDetailsMessage,

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Quote creation failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the quote error create method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the quote error create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function quoteCreateSendEmailPdf(Request $request, $quotecode, $enquiryId, $quoteId)
    {

        try {
            Log::channel("quote")->info('** started the quote list method **');
            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.alternative_mobile_no', 'bulk_order_enquiry.service_id', 'bulk_order_enquiry.address', 'bulk_order_enquiry.message', 'bulk_order_enquiry.state_id', 'bulk_order_enquiry.district_id', 'state.state_name', 'district.district_name', 's.state_name as billing_state_name', 'd.district_name as billing_city_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')
                ->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->leftjoin('state as s', 's.state_id', '=', 'bulk_order_quote.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'bulk_order_quote.billing_city_id')->where('bulk_order_quote.bulk_order_quote_id', $quoteId)->first();
            $get_customer_details = BulkOrderEnquiry::where('bulk_order_enquiry_id', $enquiryId)->first();
            $totalQuantity = BulkOrderQuoteDetails::where('bulk_order_quote_id', $quoteId)->sum('quantity');

            if (!empty($quoteDetails)) {

                $quoteDate = date('d-m-Y', strtotime($quoteDetails->quote_date)) ?? "-";

                if ($quoteDetails->customer_type == 1) {
                    $customerType = "Individual";
                } else if ($quoteDetails->customer_type == 2) {
                    $customerType = "Company";
                }

                // $totalAmount = $quoteDetails->grand_total;

                // $remaining_value = 0;
                // $roundOffValueSymbol = "";

                // $rounded_value = round($totalAmount);
                // $remainingValue = ($totalAmount) - $rounded_value;
                // $remainingAbsValue = abs($remainingValue);
                // $remaining_value = sprintf("%.2f", $remainingAbsValue);

                $remainingAbsValue = abs($quoteDetails->round_off);
                $remaining_value = sprintf("%.2f", $remainingAbsValue);
                if ($quoteDetails->round_off >= 0.00) {
                    $roundOffValueSymbol = "+";
                } else {
                    $roundOffValueSymbol = "-";
                }

                // $grandTotalRoundOff = round($quoteDetails->grand_total);
                // $grand_total = sprintf("%.2f", $grandTotalRoundOff);

                $quoteOrderDetails = $this->bulkOrderQuoteDetailsPdf($quoteDetails->bulk_order_quote_id);

                $termsAndCOnditionDetails = $this->termsAndCOnditionDetails($quoteDetails->bulk_order_quote_id);

                $company_details = CompanyInfo::first();

                $quoteFileName = $quoteDetails->quote_code . "-" . date('d-m-Y') . ".pdf";

                $path = public_path() . "/quotesendemail";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "quotesendemail_" . time() . '.pdf';
                $location = public_path() . '/quotesendemail/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/quotesendemail', $quoteDetails)->with('quoteOrderDetails', $quoteOrderDetails)->with('termsAndCOnditionDetails', $termsAndCOnditionDetails)->with('quoteDate', $quoteDate)->with('quote_code', $quoteDetails->quote_code)->with('company_details', $company_details)->with('customerType', $customerType)->with('company_name', $quoteDetails->company_name)->with('contact_person_name', $quoteDetails->contact_person_name)->with('mobile_no', $quoteDetails->mobile_no)
                    ->with('alternative_mobile_no', $quoteDetails->alternative_mobile_no)->with('email', $quoteDetails->email)->with('address', $quoteDetails->address)->with('state_name', $quoteDetails->state_name)->with('district_name', $quoteDetails->district_name)->with('billing_customer_first_name', $quoteDetails->billing_customer_first_name)->with('billing_mobile_number', $quoteDetails->billing_mobile_number)->with('billing_alt_mobile_number', $quoteDetails->billing_alt_mobile_number)->with('billing_email', $quoteDetails->billing_email)->with('billing_gst_no', $quoteDetails->billing_gst_no)->with('billing_pincode', $quoteDetails->billing_pincode)->with('billing_address_1', $quoteDetails->billing_address_1)->with('billing_address_2', $quoteDetails->billing_address_2)->with('billing_landmark', $quoteDetails->billing_landmark)->with('billing_city_name', $quoteDetails->billing_city_name)->with('billing_state_name', $quoteDetails->billing_state_name)->with('sub_total', $quoteDetails->sub_total)->with('delivery_charge', $quoteDetails->delivery_charge)->with('grand_total', $quoteDetails->grand_total)->with('remaining_value', $remaining_value)->with('roundOffValueSymbol', $roundOffValueSymbol)->with('req', $request)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                // Send email with attachment
                // Mail::send([], [], function ($message) use ($location, $final) {
                //     $message->to('kamesh@technogenesis.in')->subject('Your Subject');
                //     $message->attach($location, [
                //         'as' => 'quotesendemail.pdf',
                //         'mime' => 'application/pdf',
                //     ]);
                // });

                if ($quoteDetails->email != null) {
                    $mail_data = [
                        'email' => $quoteDetails->email,
                        'order_id' => $quoteDetails->order_code,
                        'contact_person_name' => $quoteDetails->contact_person_name,
                        'quote_code' => $quotecode,
                        'enquiry_code' => $get_customer_details->enquiry_code,
                        'quantity' => $totalQuantity,
                    ];
                    Mail::send('mail.sendquoteconvert', $mail_data, function ($message) use ($mail_data, $location, $quoteFileName) {
                        $message->to($mail_data['email'])
                            ->subject('Quote Created Successfully')
                            ->attach($location, [
                                'as' => $quoteFileName,
                                'mime' => 'application/pdf',
                            ]);
                    });

                    return response()->json(
                        [
                            'keyword' => 'success',
                            'message' => __('Email sent successfully'),
                            'data' => [],
                        ]
                    );
                } else {
                    return response()->json(
                        [
                            'keyword' => 'failure',
                            'message' => __('Email not found'),
                            'data' => [],
                        ]
                    );
                }
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No Data Found'),
                        'data' => []
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('** end the quote list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteReraisedCreate(QuoteReraisedCreateRequest $request)
    {
        try {
            Log::channel("quote")->info('** started the quote Quote re-raised method **');

            $quoteDeails = BulkOrderQuote::where('bulk_order_quote_id', $request->bulk_order_quote_id)->first();
            $quoteDeails->status = 3;
            $quoteDeails->save();

            $quoteSaveDetailsMessage = "";
            $quote = new BulkOrderQuote();
            $quote->bulk_order_enquiry_id = $quoteDeails->bulk_order_enquiry_id;
            $quote->quote_date = date('Y-m-d');
            $quote->billing_customer_first_name = $quoteDeails->billing_customer_first_name;
            $quote->billing_mobile_number = $quoteDeails->billing_mobile_number;
            $quote->billing_alt_mobile_number = $quoteDeails->billing_alt_mobile_number;
            $quote->billing_email = $quoteDeails->billing_email;
            $quote->billing_gst_no = $quoteDeails->billing_gst_no;
            $quote->billing_pincode = $quoteDeails->billing_pincode;
            $quote->billing_address_1 = $quoteDeails->billing_address_1;
            $quote->billing_address_2 = $quoteDeails->billing_address_2;
            $quote->billing_landmark = $quoteDeails->billing_landmark;
            $quote->billing_state_id = $quoteDeails->billing_state_id;
            $quote->billing_city_id = $quoteDeails->billing_city_id;
            $quote->sub_total = $request->sub_total;
            $quote->delivery_charge = $request->delivery_charge;
            $quote->round_off = $request->round_off;
            $quote->grand_total = $request->grand_total;
            $quote->portal_type = 1;
            $quote->reraise_reason = $request->reraise_reason;
            $quote->created_on = Server::getDateTime();
            $quote->created_by = JwtHelper::getSesUserId();

            $bulkOrderDetails = BulkOrderQuote::where('bulk_order_quote_id', $request->bulk_order_quote_id)->first();

            if ($bulkOrderDetails->reraise_quote_id == NULL) {
                $quote->reraise_quote_id = $request->bulk_order_quote_id;
            } else {
                $quote->reraise_quote_id = $bulkOrderDetails->reraise_quote_id;
            }

            if ($quote->save()) {

                $bulkOrderReraiseDetails = BulkOrderQuote::where('reraise_quote_id', $quote->reraise_quote_id)->get();
                $quote_count = $bulkOrderReraiseDetails->count();

                $quoteCodeDetails = BulkOrderQuote::where('bulk_order_quote_id', $quote->reraise_quote_id)->first();
                $quote_code = $quoteCodeDetails->quote_code . '_' . str_pad($quote_count, 2, '0', STR_PAD_LEFT);
                $quote->quote_code = $quote_code;
                $quote->save();

                $quote_details_validation = $this->quoteDetailsValidation($request->quote_details);

                if ($quote_details_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $quote_details_validation, 'data' => []]);
                } else {

                    $quoteDetails = $request->quote_details;
                }

                if (!empty($quoteDetails)) {

                    $quoteSaveDetails = $this->quoteDetailsInsert($quoteDetails, $quote->bulk_order_quote_id, $request->delivery_charge);

                    if ($quoteSaveDetails == true) {

                        $quoteSaveDetailsMessage = "Quote details successfullly inserted in sub table";
                        Log::channel("quote")->info($quoteSaveDetailsMessage);
                    } else {

                        $quoteSaveDetailsMessage = "Quote detail sub table records not inserted correctly";
                        Log::channel("quote")->info($quoteSaveDetailsMessage);
                    }
                }

                //Update the enquiry table
                $updateStatusEnquiry = BulkOrderEnquiry::find($quote->bulk_order_enquiry_id);
                $updateStatusEnquiry->status = 12;
                $updateStatusEnquiry->updated_on = Server::getDateTime();
                $updateStatusEnquiry->updated_by = JwtHelper::getSesUserId();
                $updateStatusEnquiry->save();
                Log::channel("createOrder")->info("update the enquiry status save value :: $updateStatusEnquiry");

                //BulkOrderTrackHistory
                $updateStatusEnquiryHistory = new BulkOrderTrackHistory();
                $updateStatusEnquiryHistory->bulk_order_enquiry_id = $quote->bulk_order_enquiry_id;
                $updateStatusEnquiryHistory->status = 12;
                $updateStatusEnquiryHistory->portal_type = 1;
                $updateStatusEnquiryHistory->created_on = Server::getDateTime();
                $updateStatusEnquiryHistory->acl_user_id = JwtHelper::getSesUserId();
                $updateStatusEnquiryHistory->save();
                Log::channel("createOrder")->info("insert the enquiry history tablesave value :: $updateStatusEnquiryHistory");

                Log::channel("quote")->info("quote main table save value :: $quote");
                Log::channel("quote")->info('** end the quote update method **');

                // log activity
                $desc =  $quote->quote_code . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Quote');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //mail send
                $get_employee_id = BulkOrderEnquiry::where('bulk_order_enquiry_id', $quoteDeails->bulk_order_enquiry_id)->first();

                if (!empty($get_employee_id->employee_id)) {

                    $get_employee_details = Employee::where('employee_id', $get_employee_id->employee_id)->first();
                    if ($get_employee_details->email != null) {
                        $mail_data = [];
                        $mail_data['employee_name'] = !empty($get_employee_details->employee_name) ? $get_employee_details->employee_name : $get_employee_details->employee_name;
                        $mail_data['email'] = $get_employee_details->email;
                        $mail_data['quote_code'] = $quote_code;

                        if ($get_employee_details->email != '') {
                            event(new QuoteReraiseApprovalEmployee($mail_data));
                        }
                    }

                    $title = "Quote Reraised Approved" . " - " . $quote_code;
                    $body = "Your reraised quote $quote_code has been approved by admin.";
                    $module = 'Quote reraised Approved';
                    $page = 'quote_reraised_approved';
                    $portal = 'employee';
                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    $data = [
                        'bulk_order_enquiry_id' => $quoteDeails->bulk_order_enquiry_id,
                        'quote_id' => $request->bulk_order_quote_id,
                        'platform' => "employee",
                        'employee_name' => $get_employee_details->employee_name,
                        'random_id' => $random_id,
                        'page' => 'quote_reraised_approved',
                        'url' => ''
                    ];
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal,
                        'module' => $module
                    ];
                    $token = Employee::where('employee_id', $get_employee_details->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token')->first();
                    $user_id = JwtHelper::getSesUserId();
                    if (!empty($token)) {
                        $push = Firebase::sendSingle($token->fcm_token, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 1, $user_id, $get_employee_id->employee_id, $module, $page, $portal, $data, $random_id);
                }

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Quote re-raised successfully'),
                    'data'        => $quote,
                    'quoteSaveDetailsMessage' => $quoteSaveDetailsMessage,

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Quote re-raised failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the quote error Quote re-raised method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the quote error Quote re-raised method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function serviceBasedProductList($serviceId)
    {
        try {
            Log::channel("quote")->info('** start the serviceBasedProductList method **');
            $productDetails = ProductCatalogue::where('product.status', 1)->where('is_publish', 1)->where('product.service_id', $serviceId)->select('product.*')->get();

            $final = [];
            if (!empty($productDetails)) {

                foreach ($productDetails as $data) {
                    $ary = [];
                    $ary['product_id'] = $data['product_id'];
                    $ary['service_id'] = $data['service_id'];
                    $ary['product_name'] = $data['product_name'];
                    $ary['product_code'] = $data['product_code'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Product name listed successfully'),
                        'data' => $final,
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                        'data' => [],
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the serviceBasedProductList error method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the serviceBasedProductList error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function productfilter(Request $request)
    {
        try {
            Log::channel("quote")->info('** start the productfilter method **');

            $product_id = ($request->product_id) ? $request->product_id : '';
            $product_code = ($request->product_code) ? $request->product_code : '';

            if (!empty($product_id)) {
                $productDetails = ProductCatalogue::where('product.status', 1)->where('product.product_id', $product_id)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')->leftjoin('terms_and_conditions', 'terms_and_conditions.service_id', '=', 'product.service_id')->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'product.service_id')->select('product.*', 'service.service_name', 'terms_and_conditions.description as terms_and_condition', 'photo_print_setting.width', 'photo_print_setting.height', 'gst_percentage.gst_percentage as gst_value', 'delivery_management.slab_details')->first();
            } else if (!empty($product_code)) {
                $productDetails = ProductCatalogue::where('product.status', 1)->where('product.product_code', $product_code)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')->leftjoin('terms_and_conditions', 'terms_and_conditions.service_id', '=', 'product.service_id')->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'product.service_id')->select('product.*', 'service.service_name', 'terms_and_conditions.description as terms_and_condition', 'photo_print_setting.width', 'photo_print_setting.height', 'gst_percentage.gst_percentage as gst_value', 'delivery_management.slab_details')->first();
            }

            if (!empty($productDetails)) {

                $ary = [];
                $ary['product_id'] = $productDetails->product_id;
                $ary['service_id'] = $productDetails->service_id;
                $ary['product_name'] = $productDetails->product_name;
                $ary['product_code'] = $productDetails->product_code;
                $ary['gst_value'] = $productDetails->gst_value;
                $ary['slab_details'] = json_decode($productDetails->slab_details, true);
                $ary['primary_variant_details'] = $this->primaryVariantDetailsJson($productDetails->primary_variant_details);
                if ($productDetails->service_id == 1) {
                    $ary['weight'] = $productDetails->weight;
                    $ary['mrp'] = $productDetails->mrp;
                    $ary['selling_price'] = $productDetails->selling_price;
                }
                if ($productDetails->service_id == 2) {
                    $ary['weight'] = $productDetails->weight;
                    $ary['mrp'] = $productDetails->mrp;
                    $ary['selling_price'] = $productDetails->first_copy_selling_price;
                    $ary['additional_selling_price'] = $productDetails->additional_copy_selling_price;
                    $ary['print_size'] = $productDetails->width . '*' . $productDetails->height;
                }
                if ($productDetails->service_id == 3) {

                    $ary['mrp'] = $this->productAmountDetails($productDetails->product_id, "mrp");
                    $ary['selling_price'] = $this->productAmountDetails($productDetails->product_id, "selling");
                    $ary['variant_details'] = $this->getPhotoFrameVariantDetails($productDetails->product_id);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($productDetails->selected_variants, true));
                }
                if ($productDetails->service_id == 4) {

                    $ary['mrp'] = $this->productAmountDetails($productDetails->product_id, "mrp");
                    $ary['selling_price'] = $this->productAmountDetails($productDetails->product_id, "selling");
                    $ary['is_customized'] = $productDetails->is_customized;
                    $ary['variant_details'] = $this->getPersonalizedVariantDetails($productDetails->product_id);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($productDetails->selected_variants, true));
                }
                if ($productDetails->service_id == 5) {

                    $ary['mrp'] = $this->productAmountDetails($productDetails->product_id, "mrp");
                    $ary['selling_price'] = $this->productAmountDetails($productDetails->product_id, "selling");
                    $ary['variant_details'] = $this->getEcommerceVariantDetails($productDetails->product_id);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($productDetails->selected_variants, true));
                }
                if ($productDetails->service_id == 6) {

                    $ary['mrp'] = $this->productAmountDetails($productDetails->product_id, "mrp");
                    $ary['selling_price'] = $this->productAmountDetails($productDetails->product_id, "selling");
                    $ary['variant_details'] = $this->getSelfieVariantDetails($productDetails->product_id);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($productDetails->selected_variants, true));
                }
                $ary['status'] = $productDetails->status;
                $ary['service_name'] = $productDetails->service_name;
                $ary['terms_and_condition'] = $productDetails->terms_and_condition;
                $final[] = $ary;
            }


            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Product details viewed successfully'),
                        'data' => $final,
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                        'data' => [],
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the productfilter error method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the productfilter error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function termsAndCondition(Request $request)
    {
        try {
            Log::channel("quote")->info('** start the termsAndCondition method **');
            $ids = $request->serviceIds;

            $serviceIds = json_decode($ids, true);
            $termsDetails = TermsAndConditions::whereIn('terms_and_conditions.service_id', $serviceIds)->leftjoin('service', 'service.service_id', '=', 'terms_and_conditions.service_id')->select('terms_and_conditions.*', 'service.service_name')->get();

            if (!empty($termsDetails)) {
                $final = [];
                foreach ($termsDetails as $detail) {
                    $ary = [];
                    $ary['service_name'] = $detail->service_name;
                    $ary['description'] = $detail->description;
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Terms and conditions listed successfully'),
                        'data' => $final,
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                        'data' => [],
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->info('** start the termsAndCondition error method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->info('** end the termsAndCondition error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteList(Request $request)
    {
        try {
            Log::channel("quote")->info('** started the quote list method **');
            $type = ($request->type) ? $request->type : '';
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'created_on' => 'bulk_order_quote.created_on',
                'quote_date' => 'bulk_order_quote.quote_date',
                'quote_code' => 'bulk_order_quote.quote_code',
                'customer_name' => 'bulk_order_enquiry.contact_person_name',
                'mobile_no' => 'bulk_order_enquiry.mobile_no',
                'email' => 'bulk_order_enquiry.email',
                'company_name' => 'bulk_order_enquiry.company_name',

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "bulk_order_quote.bulk_order_quote_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");



            $column_search = array(
                'bulk_order_quote.created_on', 'bulk_order_quote.quote_date', 'bulk_order_quote.quote_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.company_name'
            );



            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id');

            $quoteDetails->where(function ($query) use (
                $searchval,
                $column_search,
                $quoteDetails
            ) {
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
                $quoteDetails->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $quoteDetails->where(function ($query) use ($from_date) {
                    $query->whereDate('bulk_order_quote.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $quoteDetails->where(function ($query) use ($to_date) {
                    $query->whereDate('bulk_order_quote.created_on', '<=', $to_date);
                });
            }

            if (!empty($type)) {
                if ($type == "overall") {
                    $quoteDetails->whereIn('bulk_order_quote.status', [1, 2, 3, 4, 5, 6, 7, 8]);
                } else {
                    $quoteDetails->where('bulk_order_quote.is_reraised', 1);
                }
            }

            if (!empty($filterByStatus)) {
                if ($filterByStatus == 1) {
                    $quoteDetails->whereIn('bulk_order_quote.status', [1, 7]);
                } else if ($filterByStatus == 2) {
                    $quoteDetails->whereIn('bulk_order_quote.status', [2, 6]);
                } else if ($filterByStatus == 6) {
                    $quoteDetails->where('bulk_order_quote.status', 8);
                } else {
                    $quoteDetails->where('bulk_order_quote.status', $filterByStatus);
                }
            }

            if (!empty($filterByCustomerType)) {
                $quoteDetails->where('bulk_order_enquiry.customer_type', $filterByCustomerType);
            }


            $count = $quoteDetails->count();

            if ($offset) {
                $offset = $offset * $limit;
                $quoteDetails->offset($offset);
            }
            if ($limit) {
                $quoteDetails->limit($limit);
            }
            $quoteDetails->orderBy('bulk_order_quote.bulk_order_quote_id', 'desc');
            $quoteDetails = $quoteDetails->get();

            $totalCount = BulkOrderQuote::get()->Count();
            $final = [];

            if ($count > 0) {
                foreach ($quoteDetails as $value) {
                    $ary = [];
                    $ary['bulk_order_quote_id'] = $value['bulk_order_quote_id'];
                    $ary['created_on'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['quote_date'] = date('d-m-Y', strtotime($value['quote_date']));
                    $ary['quote_code'] = $value['quote_code'];
                    $ary['customer_name'] = $value['contact_person_name'];
                    $ary['mobile_no'] = $value['mobile_no'];
                    $ary['email'] = $value['email'];
                    if ($value['customer_type'] == 1) {
                        $ary['customer_type'] = "Individual";
                    } else if ($value['customer_type'] == 2) {
                        $ary['customer_type'] = "Company";
                    }
                    $ary['company_name'] = $value['company_name'];
                    $ary['status'] = $value['status'];
                    $ary['is_orderplaced'] = $value['is_orderplaced'];
                    $ary['employee_request_raise_notes'] = $value['notes'];
                    $ary['admin_quote_reject_notes'] = $value['admin_quote_reject_notes'];
                    $ary['admin_reason_quote_reject_notes'] = $value['admin_reason_quote_reject_notes'];
                    $ary['reraise_quote_id'] = $value['reraise_quote_id'];
                    $ary['quote_reason_history'] = $this->quoteReasonHistory($value['bulk_order_quote_id']);
                    $final[] = $ary;
                }
            }


            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("quote")->info("list value :: $log");
                Log::channel("quote")->info('** end the quote list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Quote listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'totalCount' => $totalCount
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('** end the quote list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteExcel(Request $request)
    {
        Log::channel("enquiryModule")->info('** started the admin enquiry list excel method **');
        $type = ($request->type) ? $request->type : '';
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';
        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'created_on' => 'bulk_order_quote.created_on',
            'quote_date' => 'bulk_order_quote.quote_date',
            'quote_code' => 'bulk_order_quote.quote_code',
            'customer_name' => 'bulk_order_enquiry.contact_person_name',
            'mobile_no' => 'bulk_order_enquiry.mobile_no',
            'email' => 'bulk_order_enquiry.email',
            'company_name' => 'bulk_order_enquiry.company_name',

        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "bulk_order_quote.bulk_order_quote_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");



        $column_search = array(
            'bulk_order_quote.created_on', 'bulk_order_quote.quote_date', 'bulk_order_quote.quote_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.company_name'
        );



        $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id');

        $quoteDetails->where(function ($query) use (
            $searchval,
            $column_search,
            $quoteDetails
        ) {
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
            $quoteDetails->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if (!empty($from_date)) {
            $quoteDetails->where(function ($query) use ($from_date) {
                $query->whereDate('bulk_order_quote.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $quoteDetails->where(function ($query) use ($to_date) {
                $query->whereDate('bulk_order_quote.created_on', '<=', $to_date);
            });
        }

        if (!empty($type)) {
            if ($type == "overall") {
                $quoteDetails->whereIn('bulk_order_quote.status', [1, 2, 3, 4, 5, 6, 7, 8]);
            } else {
                $quoteDetails->where('bulk_order_quote.is_reraised', 1);
            }
        }

        if (!empty($filterByStatus)) {
            if ($filterByStatus == 1) {
                $quoteDetails->whereIn('bulk_order_quote.status', [1, 7]);
            } else if ($filterByStatus == 2) {
                $quoteDetails->whereIn('bulk_order_quote.status', [2, 6]);
            } else if ($filterByStatus == 6) {
                $quoteDetails->where('bulk_order_quote.status', 8);
            } else {
                $quoteDetails->where('bulk_order_quote.status', $filterByStatus);
            }
        }

        if (!empty($filterByCustomerType)) {
            $quoteDetails->where('bulk_order_enquiry.customer_type', $filterByCustomerType);
        }


        $count = $quoteDetails->count();

        if ($offset) {
            $offset = $offset * $limit;
            $quoteDetails->offset($offset);
        }
        if ($limit) {
            $quoteDetails->limit($limit);
        }
        $quoteDetails->orderBy('bulk_order_quote.bulk_order_quote_id', 'desc');
        $quoteDetails = $quoteDetails->get();
        $s = 1;
        if (!empty($quoteDetails)) {
            if ($count > 0) {
                $overll = [];
                foreach ($quoteDetails as $value) {
                    $ary = [];
                    $ary['created_on'] = date('d-m-Y', strtotime($value['created_on']));
                    // $ary['quote_date'] = date('d-m-Y', strtotime($value['quote_date']));
                    $ary['quote_code'] = $value['quote_code'];
                    $ary['customer_name'] = $value['contact_person_name'];
                    $ary['mobile_no'] = $value['mobile_no'];
                    $ary['email'] = $value['email'];
                    if ($value['customer_type'] == 1) {
                        $ary['customer_type'] = "Individual";
                    } else {
                        $ary['customer_type'] = "Company";
                    }
                    $ary['company_name'] = !empty($value['company_name']) ? $value['company_name'] : '-';
                    if ($value['status'] == 1 || $value['status'] == 7) {
                        $ary['status'] = "Quote Pending";
                    } else if ($value['status'] == 2 || $value['status'] == 6) {
                        $ary['status'] = "Request for Reraise";
                    } else if ($value['status'] == 3) {
                        $ary['status'] = "Quote re-raised";
                    } else if ($value['status'] == 4) {
                        $ary['status'] = "Quote Approved";
                    } else if ($value['status'] == 5) {
                        $ary['status'] = "Quote Disapproved";
                    } else if ($value['status'] == 6) {
                        $ary['status'] = "Order Placed";
                    }
                    $overll[] = $ary;
                }
                $s++;
                $excel_report_title = "Quote List Report";
                $spreadsheet = new Spreadsheet();
                //Set document properties
                $spreadsheet->getProperties()->setCreator("Technogenesis")
                    ->setLastModifiedBy("Technogenesis")
                    ->setTitle("Quote List Report")
                    ->setSubject("Quote List Report")
                    ->setDescription("Quote List Report")
                    ->setKeywords("Quote List Report")
                    ->setCategory("Quote List Report");
                $spreadsheet->getProperties()->setCreator("technogenesis.in")
                    ->setLastModifiedBy("Technogenesis");
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                //name the worksheet
                $sheet->setTitle($excel_report_title);
                $sheet->setCellValue('A1', 'Date');
                $sheet->setCellValue('B1', 'Quote No');
                $sheet->setCellValue('C1', 'Customer Name');
                $sheet->setCellValue('D1', 'Mobile Number');
                $sheet->setCellValue('E1', 'Email Id');
                $sheet->setCellValue('F1', 'Customer Type');
                $sheet->setCellValue('G1', 'Company Name');
                $sheet->setCellValue('H1', 'Quote Status');
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
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
                for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                    $sheet->getColumnDimension(chr($col))->setAutoSize(true);
                }
                //retrieve  table data
                $overll[] = array('', '', '', '');
                //Fill data
                $sheet->fromArray($overll, null, 'A2');
                $writer = new Xls($spreadsheet);
                $file_name = "Quote-report-data.xls";
                $fullpath = storage_path() . '/app/Quote_report' . $file_name;
                $writer->save($fullpath); // download file
                return response()->download(storage_path('app/Quote_reportQuote-report-data.xls'), "Quote_report.xls");
            }
        }
    }

    public function view($enquiryId)
    {
        $enquiryDetails = BulkOrderEnquiry::select('bulk_order_enquiry.*', 'state.state_name', 'district.district_name')->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')
            ->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->where('bulk_order_enquiry_id', $enquiryId)->first();

        if (!empty($enquiryDetails)) {

            $ary = [];
            $ary['bulk_order_enquiry_id'] = $enquiryDetails->bulk_order_enquiry_id;
            if ($enquiryDetails->customer_type == 1) {
                $ary['customer_type'] = "Individual";
            } else if ($enquiryDetails->customer_type == 2) {
                $ary['customer_type'] = "Company";
            }
            $ary['contact_person_name'] = $enquiryDetails->contact_person_name;
            $ary['company_name'] = $enquiryDetails->company_name;
            $ary['mobile_no'] = $enquiryDetails->mobile_no;
            $ary['alternative_mobile_no'] = $enquiryDetails->alternative_mobile_no;
            $ary['email'] = $enquiryDetails->email;
            if (!empty($enquiryDetails->service_id)) {
                $service_name = $this->getServiceNameforList($enquiryDetails->service_id);
                $ary['category'] = (!empty($service_name)) ? json_encode($service_name, true) : NULL;
            }
            $ary['service_id'] = $enquiryDetails->service_id;
            $ary['address'] = $enquiryDetails->address;
            $ary['message'] = $enquiryDetails->message;
            $ary['state_id'] = $enquiryDetails->state_id;
            $ary['state_name'] = $enquiryDetails->state_name;
            $ary['district_id'] = $enquiryDetails->district_id;
            $ary['district_name'] = $enquiryDetails->district_name;
            $final[] = $ary;
        }


        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Enquiry details viewed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }

    public function quoteView(Request $request, $quoteId)
    {
        try {
            Log::channel("quote")->error('**started the quoteView method**');
            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.enquiry_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.alternative_mobile_no', 'bulk_order_enquiry.service_id', 'bulk_order_enquiry.address', 'bulk_order_enquiry.message', 'bulk_order_enquiry.state_id', 'bulk_order_enquiry.district_id', 'state.state_name', 'district.district_name', 's.state_name as billing_state_name', 'd.district_name as billing_city_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')
                ->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->leftjoin('state as s', 's.state_id', '=', 'bulk_order_quote.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'bulk_order_quote.billing_city_id')->where('bulk_order_quote.bulk_order_quote_id', $quoteId)->first();

            if (!empty($quoteDetails)) {

                $ary = [];
                $ary['company_details'] = CompanyInfo::first();
                $ary['quote_date'] = $quoteDetails->quote_date;
                $ary['quote_code'] = $quoteDetails->quote_code;
                $ary['enquiry_code'] = $quoteDetails->enquiry_code;
                $ary['bulk_order_enquiry_id'] = $quoteDetails->bulk_order_enquiry_id;
                $ary['bulk_order_quote_id'] = $quoteDetails->bulk_order_quote_id;
                if ($quoteDetails->customer_type == 1) {
                    $ary['customer_type'] = "Individual";
                } else if ($quoteDetails->customer_type == 2) {
                    $ary['customer_type'] = "Company";
                }
                $ary['contact_person_name'] = $quoteDetails->contact_person_name;
                $ary['company_name'] = $quoteDetails->company_name;
                $ary['mobile_no'] = $quoteDetails->mobile_no;
                $ary['alternative_mobile_no'] = $quoteDetails->alternative_mobile_no;
                $ary['email'] = $quoteDetails->email;
                $ary['service_id'] = $quoteDetails->service_id;
                if (!empty($quoteDetails->service_id)) {
                    $service_name = $this->getServiceNameforList($quoteDetails->service_id);
                    $ary['category'] = (!empty($service_name)) ? json_encode($service_name, true) : NULL;
                }
                $ary['address'] = $quoteDetails->address;
                $ary['message'] = $quoteDetails->message;
                $ary['state_name'] = $quoteDetails->state_name;
                $ary['district_name'] = $quoteDetails->district_name;
                $ary['sub_total'] = $quoteDetails->sub_total;
                $ary['delivery_charge'] = $quoteDetails->delivery_charge;
                $ary['round_off'] = $quoteDetails->round_off;
                $ary['grand_total'] = $quoteDetails->grand_total;
                $ary['billing_customer_first_name'] = $quoteDetails->billing_customer_first_name;
                $ary['billing_mobile_number'] = $quoteDetails->billing_mobile_number;
                $ary['billing_alt_mobile_number'] = $quoteDetails->billing_alt_mobile_number;
                $ary['billing_email'] = $quoteDetails->billing_email;
                $ary['billing_gst_no'] = $quoteDetails->billing_gst_no;
                $ary['billing_pincode'] = $quoteDetails->billing_pincode;
                $ary['billing_address_1'] = $quoteDetails->billing_address_1;
                $ary['billing_address_2'] = $quoteDetails->billing_address_2;
                $ary['billing_landmark'] = $quoteDetails->billing_landmark;
                $ary['billing_state_id'] = $quoteDetails->billing_state_id;
                $ary['billing_city_id'] = $quoteDetails->billing_city_id;
                $ary['billing_state_name'] = $quoteDetails->billing_state_name;
                $ary['billing_city_name'] = $quoteDetails->billing_city_name;
                $ary['status'] = $quoteDetails->status;
                $ary['reraise_reason'] = $quoteDetails->reraise_reason;
                $quoteOrderDetails = $this->bulkOrderQuoteDetails($quoteDetails->bulk_order_quote_id);
                $termsAndCOnditionDetails = $this->termsAndCOnditionDetails($quoteDetails->bulk_order_quote_id);
                $ary['quoteOrderDetails'] = $quoteOrderDetails;
                $ary['termsAndCOnditionDetails'] = $termsAndCOnditionDetails;
                $ary['review'] = $this->reviewList($quoteDetails->bulk_order_enquiry_id, $quoteDetails->bulk_order_quote_id);
                $final[] = $ary;
            }


            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Quote viewed successfully'),
                        'data' => $final,
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                        'data' => [],
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error('**started the error quoteView method**');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('**ended the error quoteView method**');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteUpdateStatus(Request $request)
    {
        try {
            Log::channel("quote")->info('** started the quoteUpdateStatus update method **');
            $updateStatus = BulkOrderQuote::find($request->bulk_order_quote_id);
            $updateStatus->status = $request->status;
            if ($request->status == 7) {
                $updateStatus->admin_reason_quote_reject_notes = $request->notes;
            } else if ($request->status == 5) {
                $updateStatus->admin_quote_reject_notes = $request->notes;
            }
            $updateStatus->updated_on = Server::getDateTime();
            $updateStatus->updated_by = JwtHelper::getSesUserId();
            if ($updateStatus->save()) {
                Log::channel("quote")->info("Updated the BulkOrderQuote table in status :: $updateStatus");

                //Bulk order enquiry table update
                $enquiryStatusUpdate = BulkOrderEnquiry::find($updateStatus->bulk_order_enquiry_id);

                if ($request->status == 6) {
                    Log::channel("quote")->info('** Updated the enquiry table status Request for reraise approved **');
                    $status = 15;
                    $enquiry_status = 7;
                } else if ($request->status == 7) {
                    Log::channel("quote")->info('** Updated the enquiry table status Request for reraise rejected **');
                    $status = 16;
                    $enquiry_status = 8;
                } else if ($request->status == 4) {
                    Log::channel("quote")->info('** Updated the enquiry table status Quote Approved **');
                    $status = 9;
                    $enquiry_status = 9;
                } else if ($request->status == 5) {
                    Log::channel("quote")->info('** Updated the enquiry table status Quote Rejected **');
                    $status = 10;
                    $enquiry_status = 10;
                }
                $enquiryStatusUpdate->status = $enquiry_status;
                $enquiryStatusUpdate->updated_on = Server::getDateTime();
                $enquiryStatusUpdate->updated_by = JwtHelper::getSesUserId();
                $enquiryStatusUpdate->save();

                if ($request->status == 7) {
                    $quoteReasonUpdate = new QuoteReasonHistory();
                    $quoteReasonUpdate->bulk_order_quote_id = $request->bulk_order_quote_id;
                    $quoteReasonUpdate->notes = $request->notes;
                    $quoteReasonUpdate->acl_user_id = JwtHelper::getSesUserId();
                    $quoteReasonUpdate->created_on = Server::getDateTime();
                    $quoteReasonUpdate->save();
                }
                Log::channel("quote")->info("Updated the enquiry table in status :: $enquiryStatusUpdate");

                //BulkOrderTrackHistory
                $updateStatusEnquiry = new BulkOrderTrackHistory();
                $updateStatusEnquiry->bulk_order_enquiry_id = $updateStatus->bulk_order_enquiry_id;
                $updateStatusEnquiry->status = $status;
                $updateStatusEnquiry->portal_type = 1;
                $updateStatusEnquiry->enquiry_notes = $request->notes;
                $updateStatusEnquiry->created_on = Server::getDateTime();
                $updateStatusEnquiry->acl_user_id = JwtHelper::getSesUserId();
                $updateStatusEnquiry->save();
                Log::channel("quote")->info("Updated the enquiry track history table in status :: $updateStatusEnquiry");

                $getStatus = BulkOrderEnquiryStatus::where('bulk_order_enquiry_status_id', $enquiryStatusUpdate->status)->first();

                //log activity
                $desc =  $updateStatus->quote_code . ' is status(' . $getStatus->status . ') updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Quote');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("quote")->info('** end the quoteUpdateStatus update method **');

                if ($request->status == 6) {
                    return response()->json(['keyword' => 'success', 'message' => ('Request for reraise approved successfully'), 'data' => []]);
                } else if ($request->status == 7) {
                    return response()->json(['keyword' => 'success', 'message' => ('Request for reraise rejected successfully'), 'data' => []]);
                } else if ($request->status == 4) {
                    $sendQuoteApproveMail = $this->sendQuoteApproveMail($updateStatus->quote_code, $updateStatus->bulk_order_enquiry_id);
                    return response()->json(['keyword' => 'success', 'message' => ('Quote approved successfully'), 'data' => []]);
                } else if ($request->status == 5) {
                    $quoteSaveDetails = $this->quoterejectEmail($request->notes, $updateStatus->quote_code, $updateStatus->bulk_order_enquiry_id);
                    $get_employee_id = BulkOrderEnquiry::where('bulk_order_enquiry_id', $updateStatus->bulk_order_enquiry_id)->first();

                    if (!empty($get_employee_id->employee_id)) {
                        $get_employee_details = Employee::where('employee_id', $get_employee_id->employee_id)->first();
                        $title = "Quote reraised Rejected" . " - " . $updateStatus->quote_code;
                        $body = "Your reraised quote $updateStatus->quote_code has been rejected by admin.";
                        $module = 'Quote reraised rejected';
                        $page = 'quote_reraised_rejected';
                        $portal = 'employee';
                        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                        $data = [
                            'bulk_order_enquiry_id' => $updateStatus->bulk_order_enquiry_id,
                            'quote_id' => $request->bulk_order_quote_id,
                            'platform' => "employee",
                            'employee_name' => $get_employee_details->employee_name,
                            'random_id' => $random_id,
                            'page' => 'quote_reraised_rejected',
                            'url' => ''
                        ];

                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal,
                            'module' => $module
                        ];
                        $token = Employee::where('employee_id', $get_employee_details->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token')->first();
                        $user_id = JwtHelper::getSesUserId();
                        if (!empty($token)) {
                            $push = Firebase::sendSingle($token->fcm_token, $message);
                        }
                        $getdata = GlobalHelper::notification_create($title, $body, 3, $user_id, $get_employee_id->employee_id, $module, $page, $portal, $data, $random_id);
                    }
                    return response()->json(['keyword' => 'success', 'message' => ('Quote rejected successfully'), 'data' => []]);
                }
            } else {
                return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error('**starts error occured in quoteUpdateStatus update method **');
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('**end error occured in quoteUpdateStatus update method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function sendQuoteApproveMail($quotecode, $enquiryId)
    {
        $getCustomerEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $enquiryId)->first();

        if (!empty($getCustomerEmail)) {
            if ($getCustomerEmail->email != null) {
                $mail_data = [
                    'email' => $getCustomerEmail->email,
                    'quote_code' => $quotecode,
                    'customer_name' => $getCustomerEmail->contact_person_name,
                ];

                Mail::send('mail.sendquoteapprovecustomer', $mail_data, function ($message) use ($mail_data) {
                    $message->to($mail_data['email'])
                        ->subject('Quote Approved');
                });
            }
        }
    }

    public function quoterejectEmail($notes, $quote_code, $getenquiryId)
    {
        //mail send
        $get_employee_id = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getenquiryId)->first();

        if (!empty($get_employee_id->employee_id)) {
            $get_employee_details = Employee::where('employee_id', $get_employee_id->employee_id)->first();
            if ($get_employee_details->email != null) {
                $mail_data = [];
                $mail_data['employee_name'] = !empty($get_employee_details->employee_name) ? $get_employee_details->employee_name : $get_employee_details->employee_name;
                $mail_data['email'] = $get_employee_details->email;
                $mail_data['quote_code'] = $quote_code;
                $mail_data['notes'] = $notes;

                if ($get_employee_details->email != '') {
                    event(new QuoteReraiseRejectEmployee($mail_data));
                }
            }
        }
    }

    public function quoteSendEmailPdf(Request $request, $quoteId)
    {
        try {
            Log::channel("quote")->info('** started the quote list method **');
            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.alternative_mobile_no', 'bulk_order_enquiry.service_id', 'bulk_order_enquiry.address', 'bulk_order_enquiry.message', 'bulk_order_enquiry.state_id', 'bulk_order_enquiry.district_id', 'state.state_name', 'district.district_name', 's.state_name as billing_state_name', 'd.district_name as billing_city_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')
                ->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->leftjoin('state as s', 's.state_id', '=', 'bulk_order_quote.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'bulk_order_quote.billing_city_id')->where('bulk_order_quote.bulk_order_quote_id', $quoteId)->first();
            $getquotecode = BulkOrderQuote::where('bulk_order_quote_id', $quoteId)->first();

            $getenquiryIdDetails = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getquotecode->bulk_order_enquiry_id)->first();

            $totalQuantity = BulkOrderQuoteDetails::where('bulk_order_quote_id', $quoteId)->sum('quantity');
            if (!empty($quoteDetails)) {

                $quoteDate = date('d-m-Y', strtotime($quoteDetails->quote_date)) ?? "-";

                if ($quoteDetails->customer_type == 1) {
                    $customerType = "Individual";
                } else if ($quoteDetails->customer_type == 2) {
                    $customerType = "Company";
                }

                // $totalAmount = $quoteDetails->grand_total;

                // $remaining_value = 0;
                // $roundOffValueSymbol = "";

                // $rounded_value = round($totalAmount);
                // $remainingValue = ($totalAmount) - $rounded_value;
                // $remainingAbsValue = abs($remainingValue);
                // $remaining_value = sprintf("%.2f", $remainingAbsValue);
                $remainingAbsValue = abs($quoteDetails->round_off);
                $remaining_value = sprintf("%.2f", $remainingAbsValue);
                if ($quoteDetails->round_off >= 0.00) {
                    $roundOffValueSymbol = "+";
                } else {
                    $roundOffValueSymbol = "-";
                }

                // $grandTotalRoundOff = round($quoteDetails->grand_total);
                // $grand_total = sprintf("%.2f", $grandTotalRoundOff);

                $quoteOrderDetails = $this->bulkOrderQuoteDetailsPdf($quoteDetails->bulk_order_quote_id);

                $termsAndCOnditionDetails = $this->termsAndCOnditionDetails($quoteDetails->bulk_order_quote_id);

                $company_details = CompanyInfo::first();

                $quoteFileName = $quoteDetails->quote_code . "-" . date('d-m-Y') . ".pdf";

                $path = public_path() . "/quotesendemail";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "quotesendemail_" . time() . '.pdf';
                $location = public_path() . '/quotesendemail/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/quotesendemail', $quoteDetails)->with('quoteOrderDetails', $quoteOrderDetails)->with('termsAndCOnditionDetails', $termsAndCOnditionDetails)->with('quoteDate', $quoteDate)->with('quote_code', $quoteDetails->quote_code)->with('company_details', $company_details)->with('customerType', $customerType)->with('company_name', $quoteDetails->company_name)->with('contact_person_name', $quoteDetails->contact_person_name)->with('mobile_no', $quoteDetails->mobile_no)
                    ->with('alternative_mobile_no', $quoteDetails->alternative_mobile_no)->with('email', $quoteDetails->email)->with('address', $quoteDetails->address)->with('state_name', $quoteDetails->state_name)->with('district_name', $quoteDetails->district_name)->with('billing_customer_first_name', $quoteDetails->billing_customer_first_name)->with('billing_mobile_number', $quoteDetails->billing_mobile_number)->with('billing_alt_mobile_number', $quoteDetails->billing_alt_mobile_number)->with('billing_email', $quoteDetails->billing_email)->with('billing_gst_no', $quoteDetails->billing_gst_no)->with('billing_pincode', $quoteDetails->billing_pincode)->with('billing_address_1', $quoteDetails->billing_address_1)->with('billing_address_2', $quoteDetails->billing_address_2)->with('billing_landmark', $quoteDetails->billing_landmark)->with('billing_city_name', $quoteDetails->billing_city_name)->with('billing_state_name', $quoteDetails->billing_state_name)->with('sub_total', $quoteDetails->sub_total)->with('delivery_charge', $quoteDetails->delivery_charge)->with('grand_total', $quoteDetails->grand_total)->with('req', $request)->with('remaining_value', $remaining_value)->with('roundOffValueSymbol', $roundOffValueSymbol)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                // Send email with attachment
                // Mail::send([], [], function ($message) use ($location, $final) {
                //     $message->to('kamesh@technogenesis.in')->subject('Your Subject');
                //     $message->attach($location, [
                //         'as' => 'quotesendemail.pdf',
                //         'mime' => 'application/pdf',
                //     ]);
                // });


                if ($quoteDetails->email != null) {
                    $mail_data = [
                        'email' => $quoteDetails->email,
                        'order_id' => $quoteDetails->order_code,
                        'contact_person_name' => $quoteDetails->contact_person_name,
                        'quote_code' => $getquotecode->quote_code,
                        'enquiry_code' => $getenquiryIdDetails->enquiry_code,
                        'quantity' => $totalQuantity,
                    ];
                    Mail::send('mail.quotesendemail', $mail_data, function ($message) use ($mail_data, $location, $quoteFileName) {
                        $message->to($mail_data['email'])
                            ->subject('Quote Created Successfully')
                            ->attach($location, [
                                'as' => $quoteFileName,
                                'mime' => 'application/pdf',
                            ]);
                    });
                    return response()->json(
                        [
                            'keyword' => 'success',
                            'message' => __('Email sent successfully'),
                            'data' => [],
                        ]
                    );
                } else {
                    return response()->json(
                        [
                            'keyword' => 'failure',
                            'message' => __('Email not found'),
                            'data' => [],
                        ]
                    );
                }
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No Data Found'),
                        'data' => []
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('** end the quote list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteSendEmailPdfDownload(Request $request, $quoteId)
    {
        try {
            Log::channel("quote")->info('** started the quote list method **');
            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.customer_type', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.alternative_mobile_no', 'bulk_order_enquiry.service_id', 'bulk_order_enquiry.address', 'bulk_order_enquiry.message', 'bulk_order_enquiry.state_id', 'bulk_order_enquiry.district_id', 'state.state_name', 'district.district_name', 's.state_name as billing_state_name', 'd.district_name as billing_city_name')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')
                ->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->leftjoin('state as s', 's.state_id', '=', 'bulk_order_quote.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'bulk_order_quote.billing_city_id')->where('bulk_order_quote.bulk_order_quote_id', $quoteId)->first();

            if (!empty($quoteDetails)) {

                $quoteDate = date('d-m-Y', strtotime($quoteDetails->quote_date)) ?? "-";

                if ($quoteDetails->customer_type == 1) {
                    $customerType = "Individual";
                } else if ($quoteDetails->customer_type == 2) {
                    $customerType = "Company";
                }

                // $totalAmount = $quoteDetails->grand_total;

                // $remaining_value = 0;
                // $roundOffValueSymbol = "";

                // $rounded_value = round($totalAmount);
                // $remainingValue = ($totalAmount) - $rounded_value;
                // $remainingAbsValue = abs($remainingValue);
                // $remaining_value = sprintf("%.2f", $remainingAbsValue);
                $remainingAbsValue = abs($quoteDetails->round_off);
                $remaining_value = sprintf("%.2f", $remainingAbsValue);
                if ($quoteDetails->round_off >= 0.00) {
                    $roundOffValueSymbol = "+";
                } else {
                    $roundOffValueSymbol = "-";
                }

                // $grandTotalRoundOff = round($quoteDetails->grand_total);
                // $grand_total = sprintf("%.2f", $grandTotalRoundOff);

                $quoteOrderDetails = $this->bulkOrderQuoteDetailsPdf($quoteDetails->bulk_order_quote_id);

                $termsAndCOnditionDetails = $this->termsAndCOnditionDetails($quoteDetails->bulk_order_quote_id);

                $company_details = CompanyInfo::first();

                $path = public_path() . "/quotesendemail";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "quotesendemail_" . time() . '.pdf';
                $location = public_path() . '/quotesendemail/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/quotesendemail', $quoteDetails)->with('quoteOrderDetails', $quoteOrderDetails)->with('termsAndCOnditionDetails', $termsAndCOnditionDetails)->with('quoteDate', $quoteDate)->with('quote_code', $quoteDetails->quote_code)->with('company_details', $company_details)->with('customerType', $customerType)->with('company_name', $quoteDetails->company_name)->with('contact_person_name', $quoteDetails->contact_person_name)->with('mobile_no', $quoteDetails->mobile_no)
                    ->with('alternative_mobile_no', $quoteDetails->alternative_mobile_no)->with('email', $quoteDetails->email)->with('address', $quoteDetails->address)->with('state_name', $quoteDetails->state_name)->with('district_name', $quoteDetails->district_name)->with('billing_customer_first_name', $quoteDetails->billing_customer_first_name)->with('billing_mobile_number', $quoteDetails->billing_mobile_number)->with('billing_alt_mobile_number', $quoteDetails->billing_alt_mobile_number)->with('billing_email', $quoteDetails->billing_email)->with('billing_gst_no', $quoteDetails->billing_gst_no)->with('billing_pincode', $quoteDetails->billing_pincode)->with('billing_address_1', $quoteDetails->billing_address_1)->with('billing_address_2', $quoteDetails->billing_address_2)->with('billing_landmark', $quoteDetails->billing_landmark)->with('billing_city_name', $quoteDetails->billing_city_name)->with('billing_state_name', $quoteDetails->billing_state_name)->with('sub_total', $quoteDetails->sub_total)->with('delivery_charge', $quoteDetails->delivery_charge)->with('grand_total', $quoteDetails->grand_total)->with('remaining_value', $remaining_value)->with('roundOffValueSymbol', $roundOffValueSymbol)->with('req', $request)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                return response()->download($location, "quotesendemail.pdf");
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No Data Found'),
                        'data' => []
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("quote")->error($exception);
            Log::channel("quote")->error('** end the quote list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function quoteReasonHistory($quoteId)
    {
        $quoteReasonHistoryDetails = QuoteReasonHistory::where('bulk_order_quote_id', $quoteId)->orderBy('quote_reason_history_id', 'desc')->get();

        $quoteHistoryAry = [];
        if (!empty($quoteReasonHistoryDetails)) {
            foreach ($quoteReasonHistoryDetails as $quoteReasonHistoryDetail) {
                $ary = [];
                $ary['quote_reason_history_id'] = $quoteReasonHistoryDetail['quote_reason_history_id'];
                $ary['bulk_order_quote_id'] = $quoteReasonHistoryDetail['bulk_order_quote_id'];
                $ary['acl_user_id'] = $quoteReasonHistoryDetail['acl_user_id'];
                $ary['employee_id'] = $quoteReasonHistoryDetail['employee_id'];
                if ($quoteReasonHistoryDetail['acl_user_id'] != '') {
                    $getAdminUserName = UserModel::where('acl_user_id', $quoteReasonHistoryDetail['acl_user_id'])->first();
                    $ary['created_by_name'] = $getAdminUserName->name;
                    $ary['created_by_email'] = $getAdminUserName->email;
                }
                if ($quoteReasonHistoryDetail['employee_id'] != '') {
                    $getEmployeeName = Employee::where('employee_id', $quoteReasonHistoryDetail['employee_id'])->first();
                    $ary['created_by_name'] = $getEmployeeName->employee_name;
                    $ary['created_by_email'] = $getEmployeeName->email;
                }
                $ary['notes'] = $quoteReasonHistoryDetail['notes'];
                $ary['created_on'] = $quoteReasonHistoryDetail['created_on'];
                $quoteHistoryAry[] = $ary;
            }
        }
        return $quoteHistoryAry;
    }
}
