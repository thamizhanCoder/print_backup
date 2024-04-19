<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group([
        'prefix' => 'V1/AP',
        'namespace' => 'App\Http\Controllers\API\V1\AP',
], function ($router) {

        Route::get('revokeCron', 'CronController@revokeCron');

        Route::post('emp/create', 'TestEmployeeContoller@employee_create');
        Route::post('emp/update', 'TestEmployeeContoller@employee_update');
        Route::get('emp/list', 'TestEmployeeContoller@employee_list');
        Route::get('empview/{id}', 'TestEmployeeContoller@employee_view');
        Route::post('emp/status', 'TestEmployeeContoller@employee_status');
        Route::post('emp/delete', 'TestEmployeeContoller@employee_delete');
        Route::get('dep/getcall', 'TestEmployeeContoller@department_getcall');
        Route::get('emp/Excel', 'TestEmployeeContoller@employee_Excel');

        Route::post('upload/file', 'FileUploadController@upload');
        Route::post('webhook/paytm', 'PgLinkController@handlePaytmWebhook');

        //user login
        Route::post('login', 'LoginController@login');
        Route::post('forget', 'LoginController@forget');
        Route::post('reset', 'LoginController@reset');
        Route::post('logoutActivityLog', 'LoginController@logoutActivityLog');

        Route::get('getQrCode', 'QRcodeController@getQrCode');

        Route::middleware(['roleauth'])->group(function () {

                //Location
                Route::get('state', 'LocationController@getState');
                Route::get('country', 'LocationController@country');
                Route::get('city', 'LocationController@getCity');

                //Route::post('upload/file', 'FileUploadController@upload');
                Route::get('generatePaytmLink/{orderId}', 'PgLinkController@generatePaytmLink');
                Route::get('SendPgLinkEmail/{orderId}', 'PgLinkController@SendPgLinkEmail');

                Route::post('userupdateFcmToken', 'LoginController@userupdateFcmToken');

                //Activity Log
                Route::get('logactivity/list', 'LogActivityContoller@logactivity_list');
                Route::get('logactivitytype/list', 'LogActivityContoller@logactivitytype_list');
                //Category
                Route::get('category/list', 'CategoryController@category_list');
                Route::get('category/view/{id}', 'CategoryController@category_view');
                Route::get('servicename/getcall', 'CategoryController@servicename_getcall');

                //VariantType
                Route::get('varianttype/view/{id}', 'VariantTypeController@variant_type_view');
                Route::get('varianttype/list', 'VariantTypeController@variant_type_list');

                //Expecteddeliverydays
                Route::get('expdays/list', 'ExpecteddeliverydaysController@exp_days_list');

                //ShippedVendor
                Route::get('shippedVendorDetails/list', 'ShippedVendorDetailsController@shippedVendorDetails_list');

                //Rating
                Route::get('product/name', 'RatingReviewController@product_name');
                Route::get('ratingfilter', 'RatingReviewController@rating');
                Route::get('rating/list', 'RatingReviewController@rating_list');

                //Department
                Route::get('department/list', 'DepartmentController@dept_list');
                Route::get('departmentview/{id}', 'DepartmentController@dept_view');

                //TaskDuration
                Route::get('revertstatus/get', 'TaskDurationController@revertstatus_getcall');
                Route::get('taskduration/list', 'TaskDurationController@task_duration_list');

                //Termsandconditions
                Route::post('terms/update', 'TermsAndConditionController@update');
                Route::get('terms/view/{id}', 'TermsAndConditionController@view');

                //Photoprintsettings
                Route::get('photoprint/list', 'PhotoprintsettingsController@photoprintsettings_list');
                Route::get('photoprintview/{id}', 'PhotoprintsettingsController@photoprintsettings_view');

                //GstPercentage
                Route::get('gstpercentage/list', 'GstPercentageController@gstpercentage_list');
                Route::get('gstpercentageview/{id}', 'GstPercentageController@gstpercentage_view');

                // other district
                Route::get('otherdistrict/list', 'OtherDistrictController@otherdistrict_list');

                //Role
                Route::get('role/list', 'RoleController@role_list');
                Route::get('role/view/{id}', 'RoleController@role_view');

                //User
                Route::get('user/list', 'UserController@user_list');
                Route::get('user/view/{id}', 'UserController@user_view');

                //Cmsbanner
                Route::post('OrderUpdateBannerItems', 'CmsbannerController@OrderUpdateBannerItems');
                Route::get('cmsBanner/list', 'CmsbannerController@cmsBanner_list');
                Route::get('cmsBanner/view/{id}', 'CmsbannerController@cmsBanner_view');

                //Cmsvideo
                Route::get('cmsvideo/view/{id}', 'CmsVideoController@cmsvideo_view');
                Route::get('cmsvideo/list', 'CmsVideoController@cmsvideo_list');

                //Cmsgreetings
                Route::get('cmsGreet/list', 'CmsgreetingsController@cmsGreet_list');
                Route::get('cmsGreet/view/{id}', 'CmsgreetingsController@cmsGreet_view');
                Route::post('cmsGreet/status', 'CmsgreetingsController@cmsGreet_status');

                //notification
                Route::get('notification/list', 'NotificationController@list');
                Route::post('readNotification', 'NotificationController@update_notification');

                //RolePermission
                Route::post('rolepermission/update', 'RolePermissionController@role_permissionupdate');
                Route::get('rolepermission/list', 'RolePermissionController@role_permissionlist');

                //Delivery charge
                Route::post('DeliveryChargeUpdate', 'DeliveryChargeController@DeliveryChargeUpdate');
                Route::get('getDeliveryCharge', 'DeliveryChargeController@getDeliveryCharge');

                //productget calls
                Route::get('photoprint/getcall', 'ProductCatalogueGetCallController@photoprint_getcall');
                Route::get('gst/getcall', 'ProductCatalogueGetCallController@gst_getcall');
                Route::get('servicetype/getcall', 'ProductCatalogueGetCallController@servicetype_getcall');
                Route::get('productname/getcall/{id}', 'ProductCatalogueGetCallController@productname_getcall');
                Route::get('variant/getall', 'ProductCatalogueGetCallController@variant_getall');
                Route::get('categoryGetAll', 'ProductCatalogueGetCallController@categorygetcall');
                Route::post('productVariantDelete', 'ProductCatalogueGetCallController@productVariantDelete');

                Route::middleware(['permauth'])->group(function () {

                        Route::post('changepassword', 'LoginController@changepassword');
                        Route::get('view/{id}', 'LoginController@user_viewprofile');

                        //Delivery Management
                        Route::post('deliveryManagement/update', 'DeliveryManagementController@DeliveryManagementUpdate');
                        Route::get('deliveryManagement/view/{id}', 'DeliveryManagementController@deliveryManagement_view');

                        //Enquiry
                        Route::post('enquiry/create', 'EnquiryController@create_enquiry');
                        Route::post('enquiry/update', 'EnquiryController@update_enquiry');
                        Route::get('enquiry/list', 'EnquiryController@enquiry_list');
                        Route::get('enquiry/list/excel', 'EnquiryController@enquiry_list_excel');
                        Route::post('enquiry/status/update', 'EnquiryController@enquiry_status_update');
                        Route::post('enquiry/assign', 'EnquiryController@enquiry_assign');
                        Route::post('enquiry/revoke', 'EnquiryController@enquiry_revoke');
                        Route::get('enquiry/department/getcall', 'EnquiryController@enquiry_department_getcall');
                        Route::get('enquiry/employee/getcall/{id}', 'EnquiryController@enquiry_employee_getcall');
                        Route::get('enquiry/updatestatus/getcall', 'EnquiryController@enquiry_update_status_getcall');
                        Route::get('enquiry/view/{id}', 'EnquiryController@enquiry_view');
                        Route::get('enquiry/search', 'EnquiryController@searchEnquiry');
                        Route::get('enquiry/searchview/{id}/{type}', 'EnquiryController@enquiry_search_view');
                        Route::get('enquiry/statusUpdate', 'EnquiryController@enquiry_list_update_status_getcall');
                        //Quote
                        Route::get('serviceGetAll', 'QuoteController@serviceGetAll');
                        Route::post('quoteCreate', 'QuoteController@quoteCreate');
                        Route::post('quoteReraisedCreate', 'QuoteController@quoteReraisedCreate');
                        Route::get('serviceBasedProductList/{serviceId}', 'QuoteController@serviceBasedProductList');
                        Route::get('productfilter', 'QuoteController@productfilter');
                        Route::get('termsAndCondition', 'QuoteController@termsAndCondition');
                        Route::get('quoteList', 'QuoteController@quoteList');
                        Route::get('quoteExcel', 'QuoteController@quoteExcel');
                        Route::get('enquiryView/{id}', 'QuoteController@view');
                        Route::get('quoteView/{id}', 'QuoteController@quoteView');
                        Route::post('quoteUpdateStatus', 'QuoteController@quoteUpdateStatus');
                        Route::get('quoteSendEmailPdf/{quoteId}', 'QuoteController@quoteSendEmailPdf');
                        Route::get('quoteCreateSendEmailPdf/{quoteId}', 'QuoteController@quoteCreateSendEmailPdf');
                        Route::get('quoteStatusGetAll', 'QuoteController@quoteStatusGetAll');
                        Route::get('quoteSendEmailPdfDownload/{quoteId}', 'QuoteController@quoteSendEmailPdfDownload');
                        Route::get('quoteReasonHistory/{quoteId}', 'QuoteController@quoteReasonHistory');

                        //Create order
                        Route::post('updateQuoteAttachment', 'CreateOrderController@updateQuoteAttachment');
                        Route::post('bulkOrderCreate', 'CreateOrderController@bulkOrderCreate');
                        Route::get('createOrderview/{quoteId}', 'CreateOrderController@createOrderview');
                        Route::post('bulkOrderUpdate', 'CreateOrderController@bulkOrderUpdate');
                        Route::get('bulkOrderView/{id}', 'CreateOrderController@bulkOrderView');

                        //User
                        Route::post('user/create', 'UserController@user_create');
                        Route::post('user/update', 'UserController@user_update');
                        Route::post('user/status', 'UserController@user_status');
                        Route::post('user/delete', 'UserController@user_delete');

                        //Role
                        Route::post('role/create', 'RoleController@role_create');
                        Route::post('role/update', 'RoleController@role_update');
                        Route::post('role/status', 'RoleController@role_status');
                        Route::post('role/delete', 'RoleController@role_delete');

                        //Shippedvendordetails
                        Route::post('shippedVendorDetails/create', 'ShippedVendorDetailsController@shippedVendorDetails_create');
                        Route::post('shippedVendorDetails/update', 'ShippedVendorDetailsController@shippedVendorDetails_update');
                        Route::post('shippedVendorDetails/status', 'ShippedVendorDetailsController@shippedVendorDetails_status');
                        Route::post('shippedVendorDetails/delete', 'ShippedVendorDetailsController@shippedVendorDetails_delete');

                        //Cmsbanner
                        Route::post('cmsBanner/create', 'CmsbannerController@cmsBanner_create');
                        Route::post('cmsBanner/delete', 'CmsbannerController@cmsBanner_delete');
                        Route::post('cmsBanner/update', 'CmsbannerController@cmsBanner_update');
                        Route::post('cmsBanner/status', 'CmsbannerController@cmsBanner_status');

                        //Cmsgreetings
                        Route::post('cmsGreet/create', 'CmsgreetingsController@cmsGreet_create');
                        Route::post('cmsGreet/update', 'CmsgreetingsController@cmsGreet_update');
                        Route::post('cmsGreet/delete', 'CmsgreetingsController@cmsGreet_delete');

                        //Cmsvideo
                        Route::post('cmsvideo/update', 'CmsVideoController@cmsvideo_update');
                        Route::post('cmsvideo/status', 'CmsVideoController@cmsvideo_status');
                        Route::post('cmsvideo/create', 'CmsVideoController@cmsvideo_create');
                        Route::post('cmsvideo/delete', 'CmsVideoController@cmsvideo_delete');

                        Route::post('employee/create', 'EmployeeController@employee_create');
                        Route::post('employee/update', 'EmployeeController@employee_update');
                        Route::get('employee/list', 'EmployeeController@employee_list');
                        Route::get('employeeview/{id}', 'EmployeeController@employee_view');
                        Route::post('employee/status', 'EmployeeController@employee_status');
                        Route::post('employee/delete', 'EmployeeController@employee_delete');
                        Route::get('department/getcall', 'EmployeeController@department_getcall');
                        Route::get('employee/Excel', 'EmployeeController@employee_Excel');

                        //customer
                        Route::get('customer/list', 'CustomerController@customer_list');
                        Route::post('customer/status', 'CustomerController@customer_status');
                        Route::post('customer/delete', 'CustomerController@customer_delete');
                        Route::get('customerInfo/view/{id}', 'CustomerController@customer_view_info');

                        //department
                        Route::post('department/create', 'DepartmentController@dept_create');
                        Route::post('department/update', 'DepartmentController@dept_update');
                        Route::post('department/status', 'DepartmentController@dept_status');
                        Route::post('department/delete', 'DepartmentController@dept_delete');

                        // task stage
                        Route::post('taskstage/create', 'TaskStageController@taskstage_create');
                        Route::get('taskstage/list', 'TaskStageController@taskstage_list');
                        Route::post('taskstage/update', 'TaskStageController@taskstage_update');
                        Route::get('taskstageview/{id}', 'TaskStageController@taskstage_view');
                        Route::post('taskstage/status', 'TaskStageController@taskstage_status');
                        Route::get('serviceListForTaskStage', 'TaskStageController@serviceListForTaskStage');
                        Route::get('departmentListForTaskStage', 'TaskStageController@departmentListForTaskStage');

                        // task duration
                        Route::post('taskduration/update', 'TaskDurationController@taskduration_update');

                        //expected delivery days
                        Route::post('expdeliverydays/update', 'ExpecteddeliverydaysController@exp_days_update');

                        //photo print setting
                        Route::post('photoprint/create', 'PhotoprintsettingsController@photoprintsettings_create');
                        Route::post('photoprint/update', 'PhotoprintsettingsController@photoprintsettings_update');
                        Route::post('photoprint/status', 'PhotoprintsettingsController@photoprintsettings_delete');
                        //Route::post('photoprint/delete', 'PhotoprintsettingsController@photoprintsettings_delete');

                        //gstpercentage
                        Route::post('gstpercentage/create', 'GstPercentageController@gstpercentage_create');
                        Route::post('gstpercentage/update', 'GstPercentageController@gstpercentage_update');
                        Route::post('gstpercentage/delete', 'GstPercentageController@gstpercentage_delete');

                        //PassportSize Photo
                        Route::post('passportsizephoto/create', 'PassportSizePhotoController@passportsizephoto_create');
                        Route::post('passportsizephoto/update', 'PassportSizePhotoController@passportsizephoto_update');
                        Route::post('passportsizephoto/delete', 'PassportSizePhotoController@passportsizephoto_delete');
                        Route::post('passportsizephoto/status', 'PassportSizePhotoController@passportsizephoto_status');
                        Route::get('passportsizephoto/view/{id}', 'PassportSizePhotoController@passportsizephoto_view');
                        Route::get('passportsizephoto/list', 'PassportSizePhotoController@passportsizephoto_list');
                        Route::get('passportsizephoto/excel', 'PassportSizePhotoController@passportsizephoto_excel');
                        Route::get('passportsizephoto/total', 'PassportSizePhotoController@getpassportsizephoto_totalcount');
                        Route::post('passportsizephotopublish/status', 'PassportSizePhotoController@passportsizephoto_publishstatus');

                        //photoframe
                        Route::post('photoframe/create', 'PhotoFrameController@photoframe_create');
                        Route::post('photoframe/update', 'PhotoFrameController@photoframe_update');
                        Route::get('photoframe/list', 'PhotoFrameController@photoframe_list');
                        Route::get('photoframe/view/{id}', 'PhotoFrameController@photoframe_view');
                        Route::get('photoframe/countSummary', 'PhotoFrameController@countSummaryPhotoframe');
                        Route::post('photoframepublish/status', 'PhotoFrameController@photoframePublish_status');
                        Route::post('photoframe/status', 'PhotoFrameController@photoframe_status');
                        Route::get('photoframe/excel', 'PhotoFrameController@photoframe_excel');

                        //photoframe
                        Route::post('productphotoprint/create', 'PhotoPrintController@photoprint_create');
                        Route::post('productphotoprint/update', 'PhotoPrintController@photoprint_update');
                        Route::get('productphotoprint/list', 'PhotoPrintController@photoprint_list');
                        Route::get('productphotoprint/view/{id}', 'PhotoPrintController@photoprint_view');
                        Route::get('photoprint/excel', 'PhotoPrintController@photoprint_excel');
                        Route::post('photoprintpublish/status', 'PhotoPrintController@photoprintPublish_status');
                        Route::post('productphotoprint/status', 'PhotoPrintController@photoprint_status');
                        Route::get('total/photoprintcount', 'PhotoPrintController@getphotoprint_totalcount');

                        //Variant-Type
                        Route::post('varianttype/create', 'VariantTypeController@variant_type_create');
                        Route::post('varianttype/update', 'VariantTypeController@variant_type_update');
                        Route::post('varianttype/delete', 'VariantTypeController@variant_type_delete');

                        //Coupon Code
                        Route::post('couponcode/create', 'CouponCodeController@coupon_code_create');
                        Route::post('couponcode/update', 'CouponCodeController@coupon_code_update');
                        Route::get('couponcode/view/{id}', 'CouponCodeController@coupon_code_view');
                        Route::get('couponcode/list', 'CouponCodeController@coupon_code_list');
                        Route::post('couponcode/delete', 'CouponCodeController@coupon_code_delete');
                        Route::post('couponcode/status', 'CouponCodeController@coupon_code_status');

                        // tickets rajesh kannan
                        Route::get('ticket/list', 'TicketController@ticket_list');
                        Route::get('ticket/view/{id}', 'TicketController@ticket_view');
                        Route::get('ticketinbox/view/{id}', 'TicketController@ticketinboxview');
                        Route::post('ticketinbox/create', 'TicketController@ticketinbox_create');
                        // Route::get('ticket/view/{id}', 'TicketController@view');
                        Route::get('singleticket/list/{id}', 'TicketController@tickets_list');
                        Route::post('ticket/create', 'TicketController@ticket_create');
                        Route::post('replystatus/create', 'TicketController@replystatus_create');
                        Route::post('ticket/status/admin', 'TicketController@ticket_status_update');

                        //Category
                        Route::post('category/create', 'CategoryController@category_create');
                        Route::post('category/update', 'CategoryController@category_update');
                        Route::post('category/delete', 'CategoryController@category_delete');

                        //Admin contest by Muthuselvam
                        Route::post('contest/create', 'ContestController@contest_create');
                        Route::post('contest/update', 'ContestController@contest_update');
                        Route::get('contest/list', 'ContestController@contest_list');
                        Route::get('contest/view/{id}', 'ContestController@contest_view');
                        Route::post('contest/status', 'ContestController@contest_update_status');
                        Route::post('contest/delete', 'ContestController@contest_delete');
                        Route::get('contest/participant/view/{c_id}/{id}', 'ContestController@contest_participant_details_view');
                        Route::get('contest/participant/list/{contest_id}', 'ContestController@contest_participant_view');

                        //Payment Transaction by Muthuselvam
                        Route::get('transaction/list', 'TransactionController@transaction_list');

                        // other district
                        Route::post('otherdistrict/update', 'OtherDistrictController@otherdistrict_update');

                        //Rating
                        Route::post('rating/status', 'RatingReviewController@rating_status');

                        //personalized
                        Route::get('countSummaryPersonalized', 'PersonalizedProductController@countSummaryPersonalized');
                        Route::post('personalizedCreate', 'PersonalizedProductController@personalizedCreate');
                        Route::get('personalizedView/{id}', 'PersonalizedProductController@personalizedView');
                        Route::post('personalizedUpdate', 'PersonalizedProductController@personalizedUpdate');
                        Route::get('personalizedList', 'PersonalizedProductController@personalizedList');
                        Route::post('personlaizedPublishStatus', 'PersonalizedProductController@personlaizedPublishStatus');
                        Route::post('personalizedStatus', 'PersonalizedProductController@personalizedStatus');
                        Route::get('personalizedExcel', 'PersonalizedProductController@personalizedExcel');

                        //E-Commerce Products kamesh 29-09-2022
                        Route::post('ecommerce/create', 'EcommerceProductController@ecommerce_create');
                        Route::post('ecommerce/update', 'EcommerceProductController@ecommerce_update');
                        Route::get('ecommerce/list', 'EcommerceProductController@ecommerce_list');
                        Route::get('ecommerce/view/{id}', 'EcommerceProductController@ecommerce_view');
                        Route::post('ecommercepublish/status', 'EcommerceProductController@ecommercePublish_status');
                        Route::post('ecommerce/status', 'EcommerceProductController@ecommerce_status');
                        Route::get('ecommerce/excel', 'EcommerceProductController@ecommerce_excel');
                        Route::get('ecommerce/countSummary', 'EcommerceProductController@countSummaryEcommerce');

                        //Selfie Album
                        Route::get('countSummaryselfiealbum', 'SelfieAlbumController@countSummaryselfiealbum');
                        Route::post('selfiealbumCreate', 'SelfieAlbumController@selfiealbumCreate');
                        Route::get('selfiealbumView/{id}', 'SelfieAlbumController@selfiealbumView');
                        Route::post('selfiealbumUpdate', 'SelfieAlbumController@selfiealbumUpdate');
                        Route::get('selfiealbumList', 'SelfieAlbumController@selfiealbumList');
                        Route::post('selfiealbumPublishStatus', 'SelfieAlbumController@selfiealbumPublishStatus');
                        Route::post('selfiealbumStatus', 'SelfieAlbumController@selfiealbumStatus');
                        Route::get('selfiealbumExcel', 'SelfieAlbumController@selfiealbumExcel');

                        //Order
                        //Order
                        Route::get('waitingpayment/list', 'OrderController@waitingpayment_list');
                        // Route::get('waitingpayment/view/{id}', 'OrderController@waitingpayment_view');
                        Route::get('waitingcod/list', 'OrderController@waitingcod_list');
                        // Route::get('waitingcod/view/{id}', 'OrderController@waitingcod_view');
                        Route::post('orderStatusUpdate', 'OrderController@orderStatusUpdate');
                        Route::post('codorderStatusUpdate', 'OrderController@codorderStatusUpdate');
                        Route::post('cancelOrder', 'OrderController@cancelOrder');
                        Route::get('waitingdispatch/list', 'OrderController@waitingdispatch_list');
                        Route::get('waitingdelivery/list', 'OrderController@waitingdelivery_list');
                        Route::get('orderitem/list/{id}', 'OrderController@Order_Item_List');
                        Route::get('trackorder/orderList/{id}', 'OrderController@track_order_view');
                        Route::get('trackorder/orderItemView/{id}/{orderItemId}', 'OrderController@track_order_item_view');
                        Route::get('orderItemView/{ordId}', 'OrderController@orderItemView');
                        Route::get('product/list/{id}', 'OrderController@Product_list');
                        Route::get('cancelled/product/list/{id}', 'OrderController@cancelled_Product_list');
                        Route::get('delivered/product/list/{id}', 'OrderController@delivery_Product_list');
                        Route::get('cancelledList', 'OrderController@cancelledList');
                        Route::get('trackOrder', 'OrderController@trackOrder');
                        Route::post('updateDeliveredStatus/{type}', 'OrderController@updateDeliveredStatus');
                        Route::post('billgenerate', 'OrderController@BillGenerate');
                        Route::get('billgenerate/invoice', 'OrderController@waitingdispatch_invoice');
                        Route::get('billgenerate/invoice/pdf', 'OrderController@waitingdispatch_invoice_pdf');
                        Route::post('orderdispatch/update', 'OrderController@orderDispatch_update');
                        Route::get('courier/name/getcall/{bill_no}', 'OrderController@courier_name_getcall');
                        Route::get('courier/name/getall', 'OrderController@courier_name_getall');
                        Route::get('item/invoice/view/{order_item_id}', 'OrderController@item_invoice_view');
                        Route::get('couriernameGetAll', 'OrderController@couriernameGetAll');
                        Route::get('courierurlGetAll/{id}', 'OrderController@courierurlGetAll');
                        //Task Manager
                        Route::post('task/create', 'TaskManagerController@task_create');
                        Route::post('task/update', 'TaskManagerController@task_update');
                        Route::get('task/view/{id}', 'TaskManagerController@task_view');
                        Route::post('task/delete', 'TaskManagerController@task_delete');
                        Route::post('statusChange', 'TaskManagerController@statusChange');
                        Route::post('productionToOperationDelete', 'TaskManagerController@productionToOperationDelete');
                        Route::post('qcstatusChange', 'TaskManagerController@qcstatusChange');
                        Route::get('operationList', 'TaskManagerController@operationList');
                        Route::get('productionList', 'TaskManagerController@productionList');
                        Route::get('qcList', 'TaskManagerController@qcList');
                        Route::get('deliveryList', 'TaskManagerController@deliveryList');
                        Route::get('serviceBasedDepartmentGetAll/{id}', 'TaskManagerController@serviceBasedDepartmentGetAll');
                        Route::get('employeeGetAll/{dep_id}/{type}', 'TaskManagerController@employeeGetAll');
                        Route::post('assign', 'TaskManagerController@assign');
                        Route::post('revoke', 'TaskManagerController@revoke');
                        Route::post('custom/revoke', 'TaskManagerController@custom_revoke');
                        Route::post('qcApprovedRejected', 'TaskManagerController@qcApprovedRejected');
                        Route::post('onlyQcApprovedRejected', 'TaskManagerController@onlyQcApprovedRejected');
                        Route::post('onlyTaskQcApprovedRejected', 'TaskManagerController@onlyTaskQcApprovedRejected');
                        Route::get('orderItemHistoryView/{id}', 'TaskManagerController@orderItemHistoryView');
                        Route::get('myorderItem/view/{id}', 'TaskManagerController@myorderItem_view');
                        Route::get('qcDetails/{id}', 'TaskManagerController@qcDetails');
                        Route::get('previewDetails/{id}', 'TaskManagerController@previewDetails');
                        Route::get('downloadSinglefile', 'TaskManagerController@downloadSingle_file');
                        Route::get('downloadZip', 'TaskManagerController@downloadZip');
                        Route::get('deliveryToDispatch', 'TaskManagerController@deliveryToDispatch');
                        Route::get('countSummaryTaskManager', 'TaskManagerController@countSummaryTaskManager');

                        //Admin Chat View
                        Route::get('admin/chat/view', 'AdminChatViewController@chatConversation');
                        Route::get('admin/get/userId/{orderitemid}/{employee_id}', 'AdminChatViewController@getfromtoUserId');
                        //EmployeeCustomerChat
                        Route::get('admin/chatlist/view', 'EmployeeCustomerChatController@employee_chat_list');
                        Route::get('admin/chatlist/conversation', 'EmployeeCustomerChatController@empcuschatConversation');
                        //Employee task
                        Route::get('emp/downloadSinglefile', 'EmployeeViewTaskController@downloadSingle_file');
                        Route::get('emp/downloadZip', 'EmployeeViewTaskController@downloadZip');
                        //Task manager
                        Route::get('emp/countSummaryTaskManager', 'EmployeeViewTaskController@countSummaryTaskManager');
                        Route::get('emp/todoList', 'EmployeeViewTaskController@todoList');
                        Route::get('emp/inprogressList', 'EmployeeViewTaskController@inprogressList');
                        Route::get('emp/previewList', 'EmployeeViewTaskController@previewList');
                        Route::get('emp/completedList', 'EmployeeViewTaskController@completedList');
                        Route::post('emp/statusChange', 'EmployeeViewTaskController@statusChange');
                        Route::get('emp/orderItemHistoryView/{id}', 'EmployeeViewTaskController@orderItemHistoryView');
                        Route::post('emp/approvedRejectedStatus', 'EmployeeViewTaskController@approvedRejectedStatus');
                        Route::post('emp/moveToQc', 'EmployeeViewTaskController@moveToQc');
                        Route::post('emp/moveToTaskQc', 'EmployeeViewTaskController@moveToTaskQc');
                        Route::post('emp/moveToPreview', 'EmployeeViewTaskController@moveToPreview');
                        Route::post('emp/moveToCompleted', 'EmployeeViewTaskController@moveToCompleted');
                        Route::post('emp/onlyQc', 'EmployeeViewTaskController@onlyQc');
                        Route::get('emp/task/view/{id}', 'EmployeeViewTaskController@task_view');
                        Route::get('emp/qcDetails/{id}', 'EmployeeViewTaskController@qcDetails');
                        Route::get('emp/previewDetails/{id}', 'EmployeeViewTaskController@previewDetails');

                        //Customer Report by Muthuselvam
                        Route::get('customer/report/list', 'CustomerReportController@Customer_report_list');
                        Route::get('customer/report/list/excel', 'CustomerReportController@customer_list_report_Excel');

                        //Employee Report by Muthuselvam
                        Route::get('employee/report/list', 'EmployeeReportController@employee_report_list');
                        Route::get('employee/report/list/excel', 'EmployeeReportController@employee_list_report_Excel');

                        //payment transaction report
                        Route::get('payTransReport/list', 'PaymentTransactionReportController@paymentTranslist');
                        Route::get('payTransReport/excel', 'PaymentTransactionReportController@paymentTransExcel');

                        //Order Report by Hussain
                        Route::get('order/report/list', 'OrderReportController@Order_report_list');
                        Route::get('order/report/list/excel', 'OrderReportController@Order_list_report_Excel');

                        Route::get('taskReportList', 'TaskReportController@taskReportList');
                        Route::get('taskReportListPdf', 'TaskReportController@taskReportListPdf');
                        Route::get('assignedEmployeeNameList', 'TaskReportController@assignedEmployeeNameList');

                        //Refud by muthuselvam
                        Route::get('refund/list', 'RefundController@refund_list');
                        Route::post('refund/status', 'RefundController@refunded_order');

                        //Refund Report By Muthuselvam
                        Route::get('refund/report/list', 'RefundReportController@refund_report_list');
                        Route::get('refund/report/excel', 'RefundReportController@refund_report_Excel');
                        Route::get('reason/getcall', 'RefundReportController@refund_reason');

                        //Rating Review Report By Muthuselvam
                        Route::get('rating/review/report/list', 'RatingReviewReportController@rating_report_list');
                        Route::get('rating/review/report/excel', 'RatingReviewReportController@rating_list_report_Excel');
                        Route::get('rating/product/getcall', 'RatingReviewReportController@product_getcall_rating');

                        //Product Report By Muthuselvam
                        Route::get('product/report/list', 'ProductReportController@product_report_list');
                        Route::get('product/report/list/excel', 'ProductReportController@product_list_report_Excel');

                        //Gst Report By Muthuselvam 18-01-2023
                        Route::get('gst/report/list', 'GstReportController@gst_report_list');
                        Route::get('gst/report/list/excel', 'GstReportController@gst_report_list_Excel');

                        //Stock Report By Rajesh Kannan.N dated on 09/01/2023.
                        Route::get('stockreport/excel', 'StockReportController@stockreport_excel');
                        Route::get('stockreport/list', 'StockReportController@stockreport_list');
                        Route::get('stockreport/pdf', 'StockReportController@stockreport_pdf');

                        //Ticket Report By Rajesh Kannan.N dated on 12/01/2023.
                        Route::get('ticketreport/excel', 'TicketReportController@ticketreport_excel');
                        Route::get('ticketreport/list', 'TicketReportController@ticketreport_list');

                        //Bill Management
                        Route::get('bill/management', 'BillingManagementController@billing_management_list');
                        Route::get('bill/management/view/{bill_no}', 'BillingManagementController@Billing_Management_View');

                        //Dashboard
                        Route::get('recentorders/list', 'DashBoardController@recentorders_list');
                        Route::get('overview/counts', 'DashBoardController@overview');
                        Route::get('customer/counts', 'DashBoardController@customer_count');
                        Route::get('revenue/analytics/{type}', 'DashBoardController@revenue_analytics');
                        Route::get('order/analytics/{date}', 'DashBoardController@order_analytics');
                        Route::get('recentfeedback/list', 'DashBoardController@recentfeedback_list');
                        //Route::post('replystatus/create', 'DashBoardController@replystatus_create');
                        Route::get('order/statistics', 'DashBoardController@order_statistics');
                        Route::get('topproducts/sale/{date}', 'DashBoardController@Product_sale_count');
                        Route::get('sales/location/{state_id}/{top}/{days}', 'DashBoardController@sales_location');
                        Route::get('new/customer/{state_id}/{top}/{days}', 'DashBoardController@new_customer_district_wise');
                        Route::get('top/products/seen/{type}', 'DashBoardController@topProductSeen');
                        Route::get('visitorsByWeekMonth/analytics/{type}', 'DashBoardController@visitorsByWeekMonth');
                        Route::get('visitor/analytics/{type}', 'DashBoardController@visitor_analytics');
                        Route::get('new/customer/{state_id}/{top}/{days}', 'DashBoardController@new_customer');
                        Route::get('visitor/history/{type}', 'DashBoardController@Most_Visitors_Time');
                        Route::post('create/visitor', 'DashBoardController@visit_time_history');
                        // Management Communication  rajesh kannan
                        Route::get('communication/list', 'ManagementCommunicationController@communication_list');
                        Route::get('communication/view', 'ManagementCommunicationController@communication_view');
                        Route::post('admin/reply/create', 'ManagementCommunicationController@adminreply_create');
                        Route::get('communicationdownloadZip', 'ManagementCommunicationController@downloadZip');
                        Route::get('admin/stageHistoryDetails/{id}', 'ManagementCommunicationController@stageHistoryDetails');
                        Route::get('admin/customtaskview/{id}', 'ManagementCommunicationController@task_view');

                        // //Notification List
                        // Route::get('list', 'NotificationController@list');
                        // Route::post('read/notification', 'NotificationController@update_notification');


                });
        });
});

