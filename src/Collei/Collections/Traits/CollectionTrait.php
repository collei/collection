<?php
namespace Collei\Collections\Traits;

use ArrayAccess;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;
use ArgumentCountError;
use Exception;
use InvalidArgumentException;
use UnexpectedValueException;
use Collei\Collections\Collection;
use Collei\Collections\CollectionInterface;
use Collei\Collections\HighOrderCollectionProxy;
use Collei\Collections\Exceptions\CollectionException;
use Collei\Collections\Exceptions\ItemNotFoundException;
use Collei\Support\Arr;
use Collei\Support\Arrayable;
use Collei\Support\Jsonable;
use Collei\Support\WhereFilter;

/**
 * Partially implements the CollectionInterface
 */
trait CollectionTrait
{
	/**
	 * @static @var array
	 */
    protected static $proxyable = [
        'avg',
        'average',
        'sum',
        'each',
        'max',
        'min',
        'median',
        'mode',
        'groupBy',
        'countBy',
    ];

	/**
	 * Leverages certain actions to a high order proxy.
	 * 
	 * @param string $name Name of the method
	 * @return HighOrderCollectionProxy
	 */
	public function __get(string $name)
	{
        if (! in_array($name, static::$proxyable, true)) {
            throw new Exception(sprintf('Collection does not have the \'%s\' property', $name));
        }

		return new HighOrderCollectionProxy($this, $name);
	}

	/**
	 * Converts to string.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * Crafts a brand new craft collection.
	 * 
	 * @static
	 * @return static
	 */
	public static function empty()
	{
		return new static([]);
	}

	/**
	 * Crafts a brand new collection from the argument.
	 * 
	 * @static
	 * @param mixed $items
	 * @return static
	 */
	public static function make($items)
	{
		return new static($items);
	}

	/**
	 * Wraps the value into a brand new collection if needed.
	 * 
	 * @static
	 * @param mixed $value
	 * @return static
	 */
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

	/**
	 * Unwraps the underlying array from the instance if one.
	 * 
	 * @static
	 * @param mixed $value
	 * @return array
	 */
	public static function unwrap($value)
	{
		return ($value instanceof CollectionInterface) ? $value->all() : $value;
	}

	/**
	 * Returns a brand new collection with zero or $number items a value
	 * defined by the $callback (or by range() if $callback is null or not provided).
	 * 
	 * @static
	 * @param int $number
	 * @param callable $callback = null
	 * @return static
	 */
	public static function times(int $number, callable $callback = null)
	{
		if ($number < 1) {
			return new static();
		}

		return static::range(1, $number)->unless($callback == null)->map($callback);
	}

	/**
	 * Returns a brand new collection from the array.
	 * 
	 * @static
	 * @param array $items
	 * @return static
	 */
	public static function fromArray(array $items)
	{
		return static::make($items);
	}

	/**
	 * Returns a brand new collection from the given JSON string.
	 * 
	 * @static
	 * @param strign $json
	 * @param int $depth = 512
	 * @param int $options = 0
	 * @return static
	 */
	public static function fromJson(string $json, int $depth = 512, int $options = 0)
	{
		return new static(json_decode($json, true, $depth, $options));
	}

