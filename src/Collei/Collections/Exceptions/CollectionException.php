<?php
namespace Collei\Collections\Exceptions;

use Throwable;
use RuntimeException;
use Collei\Collections\Collection;

class CollectionException extends RuntimeException
{
    protected $source;

    public function __construct(Collection $source, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->source = $source;
    }

    public function source()
    {
        return $this->source;
    }
}