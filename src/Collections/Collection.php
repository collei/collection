<?php
namespace Collei\Collections;

use ArrayIterator;
use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use Collei\Collections\Traits\CollectionTrait;
use Collei\Collections\Exceptions\CollectionException;

/**
 * Encapsulates an array or an associative array into
 * a class while embodying several operations.
 */
class Collection implements CollectionInterface, IteratorAggregate
{
    use CollectionTrait;

    /**
     * @var array
     */
    private $items = [];

    /**
     * Creates a new collection with the given items.
     * 
     * @param array|Traversable|CollectionInterface $items = []
     */
    public function __construct($items = [])
    {
        $this->items = $this->arrayFrom($items);
    }

    /**
     * Returns a new filled collection indexed from $startIndex
     * with $count elements, and using $value as item values.
     * 
     * @param int $startIndex
     * @param int $count
     * @param mixed $value = null
     * @return static   
     */
    public static function fill(int $startIndex, int $count, $value = null)
    {
        return new static(array_fill($startIndex, $count, $value));
    }

    /**
     * Creates a new collection from integer range.
     * 
     * @param int|float|string $start
     * @param int|float|string $end
     * @param int|float $step = 1
     * @return static
     */
    public static function range($start, $end, $step = 1)
    {
        return new static(range($start, $end, $step));
    }

    /**
     * Converts the collection to array.
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Retrieve an external iterator or traversable.
     * 
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), returning it as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diff($array, ...$arrays)
    {
        return new static(
            array_diff($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), with additional index check, and
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diffAssoc($array, ...$arrays)
    {
        return new static(
            array_diff_assoc($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

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
    public function diffAssocUsing($items, ...$arguments)
    {
        return new static(
            array_diff_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    /**
     * Computes the difference of this collection with the given
     * array(s) or collection(s), using keys for comparison,
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function diffKeys($items, ...$arrays)
    {
        return new static(
            array_diff_key($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

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
    public function diffKeysUsing($items, ...$arguments)
    {
        return new static(
            array_diff_ukey($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function diffUsing($items, ...$arguments)
    {
        return new static(
            array_udiff($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function diffUsingAssoc($items, ...$arguments)
    {
        return new static(
            array_udiff_assoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function diffUsingAssocUsing($items, ...$arguments)
    {
        return new static(
            array_udiff_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersect($items, ...$arrays)
    {
        return new static(
            array_intersect($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, with additional index check, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersectAssoc($items, ...$arrays)
    {
        return new static(
            array_intersect_assoc($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

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
    public function intersectAssocUsing($items, ...$arguments)
    {
        return new static(
            array_intersect_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    /**
     * Computes the intersection of the given arrays/collections with
     * this collection, using keys for comparison, and returns the result.
     * 
     * @param iterable|CollectionInterface $items
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function intersectKeys($items, ...$arrays)
    {
        return new static(
            array_intersect_key($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

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
    public function intersectKeysUsing($items, ...$rest)
    {
        return new static(
            array_intersect_ukey($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function intersectUsing($items, ...$arguments)
    {
        return new static(
            array_uintersect($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function intersectUsingAssoc($items, ...$arguments)
    {
        return new static(
            array_uintersect_assoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

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
    public function intersectUsingAssocUsing($items, ...$arguments)
    {
        return new static(
            array_uintersect_uassoc($this->items, ...$this->withClosuresToEnd($items, ...$arguments))
        );
    }

    /**
     * Tells whether the collection has sequential keys from zero
     * (a normal array).
     * 
     * @return bool
     */
    public function isList()
    {
        return array_is_list($this->items);
    }

    /**
     * Merges this collection with the given array(s) or collection(s),
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function merge($array, ...$arrays)
    {
        return new static(
            array_merge($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    /**
     * Recursively merges this collection with the given array(s)
     * or collection(s), returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function mergeRecursive($array, ...$arrays)
    {
        return new static(
            array_merge_recursive($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    /**
     * Push one or more elements onto the end of this collection.
     * Returns this collection.
     * 
     * @param mixed $value
     * @param mixed ...$values
     * @return $this
     */
    public function push($value, ...$values)
    {
        array_push($this->items, $value, ...$values);

        return $this;
    }

    /**
     * Returns one or more random items from the collection.
     *
     * @param int $count = 1
     * @return mixed
     */
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

            foreach ($this as $key => $item) {
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

    /**
     * Replaces elements from passed arrays or collections into
     * a copy of this collection and returns the resulting collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function replace($array, ...$arrays)
    {
        return new static(
            array_replace($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

    /**
     * Recursively replaces elements from passed arrays or
     * collections into a copy of this collection and returns
     * the resulting collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     */
    public function replaceRecursive($array, ...$arrays)
    {
        return new static(
            array_replace_recursive($this->items, ...$this->arraysFrom(func_get_args(), true))
        );
    }

	/**
	 * Return a copy of this collection with elements in
     * reverse order.
	 *
	 * @return static
	 */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Shifts the first element of the collection and retrieves it.
     * 
     * @param bool $throwException = false
     * @return mixed
     * @throws CollectionException if $throwException is set to true and the collection is empty.
     */
    public function shift(bool $throwException = false)
    {
        if (empty($this->items) && $throwException) {
            throw new CollectionException($this, 'Should not shift an already empty collection !');
        }

        return array_shift($this->array);
    }

    /**
     * Returns a copy of this collection with all items shuffled.
     * It uses random_int() function as its source of randomness.
     * 
     * @return static
     */
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

	/**
	 * Sort-asc the collection, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sort($callback = null)
    {
        $ordered = $this->toArray();

        ($callback && is_callable($callback))
            ? uasort($ordered, $callback)
            : asort($ordered, $callback ?? SORT_REGULAR);

        return new static($ordered);
    }

	/**
	 * Sort the collection by one or more fields or a callback.
	 *
	 * @param string|array|callable $field
	 * @param int $options
	 * @param bool $descending
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
		foreach ($this as $key => $value) {
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
	 * Sort-desc the collection by one field or a callback.
	 *
	 * @param string|array|callable $field
	 * @param int $options
	 * @return static
	 */
	public function sortByDesc($field, $options = SORT_REGULAR)
    {
        if (is_array($field) && count($field) > 0) {
            $field = array_shift($field);
        }

        return $this->sortBy($field, $options, true);
    }

	/**
	 * Sort the collection through several fields.
	 *
	 * @param array $comparisons
	 * @return static
	 */
	protected function sortByFields(array $comparisons = [])
	{
		$items = $this->toArray();

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
                        ? [deep_get($a, $prop), deep_get($b, $prop)]
                        : [deep_get($b, $prop), deep_get($a, $prop)];

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

	/**
	 * Sort-asc the collection by key, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sortByKey($callback = null)
    {
        $ordered = $this->toArray();

        ($callback && is_callable($callback))
            ? uksort($ordered, $callback)
            : ksort($ordered, $callback ?? SORT_REGULAR);

        return new static($ordered);
    }

	/**
	 * Sort-desc the collection by key, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sortByKeyDesc($options = SORT_REGULAR)
    {
        $ordered = $this->toArray();

        krsort($ordered, $options);

        return new static($ordered);
    }

	/**
	 * Sort-desc the collection.
	 *
	 * @param int $options = null
	 * @return static
	 */
    public function sortDesc($options = SORT_REGULAR)
    {
        $ordered = $this->toArray();

        arsort($ordered, $options);

        return new static($ordered);
    }
}