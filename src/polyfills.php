<?php

if (! function_exists('array_all')) {
    /**
     * Returns true if ALL array values conforms to the callback condition.
     * 
     * @param array $array
     * @param callable $callable
     * @return bool
     */
    function array_all(array $array, callable $callable)
    {
        foreach ($array as $key => $value) if (! $callable($value, $key)) {
            return false;
        }

        return true;
    }
}

if (! function_exists('array_any')) {
    /**
     * Returns true if ANY value of array conforms to the callback condition.
     * 
     * @param array $array
     * @param callable $callable
     * @return bool
     */
    function array_any(array $array, callable $callable)
    {
        foreach ($array as $key => $value) if ($callable($value, $key)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('array_find')) {
    /**
     * Porting of PHP 8.4 function
     *
     * @param array $array
     * @param (callable($value, $key): bool) $callback
     * @return mixed
     *
     * @see https://www.php.net/manual/en/function.array-find.php
     */
    function array_find(array $array, callable $callback)
    {
        foreach ($array as $key => $value) if ($callback($value, $key)) {
            return $value;
        }

        return null;
    }
}

if (! function_exists('array_find_key')) {
    /**
     * Porting of PHP 8.4 function
     *
     * @param array $array
     * @param (callable($value, $key): bool) $callback
     * @return int|string|null
     *
     * @see https://www.php.net/manual/en/function.array-find.php
     */
    function array_find_key(array $array, callable $callback)
    {
        foreach ($array as $key => $value) if ($callback($value, $key)) {
            return $key;
        }

        return null;
    }
}

if (! function_exists('array_key_first')) {
    /**
     * Gets the first key of an array.
     * 
     * @param array $array
     * @return int|string|null
     */
    function array_key_first(array $array)
    {
        foreach ($array as $key => $unused) {
            return $key;
        }

        return null;
    }
}

if (! function_exists("array_first")) {
    /**
     * Gets the first value of an array.
     * 
     * @param array $array
     * @return mixed
     */
    function array_first(array $array)
    {
        return $array ? $array[array_key_first($array)] : null;
    }
}

if (! function_exists("array_key_last")) {
    /**
     * Gets the last key of an array.
     * 
     * @param array $array
     * @return int|string|null
     */
    function array_key_last($array)
    {
        if (! is_array($array) || empty($array)) {
            return null;
        }
        
        return array_keys($array)[count($array)-1];
    }
}

if (! function_exists("array_last")) {
    /**
     * Gets the last value of an array.
     * 
     * @param array $array
     * @return mixed
     */
    function array_last(array $array)
    {
        return $array ? $array[array_key_last($array)] : null;
    }
}

if (! function_exists('array_is_list')) {
    /**
     * Checks whether the given array is a list.
     * 
     * @param array $array
     * @return bool
     */
    function array_is_list(array $array)
    {
        return $array === [] || (array_keys($array) === range(0, count($array) - 1));
    }
}

if (! function_exists('mb_array_change_key_case')) {
    /**
     * Changes the case of all keys in an array.
     * 
     * @param array $array
     * @param int $case = CASE_LOWER
     * @return array
     */
    function mb_array_change_key_case(array $array, int $case = CASE_LOWER)
    {
        $case = ($case == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;

        foreach ($array as $k => $val) {
            $ret[mb_convert_case($k, $case, "UTF-8")] = $val;
        }

        return $ret;
    }
}

if (! function_exists('mb_array_change_key_case_recursive')) {
    /**
     * Changes the case of all keys in an array, recursively.
     * 
     * @param array $array
     * @param int $case = CASE_LOWER
     * @return array
     */
    function mb_array_change_key_case_recursive(array $array, int $case = CASE_LOWER)
    {
        foreach ($array as $k => $val) {
            $converted = mb_convert_case(
                $k, (($case === CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER), "UTF-8"
            );

            $ret[$converted] = is_array($val)
                ? mb_array_change_key_case_recursive($val, $case)
                : $val;
        }
        
        return $ret;
    }
}

if (! function_exists('array_column_callback')) {
    /**
     * Return the values from a single column in the input array.
     * 
     * @param array $array
     * @param int|string|callable $column_key
     * @param int|string|callable $index_key = null
     * @return array
     */
    function array_column_callback(array $array, $column_key, $index_key = null)
    {
        if (is_null($column_key) && is_null($index_key)) {
            return array_values($array);
        }

        if (
            (is_int($column_key) || is_string($column_key) || is_null($column_key)) &&
            (is_int($index_key) || is_string($index_key) || is_null($index_key))
        ) {
            return array_column($array, $column_key, $index_key);
        }

        $column_key = is_callable($column_key) ? $column_key : (function($item) { return $item; });

        $result = [];

        if (is_callable($index_key)) {
            foreach ($array as $item) {
                $result[$index_key($item)] = $column_key($item);
            }
        } else {
            foreach ($array as $item) {
                $result[] = $column_key($item);
            }
        }

        return $result;
    }
}
