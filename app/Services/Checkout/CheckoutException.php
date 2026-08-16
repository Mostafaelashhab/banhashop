<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * Thrown when an order cannot be placed for a reason the customer can act on.
 * The message is user-facing Arabic and is shown as-is.
 */
class CheckoutException extends RuntimeException {}
