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
            : (function($item, $key = null) use ($value) { return deep_get($item, $value); });
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
	protected function equality($value, bool $strict = true)
	{
		if ($strict) {
			return (function ($item, $key = null) use ($value) { return $item === $value; });
		}

		return (function ($item, $key = null) use ($value) { return $item == $value; });
	}

	/**
	 * Make a function that returns what's passed to it.
	 *
	 * @return \Closure
	 */
	protected function identity()
	{
		return (function ($value, $key = null) { return $value; });
	}

	/**
	 * Tells if the closure is a Generator function.
	 * 
	 * @param Closure $closure
	 * @return bool
	 */
	protected function isGenerator(Closure $closure)
	{
		try {
			$refl = new ReflectionFunction($closure);

			return $refl->isGenerator();
			//
		} catch(ReflectionException $re) {
			return false;
		}
	}

	/**
	 * Tells generator closures and non-generator closures apart,
	 * returning two arrays: the former with generator closures
	 * and the latter with non-generator closures.
	 * 
	 * @param mixed ...$generators
	 * @return array
	 */
	protected function tellGeneratorsApart(...$generators)
	{
		$lambdas = [];

		foreach ($generators as $key => $generator) {
			if (is_iterable($generator)) {
				$generators[$key] = (function() use ($generator) {
					foreach ($generator as $key => $value) {
						yield $key => $value;
					}
				});

				continue;
			}

			if (! ($generator instanceof Closure)) {
				unset($generators[$key]);

				continue;
			}

			if ($this->isGenerator($generator)) {
				continue;
			}

			$lambdas[$key] = $generator;

			unset($generators[$key]);
		}

		return array($generators, $lambdas);
	}

	/**
	 * Returns a comparator closure that compares two keys for
	 * any array.
	 * 
	 * @return Closure
	 */
	protected function stringableKeyEquality()
	{
		return function($a, $b) { return (string) $a === (string) $b; };
	}

	/**
	 * Returns a comparator closure that tries to compare two values
	 * by their string representations, returning false if one of them
	 * cannot be cast to string. If both are string castable, then
	 * the closure returns the result of (string) $a === (string) $b
	 * 
	 * @return Closure
	 */
	protected function stringableValueEquality()
	{
		if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
			return function($a, $b) {
				try {
					return @(string) $a === @(string) $b;
				} catch (Throwable $error) {
					return false;
				}
			};
		}

		return function($a, $b) {
			if (is_array($a) && is_array($b)) {
				return $a === $b;
			}

			if ((is_object($a) && ! method_exists($a, '__toString')) || is_resource($a)) {
				return false;
			}

			if ((is_object($b) && ! method_exists($b, '__toString')) || is_resource($b)) {
				return false;
			}

			if (! is_array($a) && ! is_array($b)) {
				return (string) $a === (string) $b;
			}

			return false;
		};
	}
}