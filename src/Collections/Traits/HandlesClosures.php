<?php
namespace Collei\Collections\Traits;

/**
 * Internal Closure utilities.
 */
trait HandlesClosures
{
	/**
	 * Get a value retrieving callback.
	 *
	 * @param callable|string|null $value
	 * @return callable
	 */
	protected function valueRetriever($value)
	{
        return (! is_string($value) && is_callable($value))
            ? $value
            : (function($item, $key) use ($value) { return deep_get($item, $value); });
	}

	/**
	 * Make a function using another function, by negating its result.
	 *
	 * @param \Closure $callback
	 * @return \Closure
	 */
	protected function negate(Closure $callback)
	{
		return (function (...$params) use ($callback) { return ! $callback(...$params);	});
	}

	/**
	 * Make a function to check an item's equality.
	 *
	 * @param mixed $value
	 * @return \Closure
	 */
	protected function equality($value)
	{
		return (function ($item) use ($value) { return $item === $value; });
	}

	/**
	 * Make a function that returns what's passed to it.
	 *
	 * @return \Closure
	 */
	protected function identity()
	{
		return (function ($value) { return $value; });
	}
}