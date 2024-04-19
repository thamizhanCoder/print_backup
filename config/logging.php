<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
        'employee' => [
            'driver' => 'daily',
            'path' => storage_path('logs/employee/employee.log'),
            'level' => 'debug',
        ],
        'department' => [
            'driver' => 'daily',
            'path' => storage_path('logs/department/department.log'),
            'level' => 'debug',
        ],
        'designation' => [
            'driver' => 'daily',
            'path' => storage_path('logs/designation/designation.log'),
            'level' => 'debug',
        ],
        'taskstage' => [
            'driver' => 'daily',
            'path' => storage_path('logs/taskstage/taskstage.log'),
            'level' => 'debug',
        ],
        'taskduration' => [
            'driver' => 'daily',
            'path' => storage_path('logs/taskduration/taskduration.log'),
            'level' => 'debug',
        ],
        'expdeliverydays' => [
            'driver' => 'daily',
            'path' => storage_path('logs/exp_delivery_days/exp_delivery_days.log'),
            'level' => 'debug',
        ],
        'deliverCharge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deliverCharge/deliverCharge.log'),
            'level' => 'debug',
        ],
        'photoprintsettings' => [
            'driver' => 'daily',
            'path' => storage_path('logs/photo_print_settings/photo_print_settings.log'),
            'level' => 'debug',
        ],
        'ticket' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ticket/ticket.log'),
            'level' => 'debug',
        ],
        'gstpercentage' => [
            'driver' => 'daily',
            'path' => storage_path('logs/gstpercentage/gstpercentage.log'),
            'level' => 'debug',
        ],
        'gstreport' => [
            'driver' => 'daily',
            'path' => storage_path('logs/gstreport/gstreport.log'),
            'level' => 'debug',
        ],
        'varianttype' => [
            'driver' => 'daily',
            'path' => storage_path('logs/varianttype/varianttype.log'),
            'level' => 'debug',
        ],
        'category' => [
            'driver' => 'daily',
            'path' => storage_path('logs/category/category.log'),
            'level' => 'debug',
        ],
        'couponcode' => [
            'driver' => 'daily',
            'path' => storage_path('logs/couponcode/couponcode.log'),
            'level' => 'debug',
        ],
        'shippedvendordetails' => [
            'driver' => 'daily',
            'path' => storage_path('logs/shippedvendordetails/shippedvendordetails.log'),
            'level' => 'debug',
        ],
        'cmsbanner' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cmsbanner/cmsbanner.log'),
            'level' => 'debug',
        ],
        'cmsvideo' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cmsvideo/cmsvideo.log'),
            'level' => 'debug',
        ],
        'cmsgreeting' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cmsgreeting/cmsgreeting.log'),
            'level' => 'debug',
        ],
        'contest' => [
            'driver' => 'daily',
            'path' => storage_path('logs/contest/contest.log'),
            'level' => 'debug',
        ],
        'otherdistrictsetting' => [
            'driver' => 'daily',
            'path' => storage_path('logs/otherdistrictsetting/otherdistrictsetting.log'),
            'level' => 'debug',
        ],
        'passportsizephoto' => [
            'driver' => 'daily',
            'path' => storage_path('logs/passportsizephoto/passportsizephoto.log'),
            'level' => 'debug',
        ],
        'photoprint' => [
            'driver' => 'daily',
            'path' => storage_path('logs/photoprint/photoprint.log'),
            'level' => 'debug',
        ],
        'photoframe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/photoframe/photoframe.log'),
            'level' => 'debug',
        ],
        'personalized' => [
            'driver' => 'daily',
            'path' => storage_path('logs/personalized/personalized.log'),
            'level' => 'debug',
        ],
        'ecommerce' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ecommerce/ecommerce.log'),
            'level' => 'debug',
        ],
        'selfiealbum' => [
            'driver' => 'daily',
            'path' => storage_path('logs/selfiealbum/selfiealbum.log'),
            'level' => 'debug',
        ],
    'customer_admin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/customer_admin/customer_admin.log'),
        'level' => 'debug',
    ],
    'mobilelogin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobilelogin/mobilelogin.log'),
        'level' => 'debug',
    ],
    'mobileviewprofile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobileviewprofile/mobileviewprofile.log'),
        'level' => 'debug',
    ],
    'mobiletokenupdate' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobiletokenupdate/mobiletokenupdate.log'),
        'level' => 'debug',
    ],
    'mobilemyprofile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobilemyprofile/mobilemyprofile.log'),
        'level' => 'debug',
    ],
    'websitelogin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/websitelogin/websitelogin.log'),
        'level' => 'debug',
    ],
    'websitemyprofile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/websitemyprofile/websitemyprofile.log'),
        'level' => 'debug',
    ],
    'addtocart_photoframe_web' => [
        'driver' => 'daily',
        'path' => storage_path('logs/addtocart_photoframe_web/addtocart_photoframe_web.log'),
        'level' => 'debug',
    ],
    'addtocart_photoframe_mobile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/addtocart_photoframe_mobile/addtocart_photoframe_mobile.log'),
        'level' => 'debug',
    ],
    'addtocart_personalized_web' => [
        'driver' => 'daily',
        'path' => storage_path('logs/addtocart_personalized_web/addtocart_personalized_web.log'),
        'level' => 'debug',
    ],
    'addtocart_personalized_mobile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/addtocart_personalized_mobile/addtocart_personalized_mobile.log'),
        'level' => 'debug',
    ],
    'addtocart_mobile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/addtocart_mobile/addtocart_mobile.log'),
        'level' => 'debug',
    ],
    'repayment_website' => [
        'driver' => 'daily',
        'path' => storage_path('logs/repayment_website/repayment_website.log'),
        'level' => 'debug',
    ],
    'employeelogin' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeelogin/employeelogin.log'),
        'level' => 'debug',
    ],
    'attachedImageApproved' => [
        'driver' => 'daily',
        'path' => storage_path('logs/attachedImageApproved/attachedImageApproved.log'),
        'level' => 'debug',
    ],
    'attachedImageRejected' => [
        'driver' => 'daily',
        'path' => storage_path('logs/attachedImageRejected/attachedImageRejected.log'),
        'level' => 'debug',
    ],
    'previewImageApproved' => [
        'driver' => 'daily',
        'path' => storage_path('logs/previewImageApproved/previewImageApproved.log'),
        'level' => 'debug',
    ],
    'previewImageRejected' => [
        'driver' => 'daily',
        'path' => storage_path('logs/previewImageRejected/previewImageRejected.log'),
        'level' => 'debug',
    ],
    'attachedImageUpload' => [
        'driver' => 'daily',
        'path' => storage_path('logs/attachedImageUpload/attachedImageUpload.log'),
        'level' => 'debug',
    ],
    'directtask' => [
        'driver' => 'daily',
        'path' => storage_path('logs/directtask/directtask.log'),
        'level' => 'debug',
    ],
    'productiontaskassign' => [
        'driver' => 'daily',
        'path' => storage_path('logs/productiontaskassign/productiontaskassign.log'),
        'level' => 'debug',
    ],
    'productiontaskrevoke' => [
        'driver' => 'daily',
        'path' => storage_path('logs/productiontaskrevoke/productiontaskrevoke.log'),
        'level' => 'debug',
    ],
    'qcApprove' => [
        'driver' => 'daily',
        'path' => storage_path('logs/qcApprove/qcApprove.log'),
        'level' => 'debug',
    ],
    'qcReject' => [
        'driver' => 'daily',
        'path' => storage_path('logs/qcReject/qcReject.log'),
        'level' => 'debug',
    ],
    'statusChange' => [
        'driver' => 'daily',
        'path' => storage_path('logs/statusChange/statusChange.log'),
        'level' => 'debug',
    ],
    'employeestatusChange' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeestatusChange/employeestatusChange.log'),
        'level' => 'debug',
    ],
    'employeeitemview' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeeitemview/employeeitemview.log'),
        'level' => 'debug',
    ],
    'employeeOrderItemViewImagaeList' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeeOrderItemViewImagaeList/employeeOrderItemViewImagaeList.log'),
        'level' => 'debug',
    ],
    'termsandcondition' => [
        'driver' => 'daily',
        'path' => storage_path('logs/termsandcondition/termsandcondition.log'),
        'level' => 'debug',
    ],
    'deliveryManagement' => [
        'driver' => 'daily',
        'path' => storage_path('logs/deliveryManagement/deliveryManagement.log'),
        'level' => 'debug',
    ],
    'bulkordermobile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/bulkordermobile/bulkordermobile.log'),
        'level' => 'debug',
    ],
    'bulkorderwebsite' => [
        'driver' => 'daily',
        'path' => storage_path('logs/bulkorderwebsite/bulkorderwebsite.log'),
        'level' => 'debug',
    ],
    'quote' => [
        'driver' => 'daily',
        'path' => storage_path('logs/quote/quote.log'),
        'level' => 'debug',
    ],
    'webhook' => [
        'driver' => 'daily',
        'path' => storage_path('logs/webhook/webhook.log'),
        'level' => 'debug',
    ],
    'pglinkgenerate' => [
        'driver' => 'daily',
        'path' => storage_path('logs/pglinkgenerate/pglinkgenerate.log'),
        'level' => 'debug',
    ],
    'employeeEnquiry' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeeEnquiry/employeeEnquiry.log'),
        'level' => 'debug',
    ],
    'enquiryModule' => [
        'driver' => 'daily',
        'path' => storage_path('logs/enquiryModule/enquiryModule.log'),
        'level' => 'debug',
    ],
    'employeeQuote' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeeQuote/employeeQuote.log'),
        'level' => 'debug',
    ],
    'employeeCreateOrder' => [
        'driver' => 'daily',
        'path' => storage_path('logs/employeeCreateOrder/employeeCreateOrder.log'),
        'level' => 'debug',
    ],
    'createOrder' => [
        'driver' => 'daily',
        'path' => storage_path('logs/createOrder/createOrder.log'),
        'level' => 'debug',
    ],
    'orderwebsite' => [
        'driver' => 'daily',
        'path' => storage_path('logs/orderwebsite/orderwebsite.log'),
        'level' => 'debug',
    ],
    'ordermobile' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ordermobile/ordermobile.log'),
        'level' => 'debug',
    ],
]
];
