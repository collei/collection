<?php
namespace Collei\Collections;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use Traits\HasDeepRetriever;
use Traits\HasQuerifulSelector;
use Traits\HandlesClosures;
use Exceptions\CollectionException;

class Collection implements CollectionInterface, IteratorAggregate
{
    use HasDeepRetriever;
    use HasQuerifulSelector;
    use HandlesClosures;

    private $items = [];

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public static function range($start, $end, $step = 1)
    {
        return new static(range($start, $end, $step));
    }

    public function toArray()
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function all(callable $callback)
    {
        return array_all($this->items, $callback);
    }

    public function any(callable $callback)
    {
        return array_any($this->items, $callback);
    }

    public function chunk(int $length, bool $preserveKeys = false)
    {
        $chunks = array_chunk($this->items, $length, $preserveKeys);

        $callback = function ($chunk) {
            return new static($chunk);
        };

        return new static(array_map($callback, $chunks));
    }

    public function column($columnKey, $indexKey = null)
    {
        return new static(array_column_callback($this->items, ...func_get_args()));
    }

    public function combine($items = [])
    {
        return new static(array_combine($this->items, $this->arrayFrom($items, true)));
    }

    public function combineTo($items = [])
    {
        if ($items instanceof CollectionInterface) {
            return $items->combine($this);
        }

        return new static(array_combine($items, $this->items));
    }

    public function count()
    {
        return count($this->items);
    }

