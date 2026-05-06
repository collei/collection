<?php
namespace Collei\Support;

use RuntimeException;

/**
 * Embodies a key-value pair.
 */
class KeyedValue
{
    /**
     * @var mixed
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Initialization.
     * 
     * @param mixed $value
     * @param mixed $key
     */
    public function __construct($value, $key)
    {
        [$this->value, $this->key] = [$value, $key];
    }

    /**
     * Returns the value.
     * 
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Returns the key.
     * 
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Returns the value type.
     * 
     * @return mixed
     */
    public function type()
    {
        return gettype($this->value);
    }

    /**
     * Returns if the underlying value is and instance of
     * one of the listed classes.
     * 
     * @param string ...$classes
     * @return bool
     */
    public function instanceOf(string ...$classes)
    {
        if (! is_object($this->value)) {
            return false;
        }
        
        foreach ($classes as $class) {
            return $this->value instanceof $class;
        }

        return false;
    }

    /**
     * Returns the properties if they exist.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (is_object($this->value) && property_exists($this->value, $name)) {
            return $this->value->$name;
        }

        return null;
    }

    /**
     * Calls underlying method of $value (if it is an object).
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (is_object($this->value)) {
            if (method_exists($object, $method) || method_exists($object, '__call')) {
                return $this->value->{$method}(...$arguments);
            }

            throw new RuntimeException(sprintf('Method %s does not exist in the value', $method));
        }

        throw new RuntimeException('The value on the pair is not an object');
    }
}