Route::group([
        'prefix' => 'V1/EP',
        'namespace' => 'App\Http\Controllers\API\V1\EP',
], function ($router) {

        Route::post('login', 'EmployeeLoginController@login');
        Route::post('forget', 'EmployeeLoginController@forget');
        Route::post('reset', 'EmployeeLoginController@reset');
        Route::post('changepassword', 'EmployeeLoginController@changepassword');

        Route::post('employeeFcmTokenUpdate', 'EmployeeLoginController@employeeFcmTokenUpdate');

        //file upload
        Route::post('upload/file', 'FileUploadController@upload');
        Route::post('removeFile', 'FileUploadController@removeFile');
        Route::post('removeFileManagement', 'FileUploadController@removeFileManagement');

        Route::get('downloadSinglefile', 'TaskManagerController@downloadSingle_file');
        Route::get('downloadZip', 'TaskManagerController@downloadZip');

        //Task manager
        Route::get('countSummaryTaskManager', 'TaskManagerController@countSummaryTaskManager');
        Route::get('todoList', 'TaskManagerController@todoList');
        Route::get('inprogressList', 'TaskManagerController@inprogressList');
        Route::get('previewList', 'TaskManagerController@previewList');
        Route::get('completedList', 'TaskManagerController@completedList');
        Route::post('statusChange', 'TaskManagerController@statusChange');

        Route::get('orderItemHistoryView/{id}', 'TaskManagerController@orderItemHistoryView');
        // Route::get('photoFrameimageVerificationList/{ordId}', 'TaskManagerController@photoFrameimageVerificationList');
        // Route::get('personalizedimageVerificationList/{ordId}', 'TaskManagerController@personalizedimageVerificationList');
        // Route::get('selfieimageVerificationList/{ordId}', 'TaskManagerController@selfieimageVerificationList');
        // Route::post('previewAttachedImageUpload', 'TaskManagerController@previewAttachedImageUpload');
        Route::post('approvedRejectedStatus', 'TaskManagerController@approvedRejectedStatus');
        Route::post('moveToQc', 'TaskManagerController@moveToQc');
        Route::post('moveToTaskQc', 'TaskManagerController@moveToTaskQc');
        Route::post('moveToPreview', 'TaskManagerController@moveToPreview');
        Route::post('moveToCompleted', 'TaskManagerController@moveToCompleted');
        Route::post('onlyQc', 'TaskManagerController@onlyQc');
        Route::get('task/view/{id}', 'TaskManagerController@task_view');
        Route::get('qcDetails/{id}', 'TaskManagerController@qcDetails');
        Route::get('previewDetails/{id}', 'TaskManagerController@previewDetails');
        Route::post('imageUploadVerificationByEmployee', 'TaskManagerController@attachedImageUploadVerificationByEmployee');
        
        //Customer preview approve reject
        Route::post('previewApprovedRejectedStatus', 'TaskManagerController@previewApprovedRejectedStatus');

        Route::get('employee/chat/list', 'EmployeeChatController@employee_chat_list');
        Route::get('chat/conversation', 'EmployeeChatController@chatConversation');
        Route::get('get/userId/{orderitemid}', 'EmployeeChatController@getfromtoUserId');

        //Employee portal dashboard
        Route::get('chat/conversation/list', 'DashboardController@chat_conversation_list');
        Route::get('management/communication/chat/list', 'DashboardController@management_communication');
        Route::get('communicationChat/view', 'DashboardController@conversationhistory_view_dashboard');
        Route::get('currentMonth/task', 'DashboardController@current_month_task');
        Route::get('taskAnalysis', 'DashboardController@task_analysis');
        Route::post('chat/message/update', 'DashboardController@chat_message_read_update');

        //Employee Chat Controller worked by Rajesh kannan.N 02/03/2023.......
        Route::post('empchatwithadminbox/create', 'EmployeeChatController@empchatwithadminbox_create');
        Route::post('employeereply/create', 'EmployeeChatController@employeereply_create');
        Route::get('conversationhistory/view', 'EmployeeChatController@conversationhistory_view');
        Route::get('admincommunication/list', 'EmployeeChatController@admincommunication_list');
        Route::post('adminreply/create', 'EmployeeChatController@adminreply_create');
        Route::get('downloadadminzip/{id}', 'EmployeeChatController@downloadadminZip');
        Route::get('downloadZip', 'EmployeeChatController@downloadZip');

        //notification
        Route::get('notification/list', 'NotificationController@list');
        Route::post('readNotification', 'NotificationController@update_notification');

        //Enquiry
        Route::post('create/enquiry', 'EnquiryController@create_enquiry_employee');
        Route::post('update/enquiry', 'EnquiryController@update_enquiry_employee');
        Route::get('enquiry/list', 'EnquiryController@employee_enquiry_list');
        Route::get('enquiry/list/excel', 'EnquiryController@employee_enquiry_list_excel');
        Route::post('enquiry/update/Status', 'EnquiryController@employee_enquiry_status_update');
        Route::get('enquiry/view/{enqid}', 'EnquiryController@employee_enquiry_view');
        Route::get('enquiry/list/search', 'EnquiryController@EmployeeSearchEnquiry');
        Route::get('enquiry/searchView/{id}/{type}', 'EnquiryController@employee_enquiry_search_view');
        Route::get('enquiry/updateStatus/getCall', 'EnquiryController@employee_enquiry_update_status_getcall');
        Route::get('enquiry/listStatus/getCall', 'EnquiryController@enquiry_list_status_getcall');
        Route::get('state', 'EnquiryController@getState');
        Route::get('city', 'EnquiryController@getCity');
        Route::get('servicename/getcall', 'EnquiryController@servicename_getcall');

        //Quote
        Route::get('serviceGetAll', 'QuoteController@serviceGetAll');
        Route::post('quoteCreate', 'QuoteController@quoteCreate');
        Route::get('serviceBasedProductList/{serviceId}', 'QuoteController@serviceBasedProductList');
        Route::get('productfilter', 'QuoteController@productfilter');
        Route::get('termsAndCondition', 'QuoteController@termsAndCondition');
        Route::get('quoteList', 'QuoteController@quoteList');
        Route::get('quoteExcel', 'QuoteController@quoteExcel');
        Route::get('enquiryView/{id}', 'QuoteController@view');
        Route::get('quoteView/{id}', 'QuoteController@quoteView');
        Route::post('quoteUpdateStatus', 'QuoteController@quoteUpdateStatus');
        Route::get('quoteSendEmailPdf/{quoteId}', 'QuoteController@quoteSendEmailPdf');
        Route::get('quoteCreateSendEmailPdf/{quoteId}', 'QuoteController@quoteCreateSendEmailPdf');
        Route::get('quoteStatusGetAll', 'QuoteController@quoteStatusGetAll');
        Route::get('quoteSendEmailPdfDownload/{quoteId}', 'QuoteController@quoteSendEmailPdfDownload');
        Route::get('quoteReasonHistory/{quoteId}', 'QuoteController@quoteReasonHistory');

        //Create order
        Route::post('updateQuoteAttachment', 'CreateOrderController@updateQuoteAttachment');
        Route::post('bulkOrderCreate', 'CreateOrderController@bulkOrderCreate');
        Route::get('createOrderview/{quoteId}', 'CreateOrderController@createOrderview');
        Route::post('bulkOrderUpdate', 'CreateOrderController@bulkOrderUpdate');
        Route::get('bulkOrderView/{id}', 'CreateOrderController@bulkOrderView');

});

