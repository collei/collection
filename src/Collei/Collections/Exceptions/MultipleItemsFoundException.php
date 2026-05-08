<?php
namespace Collei\Collections\Exceptions;

/**
 * For item not found.
 */
class MultipleItemsFoundException extends CollectionException
{
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
        if (empty($message)) {
            $message = sprintf('Multiple items found in this collection');
        }

        parent::__construct($source, $message, $code, $previous);
    }
}