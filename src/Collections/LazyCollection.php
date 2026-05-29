<?php
namespace Collei\Collections;

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
class LazyCollection implements CollectionInterface
{
    use CollectionTrait;

    /**
     * @var array
     */
    private $generator;

    /**
     * Creates a new collection with the given items.
     * 
     * @param callable|CollectionInterface $source
     */
    public function __construct($source)
    {
        if (($source instanceof Closure) && $this->isGenerator($source)) {
            $this->generator = $source;
        } elseif ($source instanceof self) {
            $generator = $source->getGenerator();

            $this->generator = function() use ($source, $generator) {
                yield from $generator();
            };
        } elseif (is_null($source)) {
            $this->generator = function() {
                yield null;
            };
        } elseif ($source instanceof Generator) {
            throw new InvalidArgumentException(
                'Generators should not be passed directly -- instead, pass the generator closure'
            );
        } elseif (is_iterable($source)) {
            $this->generator = function() use ($source) {
                yield from $source;
            };
        } else {
            throw new InvalidArgumentException(
                'LazyCollection accepts only generator functions, other instances, iterables and null.'
            );
        }
    }

    public function __debugInfo()
    {
        return [
            'count' => iterator_count(($this->generator)())
        ];
    }

    /**
     * Retrieve the generator.
     * 
     * @return Closure
     */
    protected function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Retrieve an external iterator or traversable.
     * 
     * @return Generator
     */
    public function getIterator(): Traversable
    {
        return ($this->generator)();
    }
    
    /**
     * Checks if all array elements satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function all(callable $callback)
    {
        foreach ($this as $key => $value) if (! $callback($value, $key)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if any array element satisfy a callback function.
     * 
     * @param callable $callback
     * @return bool
     */
    public function any(callable $callback)
    {
        foreach ($this as $key => $value) if ($callback($value, $key)) {
            return true;
        }

        return false;
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
        $original = $this->getGenerator();

        return new static(function() use ($original, $length, $preserveKeys) {
            $cumulated = [];

            foreach ($original() as $key => $value) {
                $cumulated[] = [$key => $value];

                if (count($cumulated) === $length) {
                    $chunk = function() use ($cumulated, $preserveKeys) {
                        $counting = 0;
                        foreach ($cumulated as $item) {
                            yield $preserveKeys ? key($item) : $counting => current($item);
                            ++$counting;
                        }
                    };

                    yield new self($chunk);

                    $cumulated = [];
                }
            }

            if (count($cumulated) > 0) {
                $chunk = function() use ($cumulated, $preserveKeys) {
                    $counting = 0;
                    foreach ($cumulated as $item) {
                        yield $preserveKeys ? key($item) : $counting => current($item);
                        ++$counting;
                    }
                };

                yield new self($chunk);
            }
        });
    }

    /**
     * Produces a copy of this collection, split into chunks by
     * using the given $callback, and returns a collection of
     * these chunks.
     * 
     * The passed callable must accept either 2 ($current, $next) or
     * 3 ($current, $key, $chunk) arguments and must return false
     * to split right at the current 
     * 
     * @param callable $callback
     * @return static
     */
    public function chunkWhile(callable $callback)
    {
        $requiredArgs = callable_count_args($callback);

        if ($requiredArgs < 2 || $requiredArgs > 3) {
            throw new InvalidArgumentException('The passed callable must accept either 2 or 3 arguments');
        }

        $original = $this->getGenerator();

        if ($requiredArgs === 3) return new static(function() use ($original, $callback) {
            $cumulated = [];

            foreach ($original() as $key => $value) {
                if (! $callback($value, $key, $cumulated)) {
                    $chunk = function() use ($cumulated) {
                        foreach ($cumulated as $key => $item) {
                            yield $key => $item;
                        }
                    };

                    yield new self($chunk);

                    $cumulated = [];
                }

                $cumulated[$key] = $value;
            }

            if (count($cumulated) > 0) {
                $chunk = function() use ($cumulated) {
                    foreach ($cumulated as $key => $item) {
                        yield $key => $item;
                    }
                };

                yield new self($chunk);
            }
        });

        if ($requiredArgs === 2) return new static(function() use ($original, $callback) {
            $cumulated = [];

            foreach ($original() as $key => $value) {
                if (! $callback(array_last($cumulated), $value)) {
                    $chunk = function() use ($cumulated) {
                        foreach ($cumulated as $key => $item) {
                            yield $key => $item;
                        }
                    };

                    yield new self($chunk);

                    $cumulated = [];
                }

                $cumulated[$key] = $value;
            }

            if (count($cumulated) > 0) {
                $chunk = function() use ($cumulated) {
                    foreach ($cumulated as $key => $item) {
                        yield $key => $item;
                    }
                };

                yield new self($chunk);
            }
        });
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
        $original = $this->getGenerator();

        $columnKey = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

        $indexKey = is_null($field)
            ? (function($item, $key) { return $key; })
            : $this->valueRetriever($field);

        return new static(function() use ($original, $columnKey, $indexKey) {
            foreach ($original() as $key => $item) {
                $item = $columnKey($item, $key);

                $key = $indexKey($item, $key);

                yield $key => $item;
            }
        });
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
        $original = $this->getGenerator();

        return new static(function() use ($original, $items) {
            $keys = $original();

            $values = ($items instanceof CollectionInterface)
                ? $items->getIterator()
                : (is_array($items) ? new ArrayIterator($items) : new IteratorIterator($items));

            while ($keys->valid() && $values->valid()) {
                $key = $keys->key();
                $value = $values->current();

                yield $key => $value;

                $keys->next();
                $values->next();
            }
        });
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
        if ($items instanceof self) {
            return $items->combine($this);
        }

        $original = $this->getGenerator();

        return new static(function() use ($original, $items) {
            $keys = ($items instanceof CollectionInterface)
                ? $items->getIterator()
                : (is_array($items) ? new ArrayIterator($items) : new IteratorIterator($items));

            $values = $original();

            while ($keys->valid() && $values->valid()) {
                $key = $keys->key();
                $value = $values->current();

                yield $key => $value;

                $keys->next();
                $values->next();
            }
        });
    }

