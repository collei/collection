<?php

function closure_count_required_args(Closure $callback)
{
    $refl = new ReflectionFunction($callback);

    return $refl->getNumberOfRequiredParameters();
}

function closure_count_args(Closure $callback)
{
    $refl = new ReflectionFunction($callback);

    return $refl->getNumberOfParameters();
}