	/**
	 * Returns a instance of CachingIterator for this instance.
	 * 
	 * @param int $flags = CachingIterator::CALL_TOSTRING
	 * @return \CachingIterator
	 */
	public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING)
	{
		return new CachingIterator($this->getIterator(), $flags);
	}

	/**
	 * Equivalent of $collection->map($callback)->collapse().
	 * 
	 * @param callable $callback
	 * @return static
	 */
	public function flatMap(callable $callback)
	{
		return $this->map($callback)->collapse();
	}

	/**
	 * Obtains an eager collection.
	 * 
	 * @return self
	 */
	public function collect()
	{
		return new Collection($this->items);
	}

	/**
	 * Returns the underlaying array items.
	 * 
	 * @return array
	 */
	public function toArray()
	{
		return $this->items;
	}

	/**
	 * Retruns the contents of collection as a JSON string.
	 * 
	 * @return string
	 */
	public function toJson()
	{
		return json_encode($this->items);
	}

	/**
	 * Retruns the contents of collection as a pretty-formatted
	 * JSON string.
	 * 
	 * @return string
	 */
	public function toPrettyJson()
	{
		return json_encode($this->items, JSON_PRETTY_PRINT);
	}

	/**
	 * Retruns an array ready for JSON serialization.
	 * 
	 * @return array
	 */
	public function jsonSerialize()
	{
		return array_map(function ($item) {
			if ($item instanceof JsonSerializable) {
				return $item->jsonSerialize();
			}

			if ($item instanceof Jsonable) {
				return json_decode($item->toJson(), true);
			}

			if ($item instanceof Arrayable) {
				return $item->toArray();
			}

			return $item;

		}, $this->all());
	}

	/**
	 * Map the collection items to instances of the given class.
	 * 
	 * @return static
	 */
	public function mapInto(string $class)
	{
		return $this->map(function($value, $key) use ($class) {
			return new $class($value);
		});
	}

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
	public function mapSpread(Closure $callback, bool $ignoreNonConformingItems = false)
	{
		return $this->map(function ($value) use ($callback, $ignoreNonConformingItems) {
			try {
				if (is_iterable($value)) {
					return $callback(...$value);
				} else {
					return $callback($value);
				}
				//
			} catch (ArgumentCountError $e) {
				if ($ignoreNonConformingItems) {
					return $value;
				}

				throw new CollectionException(
					$this, 'The callback passed must have the same argument count equals the item\'s member count', 0, $e
				);
			}
		});
	}

	/**
	 * Groups items according to $callback instructions.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function mapToGroups(Closure $callback)
	{
		$groups = $this->map($callback)->reduce(function ($groups, $pair) {
			$groups[key($pair)][] = reset($pair);
			return $groups;
		}, []);

		return (new static($groups))->mapInto(static::class);
	}

	/**
	 * Split a collection in two according to the $callback instructions.
	 * 
	 * @param Closure $callback
	 * @return array
	 */
	public function partition(Closure $callback)
	{
		$result = [
			'left' => new static(),
			'right' => new static(),
		];

		$generator = function() use ($callback) {
			foreach ($this->items as $key => $value) {
				$side = $callback($value, $key) ? 'left' : 'right';

				yield $side => [$key, $value];
			}
		};

		foreach ($generator() as $side => [$key, $value]) {
			$result[$side][$key] = $value;
		}

		return array_values($result);
	}

	/**
	 * Runs the callback passing the collection as argument.
	 * 
	 * @param Closure $callback
	 * @return mixed
	 */
	public function pipe(Closure $callback)
	{
		return $callback($this->copy());
	} 

	/**
	 * Retrieves a instance of the given class created by passing the
	 * collection as the constructor argument.
	 * 
	 * @param string $class
	 * @return mixed
	 */
	public function pipeInto(string $class)
	{
		return new $class($this->copy());
	}

	/**
	 * Send collection items through the callback pipes.
	 * 
	 * @param array $pipes
	 * @return static
	 */
	public function pipeThrough(array $pipes)
	{
		$callback = function($carry, $next) {
			if (is_callable($next)) {
				return $next($carry);
			}

			return $carry;
		};

		return new static(
			array_reduce($pipes, $transformer, $this->copy())
		);
	}

	/**
	 * Returns a collection without the items 'filtered' by the $callback.
	 * 
	 * @param Closure $callback
	 * @return static
	 */
	public function reject(Closure $callback)
	{
		return $this->filter(function($value, $key) use ($callback) {
			return ! $callback($value, $key);
		});
	}

	/**
	 * Returns the average value of items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function avg(string|Closure $callback = null)
	{
		[$count, $sum] = [0, 0];

		$callback = $this->valueRetriever($callback);

		foreach ($this as $item) {
			$number = $callback($item);

			if (is_int($number) || is_float($number)) {
				++$count;
				$sum += $number;
			}
		}

		return $count ? ($sum / $count) : null;
	}

	/**
	 * Returns the average value of items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function average(string|Closure $callback = null)
	{
		return $this->avg($callable);
	}

	/**
	 * Returns the maximum item of all.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function max($callback = null)
	{
		if (is_null($callback)) {
			return max($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->max();
	}

	/**
	 * Returns the minimum item of all.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function min($callback = null)
	{
		if (is_null($callback)) {
			return min($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->min();
	}

	/**
	 * Returns the percentage of items that passes the $callback test.
	 * 
	 * @param callable $callback
	 * @param int $precision = 2
	 * @return mixed
	 */
	public function percentage(callable $callback, int $precision = 2)
	{
		if ($this->empty()) {
			return null;
		}

		return round($this->filter($callback)->count() / $this->count() * 100, $precision);
	}

	/**
	 * Returns the sum of all items.
	 * 
	 * @param string|Closure $callback = null
	 * @return mixed
	 */
	public function sum($callback = null)
	{
		$callback = $this->valueRetriever($callback);

		$summer = function($result, $item, $key) use ($callback) {
			return $result + $callback($item);
		};

		return $this->reduce($summer, 0);
	}

	/**
	 * Runs the callback for each $item of the collection while passing
	 * the item and its key as arguments.
	 * Inside the closure, return false to stop iteration. 
	 * 
	 * @param Closure $callback
	 * @return $this
	 */
	public function each($callback)
	{
		foreach ($this->items as $key => $item) if (false === $callback($item, $key)) {
			break;
		}

		return $this;
	}

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
	public function eachSpread(Closure $callback, bool $ignoreNonConformingItems = false)
	{
		foreach ($this->items as $key => $item) {
			try {
				if (is_iterable($value)) {
					if (false === $callback(...$value)) {
						break;
					}
				} else {
					if (false === $callback($value)) {
						break;
					}
				}
				//
			} catch (ArgumentCountError $e) {
				if ($ignoreNonConformingItems) {
					continue;
				}

				throw new CollectionException(
					$this, 'The callback passed must have the same argument count equals the item\'s member count', 0, $e
				);
			}
		}

		return $this;
	}

	/**
	 * Returns a collection with alternate items picked every $step position
	 * starting at $offset.
	 * 
	 * @param int|Closure $step
	 * @param int $offset = 0
	 * @return static
	 */
	public function every($step, int $offset = 0)
	{
		if (is_int($step)) {
			return $this->nth($step, $offset);
		}

		if ($step instanceof Closure) {
			foreach ($this->items as $key => $item) if (false === $step($item)) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Returns a collection with $perPage items selected from a
	 * 'so called' collection $page.
	 * 
	 * @param int $page
	 * @param int $perPage
	 * @return static
	 */
	public function forPage(int $page, int $perPage)
	{
		$offset = max(0, ($page - 1) * $perPage);

		return $this->slice($offset, $perPage);
	}

	/**
	 * Returns the first value for the given $key.
	 * 
	 * @param string $key
	 * @param mixed $default = null
	 * @return mixed
	 */
	public function value(string $key, $default = null)
	{
		$value = $this->first(function($item) use ($key){
			return Arr::has($item, $key);
		});

		return Arr::get($value, $key, $default);
	}

	/**
	 * Tells if the collection is empty.
	 * 
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}

	/**
	 * Tells if the collection is NOT empty.
	 * 
	 * @return bool
	 */
	public function isNotEmpty()
	{
		return ! $this->isEmpty();
	}

	/**
	 * Applies a callback in order to return a single value.
	 * 
	 * @param callable $callback
	 * @param mixed $initial = null
	 * @return mixed
	 */
	public function reduce(callable $callback, $initial = null)
	{
		$result = $initial;

		foreach ($this as $key => $item) {
			$result = $callback($result, $item, $key);
		}

		return $result;
	}

	/**
	 * Applies a callback in order to return multiple values at once.
	 * 
	 * @param callable $callback
	 * @param mixed ...$initial
	 * @return array
	 */
	public function reduceSpread(callable $callback, ...$initial)
	{
		$result = $initial;

		foreach ($this as $key => $item) {
			$result = call_user_func_array($callback, array_merge($result, [$item, $key]));

			if (! is_array($result)) {
				throw new UnexpectedValueException(sprintf(
					'%s::reduceSpread expects reducer to return an array, but got a \'%s\' instead',
					class_basename(static::class),
					gettype($result)
				));
			}
		}

		return $result;
	}

	/**
	 * Applies a callback in order to return a single value.
	 * 
	 * @param callable $callback
	 * @param mixed $initial = null
	 * @return mixed
	 */
	public function reduceWithKeys(callable $callback, $initial = null)
	{
		return $this->reduce($callback, $initial);
	}

	/**
	 * Dumps the collection items and stops execution.
	 * 
	 * @return never
	 */
	public function dd()
	{
		$this->dump();

		exit(0);
	}

	/**
	 * Dumps the collection items.
	 * 
	 * @return void
	 */
	public function dump()
	{
		echo '<fieldset>';
		echo '<legend>'.(static::class).' '.md5(spl_object_hash($this)).'</legend>';
		echo '<pre>'.$this->toPrettyJson().'</pre>';
		echo '</fieldset>';
	}

	/**
	 * Ensure that all values conform to a given type or class.
	 * 
	 * @param string|array $types
	 * @return $this
	 * @throws \UnexpectedValueException if at least one item doesnt conform.
	 */
	public function ensure(string|array $types)
	{
		$types = is_array($types) ? $types : array($types);

		foreach ($this as $value) foreach ($types as $type) {
			if ('int' === $type && is_int($value)) continue;
			if ('float' === $type && is_float($value)) continue;
			if ('bool' === $type && is_bool($value)) continue;
			if ('string' === $type && is_string($value)) continue;
			if (is_a($value, $type)) continue;

			$phrased = (count($types) == 1)
				? ('type \''.$types[0].'\'')
				: ('one of the these types: \''.implode($types,'\', \'').'\''); 

			throw new UnexpectedValueException(sprintf(
				'All values should be of %s, but found a value of type %s instead',
				$phrased,
				gettype($value)
			));
		}

		return $this;
	}

	/**
	 * Calles the callback passing this collection and returns the collection.
	 * 
	 * @param Closure $callback
	 */
	public function tap(Closure $callback)
	{
		$callback($this);

		return $this;
	}

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
	public function when(bool $condition, callable $callback, callable $default = null)
	{
		if ($condition) {
			if (! is_null($callback)) {
				return static::wrap($callback($this));
			}

			return $this;
		}

		if (! is_null($default)) {
			return static::wrap($default($this));
		}

		return $this;
	}

	/**
	 * Runs the when method with isEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function whenEmpty(callable $callback, callable $default = null)
	{
		return $this->when($this->isEmpty(), $callback, $default);
	}

	/**
	 * Runs the when method with isNotEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function whenNotEmpty(callable $callback, callable $default = null)
	{
		return $this->when($this->isNotEmpty(), $callback, $default);
	}

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
	public function unless(bool $condition, callable $callback, callable $default = null)
	{
		return $this->when(! $condition, $callback, $default);
	}

	/**
	 * Runs the unless method with isEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function unlessEmpty(callable $callback, callable $default = null)
	{
		return $this->unless($this->isEmpty(), $callback, $default);
	}

	/**
	 * Runs the unless method with isNotEmpty() as condition.
	 * 
	 * @param callable $callback
	 * @param callable $default = null
	 * @return static|$this
	 */
	public function unlessNotEmpty(callable $callback, callable $default = null)
	{
		return $this->unless($this->isNotEmpty(), $callback, $default);
	}

	/**
	 * Return true if a given value is unique in this collection.
	 * 
	 * @param string|Closure|null $key
	 * @param bool $strict = false
	 * @return bool  
	 */
	public function unique($key = null, bool $strict = false)
	{
		$callback = $this->valueRetriever($key);

		$exists = [];

		return $this->reject(function($item, $key) use ($callback, $strict, &$exists){
			if (in_array($id = $callback($item, $key), $exists, $strict)) {
				return true;
			}

			$exists[] = $id;
		});
	}

	/**
	 * Return true if a given value is strictly unique in this collection.
	 * 
	 * @param string|Closure|null $key
	 * @return bool  
	 */
	public function uniqueStrict($key = null)
	{
		return $this->unique($key, true);
	}

	/**
	 * Performs a where operation and returns its result as a new collection.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function where($key, $operator = null, $value = null)
	{
		return $this->filter(WhereFilter::make(...func_get_args()));
	}

	/**
	 * Performs a where($key, '===', $value) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @param mixed $value
	 * @return static
	 */
	public function whereStrict($key, $value)
	{
		return $this->where($key, '===', $value);
	}

	/**
	 * Performs a where($key, '===', null) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @return static
	 */
	public function whereNull($key = null)
	{
		return $this->whereStrict($key, null);
	}

	/**
	 * Performs a where($key, '!==', $value) operation and returns its result
	 * as a new collection.
	 * 
	 * @param string|Closure $key
	 * @return static
	 */
	public function whereNotNull($key = null)
	{
		return $this->where($key, '!==', null);
	}

	/**
	 * Filters the collection, returning values that exists among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @param bool $strict = false
	 * @return static
	 */
	public function whereIn($key, $values, bool $strict = false)
	{
		$values = Arr::getArrayableItems($values);

		return $this->filter(function ($item) use ($key, $values, $strict) {
			return in_array(Arr::get($item, $key), $values, $strict);
		});
	}

	/**
	 * Filters the collection, returning values that strictly exists
	 * among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereInStrict($key, $values)
	{
		return $this->whereIn($key, $values, true);
	}

	/**
	 * Filters the collection, returning values that NOT exists among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @param bool $strict = false
	 * @return static
	 */
	public function whereNotIn($key, $values, $strict = false)
	{
		$values = Arr::getArrayableItems($values);

		return $this->filter(function ($item) use ($key, $values, $strict) {
			return ! in_array(Arr::get($item, $key), $values, $strict);
		});
	}

	/**
	 * Filters the collection, returning values that strictly NOT exists
	 * among the $values.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereNotInStrict($key, $values)
	{
		return $this->whereNotIn($key, $values, true);
	}

	/**
	 * Filters the collection, returning values that are in the range.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereBetween($key, $values)
	{
		return $this->where($key, '>=', reset($values))
					->where($key, '<=', end($values));
	}

	/**
	 * Filters the collection, returning values that are NOT in the range.
	 * 
	 * @param string|Closure $key
	 * @param mixed $values
	 * @return static
	 */
	public function whereNotBetween($key, $values)
	{
		return $this->filter(function($item) use ($key, $values){
			$retrieved = Arr::get($item, $key);

			return ($retrieved < reset($values)) || ($retrieved > end($values));
		});
	}

	/**
	 * Filters the collection, returning items that are instances of
	 * one of the given classes.
	 * 
	 * @param string|array $class
	 * @return static
	 */
	public function whereInstanceOf(string|array $class)
	{
		return $this->filter(function($item) use ($class){
			if (is_array($class)) {
				foreach ($class as $type) if ($item instanceof $type) {
					return true;
				}

				return false;
			}

			return $item instanceof $class;
		});
	}

	/**
	 * Returns the first result (if any) of a where operation.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function firstWhere($key, $operator = null, $value = null)
	{
		return $this->first(WhereFilter::make(...func_get_args()));
	}

	/**
	 * Returns if this collection has at least one item according
	 * to a where operation.
	 * 
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function some($key, $operator = null, $value = null)
	{
		return $this->contains(...func_get_args());
	}

	/**
	 * Returns if this collection has two or more items according
	 * to a where operation.
	 *  
	 * @param string|Closure $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return static
	 */
	public function hasMany($key, $operator = null, $value = null)
	{
		$filter = (func_num_args() > 1)
			? WhereFilter::make(...func_get_args())
			: $key;

		return $this->unless($filter == null)
					->filter($filter)
					->take(2)
					->count() === 2;
	}

	/**
	 * Tells if the argument is a Closure.
	 * 
	 * @param mixed $callback
	 * @return bool
	 */
    protected function useAsCallable($callback)
    {
        return ! is_string($callback) && is_callable($callback);
    }

	/**
	 * Returns a Closure according the passed argument.
	 * 
	 * @param mixed $value
	 * @return Closure
	 */
    protected function valueRetriever($value = null)
    {
        if (is_null($value)) {
            return $this->identity();
        }

        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item, $key = null) use ($value) {
            return is_object($item) ? $item->{$value} : $item[$value];
        };
    }

	/**
	 * Retruns a $callback that negates its result.
	 * 
	 * @param Closure $callback
	 * @return Closure
	 */
    protected function negate(Closure $callback)
    {
        return function(...$params) use ($callback) {
            return ! $callback(...$params);
        };
    }

	/**
	 * Returns a callback that returns true if a given item
	 * is equals the given value.
	 * 
	 * @param mixed $value
	 * @return Closure
	 */
    protected function equality($value)
    {
        return function($item) use ($value) {
            return $item === $value;
        };
    }

	/**
	 * Retruns a callback that returns its argument.
	 * 
	 * @return Closure
	 */
    protected function identity()
    {
        return function($value, $key = null) {
            return $value;
        };
    }
}