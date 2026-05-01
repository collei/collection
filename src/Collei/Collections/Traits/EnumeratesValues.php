<?php
namespace Collei\Collections\Traits;

trait EnumeratesValues
{
    protected function useAsCallable($callabck)
    {
        return ! is_string($callback) && is_callable($callback);
    }

    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return $item[$value] ?? $item->$value ?? $value;
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
        return function($value) {
            return $value;
        };
    }
}