Route::group([
        'prefix' => 'V1/MP',
        'namespace' => 'App\Http\Controllers\API\V1\MP',
], function ($router) {

        Route::get('greatings/list', 'CmsgreetingsController@list');
        //Location
        Route::get('state', 'LocationController@getState');
        Route::get('country', 'LocationController@country');
        Route::get('city', 'LocationController@getCity');
        Route::post('otherDistrict', 'LocationController@otherDistrict');

        //login
        Route::post('login', 'LoginController@login');
        Route::post('otpVerify', 'LoginController@otpVerify');
        Route::post('social/login', 'SocialLoginController@social');
        Route::get('get/apple/email', 'SocialLoginController@CheckAppleEmailExists');
        // changes new mobile app api calls 26-02-2022

        Route::middleware(['roleauth'])->group(function () {
                //Bulk order enquiry
                Route::post('bulkOrderCreate', 'BulkOrderController@bulkOrderCreate');
                Route::get('serviceTypeGetAll', 'BulkOrderController@servicetypegetcall');

                // Update Token
                Route::post('updateTokenForMobile', 'LoginController@updateTokenForMobile');
                Route::post('update/profile', 'LoginController@update');
                Route::get('view/profile', 'LoginController@view');
                //myprofileupdate
                Route::post('update/myprofile', 'MyProfileController@myprofile_update');
                Route::post('customerInfoUpdate', 'MyProfileController@customerInfoUpdate');
                Route::post('deliveryAddressInfoUpdate', 'MyProfileController@deliveryAddressInfoUpdate');
                Route::get('myaddress/view', 'MyProfileController@address_view');
                Route::get('couponCodeList', 'MyProfileController@couponCodeList');
                Route::post('couponCodeApply', 'MyProfileController@couponCodeApply');

                //myorders
                //Route::get('myorder/list', 'MyOrderController@myorder_list');
                //Route::get('myorder/view/{id}', 'MyOrderController@myorder_view');
                //Route::post('replaceImage', 'MyOrderController@replaceImage');
                //Route::post('cancelOrderItem', 'MyOrderController@cancelOrderItem');
                //Route::get('myorderItem/view/{id}', 'MyOrderController@myorderItem_view');

                //myorders
                Route::get('myorder/list', 'MyOrderController@myorder_list');
                Route::get('myorder/view/{id}', 'MyOrderController@myorder_view');
                Route::post('cancelOrderItem', 'MyOrderController@cancelOrderItem');
                Route::post('cancelOrder', 'MyOrderController@cancelOrder');
                Route::get('myorderItem/view/{id}', 'MyOrderController@myorderItem_view');
                Route::post('approvedRejectedStatus', 'MyOrderController@approvedRejectedStatus');
                Route::post('previewApprovedRejectedStatus', 'MyOrderController@previewApprovedRejectedStatus');
                Route::post('replaceImage', 'MyOrderController@replaceImage');
                Route::post('attachedImageUpload', 'MyOrderController@attachedImageUpload');
                Route::get('previewDetails/{id}', 'MyOrderController@previewDetails');
                Route::get('downloadSinglefile', 'MyOrderController@downloadSingle_file');
                Route::get('invoiceView/{id}', 'MyOrderController@invoice_view');
                Route::get('myOrderinvoice/pdf/download', 'MyOrderController@myorderinvoice_pdf_download');

                //file upload
                Route::post('upload/file', 'FileUploadController@upload');
                Route::post('chatUpload', 'FileUploadController@chatUpload');
                Route::post('removeFile', 'FileUploadController@removeFile');

                //GetAll
                Route::get('categorygetall', 'FillterGetAllController@categorygetall');

                //printapp ticket create on 24-08-2022
                Route::post('ticket/create', 'TicketController@create');
                Route::post('ticket/update', 'TicketController@update');

                //Mobile Contest- Muthuselvam
                Route::post('apply/contest/page', 'MobileContestController@contest_apply_page');
                Route::get('mycontest/list/page', 'MobileContestController@mycontest_list_page');
                Route::get('mycontest/view/page/{id}', 'MobileContestController@mycontest_view_page');
                Route::get('upcoming/contest/list/page', 'MobileContestController@upcoming_contest_list_page');
                Route::get('contest/view/{id}', 'MobileContestController@contest_view_page');
                Route::get('mobile/chat/list', 'MobileChatController@employee_chat_list');
                Route::get('mobile/chat/conversation', 'MobileChatController@chatConversation');

                // tickets rajesh kannan
                Route::get('ticket/list', 'TicketMobileController@ticket_list');
                Route::get('ticket/view/{id}', 'TicketMobileController@ticket_view');
                Route::get('ticketinbox/view/{id}', 'TicketMobileController@ticketinboxview');
                Route::post('ticketinbox/create', 'TicketMobileController@ticketinbox_create');
                Route::get('singleticket/list/{id}', 'TicketMobileController@tickets_list');
                Route::post('ticket/create', 'TicketMobileController@ticket_create');
                Route::post('ticket/status', 'TicketMobileController@ticket_status');
                Route::post('replystatus/create', 'TicketMobileController@replystatus_create');

                Route::post('myordercomplaint/create', 'MyOrderComplaintController@myordercomplaint_create');

                //HomePageController
                Route::get('cmsBanner/list', 'HomePageController@cmsBanner_list');
                Route::get('cmsvideo/list', 'HomePageController@cmsvideo_list');
                Route::get('cmsGreet/list', 'HomePageController@cmsGreet_list');

                //passportsizeproduct
                Route::get('passportsizemobile/list', 'PassportsizeMobileController@passportsizemobile_list');
                Route::get('passportsizemobile/view/{id}', 'PassportsizeMobileController@passportsizemobile_view');
                Route::post('passportsizeaddtocart/create', 'PassportsizeMobileController@passportsizeaddtocart_create');
                //photoprintwebsite
                Route::get('photoprintmobile/list', 'PhotoprintMobileController@photoprintmobile_list');
                Route::get('photoprintmobile/view/{id}', 'PhotoprintMobileController@photoprintmobile_view');
                Route::post('photoprintaddtocart/create', 'PhotoprintMobileController@photoprintaddtocart_create');

                //selfiealbumwebsite
                Route::get('selfiealbummobile/list', 'SelfieAlbumMobileController@selfiealbummobile_list');
                Route::get('selfiealbummobile/view/{id}', 'SelfieAlbumMobileController@selfiealbummobile_view');
                Route::post('addToCartForSelfieCreate', 'SelfieAlbumMobileController@addToCartForSelfieCreate');


                //photoframe
                Route::get('photoframeList', 'PhotoFrameController@photoframeList');
                Route::get('photoframeView/{id}', 'PhotoFrameController@photoframeView');
                Route::get('variantDetailsList/{id}', 'PhotoFrameController@variantDetailsList');
                Route::post('addToCartForPhotoFrame', 'PhotoFrameController@addToCartForPhotoFrame');

                //personalized
                Route::get('personalized/list', 'PersonalizedProductController@personalizedList');
                Route::get('personalized/listnew', 'PersonalizedProductController@personalizedList_new');
                Route::get('personalized/view/{id}', 'PersonalizedProductController@personalizedView');
                Route::post('addToCartForPersonalized', 'PersonalizedProductController@addToCartForPersonalized');

                //ecommerce
                Route::get('ecommerceList', 'EcommerceProductController@ecommerceList');
                Route::get('ecommerceView/{id}', 'EcommerceProductController@ecommerceView');
                Route::post('addToCartForEcommerceCreate', 'EcommerceProductController@addToCartForEcommerceCreate');
                Route::post('ratingcreate', 'EcommerceProductController@ratingcreate');
                Route::get('ratingReviewProductList/{id}', 'EcommerceProductController@ratingReviewProductList');

                //Add to cart
                Route::post('cartQuantityUpdate', 'AddToCartController@cartQuantityUpdate');
                Route::post('photoprintQuantityUpdate', 'AddToCartController@photoprintQuantityUpdate');
                Route::post('addToCart/Quantityupdate', 'AddToCartController@addToCart_update');
                Route::post('addToCart/delete', 'AddToCartController@addToCart_delete');
                Route::get('addToCartList', 'AddToCartController@addToCartList');
                Route::get('singleaddToCartList', 'AddToCartController@singleaddToCartList');
                Route::get('photoprintaddToCartList', 'AddToCartController@photoprintaddToCartList');
                Route::get('app/settings', 'AddToCartController@app_settings');
                Route::get('getAppUpdate', 'AddToCartController@playStore');
                Route::get('checkoutVerifyQuantity', 'AddToCartController@checkoutVerifyQuantity');
                Route::post('top/products/insert', 'AddToCartController@insert');
                Route::post('visit/history', 'AddToCartController@visit_history');
                //buynow
                Route::get('buynowList', 'AddToCartController@buynowList');
                Route::post('notifyMe', 'AddToCartController@notify_product');

                //order
                Route::post('placeOrder', 'OrderPlaceController@placeOrder');
                Route::post('orderRepayment', 'OrderPlaceController@orderRepayment');
                //paytmcheck
                Route::post('paytmCheck', 'OrderPlaceController@getPaytmintegration_post');

                //Notification
                Route::get('notification/list', 'NotificationController@list');
                Route::post('update/notification', 'NotificationController@update_notification');
        });
});

