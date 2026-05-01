<?php
namespace Collei\Collections;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use Closure;
use ArgumentCountError;
use InvalidArgumentException;
use Collei\Collections\Traits\HasArrayAccess;
use Collei\Collections\Traits\EnumeratesValues;
use Collei\Collections\Exceptions\CollectionException;
use Collei\Collections\Exceptions\ItemNotFoundException;

/**
 * Reunites array helper functions
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
	use HasArrayAccess;
	use EnumeratesValues;

	/**
	 * @var array
	 */
    private $items = [];

	/**
	 * Initialization.
	 * 
	 * @param iterable $items = []
	 * @param bool $useKeys = false
	 */
    public function __construct(iterable $items = [], bool $useKeys = false)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items, $useKeys);
    }

	/**
	 * Debug available information.
	 * 
	 * @return array
	 */
	public function __debugInfo()
	{
		return [
			'items' => $this->items
		];
	}

	/**
	 * Leverages certain actions to a high order proxy.
	 * 
	 * @param string $name Name of the method
	 * @return HighOrderCollectionProxy
	 */
	public function __get(string $name)
	{
		return new HighOrderCollectionProxy($this, $name);
	}

	/**
	 * Obtains an iterator for the collection.
	 * 
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this);
	}

	/**
	 * Returns a copy of the collection.
	 * 
	 * @return static
	 */
	public function copy(): static
	{
		return new static($this->items);
	}

	/**
	 * Obtains a generator closure for the collection.
	 * 
	 * @return Closure
	 */
	public function generator(bool $withKeys = false)
	{
		return function() use ($withKeys) {
			foreach ($this->items as $value) {
				if ($withKeys) {
					yield $key => $value;
				} else {
					yield $value;
				}
			}
		};
	}

	/**
	 * Returns the underlying array.
	 * 
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Obtains an eager collection.
	 * 
	 * @return self
	 */
	public function collect()
	{
		return new self($this->items);
	}

	public static function make(array $items)
	{
		return new static($items);
	}

	public static function fromArray(array $items)
	{
		return static::make($items);
	}

	public function toArray()
	{
		return $this->items;
	}

	public function toJson()
	{
		return json_encode($this->items);
	}

	public function toPrettyJson()
	{
		return json_encode($this->items, JSON_PRETTY_PRINT);
	}

	public function chunk(int $size, bool $preserveKeys = false)
	{
		$collections = new static();

		if ($size < 1) {
			return $collections;
		}

		$chunks = function() use ($size, $preserveKeys) {
			foreach (array_chunk($this->items, $size, $preserveKeys) as $chunk) {
				yield new static($chunk);
			}
		};

		foreach ($chunks() as $chunk) {
			$collections[] = $chunk;
		}

		return $collections;
	}

	public function chunkWhile(Closure $callback)
	{
		return null;
	}

	public function collapse()
	{
		$result = new static();

		$generator = function() {
			foreach ($this->items as $value) {
				if (is_array($value)) {
					foreach ((new static($value))->collapse()->values() as $subvalue) {
						yield $subvalue;
					}
				} else {
					yield $value;
				}
			}
		};

		foreach ($generator() as $item) {
			$result[] = $item;
		}

		return $result;
	}

	public function collapseWithKeys()
	{
		$result = new static();

		$generator = function() {
			foreach ($this->items as $key => $value) {
				if (is_array($value)) {
					foreach ((new static($value))->collapseWithKeys()->all() as $subkey => $subvalue) {
						yield $subkey => $subvalue;
					}
				} else {
					yield $key => $value;
				}
			}
		};

		foreach ($generator() as $key => $item) {
			$result[$key] = $item;
		}

		return $result;
	}

	public function combine($values)
	{
		if ($values instanceof static) {
			if ($values->count() != $this->count()) {
				throw new InvalidArgumentException('values should have the same element count as this Collection');
			}

			$values = $values->values();
		}

		if (! is_array($values)) {
			throw new InvalidArgumentException('values should be an array or Collection instance');
		}

		if (count($values) != $this->count()) {
			throw new InvalidArgumentException('values should have the same element count as this Collection');
		}

		return new static(array_combine(array_keys($this->items), array_values($values)));
	}

	public function concat($values)
	{
		if ($values instanceof static) {
			if ($values->count() != $this->count()) {
				throw new InvalidArgumentException('values should have the same element count as this Collection');
			}

			return new static($this->items + $values->values());
		}

		if (! is_array($values)) {
			throw new InvalidArgumentException('values should be an array or Collection instance');
		}

		return new static($this->items + $values);
	}

	public function flip()
	{
		return new static(array_combine(array_values($this->items), array_keys($this->items)));
	}

	public function keyBy(string|Closure $key)
	{
		$key = $this->valueRetriever($key);

		$generator = function() use ($key) {
			$idx = 0;

			foreach ($this->items as $value) {
				$k = $key($value) ?? $idx;

				++$idx;

				yield $k => $value;
			}
		};

		$result = new static();

		foreach ($generator() as $k => $v) {
			$result[$k] = $v;
		}

		return $result;
	}

	public function map(Closure $callback)
	{
		$mapper = function() use ($callback) {
			foreach ($this->items as $key => $value) {
				yield $key => $callback($value);
			}
		};

		$result = new static();

		foreach ($mapper() as $k => $v) {
			$result[$k] = $v;
		}

		return $result;
	}

	public function mapInto(string $class)
	{
		return $this->map(function($value, $key) use ($class) {
			return new $class($value);
		});
	}

	public function mapSpread(Closure $callback)
	{
		return $this->map(function ($value) use ($callback) {
			try {
				if (is_iterable($value)) {
					return $callback(...$value);
				} else {
					return $callback($value);
				}
				//
			} catch (ArgumentCountError $e) {
				throw new CollectionException(
					$this, 'Calback passed to mapSpread must be the same argument count equals to the number of members of each item', 0, $e
				);
			}
		});
	}

	public function mapToGroups(Closure $callback)
	{
		$groups = $this->map($callback)->reduce(function ($groups, $pair) {
			$groups[key($pair)][] = reset($pair);
			return $groups;
		}, []);

		return (new static($groups))->mapInto(static::class);
	}

	public function mapWithKeys(Closure $callback)
	{
		$mapper = function() use ($callback) {
			foreach ($this->items as $key => $value) {
				yield $key => $callback($value, $key);
			}
		};

		$result = new static();

		foreach ($mapper() as $k => $v) {
			$result[$k] = $v;
		}

		return $result;
	}

	public function isList()
	{
		return array_is_list($this->items);
	}

	public function merge($items)
	{
		return new static(
			array_merge(
				$this->items, $items instanceof static ? $items->all() : $items
			)
		);
	}

	public function mergeRecursive($items)
	{
		return new static(
			array_merge_recursive(
				$this->items, $items instanceof static ? $items->all() : $items
			)
		);
	}

	public function partition(Closure $callback)
	{
		$result = [
			'left' => new static(),
			'right' => new static(),
		];

		$generator = function() use ($callback) {
			foreach ($this->items as $key => $value) {
				$side = $callback($value, $key) ? 'first' : 'last';

				yield $side => [$key, $value];
			}
		};

		foreach ($generator() as $side => [$key, $value]) {
			$result[$side][$key] = $value;
		}

		return array_values($result);
	}

	public function pipe(Closure $callback)
	{
		return $callback($this->copy());
	} 

	public function pipeInto(string $class)
	{
		return new $class($this->copy());
	}

	public function pipeThrough(array $pipes)
	{
		$callback = function($carry, $next) {
			if (is_callable($next)) {
				return new $next($carry);
			}

			return $carry;
		};

		return new static(
			array_reduce($pipes, $transformer, $this->copy())
		);
	}

	public function prepend($value, $key = null)
	{
		if (! is_null($key)) {
			if (! is_array($value)) {
				$value = [$key => $value];
			}

			$this->items = $value + $this->items;

			return $this;
		}

		array_unshift($this->items, $value);

		return $this;
	}

	public function push($value)
	{
		$this->items[] = $value;

		return $this;
	}

	public function put(int|string $key, $value)
	{
		$this->items[$key] = $value;

		return $this;
	}

	public function reverse()
	{
		return new static(array_reverse($this->items));
	}

	public function shuffle()
	{
		$items = $this->items;

		$generator = function() use ($items) {
			$max = count($items);

			while ($max > 0) {
				[$current, $target, $chosen] = [0, random_int(0, $max), null];

				foreach ($items as $key => $value) {
					if ($current < $target) {
						++$current;
						continue;
					}

					$chosen = [$key, $value];
					unset($items[$key]);
					--$max;

					break;
				}

				list($key, $value) = $chosen;

				yield $key => $value;
			}
		};

		return new static(iterator_to_array($generator, true));
	}

	public function sliding(int $size, int $step = 1)
	{
		[$collection, $length, $offset] = [$this->values(), $this->count(), 0];

		[$chunk, $chunks] = [[], []];

		foreach ($collection as $key => $value) {
			if ($key % $step !== 0) {
				continue;
			}

			$maximum = ($key + $size);
			
			if ($maximum > $length) {
				break;
			}
			
			for ($offset = $key; $offset < $maximum; ++$offset) {
				$chunk[] = $collection[$offset];
			}
			
			$chunks[] = new static($chunk);
			$chunk = [];
		}

		return new static($chunks);
	}

	public function transform(Closure $callback)
	{
		foreach ($this->items as $key => $value) {
			$this->items[$key] = $callback($value, $key);
		}

		return $this;
	}

	public function union(iterable $items)
	{
		return new static($this->items + $items);
	}

	public function values()
	{
		return new static(array_values($this->items));
	}

	public static function wrap($value)
	{
		if (is_null($value)) {
			return new static();
		}

		if ($value instanceof static) {
			return $value;
		}

		return new static(is_array($value) ? $value : array($value));
	}

	public function zip(iterable $items)
	{
		return new static(array_map(null, $this->values(), array_values($items)));
	}

	################# Filtering & Searching