    public function countValues(bool $strict = false)
    {
        list($values, $counts) = array([], []);

        foreach ($this->items as $item) {
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

    public function countValuesStrict()
    {
        return $this->countValues(true);
    }

    public function diff($array, ...$arrays)
    {
        return new static(
            array_diff($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function diffAssoc($array, ...$arrays)
    {
        return new static(
            array_diff_assoc($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function diffAssocUsing($items, ...$arguments)
    {
        return new static(
            array_diff_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function diffKeys($items, ...$arrays)
    {
        return new static(
            array_diff_key($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function diffKeysUsing($items, ...$arguments)
    {
        return new static(
            array_diff_ukey($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function diffUsing($items, ...$arguments)
    {
        return new static(
            array_udiff($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function diffUsingAssoc($items, ...$arguments)
    {
        return new static(
            array_udiff_assoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function diffUsingAssocUsing($items, ...$arguments)
    {
        return new static(
            array_udiff_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function exists($value, bool $strict = false)
    {
        return in_array($value, $this->items, $strict);
    }

    public function existsStrict($value)
    {
        return $this->exists($value, true);
    }

    public function fill(int $startIndex, int $count, $value = null)
    {
        return new static(array_fill($startIndex, $count, $value));
    }

    public function fillKeys($keys, $value = null)
    {
        return new static(array_fill_keys($this->arrayFrom($keys, true), $value));
    }

    public function filter(Closure $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    public function filterWithKeys(Closure $callback)
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function find(Closure $callback)
    {
        return array_find($this->items, $callback);
    }

    public function findKey(Closure $callback)
    {
        return array_find_key($this->items, $callback);
    }

    public function first()
    {
        return array_first($this->items);
    }

    public function firstKey()
    {
        return array_key_first($this->items);
    }

    public function flip()
    {
        return new static(array_flip($this->items));
    }

    public function intersect($items, ...$arrays)
    {
        return new static(
            array_intersect($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function intersectAssoc($items, ...$arrays)
    {
        return new static(
            array_intersect_assoc($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function intersectAssocUsing($items, ...$arguments)
    {
        return new static(
            array_intersect_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function intersectKeys($items, ...$arrays)
    {
        return new static(
            array_intersect_key($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function intersectKeysUsing($items, ...$rest)
    {
        return new static(
            array_intersect_ukey($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function intersectUsing($items, ...$arguments)
    {
        return new static(
            array_uintersect($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function intersectUsingAssoc($items, ...$arguments)
    {
        return new static(
            array_uintersect_assoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function intersectUsingAssocUsing($items, ...$arguments)
    {
        return new static(
            array_uintersect_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    public function isList()
    {
        return array_is_list($this->items);
    }

    public function hasKey($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function last()
    {
        return array_last($this->items);
    }

    public function lastKey()
    {
        return array_key_last($this->items);
    }

    public function keyBy($field)
    {
        return $this->values($field)->combine($this);
    }

    public function keys()
    {
        return new static(array_keys($this->items));
    }

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

    public function max($field = null)
    {
        if (is_null($field)) {
            return max($this->items);
        }

        $callback = $this->valueRetriever($field);

        $max = -PHP_FLOAT_MAX;

        foreach ($this->items as $key => $item) {
            $value = $callback($item, $key);

            $compare = $value <=> $max;

            if ($compare > 0) {
                $max = $value;
            }
        }

        return $max;
    }

    public function merge($array, ...$arrays)
    {
        return new static(
            array_merge($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function mergeRecursive($array, ...$arrays)
    {
        return new static(
            array_merge_recursive($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function min($field = null)
    {
        if (is_null($field)) {
            return min($this->items);
        }

        $callback = $this->valueRetriever($field);

        $min = PHP_FLOAT_MAX;

        foreach ($this->items as $key => $item) {
            $value = $callback($item, $key);

            $compare = $value <=> $min;

            if ($compare < 0) {
                $min = $value;
            }
        }

        return $min;
    }

    public function pad(int $length, $value = null)
    {
        return new static(array_pad($this->items, $length, $value));
    }

    public function pop(bool $throwException = false)
    {
        if (empty($this->items) && $throwException) {
            throw new CollectionException($this, 'Should not pop an already empty collection !');
        }

        return array_pop($this->items);
    }

    public function prepend(...$values)
    {
        return $this->unshift(...$values);
    }

    public function product($field = null)
    {
        if (is_null($field)) {
            return array_product($this->items);
        }

        $callback = $this->valueRetriever($field);

        $product = 1;

        foreach ($this->items as $key => $item) {
            $value = $callback($item, $key);

            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $product *= $value;
            }
        }

        return $product;
    }

    public function push($value, ...$values)
    {
        array_push($this->items, $value, ...$values);

        return $this;
    }

    public function random(int $count = 1)
    {
        if ($count < 1) {
            throw new InvalidArgumentException('$count must be at least 1');
        }

        $length = $this->count();

        if ($count > $length) {
            throw new InvalidArgumentException('$count must not exceed the collection length');
        }

        // $picked items and $generated random numbers
        list($picked, $generated) = array([], []);

        while ($count > 0) {
            $random = random_int(0, $length);

            // traps already-generated numbers
            // and calls random_int() again.
            if (array_key_exists($random, $generated)) {
                continue;
            }

            // stores this generated number
            $generated[$random] = $random;

            $position = -1;

            foreach ($this->items as $key => $item) {
                ++$position;

                if ($position < $random) {
                    continue;
                }

                $picked[$key] = $value;

                --$count;

                break;
            }
        }

        return (count($picked) > 1) ? $picked : array_first($picked);
    }

    public function reduce(Closure $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function replace($array, ...$arrays)
    {
        return new static(
            array_replace($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function replaceRecursive($array, ...$arrays)
    {
        return new static(
            array_replace_recursive($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    public function search($needle, bool $strict = false)
    {
        return array_search($needle, $this->items, $strict);
    }

    public function searchStrict($needle)
    {
        return array_search($needle, $this->items, true);
    }

    public function shift(bool $throwException = false)
    {
        if (empty($this->items) && $throwException) {
            throw new CollectionException($this, 'Should not shift an already empty collection !');
        }

        return array_shift($this->array);
    }

    public function shuffle()
    {
        list($items, $length, $picked) = array($this->items, $this->count(), []);

        while ($length > 0) {
            $random = random_int(0, $length);
            $position = -1;

            foreach ($items as $key => $item) {
                ++$position;

                if ($position < $random) {
                    continue;
                }

                $picked[$key] = $item;

                unset($items[$key]);

                --$length;

                break;
            }
        }

        return new static($picked);
    }

    public function slice(int $offset, ?int $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function sort($callback = null)
    {
        $ordered = $this->items;

        ($callback && is_callable($callback))
            ? uasort($ordered, $callback)
            : asort($ordered, $callback ?? SORT_REGULAR);

        return new static($ordered);
    }

	/**
	 * Sort the collection by one or more fields or a callback.
	 *
	 * @param  string|array|callable  $field
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return static
	 */
	public function sortBy($field, $options = SORT_REGULAR, $descending = false)
	{
		if (is_array($field) && ! is_callable($field)) {
			return $this->sortByFields($field);
		}

		$results = [];

		$callback = $this->valueRetriever($field);

        // Builds a keyed comparator (using the callback) for the due sorting.
		foreach ($this->items as $key => $value) {
			$results[$key] = $callback($value, $key);
		}

		$descending ? arsort($results, $options) : asort($results, $options);

        // Retrieves the original items, assigning them in the reordered order.
		foreach (array_keys($results) as $key) {
			$results[$key] = $this->items[$key];
		}

		return new static($results);
	}

	/**
	 * Sort the collection through several fields.
	 *
	 * @param  array  $comparisons
	 * @return static
	 */
	protected function sortByFields(array $comparisons = [])
	{
		$items = $this->items;

		usort($items, function ($a, $b) use ($comparisons) {
			foreach ($comparisons as $comparison) {
				$comparison = is_array($comparison) ? $comparison : array($comparison);

				$prop = $comparison[0];

				$ascending = ($comparison[1] ?? true) === true || ($comparison[1] ?? true) === 'asc';

				$result = 0;

				if (! is_string($prop) && is_callable($prop)) {
					$result = $prop($a, $b);
				} else {
					$values = $ascending
                        ? [$this->deepGet($a, $prop), $this->deepGet($b, $prop)]
                        : [$this->deepGet($b, $prop), $this->deepGet($a, $prop)];

					$result = $values[0] <=> $values[1];
				}

				if ($result === 0) {
					continue;
				}

				return $result;
			}
		});

		return new static($items);
	}

    public function sortByKey($callback = null)
    {
        $ordered = $this->items;

        ($callback && is_callable($callback))
            ? uksort($ordered, $callback)
            : ksort($ordered, $callback ?? SORT_REGULAR);

        return new static($ordered);
    }

    public function sortByKeyDesc($options = SORT_REGULAR)
    {
        $ordered = $this->items;

        krsort($ordered, $options);

        return new static($ordered);
    }

    public function sortDesc($options = SORT_REGULAR)
    {
        $ordered = $this->items;

        arsort($ordered, $options);

        return new static($ordered);
    }

    public function splice(int $offset, ?int $length = null, $replacement = [])
    {
        return new static(
            array_splice($this->items, $offset, $length, $this->arrayFrom($replacement))
        );
    }

    public function sum($field = null)
    {
        if (is_null($field)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($field);

        $sum = 1;

        foreach ($this->items as $key => $item) {
            $value = $callback($item, $key);

            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $sum += $value;
            }
        }

        return $sum;
    }

    public function unique(int $flags = SORT_STRING)
    {
        return new static(array_unique($this->items, $flags));
    }

    public function unshift(...$values)
    {
        array_unshift($this->items, ...$values);

        return $this;
    }

    public function values($field = null)
    {
        if (is_null($field)) {
            return new static(array_values($this->items));
        }

        $callback = $this->valueRetriever($field);

        $values = [];

        foreach ($this->items as $key => $item) {
            $values[] = $callback($item, $key);
        }

        return new static($values);
    }

    public function withLowerKeys()
    {
        return new static(mb_array_change_key_case($this->items, CASE_LOWER));
    }

    public function withLowerKeysRecursive()
    {
        return new static(mb_array_change_key_case_recursive($this->items, CASE_LOWER));
    }

    public function withUpperKeys()
    {
        return new static(mb_array_change_key_case($this->items, CASE_UPPER));
    }

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