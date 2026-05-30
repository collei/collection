<?php
namespace Collei\Collections\Traits;

/**
 * Provides a query selector factory.
 */
trait HasQuerifulSelector
{
	/**
	 * Get an operator checker callback.
	 *
	 * @param string $key
	 * @param mixed $operator = null
	 * @param mixed $value = null
	 * @return \Closure
	 */
	protected function querifulSelector(string $key, $operator = null, $value = null)
	{
        list($value, $operator) = (func_num_args() === 1)
            ? array(true, '=')
            : ((func_num_args() === 2) ? array($operator, '=') : array($value, $operator));

		return function ($item) use ($key, $operator, $value) {
			$retrieved = deep_get($item, $key);

			$strings = array_filter([$retrieved, $value], function ($item) {
				return is_string($item) || (is_object($item) && method_exists($item, '__toString'));
			});

			if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
				return in_array($operator, ['!=', '<>', '!==']);
			}

			switch ($operator) {
				default:
				case '=':
				case '==':  return $retrieved == $value;
				case '!=':
				case '<>':  return $retrieved != $value;
				case '<':   return $retrieved < $value;
				case '>':   return $retrieved > $value;
				case '<=':  return $retrieved <= $value;
				case '>=':  return $retrieved >= $value;
				case '===': return $retrieved === $value;
				case '!==': return $retrieved !== $value;
				case 'in':     return is_array($value) ? in_array($retrieved, $value) : ($retrieved == $value);
				case 'not in': return is_array($value) ? (! in_array($retrieved, $value)) : ($retrieved != $value);
			}
		};
	}
}