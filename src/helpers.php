<?php

function get_closure_arg_count(Closure $callback, bool $requiredOnly = false)
{
    $reflect = new ReflectionFunction($callback);

    return $requiredOnly
        ? $reflect->getNumberOfRequiredParameters()
        : $reflect->getNumberOfParameters();
}

function has_closure_arg_count(Closure $callback, int $count, bool $requiredOnly = false)
{
    $reflect = new ReflectionFunction($callback);

    return $count == ($requiredOnly
        ? $reflect->getNumberOfRequiredParameters()
        : $reflect->getNumberOfParameters());
}
