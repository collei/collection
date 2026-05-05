<?php
namespace Collei\Support;

use Stringable;

/**
 * Provide a convenient filter maker for where-powered tasks.
 * It can be used to provide filters to the array_filter() function.
 */
abstract class WhereFilter
{
	/**
	 * Creates and returns a filter for arrays and iterables.
	 * 
	 * @param int|string|callable $key
	 * @param mixed $operator
	 * @param mixed $value
	 * @return \Closure
	 */
	public static function make($key, $operator = null, $value = null)
	{
		if (! is_string($key) && is_callable($key)) {
			return $key;
		}

		$argCount = func_num_args();

		[$value, $operator] = ($argCount === 1)
				? [true, '=']
				: (($argCount === 2) ? [$operator, '='] : [$value, $operator]);

		return function($item) use ($key, $operator, $value) {
			$retrieved = enum_value(Arr::get($item, $key));
			$value = enum_value($value);

			$strings = array_filter([$retrieved, $value], function($val){
				return is_string($val) || ($val instanceof Stringable);
			});

			if (count($strings) < 2) {
				if (count(array_filter([$retrieved, $value], 'is_object')) == 1) {
					return in_array($operator, ['!=','<>','!==']);
				}
			}

			switch (strtolower($operator)) {
                case '=':
                case '==':
					return $retrieved == $value;
                case '!=':
                case '<>':
					return $retrieved != $value;
                case '<':
					return $retrieved < $value;
                case '>':
					return $retrieved > $value;
                case '<=':
					return $retrieved <= $value;
                case '>=':
					return $retrieved >= $value;
                case '===':
					return $retrieved === $value;
                case '!==':
					return $retrieved !== $value;
                case '<=>':
					return $retrieved <=> $value;
				case 'in': 
					return in_array($retrieved, Arr::wrap($value));
				case 'not in':
					return ! in_array($retrieved, Arr::wrap($value));
			}

			return $retrieved == $value;
		};
	}
}