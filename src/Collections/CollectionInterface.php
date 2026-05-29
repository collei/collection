<?php
namespace Collei\Collections;

use Closure;
use IteratorAggregate;
use Traversable;

/**
 * Common methods for collections.
 */
interface CollectionInterface extends IteratorAggregate
{
    /**
     * Retrieve an external iterator or traversable.
     * 
     * @return ArrayIterator
     */
    public function getIterator(): Traversable;
    
    /**
     * Checks if all array elements satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function all(callable $callback);

    /**
     * Checks if any array element satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function any(callable $callback);

        /**
     * Retrieves the average value of the collection.
     * If the field argument is given, retrieves the average value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function average($field = null);

    /**
     * Retrieves the average value of the collection.
     * If the field argument is given, retrieves the average value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function avg($field = null);

    /**
     * Produces a copy of this collection, split into chunks,
     * and returns a collection of these chunks.
     * 
     * @param int $length
     * @param bool $preserveKeys = false
     * @return static
     */
    public function chunk(int $length, bool $preserveKeys = false);

    /**
     * Return a new collection with values from a single column
     * in the collection.
     * 
     * @param int|string|callable $columnKey
     * @param int|string|callable $indexKey = null
     * @return static
     */
    public function column($columnKey, $indexKey = null);

    /**
     * Return a new collection with the given array keyed by the
     * values of this colelction.
     * 
     * @param iterable|CollectionInterface $items = []
     * @return static
     */
    public function combine($items = []);

    /**
     * Return a new collection with the current collection items
     * and keyed by the given array values.
     * 
     * @param iterable|CollectionInterface $items = []
     * @return static
     */
    public function combineTo($items = []);

    /**
     * Retrieve the items count.
     * 
     * @return int
     */
    public function count();

    /**
     * Retrieves a new collection with the Counts of each
     * distinct value in this collection.
     * 
     * @param bool $strict = false
     * @return static
     */
    public function countValues(bool $strict = false);