/**
contains($key, $value = null): Checks if an item exists (loose comparison). 
containsStrict($key, $value = null): Checks if an item exists (strict comparison). 
doesntContain($key, $value = null): The inverse of contains. 
diff($items): Returns values not present in the given items.
diffAssoc($items): Returns key-value pairs not present in the given items. 
diffKeys($items): Returns items with keys not present in the given items.
except($keys): Returns all items except those with specified keys.
**/

	public function filter(Closure $callback)
	{
		return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
	}

	public function first(Closure $callback = null, $default = null)
	{
		if ($this->isEmpty()) {
			return null;
		}

		if (is_null($callback)) {
			return array_first($this->items);
		}

		$last = null;

		foreach ($this->items as $key => $value) {
			if ($callback($value)) {
				return $value ?? $default;
			}
		}

		return $default ?? null;
	}

	public function firstOrFail(Closure $callback = null)
	{
		$result = $this->first($callback);

		if (is_null($result)) {
			throw new ItemNotFoundException($this, 'Item not found on collection');
		}

		return $result;
	}


/**
firstWhere($key, $operator, $value): Returns the first item matching a key-value condition.
**/

	public function forget(int|string $key)
	{
		unset($this->items[$key]);

		return $this;
	}

	public function get(int|string $key, $default = null)
	{
		return $this->items[$key] ?? $default;
	}

	public function has(int|string $key)
	{
		return array_key_exists($key, $this->items);
	}

	public function hasAny(array $keys)
	{
		foreach ($keys as $key) if ($this->has($key)) {
			return true;
		}

		return false;
	}

	public function only(array $keys)
	{
		$result = [];

		foreach ($keys as $key) {
			$result[$key] = $this->items[$key];
		}

		return new static($result);
	}

	public function reject(Closure $callback)
	{
		return $this->filter(function($value, $key) use ($callback) {
			return ! $callback($value, $key);
		});
	}

	public function search($value, bool $strict = false)
	{
		return array_search($value, $this->items, $strict);
	}

