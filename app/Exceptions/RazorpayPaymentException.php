<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A payment could not be safely associated with a Merza order.
 *
 * This deliberately does not expose gateway response details to customers.
 */
class RazorpayPaymentException extends RuntimeException {}
