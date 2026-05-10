<?php
namespace Collei\Collections;

use Closure;
use Traversable;

interface CollectionInterface
{
	/**
	 * Leverages certain actions to a high order proxy.
	 * 
	 * @param string $name Name of the method
	 * @return HighOrderCollectionProxy
	 */
	public function __get(string $name);

	/**
	 * Converts to string.
	 * 
	 * @return string
	 */
	public function __toString();

	/**
	 * Crafts a brand new craft collection.
	 * 
	 * @static
	 * @return static
	 */
	public static function empty();

	/**
	 * Crafts a brand new collection from the argument.
	 * 
	 * @static
	 * @param mixed $items
	 * @return static
	 */
	public static function make($items);

    /**
     * Crafts a brand new collection using range() function.
     * 
     * @param int $start
     * @param int $end
     * @param int $step = 1
     * @return static
     */
    public static function range(int $start, int $end, int $step = 1);

	/**
	 * Returns a brand new collection with zero or $number items a value
	 * defined by the $callback (or by range() if $callback is null or not provided).
	 * 
	 * @static
	 * @param int $number
	 * @param callable $callback = null
	 * @return static
	 */
	public static function times(int $number, callable $callback = null);

	/**
	 * Unwraps the underlying array from the instance if one.
	 * 
	 * @static
	 * @param mixed $value
	 * @return array
	 */
	public static function unwrap($value);

	/**
	 * Wraps the value into a brand new collection if needed.
	 * 
	 * @static
	 * @param mixed $value
	 * @return static
	 */
	public static function wrap($value);

	/**
	 * Returns the item that comes after $value, if $value exists.
	 * Returns null if $value is not found or it is the last.
	 * 
	 * @param mixed $value
	 * @param bool $strict = false
	 * @return mixed
	 */
	public function after($value, bool $strict = false);

	/**
	 * Returns the underlying array.
	 * 
	 * @return array
	 */
	public function all();

	/**
	 * Returns the average value of items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function average(string|Closure $callback = null);

	/**
	 * Returns the average value of items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function avg(string|Closure $callback = null);

	/**
	 * Returns the item that comes before $value, if $value exists.
	 * Returns null if $value is not found or it is the first.
	 * 
	 * @param mixed $value
	 * @param bool $strict = false
	 * @return mixed
	 */
	public function before($value, bool $strict = false);

	/**
	 * Returns a collection of chunks of this collection.
	 * 
	 * @param int $size
	 * @param bool $preserveKeys = false
	 * @return static
	 */
	public function chunk(int $size, bool $preserveKeys = false);

	/**
	 * Returns a collection of chunks of this collection,
	 * custom crafted by $callback.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function chunkWhile(Closure $callback);

	/**
	 * Collapses a collection of collections in a plain collection.
	 * 
	 * @return static
	 */
	public function collapse();

	/**
	 * Obtains an eager collection.
	 * 
	 * @return self
	 */
	public function collect();

	/**
	 * Combine this collection with an array or another collection,
	 * generating a brand new collection with the items of this collection
	 * as keys and the elements of the given array or collection as values.
	 * 
	 * @param array|static $values
	 * @return static
	 */
	public function combine($values);

	/**
	 * Returns a collection resulting from the concat of items of this
	 * collection with elements of the given array or collection.
	 * 
	 * @param array|static $values
	 * @return static
	 */
	public function concat($values);

	/**
	 * Tells if the collection has any items according the criteria.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return bool
	 */
	public function contains($key, $operator = null, $value = null);

	/**
	 * Tells strictly if the collection has any item with such value.
	 * 
	 * @param string|Closure $key
	 * @param mixed $value = null
	 * @return bool
	 */
	public function containsStrict($key, $value = null);

	/**
	 * Returns the nuumber of items.
	 * 
	 * @return int
	 */
	public function count(): int;

	/**
	 * Returns the number of itens in each group, according the passed
	 * callback or given value key.
	 * 
	 * @param string|Closure $callback = null
	 * @param bool $silentMode = false
	 * @return static
	 */
	public function countBy($callback = null, bool $silentMode = false);

	/**
	 * Returns all possible combinations of items of this collection
	 * with the given items.
	 * 
	 * @param Arrayable|array $items
	 * @return static
	 */
	public function crossJoin(Arrayable|array $items);

	/**
	 * Dumps the collection items and stops execution.
	 * 
	 * @return never
	 */
	public function dd();

