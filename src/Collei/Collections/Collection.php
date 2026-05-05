<?php
namespace Collei\Collections;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use Closure;
use ArgumentCountError;
use InvalidArgumentException;
use Collei\Collections\Traits\ArrayAccessTrait;
use Collei\Collections\Traits\CollectionTrait;
use Collei\Collections\Exceptions\CollectionException;
use Collei\Collections\Exceptions\ItemNotFoundException;
use Collei\Support\Arr;

/**
 * Reunites array helper functions
 */
class Collection implements CollectionInterface, ArrayAccess, Countable, IteratorAggregate
{
	use ArrayAccessTrait;
	use CollectionTrait;

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
	 * Returns a new Collection from a number range.
	 * 
	 * @static
	 * @param int $start
	 * @param int $end
	 * @param int $step = 1
	 * @return static
	 */
	public static function range(int $start, int $end, int $step = 1)
	{
		return new static(range($start, $end, $step));
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
	 * Obtains an iterator for the collection.
	 * 
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->items);
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

	public function mapWithKeys(Closure $callback)
	{
		return new static(array_map($callback, $this->items, array_keys($this->items)));
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
		return new static(Arr::shuffle($this->items()));
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

	public function map(Closure $callback)
	{
		return new static(array_map($callback, $this->items));
	}

	public function zip(iterable $items)
	{
		return new static(array_map(null, $this->values(), array_values($items)));
	}

	public function contains($key, $operator = null, $value = null)
	{
		if (func_num_args() === 1) {
			if ($this->useAsCallable($key)) {
				return array_any($this->items, $key);
			}

			return in_array($key, $this->items);
		}

		return $this->contains(
			WhereFilter::make(...func_get_args())
		);
	}

	public function containsStrict($key, $value = null)
	{
		if (func_num_args() === 2) {
			return $this->contains(function($item) use ($key, $value){
				return Arr::get($item, $key) === $value;
			});
		}

		if ($this->useAsCallable($key)) {
			return ! is_null($this->first($key));
		}

		return in_array($key, $this->items, true);
	}

	public function doesntContain($key, $operator = null, $value = null)
	{
		return ! $this->contains(...func_get_args());
	}

	public function doesntContainStrict($key, $operator = null, $value = null)
	{
		return ! $this->containsStrict(...func_get_args());
	}

/**
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

	public function search($value, bool $strict = false)
	{
		return array_search($value, $this->items, $strict);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function countBy($callback = null, bool $silentMode = false)
	{
		$callback = $this->valueRetriever($callback);

		$counts = [];

		foreach ($this->items as $key => $item) {
			$group = $callback($item, $key);

            if (! is_int($group) && ! is_string($group)) {
                if ($silentMode) {
                    $group = md5(serialize($group));
                } else {
                    throw new CollectionException($this, 'Illegal offset type returned as result by callback');
                }
            };

			if (empty($counts[$group])) {
				$counts[$group] = 0;
			}

			$counts[$group]++;
		}

		return new static($counts);
	}

    public function median($callback = null)
    {
        if ($this->isEmpty()) {
            return null;
        }
        
        $key = $this->valueRetriever($callback);

        $items = $this->map($key)->filter(function($item, $key) {
            return is_int($item) || is_float($item);
        })->all();

        if (empty($items)) {
            return null;
        }

        sort($items);

        $count = count($items);

        return ($count % 2 == 1)
            ? $items[($count / 2)]
            : (($items[($count / 2) - 1] + $items[($count / 2)]) / 2.0);
    }

    public function mode($key = null)
    {
        if ($this->isEmpty()) {
            return null;
        }

		$collection = isset($key) ? $this->pluck($key) : $this;

		$counts = new static();

		$collection->each(function($item) use ($counts) {
			$counts[$item] = isset($counts[$value]) ? ($counts[$value] + 1) : 1;
		});

		$sorted = $counts->sort();

		$highest = $sorted->last();

		return $sorted->filter(function($item) use ($highest) {
			return $item === $highest;
		})->sort()->keys()->all();
    }

	public function after($value, bool $strict = false)
	{
		$targetKey = array_search($value, $this->items, $strict);

		if (false === $targetKey) {
			return null;
		}

		$pick = false;

		foreach ($this->items as $key => $item) {
			if ($pick) {
				return $item;
			}

			if ($key == $targetKey) {
				$pick = true;
			}
		}

		return null;
	}
	
	public function before($value, bool $strict = false)
	{
		$targetKey = array_search($value, $this->items, $strict);

		if (false === $targetKey) {
			return null;
		}

		$pick = false;

		foreach (array_reverse($this->items, true) as $key => $item) {
			if ($pick) {
				return $item;
			}

			$pick = $key == $targetKey;
		}

		return null;
	}

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

	/**
	 * Returns a new collection with the keys from this instance.
	 * 
	 * @return static
	 */
	public function keys()
	{
		return new static(array_keys($this->items));
	}

	/**
	 * Returns the last item of the collection, if any.
	 * 
	 * @param Closure|null $callback
	 * @param mixed default
	 * @return mixed
	 */
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
	 * Returns a new collection with every nth item of the collection.
	 * 
	 * @param int $step
	 * @param int $offset = 0
	 * @return static
	 */
	public function nth(int $step, int $offset = 0)
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

	public function pluck($value, $key = null)
	{
		return new static(Arr::pluck($this->items, $value, $key));
	}

	public function pop()
	{
		return array_pop($this->items);
	}

	public function shift()
	{
		return array_shift($this->items);
	}

	public function slice(int $offset, int $length = null, $preserveKeys = false)
	{
		return new static(array_slice($this->items, $offset, $length, $preserveKeys));
	}

	public function splice(int $offset, int $length = null, $replacement = [])
	{
		return new static(array_splice($this->items, $offset, $length, $replacement));
	}

	public function skip(int $count)
	{
		return $this->slice($count);
	}

	public function take(int $limit)
	{
		if ($limit < 0) {
			return $this->slice($limit, abs($limit));
		}

		return $this->slice(0, abs($limit));
	}

	################################################# Sorting

/**
sort($callback = null): Sorts the collection.
sortBy($callback, $options): Sorts by a specific key. 
sortByDesc($callback, $options): Sorts by a specific key in descending order. 
sortKeys($options): Sorts by keys.
sortKeysDesc($options): Sorts by keys in descending order. 
sortKeysUsing($callback): Sorts keys using a custom callback.
**/

/**
crossJoin($items): Cross joins the collection with another. 
hasSole($key): Checks if a key exists and is the only item. 
**/

	public function pad(int $size, $value = null)
	{
		return new static(array_pad($this->all(), $size, $value));
	}

	public function random($number = null, bool $preserveKeys = false)
	{
		if (is_null($number)) {
			return Arr::random($this->items);
		}

		if (is_callable($number)) {
			return new static(Arr::random($this->items, $number($this), $preserveKeys));
		}

		return new static(Arr::random($this->items, $number, $preserveKeys));
	}
	
/**
replace($items): Replaces items in the collection.
replaceRecursive($items): Recursively replaces items.
sole($callback = null): Returns the sole item, throwing an exception if not exactly one.
split($numberOfGroups): Splits the collection into a given number of groups. 
**/




}