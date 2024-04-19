<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\ForgetPassword' => [
            'App\Listeners\SendResetEmail',
        ],
        'App\Events\SendEmail' => [
            'App\Listeners\SendDispatchEmail',
        ],
        'App\Events\SendBillNumber' => [
            'App\Listeners\SendBillEmail',
        ],
        'App\Events\SendAvailable' => [
            'App\Listeners\SendAvailableEmail',
        ],
        'App\Events\SendApproved' => [
            'App\Listeners\SendApprovedEmail',
        ],
        'App\Events\SendDisapproved' => [
            'App\Listeners\SendDisapprovedEmail',
        ],
        'App\Events\SendRevoke' => [
            'App\Listeners\SendRevokeEmail',
        ],
        'App\Events\SendCodApproved' => [
            'App\Listeners\SendCodApprovedEmail',
        ],
        'App\Events\SendCodDisapproved' => [
            'App\Listeners\SendCodDisapprovedEmail',
        ],
        'App\Events\SendCodRevoke' => [
            'App\Listeners\SendCodRevokeEmail',
        ],
        'App\Events\SendContactUs' => [
            'App\Listeners\SendContactEmail',
        ],
        'App\Events\ContestApply' => [
            'App\Listeners\SendContestApplyEmail',
        ],
        'App\Events\Register' => [
            'App\Listeners\SendRegisterEmail',
        ],
        'App\Events\ForgetPasswordCustomer' => [
            'App\Listeners\SendResetEmailCustomer',
        ],
        'App\Events\OrderPlaced' => [
            'App\Listeners\SendPlacedOrderEmail',
        ],
        'App\Events\CancelOrder' => [
            'App\Listeners\SendCancelOrderEmail',
        ],
        'App\Events\DeliverySuccess' => [
            'App\Listeners\SendDeliverySuccessEmail',
        ],
        'App\Events\EmployeeCreate' => [
            'App\Listeners\SendEmployeeCreate',
        ],
        'App\Events\CouponCodeMail' => [
            'App\Listeners\SendCouponCodeEmail',
        ],
        'App\Events\SendPgLink' => [
            'App\Listeners\SendPgLinkEmail',
        ],
        'App\Events\BulkOrderEnquiryEvent' => [
            'App\Listeners\SendBulkOrderEmail',
        ],
        'App\Events\EnquiryAssign' => [
            'App\Listeners\SendEnquiryAssign',
        ],
        'App\Events\QuoteConvert' => [
            'App\Listeners\SendQuoteConvertEmail',
        ],
        'App\Events\OrderCreateFromAdmin' => [
            'App\Listeners\SendOrderCreateAdminEmail',
        ],
        'App\Events\QuoteReraiseApprovalEmployee' => [
            'App\Listeners\SendQuoteReraiseApprovalEmployee',
        ],
        'App\Events\QuoteReraiseRejectEmployee' => [
            'App\Listeners\SendQuoteReraiseRejectEmployee',
        ],
        'App\Events\QuoteRequestEmployee' => [
            'App\Listeners\SendQuoteRequestEmail',
        ],
        'App\Events\EnquiryRevokeEmployee' => [
            'App\Listeners\SendEnquiryRevokeEmployee',
        ],
        'App\Events\BulkOrderEnquiryCustomer' => [
            'App\Listeners\SendBulkOrderEmailCustomer',
        ],
        'App\Events\CustomerPreviewApprovalEmployee' => [
            'App\Listeners\SendCustomerPreviewApprovalEmployeeEmail',
        ],
        'App\Events\CustomerPreviewRejectionEmployee' => [
            'App\Listeners\SendCustomerPreviewRejectionEmployeeEmail',
        ],
        'App\Events\CancelOrderItems' => [
            'App\Listeners\SendCancelOrderItemsEmail',
        ],
        'App\Events\SendFinalPgLink' => [
            'App\Listeners\SendFinalPgLinkEmail',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
