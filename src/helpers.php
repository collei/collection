<?php

if (! function_exists('deep_get')) {
    /**
     * Performs deep getting of value using dot notation.
     * 
     * @param mixed $target
     * @param int|string|array|null $key
     * @param mixed $default = null
     * @return mixed
     */
    function deep_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                return ($target instanceof Closure) ? $target() : $target;
            }

            if ((is_array($target) || $target instanceof ArrayAccess) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return ($default instanceof Closure) ? $default() : $default;
            }
        }

        return $target;
    }
}

if (! function_exists('closure_count_required_args')) {
    /**
     * Retrieve how many arguments the given closure requires.
     * 
     * @return int
     */
    function closure_count_required_args(Closure $callback)
    {
        $refl = new ReflectionFunction($callback);

        return $refl->getNumberOfRequiredParameters();
    }
}

if (! function_exists('closure_count_args')) {
    /**
     * Retrieve how many arguments the given closure accepts.
     * 
     * @return int
     */
    function closure_count_args(Closure $callback)
    {
        $refl = new ReflectionFunction($callback);

        return $refl->getNumberOfParameters();
    }
}

if (! function_exists('method_count_required_args')) {
    /**
     * Retrieve how many arguments the given class method requires.
     * Returns null if the method does not exist.
     * 
     * @return int|null
     */
    function method_count_required_args(object $object, string $methodName)
    {
        $refl = new ReflectionClass($object);

        if (! $refl->hasMethod($methodName)) {
            return null;
        }

        return $refl->getMethod($methodName)->getNumberOfRequiredParameters();
    }
}

if (! function_exists('method_count_args')) {
    /**
     * Retrieve how many arguments the given class method accepts.
     * Returns null if the method does not exist.
     * 
     * @return int|null
     */
    function method_count_args(object $object, string $methodName)
    {
        $refl = new ReflectionClass($object);

        if (! $refl->hasMethod($methodName)) {
            return null;
        }

        return $refl->getMethod($methodName)->getNumberOfParameters();
    }
}

/**
 * Retrieve how many arguments the given class method accepts.
 * Returns null if the method does not exist.
 * 
 * @return int|null
 */
function method_count_args(object $object, string $methodName)
{
    $refl = new ReflectionClass($object);

    if (! $refl->hasMethod($methodName)) {
        return null;
    }

    return $refl->getMethod($methodName)->getNumberOfParameters();
}
