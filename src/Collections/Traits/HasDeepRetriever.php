<?php
namespace Collei\Collections\Traits;

use ArrayAccess;
use Closure;

/**
 * Provides a method to deep-retrieve a value from an item made
 * of nested objects and/or arrays.
 */
trait HasDeepRetriever
{
	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param mixed $target
	 * @param string|array|int|null  $key
	 * @param mixed  $default
	 * @return mixed
	 */
    protected function deepGet($target, $key, $default = null)
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