	/**
	 * Return items without such given values.
	 * 
	 * @param Arrayable|iterable $items
	 * @return static
	 */
	public function diff(Arrayable|iterable $items);

	/**
	 * Return items without such given key-value pairs.
	 * 
	 * @param Arrayable|iterable $items
	 * @return static
	 */
	public function diffAssoc(Arrayable|iterable $items);

	/**
	 * Return items not present among such $items using a $callback test.
	 * 
	 * @param Arrayable|iterable $items
	 * @param Closure $callback
	 * @return static
	 */
	public function diffAssocUsing(Arrayable|iterable $items, Closure $callback);

	/**
	 * Return items without such given keys.
	 * 
	 * @param Arrayable|iterable $items
	 * @return static
	 */
	public function diffKeys(Arrayable|iterable $items);

	/**
	 * Return items whose keys are not present among such $keys using a $callback test.
	 * 
	 * @param Arrayable|iterable $items
	 * @param Closure $callback
	 * @return static
	 */
	public function diffKeysUsing(Arrayable|iterable $items, Closure $callback);

	/**
	 * Return items without such given values.
	 * 
	 * @param Arrayable|iterable $items
	 * @param Closure $callback
	 * @return static
	 */
	public function diffUsing(Arrayable|iterable $items, Closure $callback);

	/**
	 * Tells if the collection has no items according the criteria.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return bool
	 */
	public function doesntContain($key, $operator = null, $value = null);

	/**
	 * Dumps the collection items.
	 * 
	 * @return void
	 */
	public function dump();

	/**
	 * Return a collection of duplicate items from this collection.
	 * 
	 * @param string|Closure $callback = null
	 * @param bool $strict = false
	 * @return static
	 */
	public function duplicates(string|Closure $callback = null, bool $strict = false);

	/**
	 * Return a collection of duplicate items from this collection.
	 * 
	 * @param string|Closure $callback = null
	 * @return static
	 */
	public function duplicatesStrict(string|Closure $callback = null);

	/**
	 * Runs the callback for each $item of the collection while passing
	 * the item and its key as arguments.
	 * Inside the closure, return false to stop iteration. 
	 * 
	 * @param Closure $callback
	 * @return $this
	 */
	public function each($callback);

	/**
	 * Runs the callback for each $item of the collection while passing
	 * every item's members/properties as separate arguments.
	 * Inside the closure, return false to stop iteration. 
	 * 
	 * @param Closure $callback
	 * @param bool $ignoreNonConformingItems = false
	 * @return $this
	 * @throws Collei\Collections\Exceptions\CollectionException 
	 * 		unless $ignoreNonConformingItems = true
	 */
	public function eachSpread(Closure $callback, bool $ignoreNonConformingItems = false);

	/**
	 * Returns a collection with alternate items picked every $step position
	 * starting at $offset.
	 * 
	 * @param int|Closure $step
	 * @param int $offset = 0
	 * @return static
	 */
	public function every($step, int $offset = 0);

	/**
	 * Return all items of this collection except the given keys.
	 * 
	 * @param array|static $keys / string ...$keys
	 * @return static
	 */
	public function except($keys);

	/**
	 * Filters the items using $callback, returning a new collection.
	 *
	 * @param Closure $callback
	 * @return static
	 */
	public function filter(Closure $callback);

	/**
	 * Returns the very first item in the collection that passes the
	 * $callback test, or the first if no callback is given.
	 * 
	 * @param Closure $callback = null
	 * @param mixed $default = null
	 * @return mixed
	 */
	public function first(Closure $callback = null, $default = null);

	/**
	 * Returns the very first item in the collection that passes the
	 * $callback test, or the first if no callback is given.
	 * If no item is found, throws an exception.
	 * 
	 * @param Closure $callback = null
	 * @param mixed $default = null
	 * @return mixed
	 * @throws Collei\Collections\Exceptions\ItemNotFoundException
	 */
	public function firstOrFail(Closure $callback = null);

	/**
	 * Returns the first result (if any) of a where operation.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function firstWhere($key, $operator = null, $value = null);

	/**
	 * Equivalent of $collection->map($callback)->collapse().
	 * 
	 * @param callable $callback
	 * @return static
	 */
	public function flatMap(callable $callback);

	/**
	 * Flatten a multidimensional array to an array with keys in dot notation.
	 * 
	 * @return static
	 */
	public function flatten();