Route::group([
        'prefix' => 'V1/WP',
        'namespace' => 'App\Http\Controllers\API\V1\WP',
], function ($router) {

        Route::post('bulkOrderCreate', 'BulkOrderController@bulkOrderCreate');

        Route::get('greatings/list', 'CmsgreetingsController@list');
        //Location
        Route::get('state', 'LocationController@getState');
        Route::get('country', 'LocationController@country');
        Route::get('city', 'LocationController@getCity');
        Route::post('otherDistrict', 'LocationController@otherDistrict');

        //Register
        Route::post('register', 'RegisterController@register');

        //user login
        Route::post('login', 'LoginController@login');
        Route::post('otp/login', 'LoginController@otp_login');

        Route::post('forget', 'LoginController@forget');
        Route::post('reset', 'LoginController@reset');
        Route::post('otpVerify', 'LoginController@otpVerify');
        Route::post('changepassword', 'LoginController@changepassword');
        Route::get('view', 'LoginController@view');
        Route::post('updateProfileDetails', 'LoginController@update');
        Route::post('updateProfile', 'LoginController@update_profile');
        Route::post('social/login', 'SocialLoginController@social');

        Route::post('myordercomplaint/create', 'MyOrderComplaintController@myordercomplaint_create');
        Route::get('myorderInvoice/pdf/download', 'MyOrderComplaintController@invoice_pdf_download');

        // tickets rajesh kannan
        Route::get('ticket/list', 'TicketWebsiteController@ticket_list');
        Route::get('ticket/view/{id}', 'TicketWebsiteController@ticket_view');
        Route::get('ticketinbox/view/{id}', 'TicketWebsiteController@ticketinboxview');
        Route::post('ticketinbox/create', 'TicketWebsiteController@ticketinbox_create');
        // Route::get('ticket/view/{id}', 'TicketController@view');
        Route::get('singleticket/list/{id}', 'TicketWebsiteController@tickets_list');
        Route::post('ticket/create', 'TicketWebsiteController@ticket_create');
        Route::post('ticket/status', 'TicketWebsiteController@ticket_status');
        Route::post('replystatus/create', 'TicketWebsiteController@replystatus_create');

        //Webite Contest By- Muthuselvam
        Route::post('apply/contest', 'ContestController@contest_apply');
        Route::get('mycontest/list', 'ContestController@mycontest_list');
        Route::get('mycontest/view/{id}', 'ContestController@mycontest_view');
        Route::get('upcoming/contest/list', 'ContestController@upcoming_contest_list');
        Route::get('contest/view/{id}', 'ContestController@contest_view_page');

        //myprofileupdate
        Route::post('update/myprofile', 'MyProfileController@myprofile_update');
        Route::post('customerInfoUpdate', 'MyProfileController@customerInfoUpdate');
        Route::post('deliveryAddressInfoUpdate', 'MyProfileController@deliveryAddressInfoUpdate');
        Route::get('myaddress/view', 'MyProfileController@address_view');
        Route::get('couponCodeList', 'MyProfileController@couponCodeList');
        Route::post('couponCodeApply', 'MyProfileController@couponCodeApply');

        //myorders
        Route::get('myorder/list', 'MyOrderController@myorder_list');
        Route::get('myorder/view/{id}', 'MyOrderController@myorder_view');
        Route::post('cancelOrderItem', 'MyOrderController@cancelOrderItem');
        Route::post('cancelOrder', 'MyOrderController@cancelOrder');
        Route::get('myorderItem/view/{id}', 'MyOrderController@myorderItem_view');
        //Route::post('approvedRejectedStatus', 'MyOrderController@approvedRejectedStatus');
        Route::post('previewApprovedRejectedStatus', 'MyOrderController@previewApprovedRejectedStatus');
        Route::post('replaceImage', 'MyOrderController@replaceImage');
        Route::post('attachedImageUpload', 'MyOrderController@attachedImageUpload');
        Route::get('previewDetails/{id}', 'MyOrderController@previewDetails');
        Route::get('downloadSinglefile', 'MyOrderController@downloadSingle_file');
        Route::get('invoiceView/{id}', 'MyOrderController@invoice_view');

        //file upload
        Route::post('upload/file', 'FileUploadController@upload');
        Route::post('removeFile', 'FileUploadController@removeFile');
        // Update Token
        Route::post('updateTokenForWebsite', 'LoginController@updateTokenForWebsite');
        Route::post('update/profile', 'LoginController@update');
        Route::get('view/profile', 'LoginController@view');

        //HomePageController
        Route::get('cmsBanner/list', 'HomePageController@cmsBanner_list');
        Route::get('globalSearch', 'HomePageController@search');

        //GetAll
        Route::get('categorygetall', 'FillterGetAllController@categorygetall');
        Route::get('servicetype/getcall', 'FillterGetAllController@servicetype_getcall');
        Route::get('productname/getcall/{id}', 'FillterGetAllController@productname_getcall');

        //passportsizewebsite
        Route::get('passportsizewebsite/list', 'PassportsizeWebsiteController@passportsizewebsite_list');
        Route::get('passportsizewebsite/view/{id}', 'PassportsizeWebsiteController@passportsizewebsite_view');
        Route::post('passportsizeaddtocart/create', 'PassportsizeWebsiteController@passportsizeaddtocart_create');

        //photoprintwebsite
        Route::get('photoprintwebsite/list', 'PhotoprintWebsiteController@photoprintwebsite_list');
        Route::get('photoprintwebsite/view/{id}', 'PhotoprintWebsiteController@photoprintwebsite_view');
        Route::post('photoprintaddtocart/create', 'PhotoprintWebsiteController@photoprintaddtocart_create');

        //selfiealbumwebsite
        Route::get('selfiealbumwebsite/list', 'SelfieAlbumWebsiteController@selfiealbumwebsite_list');
        Route::get('selfiealbumwebsite/view/{id}', 'SelfieAlbumWebsiteController@selfiealbumwebsite_view');
        Route::post('addToCartForSelfieCreate', 'SelfieAlbumWebsiteController@addToCartForSelfieCreate');

        //photoframe
        Route::get('photoframeList', 'PhotoFrameController@photoframeList');
        Route::get('photoframeView/{id}', 'PhotoFrameController@photoframeView');
        Route::get('variantDetailsList/{id}', 'PhotoFrameController@variantDetailsList');
        Route::post('addToCartForPhotoFrame', 'PhotoFrameController@addToCartForPhotoFrame');
        Route::post('addToCartForPhotoFrameSingleUpdate', 'PhotoFrameController@addToCartForPhotoFrameSingleUpdate');


        //personalized
        Route::get('personalized/list', 'PersonalizedProductController@personalizedList');
        Route::get('personalized/listnew', 'PersonalizedProductController@personalizedList_old');
        Route::get('personalized/view/{id}', 'PersonalizedProductController@personalizedView');
        Route::post('addToCartForPersonalized', 'PersonalizedProductController@addToCartForPersonalized');

        //ecommerce
        Route::get('ecommerceList', 'EcommerceProductController@ecommerceList');
        Route::get('ecommerceView/{id}', 'EcommerceProductController@ecommerceView');
        Route::post('addToCartForEcommerceCreate', 'EcommerceProductController@addToCartForEcommerceCreate');
        Route::post('ratingcreate', 'EcommerceProductController@ratingcreate');
        Route::get('ratingReviewProductList/{id}', 'EcommerceProductController@ratingReviewProductList');

        //Add to cart
        Route::post('cartQuantityUpdate', 'AddToCartController@cartQuantityUpdate');
        Route::post('photoprintQuantityUpdate', 'AddToCartController@photoprintQuantityUpdate');
        Route::post('addToCart/delete', 'AddToCartController@addToCart_delete');
        Route::get('addToCartList', 'AddToCartController@addToCartList');
        Route::get('checkoutVerifyQuantity', 'AddToCartController@checkoutVerifyQuantity');
        //buynow
        Route::get('buynowList', 'AddToCartController@buynowList');
        Route::post('notifyMe', 'AddToCartController@notify_product');
        Route::post('top/products/insert', 'AddToCartController@insert');
        Route::post('visit_history', 'AddToCartController@visit_history');
        //order
        Route::post('placeOrder', 'OrderPlaceController@placeOrder');
        Route::post('orderRepayment', 'OrderPlaceController@orderRepayment');
        //paytmcheck
        Route::post('paytmCheck', 'OrderPlaceController@getPaytmintegration_post');

        Route::get('website/chat/list', 'WebsiteChatController@employee_chat_list');
        Route::get('website/chat/conversation', 'WebsiteChatController@chatConversation');
        Route::get('download/file', 'WebsiteChatController@downloadSingle_file');

        //notification
        Route::get('notification/list', 'NotificationController@list');
        Route::post('readNotification', 'NotificationController@update_notification');
});
