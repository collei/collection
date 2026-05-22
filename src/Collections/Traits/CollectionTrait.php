<?php
namespace Collei\Collections\Traits;

use Closure;
use Collei\Collections\CollectionInterface;

/**
 * Common methods for collections.
 */
trait CollectionTrait
{
    use HasQuerifulSelector;
    use HandlesClosures;

    /**
     * Checks if all array elements satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function all(callable $callback)
    {
        return array_all($this->items, $callback);
    }

    /**
     * Checks if any array element satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function any(callable $callback)
    {
        return array_any($this->items, $callback);
    }

    /**
     * Retrieves the average value of the collection.
     * If the field argument is given, retrieves the average value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function average($field = null)
    {
        return $this->avg($field);
    }

    /**
     * Retrieves the average value of the collection.
     * If the field argument is given, retrieves the average value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function avg($field = null)
    {
        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

        $sum = $count = 0;

        foreach ($this as $key => $item) {
            $value = $callback($item, $key);

            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $sum += $value;
                $count++;
            }
        }

        return $sum / $count;
    }

    /**
     * Produces a copy of this collection, split into chunks,
     * and returns a collection of these chunks.
     * 
     * @param int $length
     * @param bool $preserveKeys = false
     * @return static
     */
    public function chunk(int $length, bool $preserveKeys = false)
    {
        $chunks = array_chunk($this->items, $length, $preserveKeys);

        $callback = function ($chunk) {
            return new static($chunk);
        };

        return new static(array_map($callback, $chunks));
    }

    /**
     * Return a new collection with values from a single column
     * in the collection.
     * 
     * @param int|string|callable $columnKey
     * @param int|string|callable $indexKey = null
     * @return static
     */
    public function column($columnKey, $indexKey = null)
    {
        return new static(array_column_callback($this->items, ...func_get_args()));
    }

    /**
     * Return a new collection with the given array keyed by the
     * values of this colelction.
     * 
     * @param iterable|CollectionInterface $items = []
     * @return static
     */
    public function combine($items = [])
    {
        return new static(array_combine($this->items, $this->arrayFrom($items, true)));
    }

    /**
     * Return a new collection with the current collection items
     * and keyed by the given array values.
     * 
     * @param iterable|CollectionInterface $items = []
     * @return static
     */
    public function combineTo($items = [])
    {
        if ($items instanceof CollectionInterface) {
            return $items->combine($this);
        }

        return new static(array_combine($items, $this->items));
    }

    /**
     * Retrieve the items count.
     * 
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Retrieves a new collection with the Counts of each
     * distinct value in this collection.
     * 
     * @param bool $strict = false
     * @return static
     */
    public function countValues(bool $strict = false)
    {
        list($values, $counts) = array([], []);

        foreach ($this as $item) {
            $key = array_search($item, $values, $strict);

            if ($key === false) {
                $values[] = $value;
                $counts[] = 1;
            } else {
                $counts[$key]++;
            }
        }

        $result = [];

        foreach ($values as $index => $value) {
            $count = $counts[$index];

            $result[] = compact('value','count');
        }

        return new static($result);
    }

    /**
     * Retrieves a new collection with the Counts of each strictly
     * distinct value in this collection.
     * 
     * @return static
     */
    public function countValuesStrict()
    {
        return $this->countValues(true);
    }

    /**
     * Checks if a value exists in this collection.
     * 
     * @param mixed $value
     * @param bool $strict = false
     * @return bool
     */
    public function exists($value, bool $strict = false)
    {
        return in_array($value, $this->items, $strict);
    }

    /**
     * Checks strictly if a value exists in this collection.
     * 
     * @param mixed $value
     * @return bool
     */
    public function existsStrict($value)
    {
        return $this->exists($value, true);
    }

    /**
     * Returns a new collection indexed by the kesy of this
     * collection and using $value as item values.
     * 
     * @param mixed $value = null
     * @return static   
     */
    public function fillKeys($value = null)
    {
        return new static(array_fill_keys($this->toArray(), $value));
    }

