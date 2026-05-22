<?php
namespace Collei\Collections;

/**
 * Proxies operations upon collection instances.
 */
class HigherOrderProxy
{
    /**
     * @var CollectionInterface
     */
    private $collection;

    /**
     * @var string
     */
    private $method;

    /**
     * Initialize the proxy.
     * 
     * @param CollectionInterface $collection
     * @param string $method
     */
    public function __construct(CollectionInterface $collection, string $method)
    {
        list($this->collection, $this->method) = array($collection, $method);
    }

    /**
     * Proxies property accesses onto the collection.
     * 
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        $callback = function($value) use ($property) {
            return is_array($value) ? $value[$property] : $value->$property;
        };

        return $this->collection->{$this->method}($callback);
    }

    /**
     * Proxies method calls onto the collection.
     * 
     * @param string $property
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $callback = function($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        };

        return $this->collection->{$this->method}($callback);
    }
}