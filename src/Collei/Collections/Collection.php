<?php
namespace Collei\Collections;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use InvalidArgumentException;
use Traversable;
use Closure;
use Collei\Collections\Traits\HasArrayAccess;
use Collei\Collections\Traits\EnumeratesValues;
use Collei\Collections\Exceptions\CollectionException;

/**
 * Reunites array helper functions
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
	use HasArrayAccess;
	use EnumeratesValues;

    private $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

	public function __get(string $name)
	{
		return new HighOrderCollectionProxy($this, $name);
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator($this);
	}

	public function copy(): static
	{
		return new static($this->items);
	}

	public function generator(bool $withKeys = false)
	{
		if ($withKeys) return function() {
			foreach ($this->items as $key => $value) {
				yield $key => $value;
			}
		};

		return function() {
			foreach ($this->items as $value) {
				yield $value;
			}
		};
	}

	public function all()
	{
		return $this->items;
	}

	public function collect()
	{
		return new static($this->items);
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
		try {
			return $this->map(function ($value) use ($callback) {
				if (is_iterable($value)) {
					return $callback(...$value);
				}
			});
		}
		catch (Throwable $e) {
			throw new CollectionException(
				'Calback passed to mapSpread must be the same argument count equals to the number of members of each item', 0, $e
			);
		}
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

/**
first($callback = null, $default = null): Returns the first item.
firstOrFail($callback = null): Returns the first item or throws an exception. 
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


/**
countBy($callback = null): Counts the frequency of values.
max($callback = null): Returns the maximum value.
median($callback = null): Returns the median value.
min($callback = null): Returns the minimum value.
mode($callback = null): Returns the mode value.
sum($callback = null): Returns the sum of values.
**/

	######################################### Extraction & Access

/**
after($value): Returns the item after the given value. 
before($value): Returns the item before the given value.
each($callback): Iterates over each item.
eachSpread($callback): Iterates with spread arguments.
every($step, $offset = 0): Creates a new collection with every n-th element. 
firstWhere($key, $operator, $value): Returns the first item matching a condition.
groupBy($callback): Groups items by a key. 
implode($value, $glue = null): Joins items into a string.
join($glue, $final = null): Joins items with a glue string.
**/

	public function keys()
	{
		return new static(array_keys($this->items));
	}

/**
last($callback = null, $default = null): Returns the last item. 
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
isEmpty(): Checks if the collection is empty. 
isNotEmpty(): Checks if the collection is not empty. 
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