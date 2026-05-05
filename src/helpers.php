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

function value($value, ...$args)
{
    return ($value instanceof Closure) ? $value(...$args) : $value;
}

function enum_value($value, $default = null)
{
    if (is_backed_enum($value)) {
        return $value->value;
    }

    if (is_enum($value)) {
        return $value->name;
    }

    return $value ?? value($default);
}

function is_enum($object)
{
    if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
        return $object instanceof UnitEnum;
    }

    return false;
}

function is_backed_enum($object)
{
    if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
        return $object instanceof BackedEnum;
    }

    return false;
}

