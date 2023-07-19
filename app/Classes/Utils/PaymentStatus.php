<?php


namespace App\Classes\Utils;


class PaymentStatus
{
    const SUCCESS = "Success";
    const FAILED = "Failed";
    const INITIALIZED = "Initialized";
    const PROCESSING = "Processing";
    const PENDING = "Pending";
    const EXPIRED = "Expired";
    const PARTIAL_REFUND = "Partial Refund";
    const FULL_REFUND = "Full Refund";
}
