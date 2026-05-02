<?php
namespace Collei\Collections\Exceptions;

use Throwable;
use RuntimeException;
use Collei\Collections\CollectionInterface;

class CollectionException extends RuntimeException
{
    protected $source;

    public function __construct(CollectionInterface $source, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->source = $source;
    }

    public function source()
    {
        return $this->source;
    }
}