    /**
     * Filters elements from this collection using the given
     * callback function, and returns the resulting collection.
     * 
     * @param Closure $callback
     * @return static
     */
    public function filter(Closure $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * Filters elements from this collection using the given
     * callback function, passing the key as second argument to
     * the callback, and returns the resulting collection.
     * 
     * @param Closure $callback
     * @return static
     */
    public function filterWithKeys(Closure $callback)
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Returns the first element satisfying the given callback.
     * 
     * @param Closure $callback
     * @return mixed
     */
    public function find(Closure $callback)
    {
        return array_find($this->items, $callback);
    }

    /**
     * Returns the key of the first element satisfying the given
     * callback function.
     * 
     * @param Closure $callback
     * @return int|string|null
     */
    public function findKey(Closure $callback)
    {
        return array_find_key($this->items, $callback);
    }

    /**
     * Gets the first value of this collection.
     * 
     * @return mixed
     */
    public function first()
    {
        return array_first($this->items);
    }

    /**
     * Gets the first key of this collection.
     * 
     * @return int|string|null
     */
    public function firstKey()
    {
        return array_key_first($this->items);
    }

    /**
     * Returns a collection with all values as keys and vice-versa.
     * 
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Tells whether the given key exists in this collection.
     * 
     * @param int|string $key
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Return the last item of collection.
     * 
     * @return mixed
     */
    public function last()
    {
        return array_last($this->items);
    }

    /**
     * Return the key of the last item of collection.
     * 
     * @return int|string|null
     */
    public function lastKey()
    {
        return array_key_last($this->items);
    }

    /**
     * Return a copy of this collection, but keyed by the
     * value specified by $field.
     * 
     * @param int|string|callable $field
     * @return static
     */
    public function keyBy($field)
    {
        return $this->values($field)->combine($this);
    }

    /**
     * Return a collection with all keys of this collection.
     * 
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Applies the callback to the elements of this collection,
     * returning the result as a new collection.
     * If the callback accepts two arguments, the item Key will
     * be available as the second argument.
     * 
     * @param Closure $callback
     * @return static
     */
    public function map(Closure $callback)
    {
        $count = closure_count_args($callback);

        if ($count === 2) {
            $keys = array_keys($this->items);

            $values = array_map($callback, $this->items, $keys);

            return new static(array_combine($keys, $values));
        }

        return new static(array_map($callback, $this->items));
    }

    /**
     * Retrieves the maximum value of the collection.
     * If the field argument is given, retrieves the maximum value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function max($field = null)
    {
        if (is_null($field)) {
            return max($this->items);
        }

        $callback = $this->valueRetriever($field);

        $max = -PHP_FLOAT_MAX;

        foreach ($this as $key => $item) {
            $value = $callback($item, $key);

            $compare = $value <=> $max;

            if ($compare > 0) {
                $max = $value;
            }
        }

        return $max;
    }

    /**
     * Retrieves the minimum value of the collection.
     * If the field argument is given, retrieves the minimum value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function min($field = null)
    {
        if (is_null($field)) {
            return min($this->items);
        }

        $callback = $this->valueRetriever($field);

        $min = PHP_FLOAT_MAX;

        foreach ($this as $key => $item) {
            $value = $callback($item, $key);

            $compare = $value <=> $min;

            if ($compare < 0) {
                $min = $value;
            }
        }

        return $min;
    }

    /**
     * Returns a copy of the collection, but padded to $length with
     * nulls or the given $value if provided.
     * 
     * @param int $length
     * @param mixed $value = null
     * @return static  
     */
    public function pad(int $length, $value = null)
    {
        return new static(array_pad($this->items, $length, $value));
    }

    /**
     * Appends values to the start of this collection.
     * 
     * @param mixed ...$values
     * @return $this
     */
    public function prepend(...$values)
    {
        return $this->unshift(...$values);
    }

    /**
     * Retrieves the aritmetic product of all numeric values.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function product($field = null)
    {
        if (is_null($field)) {
            return array_product($this->items);
        }

        $callback = $this->valueRetriever($field);

        $product = 1;

        foreach ($this as $key => $item) {
            $value = $callback($item, $key);

            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $product *= $value;
            }
        }

        return $product;
    }

    /**
     * Iteratively reduce the collection to a single value using
     * a callback function.
     * 
     * @param Closure $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(Closure $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

	/**
	 * Searches the collection for a given value and returns
     * the first corresponding key if successful.
	 *
	 * @param mixed $needle
     * @param bool $strict = false
	 * @return int|string|false
	 */
    public function search($needle, bool $strict = false)
    {
        return array_search($needle, $this->items, $strict);
    }

	/**
	 * Searches strictly the collection for a given value and
     * returns the first corresponding key if successful.
	 *
	 * @param mixed $needle
	 * @return int|string|false
	 */
    public function searchStrict($needle)
    {
        return array_search($needle, $this->items, true);
    }

    /**
     * Retrieves a collection without the first $count items.
     * 
     * @param int $count
     * @return static
     */
    public function skip(int $count)
    {
        if ($count < 1) {
            throw new InvalidArgumentException('$count must be a nonzero positive integer');
        }

        return $this->slice($count);
    }

    /**
     * Returns a collection from a slice of this collection.
     * 
     * @param int $offset
     * @param ?int $length = null
     * @return static
     */
    public function slice(int $offset, ?int $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Remove a portion of the collection and replace it with $replacement.
     * 
     * @param int $offset
     * @param ?int $length = null
     * @param iterable|CollectionInterface $replacement = []
     * @return static
     */
    public function splice(int $offset, ?int $length = null, $replacement = [])
    {
        $result = $this->toArray();

        array_splice($result, $offset, $length, $this->arrayFrom($replacement, true));
        
        return new static($result);
    }

    /**
     * Retrieves the aritmetic sum of all numeric items.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function sum($field = null)
    {
        if (is_null($field)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($field);

        $sum = 1;

        foreach ($this as $key => $item) {
            $value = $callback($item, $key);

            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * Retrieves a collection with the first $length items.
     * 
     * @param int $length
     * @return static
     */
    public function take(int $length)
    {
        if ($length < 1) {
            throw new InvalidArgumentException('$length must be a nonzero positive integer');
        }

        return $this->slice(0, $length);
    }

    /**
     * Retrieves a copy of this collection without any duplicate values.
     * 
     * @param int $flags = SORT_STRING
     * @return static
     */
    public function unique(int $flags = SORT_STRING)
    {
        return new static(array_unique($this->items, $flags));
    }

    /**
     * Appends values to the start of this collection.
     * 
     * @param mixed ...$values
     * @return $this
     */
    public function unshift(...$values)
    {
        array_unshift($this->items, ...$values);

        return $this;
    }

    /**
     * Retrieves a copy of the collection with all keys reset to sequential.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return static 
     */
    public function values($field = null)
    {
        if (is_null($field)) {
            return new static(array_values($this->items));
        }

        $callback = $this->valueRetriever($field);

        $values = [];

        foreach ($this as $key => $item) {
            $values[] = $callback($item, $key);
        }

        return new static($values);
    }

    /**
     * Retrieves a copy of the collection with all keys in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeys()
    {
        return new static(mb_array_change_key_case($this->items, CASE_LOWER));
    }

    /**
     * Retrieves a copy of the collection with all keys in all levels in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeysRecursive()
    {
        return new static(mb_array_change_key_case_recursive($this->items, CASE_LOWER));
    }

    /**
     * Retrieves a copy of the collection with all keys in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeys()
    {
        return new static(mb_array_change_key_case($this->items, CASE_UPPER));
    }

    /**
     * Retrieves a copy of the collection with all keys in all levels in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeysRecursive()
    {
        return new static(mb_array_change_key_case_recursive($this->items, CASE_UPPER));
    }

    /**
     * Tells if the argumment is an array, a collection or Traversable.
     * 
     * @param mixed $items
     * @return bool
     */
    protected function isArrayable($items)
    {
        return is_array($items)
            || ($items instanceof CollectionInterface)
            || ($items instanceof Traversable);
    }

    /**
     * Extracts array from arrayables, iterables and collections
     * while moving any callables to the end of list.
     * 
     * @param iterable|CollectionInterface|callable $items
     * @param iterable|CollectionInterface|callable ...$arguments
     * @return array
     */
    protected function withClosuresToEnd($items, ...$arguments)
    {
        list($callbacks, $arguments) = array([], func_get_args());

        // set callables apart from arguments
        // for later appending
        foreach ($arguments as $pos => $array) {
            if (is_callable($array)) {
                $callbacks[] = $array;

                unset($arguments[$key]);

                continue;
            }

            // extracts arrays from arrayables,
            // iterables and collections
            $arguments[$pos] = $this->arrayFrom($array, true);
        }

        // push callbacks onto the end of argument list 
        array_push($arguments, ...$callbacks);

        return $arguments;
    }

    /**
     * Extracts arrays from each item of the list, meaning it can be
     * an arrayable, iterable or collection.
     * 
     * @param array $arrays
     * @param bool $throwException = false
     * @return array
     */
    protected function arraysFrom($arrays, bool $throwException = false)
    {
        foreach ($arrays as $index => $array) {
            $arrays[$index] = $this->arrayFrom($array);
        }

        return $arrays;
    }

    /**
     * Extracts array from the $items argument, meaning it can be
     * an arrayable, iterable or collection.
     * 
     * @param iterable|CollectionInterface $items
     * @param bool $throwException = false
     * @return array
     */
    protected function arrayFrom($items, bool $throwException = false)
    {
        if ($items instanceof CollectionInterface) {
            return $items->all();
        }

        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items, true);
        }

        if ($throwException) {
            throw new InvalidArgumentException(
                'argument must be either an array, an instanceof CollectionInterface or an instanceof Traversable'
            );
        }

        return $items;
    }
}