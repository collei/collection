<?php
namespace Collei\Support;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Traversable;
use WeakMap;
use Collei\Collections\CollectionInterface;


/**
 * Reunites array helper functions
 */
abstract class Arr
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param mixed value
     * @return bool
     */
    public static function isArrayAccessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param mixed value
     * @return bool
     */
    public static function accessible($value)
    {
        return static::isArrayAccessible($value);
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function keyExists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        //
        return array_key_exists($key, $array);
    }

    /**
     * Fill in data where it's missing.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @return mixed
     */
    public static function fill(&$target, $key, $value)
    {
        return self::set($target, $key, $value, false);
    }

    /**
     * Return the default value of the given value
     *
     * @param mixed $value
     * @return mixed
     */
    public static function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Return the data types for every value, nested or not, of the given array
     * in terms of dot notation.
     *
     * @param  array  $array
     * @return array
     */
    public static function typesOf($array)
    {
        return self::dot(self::naturalTypesOf($array));
    }

    /**
     * Return the data types for every value, nested or not, of the given array.
     *
     * @param  array  $array
     * @return array
     */
    public static function naturalTypesOf($array)
    {
        $types = $array;
        //
        foreach ($types as $key => $value) {
            if (is_array($value)) {
                $types[$key] = self::naturalTypesOf($value);
            } elseif (is_object($value)) {
                $types[$key] = array_merge([get_class($value)], class_implements($value));
            } else {
                $types[$key] = gettype($value);
            }
        }
        //
        return $types;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof Enumerable) {
            return $array->has($key);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  iterable  $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @param  iterable  $array
     * @return array
     */
    public static function undot($array)
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function hasAny($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (! $array) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            if (is_object($array) && is_string($key) && property_exists($array, $key)) {
                return $array->$key;
            }
            
            return static::value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return static::value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if ('*' === $segment) {
                return $array;
            }

            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } elseif (is_object($array) && property_exists($array, $segment)) {
                $array = $array->$segment;
            } else {
                return static::value($default);
            }
        }

        return $array;
    }

    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     *
     * @return mixed
     */
    public static function set(&$target, $key, $value, bool $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        //
        if (($segment = array_shift($segments)) === '*') {
            if (! self::isArrayAccessible($target)) {
                $target = [];
            }
            //
            if ($segments) {
                foreach ($target as &$inner) {
                    self::set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (self::isArrayAccessible($target)) {
            if ($segments) {
                if (! self::keyExists($target, $segment)) {
                    $target[$segment] = [];
                }
                //
                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! self::keyExists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                //
                self::set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            //
            if ($segments) {
                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }
        //
        return $target;
    }

    /**
     * Returns an array with a $thing inserted at $where index
     *
     * @static
     * @param mixed $thing
     * @param array $original
     * @param int $where
     * @return array
     */
    public static function insert($thing, array $original, int $where)
    {
        $brandNew = [];
        // if to be the first, inserts it first in the new array
        if ($where <= 0) {
            $brandNew[] = $thing;
            foreach ($original as $piece) {
                $brandNew[] = $piece;
            }
            return $brandNew;
        }
        // if to be the last, inserts it as the last in the new array
        if ($where >= count($original)) {
            $brandNew = $original;
            $brandNew[] = $thing;
            return $brandNew;
        }
        // if to be in the middle, creates a couple of arrays...
        $before = [];
        $after = [];
        foreach ($original as $ci => $piece) {
            if ($ci < $priority) {
                $before[] = $piece;
            } else {
                $after[] = $piece;
            }
        }
        //...and joins them
        foreach ($before as $piece) {
            $brandNew[] = $piece;
        }
        $brandNew[] = $thing;
        foreach ($after as $piece) {
            $brandNew[] = $piece;
        }
        //
        return $brandNew;
    }

    /**
     * Excludes values from $array by their $keys
     *
     * @static
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function except(array $array, array $keys)
    {
        static::forget($array, $keys);
        //
        return $array;
    }

    /**
     * Filters an array by their keys
     *
     * @static
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function exceptKeys(array $array, array $keys)
    {
        return \array_diff_key($array, array_flip($keys));
    }

    /**
     * Returns an array with the keys as values, ignoring any of original values
     *
     * @static
     * @param array $array
     * @return array
     */
    public static function keys(array $array)
    {
        return \array_keys($array);
    }

    /**
     * Returns an array with the values only
     *
     * @static
     * @param array $array
     * @return array
     */
    public static function values(array $array)
    {
        return \array_values($array);
    }

    /**
     * Returns an array with the same count of elements of the given array, but
     * with the $value as the value of every key
     *
     * @static
     * @param array $array
     * @param string $value
     * @return array
     */
    public static function repeats(array $array, string $value)
    {
        $values = [];
        //
        foreach ($array as $n => $v) {
            $values[$n] = $value;
        }
        //
        return $values;
    }

    /**
     * Interlaces associative arrays in a string, with both keys and values
     *
     * @static
     * @param string $glue
     * @param array $array
     * @param string $symbol
     * @return mixed
     */
    public static function join(string $glue, array $array, string $symbol = null)
    {
        if (is_null($symbol)) {
            return implode($glue, $array);
        }
        //
        return implode($glue, array_map(
            function($n, $v) use ($symbol) {
                return $n . $symbol . $v;
            },
            self::keys($array),
            self::values($array)
        ));
    }

    /**
     * Joins the array keys only, discarding any values
     *
     * @static
     * @param string $glue
     * @param array $array
     * @return string
     */
    public static function joinKeys(string $glue, array $array)
    {
        return implode($glue, Arr::keys($array));
    }

    /**
     * Transform an associative array in a string through a bit more complex way
     *
     * @static
     * @param string $glue
     * @param array $array
     * @param mixed $holder
     * @return string
     */
    public static function joinKeyHolders(string $glue, array $array, $holder)
    {
        if (is_callable($holder) || ($holder instanceof Closure)) {
            $holders = [];
            //
            foreach ($array as $n => $v) {
                $holders[] = $holder($n);
            }
            //
            return implode($glue, $holders);
        }
        //
        if (!is_string($holder)) {
            throw new InvalidArgumentException('The argument $holder should be a string or a callable.');
        }
        //
        return implode($glue, Arr::repeats($array, $holder));
    }

    /**
     * Transform an associative array in a string through a bit more complex way, part II
     *
     * @static
     * @param string $glue
     * @param array $array
     * @param mixed $holder
     * @return string
     */
    public static function joinKeyValueHolders(
        string $glue, array $array, $holder
    ) {
        if (is_callable($holder) || ($holder instanceof Closure)) {
            $holders = [];
            //
            foreach ($array as $n => $v) {
                $holders[] = $holder($n, $v);
            }
            //
            return implode($glue, $holders);
        }
        //
        if (!is_string($holder)) {
            throw new InvalidArgumentException(
                'The argument $holder should be a string or a callable.'
            );
        }
        //
        return implode($glue, Arr::repeats($array, $holder));
    }

    /**
     * Join array values in a string with glue collapse
     *
     * $array = ['/food/','/cereals','rice/','fine-grained']
     * PHP's implode('/',$array):
     *                /food///cereals/rice//fine-grained
     * joinCollapsed('/',$array):
     *                /food/cereals/rice/fine-grained
     *
     * @static
     * @param string $glue
     * @param array $array
     * @return mixed
     */
    public static function joinCollapsed(string $glue, array $array)
    {
        $things = [];
        $first = Str::trimSuffix(array_shift($array) ?? '', $glue);
        $last = Str::trimPrefix(array_pop($array) ?? '', $glue);
        //
        if ($first != '' && $first != $glue) {
            $things[] = Str::trimSuffix($first, $glue);
        }
        //
        foreach ($array as $item) {
            $things[] = Str::trimBoth($item, $glue, $glue);
        }
        //
        if ($last != '' && $last != $glue) {
            $things[] = Str::trimPrefix($last, $glue);
        }
        //
        return Arr::join($glue, $things);
    }

    /**
     * Join array values in a string with a couple glues, like opening and
     * closing HTML tags
     *
     * $array = ['apple','orange','strawberry','grape']
     * joinEnclosed('<li>','</li>',$array):
     *            <li>apple</li><li>orange</li><li>strawberry</li><li>grape</li>
     *
     * @static
     * @param string $glue
     * @param array $array
     * @return mixed
     */
    public static function joinEnclosed(
        string $prefixGlue, string $suffixGlue, array $array
    ) {
        return $prefixGlue
                . Arr::join($prefixGlue . $suffixGlue, $array)
                . $suffixGlue;
    }

    /**
     * Scan the values to define their types
     *
     * @static
     * @param array $line
     * @return array
     */
    public static function prospectTypes(array $line)
    {
        $types = [];
        //
        foreach ($line as $n => $cell) {
            $types[$n] = gettype($cell);
        }
        //
        return $types;
    }

    /**
     * Checks if the array lines are type-consistent
     *
     * @static
     * @param array $line
     * @param array $types
     * @return bool
     */
    public static function isTypeConsistent(array $line, array $types)
    {
        if (count($line) !== count($types)) {
            return false;
        }
        //
        foreach ($types as $n => $type) {
            if (!array_key_exists($n, $line)) {
                return false;
            }
            //
            $cell = $line[$n];
            //
            if (!(gettype($cell) === $type) || is_null($cell)) {
                return false;
            }
        }
        //
        return true;
    }

    /**
     * Checks if the given array has such keys
     *
     * @static
     * @param array $array
     * @param string ...$keys
     * @return bool
     */
    public static function hasKeys(array $array, string ...$keys)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        //
        return true;
    }

    /**
     * Checks if the given array has sub-arrays in a table manner
     *
     * @static
     * @param array $array
     * @return bool
     */
    public static function hasLines(array $array)
    {
        $has = true;
        $columns = 0;
        $types = [];
        //
        foreach ($array as $line) {
            if (!is_array($line)) {
                return false;
            }
            //
            if ($columns === 0) {
                $columns = count($line);
            } elseif (count($line) !== $columns) {
                return false;
            }
            //
            if (count($types) === 0) {
                $types = self::prospectTypes($line);
            } elseif (!self::isTypeConsistent($line, $types)) {
                return false;
            }
        }
        //
        return true;
    }

    /**
     * Returns the first element.
     * If $callback is supplied, $array is filtered first. $filter will receive
     * the value as argument. Return true to include it in the returned array.
     * If resulting $array is empty, $default is returned. 
     *
     * @static
     * @param array $array
     * @param callable $filter
     * @param mixed $default
     * @return mixed
     */
    public static function first(array $array, callable $filter = null, $default = null)
    {
        if (!is_null($filter)) {
            $array = array_filter($array, $filter, 0);
        }
        //
        return array_shift($array) ?? $default;
    }

    /**
     * Returns the first element.
     * If $callback is supplied, $array is filtered first. $filter will receive
     * the value as first argument and the key as the second.
     * Return true to include it in the returned array.
     * If resulting $array is empty, $default is returned. 
     *
     * @static
     * @param array $array
     * @param callable $filter
     * @param mixed $default
     * @return mixed
     */
    public static function firstBoth(array $array, callable $filter = null, $default = null)
    {
        if (!is_null($filter)) {
            $array = array_filter($array, $filter, ARRAY_FILTER_USE_BOTH);
        }
        //
        return array_shift($array) ?? $default;
    }

    /**
     * Returns the last element
     *
     * @static
     * @param array $array
     * @return mixed
     */
    public static function last(array $array)
    {
        return array_pop($array);
    }

    /**
     * Re-key arrays according transformations implemented by the Closure
     *
     * @static
     * @param array $array
     * @param \Closure $transform
     * @return array
     */
    public static function rekey(array $array, Closure $transform)
    {
        if (!is_callable($transform)) {
            return $array;
        }
        //
        $copy = [];
        foreach ($array as $n => $v) {
            $k = $transform($n);
            //
            if (is_string($k) || is_int($k)) {
                $copy[$k] = $v;
            } else {
                throw new InvalidArgumentException(
                    "The closure must return an integer or string value."
                );
            }
        }
        //
        return $copy;
    }

    /**
     * Sort arrays
     *
     * @static
     * @param array ...$values
     * @return array
     */
    public static function sorted(...$values)
    {
        sort($values);
        return $values;
    }

    /**
     * Filter array elements by key
     *
     * @static
     * @param array $source
     * @param array $keys
     * @return array
     */
    public static function filterByKey(array $source, array $keys)
    {
        $filter = function($k) use ($keys) {
            return in_array($k, $keys);
        };
        //
        return array_filter($source, $filter, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Filter array elements by value
     *
     * @static
     * @param array $source
     * @param array $value
     * @return array
     */
    public static function filterByValue(array $source, array $values)
    {
        $filter = function($v) use ($values) {
            return in_array($v, $values);
        };
        //
        return array_filter($source, $filter);
    }

    /**
     * Filter array elements by key and value
     *
     * @static
     * @param array $source
     * @param Closure $filter
     * @return array
     */
    public static function filterByCustom(array $source, Closure $filter)
    {
        return array_filter($source, $filter, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Create an associative array with the given strings as keys
     * in the same order you give them 
     *
     * @static
     * @param string ...$keys
     * @return array
     */
    public static function create(string ...$keys)
    {
        if (empty($keys)) {
            return [];
        }
        //
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = '';
        }
        //
        return $result;
    }

    /**
     * Gets an array of objects OR arrays and returns a specific field.
     * Returns false if $column does not exist OR it is unreachable
     *    (e.g., an object private property)
     *
     * @param array $items
     * @param string $column
     * @return array|false
     */
    public static function column(array $items, string $column)
    {
        $result = [];
        //
        foreach ($items as $item) {
            if (is_array($item) && isset($item[$column])) {
                $result[] = $item[$column];
            } elseif (is_object($item) && isset($item->$column)) {
                $result[] = $item->$column;
            }
        }
        //
        if (empty($result)) {
            return false;
        }
        //
        return $result;
    }

    /**
     * Converts an associative array to a description of labeled values, i.e.:
     *        ['star'=>'sun', 'planet'=>['name'=>'earth', 'satellites'=>1]]
     * to
     *        "star: 'sun', planet: {name: 'earth', satellites: 1}"
     *
     * @param array $items
     * @return string
     */
    public static function describe(array $items)
    {
        $results = [];
        //
        foreach ($items as $n => $item) {
            if (is_array($item)) {
                $results[] = "$n: " . self::describe($item); 
            } elseif (is_object($item)) {
                $results[] = "$n: instanceof(" . get_class($item) . ')';
            } elseif (is_string($item)) {
                $results[] = "$n: '$item'";
            } else {
                $results[] = "$n: $item";
            }
        }
        //
        return '[' . implode(', ', $results) . ']';
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Filter items where the value is not null.
     *
     * @param  array  $array
     * @return array
     */
    public static function whereNotNull($array)
    {
        return static::where($array, function ($value) {
            return ! is_null($value);
        });
    }


    /**
     * Wraps the given $thing into an array if needed
     *
     * @param mixed $thing
     * @return array
     */
    public static function wrap($thing)
    {
        return is_array($thing) ? $thing : array($thing);
    }

    /**
     * Modifies the given array's leafs using the given closure
     *
     * @param array    &$target
     * @param \Closure $callback
     * @param bool $unsetNulls = false
     * @return void
     */
    public static function treeMapLeafs(array &$target, Closure $callback, bool $unsetNulls = false)
    {
        foreach ($target as $index => $item) {
            if (is_array($item)) {
                // if array, goes deeper on it
                static::treeMapLeafs($item, $callback);
                //
                // then unset nullified items if requested
                if (is_null($item) && $unsetNulls) {
                    unset($target[$index]);
                } else {
                    $target[$index] = $item;
                }
            } else {
                // if not, just applies the transform result from callback 
                $target = $callback($target);
                return;
            }
        }
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  iterable  $array
     * @return array
     */
    public static function collapse(iterable $array): array
    {
        $results = [];
        //
        foreach ($array as $values) {
            if (! is_array($values)) {
                $values = iterator_to_array($values, true);
            }
            //
            $results[] = $values;
        }
        //
        return array_merge([], ...$results);
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  iterable  $array
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     *
     * @return array
     */
    public static function pluck(iterable $array, $value, $key = null): array
    {
        $results = [];

        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) || ($key instanceof Closure) ? $key : explode('.', $key);

        foreach ($array as $item) {
            $itemValue = ($value instanceof Closure)
                ? $value($item)
                : Arr::get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = ($key instanceof Closure)
                    ? $key($item)
                    : Arr::get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  iterable  $array
     * @param  int  $depth
     *
     * @return array
     */
    public static function flatten(iterable $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : self::flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Returns an array with unique values only.
     *
     * @param  array  $array
     *
     * @return array
     */
    public static function unique(array $array)
    {
        return array_unique($array);
    }

    /**
     * Returns the values of the first array not present in the others.
     *
     * @param  array  $array
     * @param  array  ...$arrays
     *
     * @return array
     */
    public static function diff(array $array, array ...$arrays)
    {
        return array_diff($array, ...$arrays);
    }

    /**
     * Selects array elements by their specified keys.
     * The resulting array will be number-indexed (from zero) and the same length
     * as the number of keys passed. Not found keys will be null.
     * Perfect to be used along with list(...) PHP language construct for variable unpacking.
     * 
     * @param array $source
     * @param int|string ...$indexes
     * @return array  
     */
    public static function select(array $source, int|string ...$indexes)
    {
        $dest = [];

        foreach ($indexes as $key) {
            $dest[] = $source[$key] ?? null;
        }

        return $dest;
    }

    /**
     * Pick random item(s) from array using random_int() inside a generator.
     * 
     * @param array $items
     * @param int $number = 1
     * @param bool $preserveKeys = false
     * @return array
     */
    public static function random(array $items, int $number = 1, bool $preserveKeys = false)
    {
        $picked = [];

        $generator = function(int $maxItemCount) use ($items) {
            $max = count($items);

            while ($maxItemCount > 0) {
                [$current, $target, $chosen] = [0, random_int(0, $max), null];

                foreach ($items as $key => $value) {
                    if ($current < $target) {
                        $current++;
                        continue;
                    }

                    $chosen = [$key, $value];
                    unset($items[$key]);
                    --$max;
                    --$maxItemCount;

                    break;
                }

                list($key, $value) = $chosen;

                yield $key => $value;
            }
        };

        foreach ($generator($number) as $key => $item) {
            $picked[$key] = $item;
        }

        if (! $preserveKeys) {
            return array_values($picked);
        }

        return $picked;
    }

    /**
     * Shuffles the array within a generator by using random_int().
     * 
     * @param array $items
     * @return array
     */
    public static function shuffle(array $items)
    {
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

        return iterator_to_array($generator, true);
    }

    /**
     * Retrieves an array from the argument or wrap it on array.
     * 
     * @param mixed $items
     * @return array 
     */
    public static function getArrayableItems($items)
    {
        return (is_null($items) || is_scalar($items) || is_enum($items))
            ? static::wrap($items)
            : static::from($items);
    }

    /**
     * Retrieves an array from a iterable/countable/generator/collection.
     * 
     * @param mixed $items
     * @return array
     * @throws InvalidArgumentException for scalar values and other non-array-convertibles
     */
    public static function from($items)
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof CollectionInterface) {
            return $items->all();
        }

        if ($items instanceof Arrayable) {
            return $items->toArray();
        }

        if ($items instanceof WeakMap) {
            return iterator_to_array($items, false);
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        if ($items instanceof Jsonable) {
            return json_decode($items, true);
        }

        if ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        }

        if (is_object($items)) {
            return (array) $items;
        }

        throw new InvalidArgumentException('Items cannot be represented by a scalar value');
    }

    /**
     * Performs a crossJoin operation with passed arrays,
     * returning all possible cominations.
     * 
     * @param array ...$arrays
     * @return array
     */
    public static function crossJoin(array ...$arrays)
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $key => $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Performs a crossJoin operation with passed arrays,
     * saving keys while returning all possible cominations.
     * 
     * Every item is returned as a KeyedValue instance.
     * 
     * @param array ...$arrays
     * @return array
     */
    public static function crossJoinSavingKeys(array ...$arrays)
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $key => $item) {
                    $product[$index] = new KeyedValue($item, $key);

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Performs a crossJoin operation with passed arrays,
     * saving keys while returning all possible cominations.
     * 
     * Every item is returned as a array of format [$key => $value].
     * 
     * @param array ...$arrays
     * @return array
     */
    public static function crossJoinWithKeys(array ...$arrays)
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $key => $item) {
                    $product[$index] = [$key => $item];

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }
}