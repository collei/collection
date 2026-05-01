<?php
namespace Collei\Collections;

use RuntimeException;

/**
 * Higher order collection proxy.
 */
class HighOrderCollectionProxy
{
    /**
     * @var Collei\Collections\Collection The proxied collection
     */
    private $collection;

    /**
     * @var string The proxied method
     */
    private $property;

    /**
     * Initialization.
     * 
     * @param Collei\Collections\Collection $collection
     * @param string $property
     */
    public function __construct(Collection $collection, string $property)
    {
        $this->collection = $collection;
        $this->property = $property;
    }

    /**
     * Redirects calls to methods for value retrieval.
     * 
     * @param string $key
     * @return mixed
     */
	public function __get(string $key)
	{
        return $this->collection->{$this->property}(function($item) use ($key) {
            return is_array($item)
                ? $item[$key]
                : $item->{$key};
        });
	}

    /**
     * Redirects calls to methods for value attribution.
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
	public function __set(string $key, $value)
	{
        return $this->collection->{$this->property}(function($item) use ($key, $value) {
            if (is_object($item)) {
                $item->{$key} = $value;
            }
        });
	}

    /**
     * Redirects calls to items' methods.
     * 
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->collection->{$this->property}(function($item) use ($method, $arguments) {
            return is_string($item)
                ? $item::{$method}(...$arguments)
                : $item->{$method}(...$arguments);
        });
    }
}