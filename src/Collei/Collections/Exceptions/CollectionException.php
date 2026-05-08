<?php
namespace Collei\Collections\Exceptions;

use Throwable;
use RuntimeException;
use Collei\Collections\CollectionInterface;

/**
 * Exceptions related to the collection.
 */
class CollectionException extends RuntimeException
{
    /**
     * @var CollectionInterface
     */
    protected $source;

    /**
     * Initialization.
     * 
     * @param Collei\Collections\CollectionInterface $source
     * @param string $message = ''
     * @param int $code = 0
     * @param \Throwable $previous = null
     */
    public function __construct(CollectionInterface $source, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->source = $source;
    }

    /**
     * Returns the related collection.
     * 
     * @return Collei\Collections\CollectionInterface
     */
    public function source()
    {
        return $this->source;
    }
}