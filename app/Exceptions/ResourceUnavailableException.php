<?php

namespace App\Exceptions;

/**
 * Thrown by ResourceAllocationService::resolve() when the requested
 * resource quantity cannot be satisfied for the given booking slot.
 */
class ResourceUnavailableException extends \RuntimeException
{
}
