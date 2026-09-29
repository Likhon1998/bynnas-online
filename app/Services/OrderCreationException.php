<?php

namespace App\Services;

use RuntimeException;

/** A customer-facing reason an order could not be placed (stock, unavailable product, empty cart…). */
class OrderCreationException extends RuntimeException {}