/**
where($key, $operator, $value): Filters items by a key-value condition. 
whereStrict($key, $value): Filters by key-value using strict comparison. 
whereBetween($key, $values): Filters items where a key's value is within a range. 
whereIn($key, $values): Filters items where a key's value is in an array. 
whereNotIn($key, $values): Filters items where a key's value is not in an array. 
whereNull($key): Filters items where a key's value is null. 
whereNotNull($key): Filters items where a key's value is not null. 
whereInstanceOf($className): Filters items by instance type.
**/

	######################################### Aggregation & Statistics 

	public function avg(string|Closure $callback = null)
	{
		[$count, $sum] = [0, 0];

		$callback = $this->valueRetriever($callback);

		foreach ($this->items as $item) if (! is_null($number = $callback($item))) {
			++$count;
			$sum += $number;
		}

		return $count ? ($sum / $count) : null;
	}

	public function average(string|Closure $callback = null)
	{
		return $this->avg($callable);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function countBy($callback = null)
	{
		$callback = $this->valueRetriever($callback);

		$counts = [];

		foreach ($this->items as $key => $item) {
			$group = $callback($item, $key);

			if (empty($counts[$group])) {
				$counts[$group] = 0;
			}

			$counts[$group]++;
		}

		return new static($counts);
	}

	public function max($callback = null)
	{
		if (is_null($callback)) {
			return max($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->max();
	}

/**
median($callback = null): Returns the median value.
**/

	public function min($callback = null)
	{
		if (is_null($callback)) {
			return min($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->min();
	}

/**
mode($callback = null): Returns the mode value.
**/

	public function sum($callback = null)
	{
		if (is_null($callback)) {
			return array_sum($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->sum();
	}

	######################################### Extraction & Access

/**
after($value): Returns the item after the given value. 
before($value): Returns the item before the given value.
**/

	public function each($callback)
	{
		foreach ($this->items as $key => $item) if (false === $callback($item, $key)) {
			break;
		}

		return $this;
	}

	public function eachSpread(Closure $callback)
	{
		try {
			foreach ($this->items as $key => $item) {
				if (is_iterable($value)) {
					if (false === $callback(...$value)) {
						break;
					}
				} else {
					if (false === $callback($value)) {
						break;
					}
				}
			}
			//
		} catch (ArgumentCountError $e) {
			throw new CollectionException(
				$this, 'Calback passed to eachSpread must be the same argument count equals to the number of members of each item', 0, $e
			);
		}

		return $this;
	}

	public function every(int $step, int $offset = 0)
	{
		$step = ($step > 0) ? $step : 1;
		$offset = ($offset >= 0) ? $offset : 0;
		
		[$current, $selected] = [0, []];

		foreach ($this->items as $key => $item) {
			if ($current < $offset) {
				continue;
			}

			if ($current % $step === 0) {
				$selected[$key] = $item;
			}

			$current++;
		}

		return new static($selected);
	}

/**
firstWhere($key, $operator, $value): Returns the first item matching a condition.
**/

	public function groupBy($callback = null)
	{
		$callback = $this->valueRetriever($callback);

		$groups = [];

		foreach ($this->items as $key => $item) {
			$group = $callback($item, $key);

			if (empty($groups[$group])) {
				$groups[$group] = [];
			}

			$groups[$group][] = $item;
		}

		return new static($groups);
	}

	public function implode(string|Closure $value, string $glue = null)
	{
		if (is_null($glue)) {
			$glue = is_string($value) ? $value : null;

			return implode($glue, $this->items);
		}

		$callback = $this->valueRetriever($value);

		return implode($glue, $this->mapWithKeys($callback)->all());
	}

	/**
	 * Performs a string join() operation upon map()'ed elements.
	 * 
	 * Equivalent of $collection->map($value)->join($glue, $final).
	 * 
	 * @param string|Closure $value
	 * @param string $glue = null
	 * @param string $final = null
	 * @return string
	 */
	public function mappedJoin(string|Closure $value, string $glue = null, string $final = null)
	{
		if ($value instanceof Closure) {
			if (get_closure_arg_count($value) == 2) {
				return $this->mapWithKeys($value)->join($glue, $final);
			}

			return $this->map($value)->join($glue, $final);
		}

		$value = $this->valueRetriever($value);

		return $this->map($value)->join($glue, $final);
	}

	/**
	 * Performs a string join() operation.
	 * 
	 * @param string $glue
	 * @param string $final = null
	 * @return string
	 */
	public function join(string $glue, string $final = null)
	{
		if (is_null($final)) {
			return $this->implode($glue);
		}

		return implode($glue, array_slice($this->items, 0, -1)) . $final . array_last($this->items);
	}

	public function keys()
	{
		return new static(array_keys($this->items));
	}

	public function last(Closure $callback = null, $default = null)
	{
		if ($this->isEmpty()) {
			return null;
		}

		if (is_null($callback)) {
			return array_last($this->items);
		}

		$last = null;

		foreach (array_reverse($this->items, true) as $key => $value) {
			if ($callback($value)) {
				return $value ?? $default;
			}
		}

		return $default ?? null;
	}

/**
nth($step, $offset = 0): Returns every n-th item.
pluck($value, $key = null): Extracts a list of values for a given key.
pop(): Removes and returns the last item.
shift(): Removes and returns the first item.
slice($offset, $length = null): Returns a slice of the collection. 
skip($count): Skips a number of items.
take($limit): Returns a specified number of items.
value($callback): Gets the value of the first item after applying a callback.
**/

	################################################# Sorting

/**
sort($callback = null): Sorts the collection.
sortBy($callback, $options): Sorts by a specific key. 
sortByDesc($callback, $options): Sorts by a specific key in descending order. 
sortKeys($options): Sorts by keys.
sortKeysDesc($options): Sorts by keys in descending order. 
sortKeysUsing($callback): Sorts keys using a custom callback.
**/

	################################################# Specialized & Utility

/**
crossJoin($items): Cross joins the collection with another. 
dd(): Dumps the collection and terminates execution.
dump(): Dumps the collection. 
ensure($callback): Ensures a condition is met, throwing an exception otherwise.
hasSole($key): Checks if a key exists and is the only item. 
**/

	public function isEmpty()
	{
		return empty($this->items);
	}

	public function isNotEmpty()
	{
		return ! $this->isEmpty();
	}

/**
macro($name, $macro): Registers a custom macro.
pad($size, $value): Pads the collection to a specified length.
random($number = null): Returns a random item or items.
reduce($callback, $initial = null): Reduces the collection to a single value.
replace($items): Replaces items in the collection.
replaceRecursive($items): Recursively replaces items.
sole($callback = null): Returns the sole item, throwing an exception if not exactly one.
splice($offset, $length = null, $replacement = []): Removes and returns a portion of the collection. 
split($numberOfGroups): Splits the collection into a given number of groups. 
tap($callback): Passes the collection to a callback and returns the original.
times($times, $callback = null): Creates a new collection by invoking a callback a given amount of times.
unless($value, $callback): Executes a callback unless a given condition is true.
when($value, $callback, $default = null): Executes a callback when a condition is true.
whenEmpty($callback, $default = null): Executes a callback if the collection is empty.
whenNotEmpty($callback, $default = null): Executes a callback if the collection is not empty. 
**/


/**
Most Laravel Collection methods are immutable, meaning they return a new collection instance rather than changing the original.  However, the following methods modify the collection itself:

---transform: Applies a callback to each item and modifies the collection in place. 
---push: Adds one or more items to the end of the collection. 
pop: Removes and returns the last item from the collection. 
shift: Removes and returns the first item from the collection.
---put: Adds or updates an item at a specific key. 
---prepend: Adds one or more items to the beginning of the collection. 
---forget: Removes an item by its key.
**/

}