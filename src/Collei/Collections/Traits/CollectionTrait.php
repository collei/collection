<?php
namespace Collei\Collections\Traits;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use Closure;
use Exception;
use ArgumentCountError;
use InvalidArgumentException;
use Collei\Collections\{Collection, CollectionInterface};
use Collei\Collections\HighOrderCollectionProxy;
use Collei\Collections\Exceptions\CollectionException;
use Collei\Collections\Exceptions\ItemNotFoundException;
use Collei\Support\Arr;

trait CollectionTrait
{
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

	public function __toString()
	{
		return $this->toJson();
	}

	public static function empty()
	{
		return new static([]);
	}

	public static function make($items)
	{
		return new static($items);
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

	public static function unwrap($value)
	{
		return ($value instanceof CollectionInterface) ? $value->all() : $value;
	}

	public static function times(int $number, callable $callback = null)
	{
		if ($number < 1) {
			return new static();
		}

		return static::range(1, $number)->unless($callback == null)->map($callback);
	}

	public static function fromArray(array $items)
	{
		return static::make($items);
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
					$this, 'The callback passed must have the same argument count equals the item\'s member count', 0, $e
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

	public function reject(Closure $callback)
	{
		return $this->filter(function($value, $key) use ($callback) {
			return ! $callback($value, $key);
		});
	}

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

	public function max($callback = null)
	{
		if (is_null($callback)) {
			return max($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->max();
	}

	public function min($callback = null)
	{
		if (is_null($callback)) {
			return min($this->items);
		}

		$callback = $this->valueRetriever($callback);

		return $this->map($callback)->min();
	}

	public function percentage(callable $callback, int $precision = 2)
	{
		if ($this->empty()) {
			return null;
		}

		return round($this->filter($callback)->count() / $this->count() * 100, $precision);
	}

	public function sum($callback = null)
	{
		$callback = $this->valueRetriever($callback);

		$summer = function($result, $item, $key) use ($callback) {
			return $result + $callback($item);
		};

		return $this->reduce($summer, 0);
	}

	######################################### Extraction & Access

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
				$this, 'The callback passed must have the same argument count equals the item\'s member count', 0, $e
			);
		}

		return $this;
	}

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

	public function value(string $key, $default = null)
	{
		$value = $this->first(function($item) use ($key){
			return Arr::has($item, $key);
		});

		return Arr::get($value, $key, $default);
	}

	public function isEmpty()
	{
		return empty($this->items);
	}

	public function isNotEmpty()
	{
		return ! $this->isEmpty();
	}

	public function reduce(callable $callback, $initial = null)
	{
		$result = $initial;

		foreach ($this as $key => $item) {
			$result = $callback($result, $item, $key);
		}

		return $result;
	}

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

	public function reduceWithKeys(callable $callback, $initial = null)
	{
		return $this->reduce($callback, $initial);
	}

	public function dd()
	{
		$this->dump();

		exit(0);
	}

	public function dump()
	{
		echo '<fieldset>';
		echo '<legend>'.(static::class).' '.md5(spl_object_hash($this)).'</legend>';
		echo '<pre>'.$this->toPrettyJson().'</pre>';
		echo '</fieldset>';
	}

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

	public function tap(callable $callback)
	{
		$callback($this);

		return $this;
	}

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

	public function whenEmpty(callable $callback, callable $default = null)
	{
		return $this->when($this->isEmpty(), $callback, $default);
	}

	public function whenNotEmpty(callable $callback, callable $default = null)
	{
		return $this->when($this->isNotEmpty(), $callback, $default);
	}

	public function unless(bool $condition, callable $callback, callable $default = null)
	{
		return $this->when(! $condition, $callback, $default);
	}

	public function unlessEmpty(callable $callback, callable $default = null)
	{
		return $this->unless($this->isEmpty(), $callback, $default);
	}

	public function unlessNotEmpty(callable $callback, callable $default = null)
	{
		return $this->unless($this->isNotEmpty(), $callback, $default);
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
firstWhere($key, $operator, $value): Returns the first item matching a key-value condition.
**/


    protected function useAsCallable($callback)
    {
        return ! is_string($callback) && is_callable($callback);
    }

    protected function valueRetriever($value = null)
    {
        if (is_null($value)) {
            return $this->identity();
        }

        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item, $key = null) use ($value) {
            return is_object($item) ? ($item->$value ?? null) : ($item[$value] ?? null);
        };
    }

    protected function negate(Closure $callback)
    {
        return function(...$params) use ($callback) {
            return ! $callback(...$params);
        };
    }

    protected function equality($value)
    {
        return function($item) use ($value) {
            return $item === $value;
        };
    }

    protected function identity()
    {
        return function($value, $key = null) {
            return $value;
        };
    }

}