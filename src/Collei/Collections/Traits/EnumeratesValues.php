<?php
namespace Collei\Collections\Traits;

trait EnumeratesValues
{
    protected function useAsCallable($callback)
    {
        return ! is_string($callback) && is_callable($callback);
    }

    protected function valueRetriever($value = null)
    {
        if (is_null($value)) {
            return $this->identity();
        }

        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return is_object($item) ? ($item->$value ?? null) : ($item[$value] ?? null);
        };
    }

    public function negate(Closure $callback)
    {
        return function(...$params) use ($callback) {
            return ! $callback(...$params);
        };
    }

    public function equality($value)
    {
        return function($item) use ($value) {
            return $item === $value;
        };
    }

    public function identity()
    {
        return function($value, $key = null) {
            return $value;
        };
    }
}