	/**
	 * Return a collection with the keys as values and the values as keys.
	 * 
	 * @return static
	 */
	public function flip();

	/**
	 * Returns a collection with $perPage items selected from a
	 * 'so called' collection $page.
	 * 
	 * @param int $page
	 * @param int $perPage
	 * @return static
	 */
	public function forPage(int $page, int $perPage);

	/**
	 * Retrieves an item by its key, if any.
	 * 
	 * @param int|string $key
	 * @param mixed $default = null
	 * @return mixed
	 */
	public function get(int|string $key, $default = null);

	/**
	 * Returns a instance of CachingIterator for this instance.
	 * 
	 * @param int $flags = CachingIterator::CALL_TOSTRING
	 * @return \CachingIterator
	 */
	public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING);

	/**
	 * Obtains an iterator for the collection.
	 * 
	 * @return Traversable
	 */
	public function getIterator(): Traversable;

	/**
	 * Groups items by key or using $callback, returning a new
	 * collection.
	 * 
	 * @param string|Closure $callback = null
	 * @return static
	 */
	public function groupBy($callback = null);

	/**
	 * Tells if a given item exists.
	 * 
	 * @param int|string $key
	 * @return bool
	 */
	public function has(int|string $key);

	/**
	 * Tells if one of the given items does exist.
	 * 
	 * @param array $keys
	 * @return bool
	 */
	public function hasAny(array $keys);

	/**
	 * Returns if this collection has two or more items according
	 * to a where operation.
	 *  
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return bool
	 */
	public function hasMany($key, $operator = null, $value = null);

	/**
	 * Returns if this collection has just one and only item according
	 * to a where operation.
	 *  
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return bool
	 */
	public function hasSole($key, $operator = null, $value = null);

	/**
	 * Returns the collection items as a string with an optional $glue.
	 * 
	 * @param string|Closure $value
	 * @param string $glue = null
	 * @return string
	 */
	public function implode(string|Closure $value, string $glue = null);

	/**
	 * Returns the intersection of this collection with the given $items.
	 * 
	 * @param iterable|Arrayable $items
	 * @return static
	 */
	public function intersect(iterable|Arrayable $items);

	/**
	 * Returns the intersection of this collection with the given $items while
	 * preserving keys.
	 * 
	 * @param iterable|Arrayable $items
	 * @return static
	 */
	public function intersectAssoc(iterable|Arrayable $items);

	/**
	 * Returns the intersection of this collection with the given $items
	 * mediated by $callback.
	 * 
	 * @param iterable|Arrayable $items
	 * @param callable $callback
	 * @return static
	 */
	public function intersectUsing(iterable|Arrayable $items, callable $callback);

	/**
	 * Returns the intersection of this collection with the given $items
	 * mediated by $callback while preserving keys.
	 * 
	 * @param iterable|Arrayable $items
	 * @param callable $callback
	 * @return static
	 */
	public function intersectAssocUsing(iterable|Arrayable $items, callable $callback);

	/**
	 * Returns the intersection of this collection with the given $items by their keys.
	 * 
	 * @param iterable|Arrayable $items
	 * @return static
	 */
	public function intersectByKeys(iterable|Arrayable $items);

	/**
	 * Tells if the collection is empty.
	 * 
	 * @return bool
	 */
	public function isEmpty();

	/**
	 * Tells if the collection is NOT empty.
	 * 
	 * @return bool
	 */
	public function isNotEmpty();

	/**
	 * Performs a string join() operation.
	 * 
	 * @param string $glue
	 * @param string $final = null
	 * @return string
	 */
	public function join(string $glue, string $final = null);

	/**
	 * Retruns an array ready for JSON serialization.
	 * 
	 * @return array
	 */
	public function jsonSerialize();

	/**
	 * Return a collection with the given field value as key.
	 * 
	 * @param string|Closure $key
	 * @return static
	 */
	public function keyBy(string|Closure $key);

	/**
	 * Returns a new collection with the keys from this instance.
	 * 
	 * @return static
	 */
	public function keys();

	/**
	 * Returns the last item of the collection, if any.
	 * 
	 * @param Closure|null $callback
	 * @param mixed default
	 * @return mixed
	 */
	public function last(Closure $callback = null, $default = null);

	/**
	 * Performs map() on this collection items, returning the
	 * resulting collection.
	 * 
	 * @param callable $callback
	 * @return static
	 */
	public function map(callable $callback);

	/**
	 * Map the collection items to instances of the given class.
	 * 
	 * @return static
	 */
	public function mapInto(string $class);

	/**
	 * Map the collection items while making their members/properties
	 * available to the $callback as separate arguments.
	 * 
	 * @param Closure $callback
	 * @param bool $ignoreNonConformingItems = false
	 * @return static
	 * @throws Collei\Collections\Exceptions\CollectionException 
	 * 		unless $ignoreNonConformingItems = true
	 */
	public function mapSpread(Closure $callback, bool $ignoreNonConformingItems = false);

	/**
	 * Performs a dictionary map on the colleciton.
	 * Make sure $callback returns a pair, i.e., a single
	 * array element in the form [key => value], so
	 * this method may work properly.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function mapToDictionary(Closure $callback);

	/**
	 * Groups items according to $callback instructions.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function mapToGroups(Closure $callback);
	
	/**
	 * Performs a map() operation, including the keys in the arguments.
	 * 
	 * @param Closure $callback
	 * @return static 
	 */
	public function mapWithKeys(Closure $callback);

	/**
	 * Returns the maximum item of all.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function max($callback = null);

	/**
	 * Returns the median of the items in this collection.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
    public function median($callback = null);

	/**
	 * Returns a new collection with the itens from this collection merged
	 * with the given $items using array_merge().
	 * 
	 * @param array|static
	 * @return static
	 */
	public function merge($items);

	/**
	 * Returns a new collection with the itens from this collection merged
	 * with the given $items using array_merge_recursive().
	 * 
	 * @param array|static
	 * @return static
	 */
	public function mergeRecursive($items);

	/**
	 * Returns the minimum item of all.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function min($callback = null);

	/**
	 * Returns the mode of the items in this collection.
	 * 
	 * @param string|Closure $key = null
	 * @return array
	 */
    public function mode($key = null);

	/**
	 * Returns a new collection with every nth item of the collection.
	 * 
	 * @param int $step
	 * @param int $offset = 0
	 * @return static
	 */
	public function nth(int $step, int $offset = 0);

	/**
	 * Returns only the items corresponding to the given keys
	 * and produces a new collection.
	 * 
	 * @param array $keys
	 * @return static
	 */
	public function only(array $keys);

	/**
	 * Pads a collection to the given size with an optional value.
	 * 
	 * @param int $size
	 * @param mixed $value = null
	 * @return static
	 */
	public function pad(int $size, $value = null);

	/**
	 * Split a collection in two according to the $callback instructions.
	 * 
	 * @param Closure $callback
	 * @return array
	 */
	public function partition(Closure $callback);

	/**
	 * Runs the callback passing the collection as argument.
	 * 
	 * @param Closure $callback
	 * @return mixed
	 */
	public function pipe(Closure $callback);

	/**
	 * Retrieves a instance of the given class created by passing the
	 * collection as the constructor argument.
	 * 
	 * @param string $class
	 * @return mixed
	 */
	public function pipeInto(string $class);

	/**
	 * Send collection items through the callback pipes.
	 * 
	 * @param array $pipes
	 * @return static
	 */
	public function pipeThrough(array $pipes);

	/**
     * Pluck an array of values from the collection.
     *
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     * @return array
     */
	public function pluck($value, $key = null);

	/**
	 * Picks $number items randomly from the collection and returns
	 * the result as a new collection. For just an only item, returns it.
	 * It relies on random_int for randomness.
	 * 
	 * @param int $number = null
	 * @param bool $preserveKeys = false
	 * @return mixed|static 
	 */
	public function random($number = null, bool $preserveKeys = false);

	/**
	 * Applies a callback in order to return a single value.
	 * 
	 * @param callable $callback
	 * @param mixed $initial = null
	 * @return mixed
	 */
	public function reduce(callable $callback, $initial = null);

	/**
	 * Applies a callback in order to return multiple values at once.
	 * 
	 * @param callable $callback
	 * @param mixed ...$initial
	 * @return array
	 */
	public function reduceSpread(callable $callback, ...$initial);

	/**
	 * Returns a collection without the items 'filtered' by the $callback.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function reject(Closure $callback);

	/**
	 * Returns a copy of this collection with the items replaced by
	 * these given ones.
	 * 
	 * @param Arrayable|array $items
	 * @return static
	 */
	public function replace(Arrayable|array $items);

	/**
	 * Returns a copy of this collection with the items recursively
	 * replaced by these given ones.
	 * 
	 * @param Arrayable|array $items
	 * @return static
	 */
	public function replaceRecursive(Arrayable|array $items);

	/**
	 * Returns a new collection with all items reversed.
	 * 
	 * @return static
	 */
	public function reverse();

	/**
	 * Runs a search for the $value through the collection items
	 * and returns its key, if it is found.
	 * 
	 * @param mixed $value
	 * @param bool $strict = false
	 * @return int|string|null
	 */
	public function search($value, bool $strict = false);

	/**
	 * Returns a new collection with all items shuffled.
	 * It relies on random_int().
	 * 
	 * @return static
	 */
	public function shuffle();

	/**
	 * Returns a new collection with $count items skept.
	 * 
	 * @param int $count
	 * @return static
	 */
	public function skip(int $count);

	/**
	 * Skips items until condition becomes true.
	 * 
	 * @param mixed $value
	 * @return static
	 */
	public function skipUntil($value);

	/**
	 * Skips items until condition becomes false.
	 * 
	 * @param mixed $value
	 * @return static
	 */
	public function skipWhile($value);

	/**
	 * Extracts a slice of the collection and returns it as collection.
	 * 
	 * @param int $offset
	 * @param int $length = null
	 * @param bool $preserveKeys = false
	 * @return static
	 */
	public function slice(int $offset, int $length = null, $preserveKeys = false);

	/**
	 * Returns a collection of chunks resulting from a 'sliding' window.
	 * 
	 * @param int $size
	 * @param int $step = 1
	 * @return static
	 */
	public function sliding(int $size, int $step = 1);

	/**
	 * Returns the only item of collection, throwing exception if
	 * there is more than one or when no items found.
	 * 
	 * @param mixed $key = null
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return mixed
	 * @throws Collei\Collections\Exceptions\ItemNotFoundException
	 * @throws Collei\Collections\Exceptions\MultipleItemsFoundException
	 */
	public function sole($key = null, $operator = null, $value = null);

	/**
	 * Returns if this collection has at least one item according
	 * to a where operation.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return bool
	 */
	public function some($key, $operator = null, $value = null);

	/**
	 * Return the sorted version of the collection.
	 * 
	 * @param mixed $callback = null
	 * @return static
	 */
	public function sort($callback = null);

	/**
	 * Return the sorted version of the collection.
	 * 
	 * @param mixed $callback = null
	 * @param int $options = SORT_REGULAR
	 * @param bool $descending = false
	 * @return static
	 */
	public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false);

	/**
	 * Return the descending-order-sorted version of the collection
	 * using a callback
	 * 
	 * @param mixed $callback = null
	 * @param int $options = SORT_REGULAR
	 * @return static
	 */
	public function sortByDesc($callback, $options = SORT_REGULAR);

	/**
	 * Return the desc-sorted version of the collection.
	 * 
	 * @param int $options = SORT_REGULAR
	 * @return static
	 */
	public function sortDesc(int $options = SORT_REGULAR);

	/**
	 * Return the sorted-by-key version of the collection.
	 * 
	 * @param mixed $callback = null
	 * @param int $options = SORT_REGULAR
	 * @param bool $descending = false
	 * @return static
	 */
	public function sortKeys(int $options = SORT_REGULAR, bool $descending = false);

	/**
	 * Return the descending-order sorted-by-key version of the collection.
	 * 
	 * @param mixed $callback = null
	 * @param int $options = SORT_REGULAR
	 * @param bool $descending = false
	 * @return static
	 */
	public function sortKeysDesc(int $options = SORT_REGULAR);

	/**
	 * Return the sorted-by-key version of the collection using a callback.
	 * 
	 * @param mixed $callback = null
	 * @param int $options = SORT_REGULAR
	 * @param bool $descending = false
	 * @return static
	 */
	public function sortKeysUsing(callable $callback);

	/**
	 * Splits a collection in the given number of chunks.
	 * 
	 * @param int $numberOfGroups
	 * @return static
	 */
	public function split(int $numberOfGroups);

	/**
	 * Splits a collection into chunks.
	 * 
	 * @param int $numberOfGroups
	 * @return static
	 */
	public function splitIn(int $numberOfGroups);

	/**
	 * Returns the sum of all items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function sum($callback = null);

	/**
	 * Returns a new collection with only $limit items from
	 * the beginning.
	 * 
	 * @param int $limit
	 * @return static
	 */
	public function take(int $limit);

	/**
	 * Takes items until condition becomes true.
	 * 
	 * @param mixed $value
	 * @return static
	 */
	public function takeUntil($value);

	/**
	 * Takes items until condition becames false.
	 * 
	 * @param mixed $value
	 * @return static
	 */
	public function takeWhile($value);

	/**
	 * Calls the callback passing this collection and returns the collection.
	 * 
	 * @param Closure $callback
	 */
	public function tap(Closure $callback);

	/**
	 * Returns the underlaying array items.
	 * 
	 * @return array
	 */
	public function toArray();

	/**
	 * Retruns the contents of collection as a JSON string.
	 * 
	 * @return string
	 */
	public function toJson();

	/**
	 * Retruns the contents of collection as a pretty-formatted
	 * JSON string.
	 * 
	 * @return string
	 */
	public function toPrettyJson();

	/**
	 * Expands a dot notation array in a multidimensional array.
	 * 
	 * @return static
	 */
	public function undot();

	/**
	 * Return a collection resulted from union of items
	 * of this collection with the given $items.
	 * 
	 * @param iterable $items
	 * @return static
	 */
	public function union(iterable $items);

	/**
	 * Return true if a given value is unique in this collection.
	 * 
	 * @param string|Closure|null $key
	 * @param bool $strict = false
	 * @return bool  
	 */
	public function unique($key = null, bool $strict = false);

	/**
	 * Return true if a given value is strictly unique in this collection.
	 * 
	 * @param string|Closure|null $key
	 * @return bool  
	 */
	public function uniqueStrict($key = null);

	/**
	 * If $condition evaluates to false, runs the callable passing the collection
	 * to it as parameter.
	 * If a second callable is provided, when $condition evaluates to true,
	 * it will be run receiving this collection as parameter.
	 * The results of any of callables will be wrapped in a brand new collection.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function unless(bool $condition, callable $callback, callable $default = null);

	/**
	 * Runs the unless method with isEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function unlessEmpty(callable $callback, callable $default = null);

	/**
	 * Runs the unless method with isNotEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function unlessNotEmpty(callable $callback, callable $default = null);

	/**
	 * Returns a new collection with all keys reset.
	 * 
	 * @return static
	 */
	public function values();

	/**
	 * If $condition evaluates to true, runs the callable passing the collection
	 * to it as parameter.
	 * If a second callable is provided, when $condition evaluates to false,
	 * it will be run receiving this collection as parameter.
	 * The results of any of callables will be wrapped in a brand new collection.
	 * 
	 * @param bool $condition
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function when(bool $condition, callable $callback, callable $default = null);

	/**
	 * Runs the when method with isEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function whenEmpty(callable $callback, callable $default = null);

	/**
	 * Runs the when method with isNotEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function whenNotEmpty(callable $callback, callable $default = null);

	/**
	 * Performs a where operation and returns its result as a new collection.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function where($key, $operator = null, $value = null);

	/**
	 * Filters the collection, returning values that are in the range.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereBetween($key, $values);

	/**
	 * Filters the collection, returning values that exists among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @param bool $strict = false
	 * @return static
	 */
	public function whereIn($key, $values, bool $strict = false);

	/**
	 * Filters the collection, returning items that are instances of
	 * one of the given classes.
	 * 
	 * @param string|array $class
	 * @return static
	 */
	public function whereInstanceOf(string|array $class);

	/**
	 * Filters the collection, returning values that strictly exists
	 * among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereInStrict($key, $values);

	/**
	 * Filters the collection, returning values that are NOT in the range.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereNotBetween($key, $values);

	/**
	 * Filters the collection, returning values that NOT exists among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @param bool $strict = false
	 * @return static
	 */
	public function whereNotIn($key, $values, $strict = false);

	/**
	 * Filters the collection, returning values that strictly NOT exists
	 * among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereNotInStrict($key, $values);

	/**
	 * Performs a where($key, '!==', $value) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @return static
	 */
	public function whereNotNull($key = null);
	
	/**
	 * Performs a where($key, '===', null) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @return static
	 */
	public function whereNull($key = null);

	/**
	 * Performs a where($key, '===', $value) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @param mixed $value
	 * @return static
	 */
	public function whereStrict($key, $value);

	/**
	 * Performs zip() on this collection with the given $items,
	 * returning the resulting collection.
	 * 
	 * @param iterable $items
	 * @return static
	 */
	public function zip(iterable $items);
}