    /**
     * Retrieves a new collection with the Counts of each strictly
     * distinct value in this collection.
     * 
     * @return static
     */
    public function countValuesStrict();

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), returning it as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diff($array, ...$arrays);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), with additional index check, and
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diffAssoc($array, ...$arrays);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), with additional index check
     * performed by a callback, returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function diffAssocUsing($items, ...$arguments);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), using keys for comparison,
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diffKeys($items, ...$arrays);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), using a callback on the keys for
     * comparison, returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function diffKeysUsing($items, ...$arguments);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), using a callback on the values for
     * comparison, returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function diffUsing($items, ...$arguments);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), with additional index check, by
     * using a callback on the values for comparison, returning
     * the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function diffUsingAssoc($items, ...$arguments);

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), with additional index check, by
     * using a callback on the values and another callback on the
     * keys for comparison, returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $valueCallback
     * @param callable $keyCallback
     * @return static
     */
    public function diffUsingAssocUsing($items, ...$arguments);

    /**
     * Checks if a value exists in this collection.
     * 
     * @param mixed $value
     * @param bool $strict = false
     * @return bool
     */
    public function exists($value, bool $strict = false);

    /**
     * Checks strictly if a value exists in this collection.
     * 
     * @param mixed $value
     * @return bool
     */
    public function existsStrict($value);

    /**
     * Returns a new collection indexed by the kesy of this
     * collection and using $value as item values.
     * 
     * @param mixed $value = null
     * @return static   
     */
    public function fillKeys($value = null);

    /**
     * Filters elements from this collection using the given
     * callback function, and returns the resulting collection.
     * 
     * @param Closure $callback
     * @return static
     */
    public function filter(Closure $callback);

    /**
     * Filters elements from this collection using the given
     * callback function, passing the key as second argument to
     * the callback, and returns the resulting collection.
     * 
     * @param Closure $callback
     * @return static
     */
    public function filterWithKeys(Closure $callback);

    /**
     * Returns the first element satisfying the given callback.
     * 
     * @param Closure $callback
     * @return mixed
     */
    public function find(Closure $callback);

    /**
     * Returns the key of the first element satisfying the given
     * callback function.
     * 
     * @param Closure $callback
     * @return int|string|null
     */
    public function findKey(Closure $callback);

    /**
     * Gets the first value of this collection.
     * 
     * @return mixed
     */
    public function first();

    /**
     * Gets the first key of this collection.
     * 
     * @return int|string|null
     */
    public function firstKey();

    /**
     * Returns a collection with all values as keys and vice-versa.
     * 
     * @return static
     */
    public function flip();

    /**
     * Tells whether the given key exists in this collection.
     * 
     * @param int|string $key
     * @return bool
     */
    public function hasKey($key);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersect($items, ...$arrays);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, with additional index check, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersectAssoc($items, ...$arrays);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, with additional index check, using $callback for
     * index comparison, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function intersectAssocUsing($items, ...$arguments);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, using keys for comparison, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersectKeys($items, ...$arrays);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, using keys for comparison through the given
     * $callback, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function intersectKeysUsing($items, ...$rest);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, using the given $callback for value comparison,
     * and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function intersectUsing($items, ...$arguments);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, with additional index check, using the given
     * $callback for value comparison, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $callback
     * @return static
     */
    public function intersectUsingAssoc($items, ...$arguments);

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, with additional index check, using the first given
     * $valueCallback for value comparison and the second given
     * $keyCallback for key comparison, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @param callable $valueCallback
     * @param callable $keyCallback
     * @return static
     */
    public function intersectUsingAssocUsing($items, ...$arguments);

    /**
     * Return the last item of collection.
     * 
     * @return mixed
     */
    public function last();

    /**
     * Return the key of the last item of collection.
     * 
     * @return int|string|null
     */
    public function lastKey();

    /**
     * Return a copy of this collection, but keyed by the
     * value specified by $field.
     * 
     * @param int|string|callable $field
     * @return static
     */
    public function keyBy($field);

    /**
     * Return a collection with all keys of this collection.
     * 
     * @return static
     */
    public function keys();

    /**
     * Applies the callback to the elements of this collection,
     * returning the result as a new collection.
     * If the callback accepts two arguments, the item Key will
     * be available as the second argument.
     * 
     * @param Closure $callback
     * @return static
     */
    public function map(Closure $callback);

    /**
     * Retrieves the maximum value of the collection.
     * If the field argument is given, retrieves the maximum value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function max($field = null);

    /**
     * Calculates the median of the collection.
     * 
     * If the collection length is even and at least one of the elements is
     * not numeric, returns an array with both elements.
     * 
     * @param string|callable $field = null
     * @return mixed
     */
    public function median($field = null);

    /**
     * Merges this collection with the given array(s) or collection(s),
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     * @throws InvalidArgumentException when at least one argument is not an iterable
     *      or a CollectionInterface
     */
    public function merge($array, ...$arrays);

    /**
     * Recursively merges this collection with the given array(s)
     * or collection(s), returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     * @throws InvalidArgumentException when at least one argument is not an iterable
     *      or a CollectionInterface
     */
    public function mergeRecursive($array, ...$arrays);

    /**
     * Retrieves the minimum value of the collection.
     * If the field argument is given, retrieves the minimum value from
     * the given subkey (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function min($field = null);

    /**
     * Calculates the mode of the collection.
     * 
     * @param string|callable $field = null
     * @return mixed
     */
    public function mode($field = null);

    /**
     * Returns a copy of the collection, but padded to $length with
     * nulls or the given $value if provided.
     * 
     * @param int $length
     * @param mixed $value = null
     * @return static  
     */
    public function pad(int $length, $value = null);

    /**
     * Appends values to the start of this collection.
     * 
     * @param mixed ...$values
     * @return $this
     */
    public function prepend(...$values);

    /**
     * Retrieves the aritmetic product of all numeric values.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function product($field = null);

    /**
     * Iteratively reduce the collection to a single value using
     * a callback function.
     * 
     * @param Closure $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(Closure $callback, $initial = null);

    /**
     * Replaces elements from passed arrays or collections into
     * a copy of this collection and returns the resulting collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function replace($array, ...$arrays);

	/**
	 * Searches the collection for a given value and returns
     * the first corresponding key if successful.
	 *
	 * @param mixed $needle
     * @param bool $strict = false
	 * @return int|string|false
	 */
    public function search($needle, bool $strict = false);

	/**
	 * Searches strictly the collection for a given value and
     * returns the first corresponding key if successful.
	 *
	 * @param mixed $needle
	 * @return int|string|false
	 */
    public function searchStrict($needle);

    /**
     * Retrieves a collection without the first $count items.
     * 
     * @param int $count
     * @return static
     */
    public function skip(int $count);

    /**
     * Returns a collection from a slice of this collection.
     * 
     * @param int $offset
     * @param ?int $length = null
     * @return static
     */
    public function slice(int $offset, ?int $length = null);

    /**
     * Remove a portion of the collection and replace it with $replacement.
     * 
     * @param int $offset
     * @param ?int $length = null
     * @param iterable|CollectionInterface $replacement = []
     * @return static
     */
    public function splice(int $offset, ?int $length = null, $replacement = []);

    /**
     * Retrieves the aritmetic sum of all numeric items.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return int|float
     */
    public function sum($field = null);

    /**
     * Retrieves a collection with the first $length items.
     * 
     * @param int $length
     * @return static
     */
    public function take(int $length);

    /**
     * Retrieves a copy of this collection without any duplicate values.
     * 
     * @param int $flags = SORT_STRING
     * @return static
     */
    public function unique(int $flags = SORT_STRING);

    /**
     * Returns a copy of this collection with values appended
     * before the original ones.
     * 
     * @param mixed ...$values
     * @return static
     */
    public function unshift(...$values);

    /**
     * Retrieves a copy of the collection with all keys reset to sequential.
     * If the field argument is given, retrieves values from a specific subkey
     * in each item (e.g., database results).
     * 
     * @param int|string|callable $field = null
     * @return static 
     */
    public function values($field = null);

    /**
     * Retrieves a copy of the collection with all keys in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeys();

    /**
     * Retrieves a copy of the collection with all keys in all levels in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeysRecursive();

    /**
     * Retrieves a copy of the collection with all keys in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeys();

    /**
     * Retrieves a copy of the collection with all keys in all levels in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeysRecursive();
}