<?php

namespace App\Enums;

enum ConversionEventName: string
{
    case PAGE_VIEW = 'page_view';
    case COURSE_VIEW = 'course_view';
    case BUNDLE_VIEW = 'bundle_view';
    case COURSE_CTA_CLICK = 'course_cta_click';
    case BUNDLE_CTA_CLICK = 'bundle_cta_click';
    case LEAD_CREATED = 'lead_created';
    case CHECKOUT_STARTED = 'checkout_started';
    case PAYMENT_INITIATED = 'payment_initiated';
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILED = 'payment_failed';
    case COURSE_ENROLLED = 'course_enrolled';
    case BUNDLE_PURCHASED = 'bundle_purchased';
    case CERTIFICATE_DOWNLOAD = 'certificate_download';
    case CONTACT_CLICK = 'contact_click';
    case COURSE_ACCESS_EXPIRING = 'course_access_expiring';
    case COURSE_ACCESS_EXPIRED = 'course_access_expired';
    case RENEWAL_CHECKOUT_STARTED = 'renewal_checkout_started';
    case RENEWAL_PAYMENT_INITIATED = 'renewal_payment_initiated';
    case RENEWAL_PAYMENT_SUCCESS = 'renewal_payment_success';
    case RENEWAL_PAYMENT_FAILED = 'renewal_payment_failed';
    case RENEWAL_FULFILLED = 'renewal_fulfilled';
    case COURSE_ACCESS_RENEWED = 'course_access_renewed';
    case RENEWAL_NOTIFICATION_SENT = 'renewal_notification_sent';
    case RENEWAL_NOTIFICATION_FAILED = 'renewal_notification_failed';

    public function label(): string
    {
        return match ($this) {
            self::PAGE_VIEW => 'Page View',
            self::COURSE_VIEW => 'Course View',
            self::BUNDLE_VIEW => 'Bundle View',
            self::COURSE_CTA_CLICK => 'Course CTA Click',
            self::BUNDLE_CTA_CLICK => 'Bundle CTA Click',
            self::LEAD_CREATED => 'Lead Created',
            self::CHECKOUT_STARTED => 'Checkout Started',
            self::PAYMENT_INITIATED => 'Payment Initiated',
            self::PAYMENT_SUCCESS => 'Payment Success',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::COURSE_ENROLLED => 'Course Enrolled',
            self::BUNDLE_PURCHASED => 'Bundle Purchased',
            self::CERTIFICATE_DOWNLOAD => 'Certificate Downloaded',
            self::CONTACT_CLICK => 'Contact Click',
            self::COURSE_ACCESS_EXPIRING => 'Course Access Expiring',
            self::COURSE_ACCESS_EXPIRED => 'Course Access Expired',
            self::RENEWAL_CHECKOUT_STARTED => 'Renewal Checkout Started',
            self::RENEWAL_PAYMENT_INITIATED => 'Renewal Payment Initiated',
            self::RENEWAL_PAYMENT_SUCCESS => 'Renewal Payment Success',
            self::RENEWAL_PAYMENT_FAILED => 'Renewal Payment Failed',
            self::RENEWAL_FULFILLED => 'Renewal Fulfilled',
            self::COURSE_ACCESS_RENEWED => 'Course Access Renewed',
            self::RENEWAL_NOTIFICATION_SENT => 'Renewal Notification Sent',
            self::RENEWAL_NOTIFICATION_FAILED => 'Renewal Notification Failed',
        };
    }

    /**
     * Determine if this event is client-reportable.
     * High-security business events (payment, enrollment) MUST be tracked server-side only.
     */
    public function isClientReportable(): bool
    {
        return in_array($this, [
            self::COURSE_CTA_CLICK,
            self::BUNDLE_CTA_CLICK,
            self::CONTACT_CLICK,
            self::PAGE_VIEW,
        ], true);
    }
}
