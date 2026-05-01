<?php
namespace Collei\Collections\Exceptions;

use Throwable;
use RuntimeException;

class CollectionException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}