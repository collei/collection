<?php
namespace Collei\Collections\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Collection exceptions.
 */
class CollectionException extends RuntimeException
{
    /**
     * @var Collei\Collections\CollectionInterface
     */
    protected $collection;
    
    /**
     * Initialization.
     * 
     * @param Collei\Collections\CollectionInterface $collection
     * @param string $message = null
     * @param \Throwable $previous = null
     */
    public function __construct(CollectionInterface $collection, string $message = null, Throwable $previous = null)
    {
        $message = $message ?? 'A collection has thrown an exception';

        parent::__construct($message, 0, $previous);

        $this->collection = $collection;
    }

    /**
     * Retrieves the related collection.
     * 
     * @return Collei\Collections\CollectionInterface
     */
    public function collection()
    {
        return $this->collection;
    }
}