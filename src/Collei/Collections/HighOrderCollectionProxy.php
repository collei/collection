<?php
namespace Collei\Collections;

use RuntimeException;

/**
 * Reunites array helper functions
 */
class HighOrderCollectionProxy
{
    private const PROXIED_METHODS = [
        'avg',
        'average',
        'sum',
    ];

    private $collection;
    private $method;

    public function __construct(Collection $collection, string $method)
    {
        $this->collection = $collection;
        $this->method = $method;
    }

	public function __get(string $name)
	{
        if (! in_array($name, self::PROXIED_METHODS, true)) {
            throw new RuntimeException(sprintf('Method \'%s\' does not exists in the Collection instance', $name));
        }

        $method = $this->method;

		return $this->collection->{$method}($name); 
	}
}