    /**
     * Retrieve the items count.
     * 
     * @return int
     */
    public function count()
    {
        return iterator_count($this);
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
        $original = $this->getGenerator();

        return new static(function() use ($original, $strict) {
            list($values, $counts) = array([], []);

            foreach ($original() as $item) {
                $key = array_search($item, $values, $strict);

                if ($key === false) {
                    $values[] = $value;
                    $counts[] = 1;
                } else {
                    $counts[$key]++;
                }
            }

            foreach ($values as $index => $value) {
                $count = $counts[$index];

                yield compact('value','count');
            }
        });
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForDiff($arrays, $valueCompare));
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $keyCompare = $this->equalityCalculator();
        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffBoth($arrays, $valueCompare, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffBoth($arrays, $valueCompare, $keyCompare));
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $keyCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffKeys($arrays, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffKeys($arrays, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForDiff($arrays, $valueCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = $this->equalityCalculator();

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffBoth($arrays, $valueCompare, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[1]) && is_callable($callbacks[1]))
            ? $callbacks[1]
            : $this->equalityCalculator();

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForDiffBoth($arrays, $valueCompare, $keyCompare));
    }

    /**
     * Crafts a generator closure for diff functions comparing values.
     * 
     * @param array $arrays
     * @param Closure $valueCompare
     * @return Closure
     */
    protected function craftGeneratorForDiff(array $arrays, $valueCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $valueCompare) {
            foreach ($original() as $key => $value) {
                $yieldable = true;

                foreach ($arrays as $other) foreach ($other() as $otherValue) {
                    if (0 === $valueCompare($value, $otherValue)) {
                        $yieldable = false;

                        break 2;
                    }
                }

                if ($yieldable) {
                    yield $key => $value;
                }
            }
        };
    }

    /**
     * Crafts a generator closure for diff functions comparing keys.
     * 
     * @param array $arrays
     * @param Closure $keyCompare
     * @return Closure
     */
    protected function craftGeneratorForDiffKeys(array $arrays, $keyCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $keyCompare) {
            foreach ($original() as $key => $value) {
                $yieldable = true;

                foreach ($arrays as $other) foreach ($other() as $otherKey => $otherValue) {
                    if (0 === $keyCompare($key, $otherKey)) {
                        $yieldable = false;

                        break 2;
                    }
                }

                if ($yieldable) {
                    yield $key => $value;
                }
            }
        };
    }

    /**
     * Crafts a generator closure for diff functions comparing keys and values.
     * 
     * @param array $arrays
     * @param Closure $valueCompare
     * @param Closure $keyCompare
     * @return Closure
     */
    protected function craftGeneratorForDiffBoth(array $arrays, $valueCompare, $keyCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $valueCompare, $keyCompare) {
            foreach ($original() as $key => $value) {
                $yieldable = true;

                foreach ($arrays as $other) foreach ($other() as $otherKey => $otherValue) {
                    if (0 === $keyCompare($key, $otherKey) && 0 === $valueCompare($value, $otherValue)) {
                        $yieldable = false;

                        break 2;
                    }
                }

                if ($yieldable) {
                    yield $key => $value;
                }
            }
        };
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
        $callback = $this->equality($value, $strict);

        foreach ($this as $item) if ($callback($item)) {
            return true;
        }

        return false;
    }

    /**
     * Returns a new collection indexed by the keys of this
     * collection and using $value as item values.
     * 
     * @param mixed $value = null
     * @return static   
     */
    public function fillKeys($value = null)
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $value) {
            foreach ($original() as $key => $item) {
                yield $key => $value;
            }
        });
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
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) if ($callback($item)) {
                yield $key => $item;
            }
        });
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
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) if ($callback($item, $key)) {
                yield $key => $item;
            }
        });
    }

    /**
     * Returns the first element satisfying the given callback.
     * 
     * @param Closure $callback
     * @return mixed
     */
    public function find(Closure $callback)
    {
        foreach ($this as $key => $item) if ($callback($item, $key)) {
            return $item;
        }
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
        foreach ($this as $key => $item) if ($callback($item, $key)) {
            return $key;
        }
    }

    /**
     * Gets the first value of this collection.
     * 
     * @return mixed
     */
    public function first()
    {
        foreach ($this as $item) {
            return $item;
        }

        return null;
    }

    /**
     * Gets the first key of this collection.
     * 
     * @return int|string|null
     */
    public function firstKey()
    {
        foreach ($this as $key => $item) {
            return $key;
        }

        return null;
    }

    /**
     * Returns a collection with all values as keys and vice-versa.
     * 
     * @return static
     */
    public function flip()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original) {
            foreach ($original() as $key => $item) {
                yield $item => $key;
            }
        });
    }

    /**
     * Tells whether the given key exists in this collection.
     * 
     * @param int|string $key
     * @return bool
     */
    public function hasKey($key)
    {
        foreach ($this as $itemKey => $item) if ($itemKey == $key) {
            return true;
        }

        return false;
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersect($arrays, $valueCompare));
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $keyCompare = $this->equalityCalculator();
        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectBoth($arrays, $valueCompare, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        $valueCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectBoth($arrays, $valueCompare, $keyCompare));
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        list($arrays, $ignored) = $this->tellGeneratorsApart(...$arrays);

        $keyCompare = $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectKeys($arrays, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectKeys($arrays, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersect($arrays, $valueCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = $this->equalityCalculator();

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectBoth($arrays, $valueCompare, $keyCompare));
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
        list($arrays, $callbacks) = $this->tellGeneratorsApart(...func_get_args());

        $keyCompare = (isset($callbacks[1]) && is_callable($callbacks[1]))
            ? $callbacks[1]
            : $this->equalityCalculator();

        $valueCompare = (isset($callbacks[0]) && is_callable($callbacks[0]))
            ? $callbacks[0]
            : $this->equalityCalculator();

        return new static($this->craftGeneratorForIntersectBoth($arrays, $valueCompare, $keyCompare));
    }

    /**
     * Crafts a generator closure for intersect methods comparing values.
     * 
     * @param array $arrays
     * @param Closure $valueCompare
     * @return Closure
     */
    protected function craftGeneratorForIntersect(array $arrays, $valueCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $valueCompare) {
            $arraysCount = count($arrays);

            foreach ($original() as $key => $value) {
                $yieldable = 0;

                foreach ($arrays as $other) foreach ($other() as $otherValue) {
                    if (0 === $valueCompare($value, $otherValue)) {
                        ++$yieldable;

                        continue 2;
                    }
                }

                if ($arraysCount === $yieldable) {
                    yield $key => $value;
                }
            }
        };
    }

    /**
     * Crafts a generator closure for intersect methods comparing keys.
     * 
     * @param array $arrays
     * @param Closure $keyCompare
     * @return Closure
     */
    protected function craftGeneratorForIntersectKeys(array $arrays, $keyCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $keyCompare) {
            $arraysCount = count($arrays);

            foreach ($original() as $key => $value) {
                $yieldable = 0;

                foreach ($arrays as $other) foreach ($other() as $otherKey => $otherValue) {
                    if (0 === $keyCompare($key, $otherKey)) {
                        ++$yieldable;

                        continue 2;
                    }
                }

                if ($arraysCount === $yieldable) {
                    yield $key => $value;
                }
            }
        };
    }

    /**
     * Crafts a generator closure for intersect methods comparing values and keys.
     * 
     * @param array $arrays
     * @param Closure $valueCompare
     * @param Closure $keyCompare
     * @return Closure
     */
    protected function craftGeneratorForIntersectBoth(array $arrays, $valueCompare, $keyCompare)
    {
        $original = $this->getGenerator();

        return function() use ($original, $arrays, $valueCompare, $keyCompare) {
            $arraysCount = count($arrays);

            foreach ($original() as $key => $value) {
                $yieldable = 0;

                foreach ($arrays as $other) foreach ($other() as $otherKey => $otherValue) {
                    if (0 === $keyCompare($key, $otherKey) && 0 === $valueCompare($value, $otherValue)) {
                        ++$yieldable;

                        continue 2;
                    }
                }

                if ($arraysCount === $yieldable) {
                    yield $key => $value;
                }
            }
        };
    }

    /**
     * Return the last item of collection.
     * 
     * @return mixed
     */
    public function last()
    {
        $last = null;

        foreach ($this as $item) {
            $last = $item;
        }

        return $last;
    }

    /**
     * Return the key of the last item of collection.
     * 
     * @return int|string|null
     */
    public function lastKey()
    {
        $last = null;

        foreach ($this as $key => $item) {
            $last = $key;
        }

        return $last;
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
        $original = $this->getGenerator();

        $callback = $this->valueRetriever($field);

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $value) {
                $key = $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * Return a collection with all keys of this collection.
     * 
     * @return static
     */
    public function keys()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original) {
            foreach ($original() as $key => $item) {
                yield $key;
            }
        });
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
        $original = $this->getGenerator();

        $count = closure_count_args($callback);

        return new static(function() use ($original, $count, $callback) {
            foreach ($original() as $key => $item) {
                if (2 === $count) {
                    yield $key => $callback($item, $key);
                } else {
                    yield $key => $callback($item);
                }
            }
        });
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
        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

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
     * Merges this collection with the given array(s) or collection(s),
     * returning the result as a new collection.
     * 
     * @param iterable|CollectionInterface $array
     * @param iterable|CollectionInterface ...$arrays
     * @return static
     * @throws InvalidArgumentException when at least one argument is not an iterable
     *      or a CollectionInterface
     */
    public function merge($array, ...$arrays)
    {
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        $original = $this->getGenerator();

        return new static(function() use ($original, $arrays) {
            yield from $original();

            foreach ($arrays as $array) {
                yield from $array;
            }
        });
    }

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
    public function mergeRecursive($array, ...$arrays)
    {
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        $original = $this->getGenerator();

        return new static(function() use ($original, $arrays) {
            $iterables = array_merge([$original()], $arrays);

            list($numericIndex, $merged) = array(0, []);

            foreach ($iterables as $iterable) foreach ($iterable as $key => $value) {
                if (is_int($key)) {
                    // Numeric keys are re-indexed sequentially
                    yield $numericIndex++ => $value;
                } else {
                    // String keys: if key exists, wrap in array (recursive behavior)
                    if (array_key_exists($key, $merged)) {
                        // merges recursively by using PHP internal function under the hood
                        if (is_array($merged[$key])) {
                            $merged[$key] = array_merge_recursive($merged[$key], $value);
                        } else {
                            $merged[$key] = [$merged[$key], $value];
                        }
                    } else {
                        $merged[$key] = $value;
                    }
                }
            }

            foreach ($merged as $key => $value) {
                yield $key => $value;
            }
        });
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
        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

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
        $original = $this->getGenerator();

        return new static(function() use ($original, $length, $value) {
            foreach ($original() as $key => $item) {
                --$length;

                yield $key => $item;
            }

            while ($length > 0) {
                --$length;

                yield $value;
            }
        });
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
        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

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
        $result = $initial;

        foreach ($this as $item) {
            $result = $callback($result, $item);
        }

        return $result;
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
        $arrays = $this->ensureEveryArgumentIsIterable(__METHOD__, ...func_get_args());

        $original = $this->getGenerator();

        return new static(function() use ($original, $arrays) {
            foreach ($original() as $key => $item) {
                yield $key => $item;
            }

            foreach ($arrays as $array) foreach ($array as $key => $value) {
                yield $key => $item;
            }
        });
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
        $equals = $this->equality($needle, $strict);

        foreach ($this as $key => $value) if ($equals($value)) {
            return $key;
        }

        return null;
    }

    /**
     * Retrieves a collection without the first $count items.
     * 
     * @param int $count
     * @return static
     */
    public function skip(int $count)
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $count) {
            $count = abs($count);

            foreach ($original() as $key => $value) {
                if ($count > 0) {
                    --$count;

                    continue;
                }

                yield $key => $value;
            }
        });
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
        $original = $this->getGenerator();

        $offset = abs($offset);
        $length = is_null($length) ? $this->count() : abs($length);

        return new static(function() use ($original, $offset, $length) {
            foreach ($original() as $key => $value) {
                if ($offset > 0) {
                    --$offset;

                    continue;
                }

                if ($length > 0) {
                    --$length;

                    yield $key => $value;
                    
                    continue;
                }

                break;
            }
        });
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
        $original = $this->getGenerator();

        $offset = abs($offset);
        $length = is_null($length) ? $this->count() : abs($length);

        return new static(function() use ($original, $offset, $length, $replacement) {
            $replaced = false;

            foreach ($original() as $key => $value) {
                if ($offset > 0) {
                    --$offset;

                    yield $key => $value;
                    
                    continue;
                }

                if ($length > 0) {
                    --$length;
                    
                    continue;
                }

                if (! $replaced) {
                    yield from $replacement;

                    $replaced = true;
                }

                yield $key => $value;
            }
        });
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
        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

        $sum = 0;

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
        $original = $this->getGenerator();

        return new static(function() use ($original, $length) {
            $length = abs($length);

            foreach ($original() as $key => $value) {
                if (0 === $length) {
                    break;
                }

                yield $key => $value;

                --$length;
            }
        });
    }

    /**
     * Retrieves a copy of this collection without any duplicate values.
     * 
     * @param int $flags = SORT_STRING
     * @return static
     */
    public function unique(int $flags = SORT_STRING)
    {
        $original = $this->getGenerator();

        return new static(function() use ($original) {
            $values = [];

            foreach ($original() as $key => $value) {
                if (in_array($value, $values, true)) {
                    continue;
                }

                $values[] = $value;

                yield $key => $value;
            }

            unset($values);
        });
    }

    /**
     * Returns a copy of this collection with values appended
     * before the original ones.
     * 
     * @param mixed ...$values
     * @return static
     */
    public function unshift(...$values)
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $values) {
            yield from $values;
            yield from $original();
        });
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
        $original = $this->getGenerator();

        $callback = is_null($field)
            ? $this->identity()
            : $this->valueRetriever($field);

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) {
                yield $callback($item, $key);
            }
        });
    }

    /**
     * Retrieves a copy of the collection with all keys in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeys()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) {
                if (is_string($key)) {
                    $key = mb_convert_case($key, MB_CASE_LOWER);
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Retrieves a copy of the collection with all keys in all levels in lowercase.
     * 
     * @return static 
     */
    public function withLowerKeysRecursive()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) {
                if (is_string($key)) {
                    $key = mb_convert_case($key, MB_CASE_LOWER);
                }

                if ($item instanceof LazyCollection) {
                    $item = $item->withLowerKeysRecursive();
                } elseif (is_array($item)) {
                    $item = mb_array_change_key_case_recursive($item, CASE_LOWER);
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Retrieves a copy of the collection with all keys in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeys()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) {
                if (is_string($key)) {
                    $key = mb_convert_case($key, MB_CASE_UPPER);
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Retrieves a copy of the collection with all keys in all levels in uppercase.
     * 
     * @return static 
     */
    public function withUpperKeysRecursive()
    {
        $original = $this->getGenerator();

        return new static(function() use ($original, $callback) {
            foreach ($original() as $key => $item) {
                if (is_string($key)) {
                    $key = mb_convert_case($key, MB_CASE_UPPER);
                }

                if ($item instanceof LazyCollection) {
                    $item = $item->withUpperKeysRecursive();
                } elseif (is_array($item)) {
                    $item = mb_array_change_key_case_recursive($item, CASE_UPPER);
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Ensure that every argument after the method name is iterable,
     * throwing an InvalidArgumentException if at least one is not.
     * 
     * @param string $method
     * @param mixed ...$arguments
     * @return array 
     */
    protected function ensureEveryArgumentIsIterable(string $method, ...$arguments)
    {
        foreach ($arguments as $argument) if (! is_iterable($argument)) {
            throw new InvalidArgumentException(
                sprintf('Ensure every argument passed to %s() is a iterable', $method)
            );
        }

        return $arguments;        
    }
}