<?php

if (! function_exists('array_is_list')) {
    function array_is_list(array $array)
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) === range(0, count($arrat) - 1);
    }
}

if (! function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

if (! function_exists('array_first')) {
    function array_first(array $array) {
        return $array ? $array[array_key_first($array)] : null;
    }
}

if (! function_exists('array_key_last')) {
    function array_key_last($array) {
        if (!is_array($array) || empty($array)) {
            return null;
        }
        
        return array_keys($array)[count($array)-1];
    }
}

if (! function_exists('array_last')) {
    function array_last(array $array) {
        return $array ? $array[array_key_last($array)] : null;
    }
}

if (! function_exists('array_any')) {
    function array_any(array $array, callable $callback) {
        static $natives = [
            'is_array' => 'is_array',
            'is_bool' => 'is_bool',
            'is_callable' => 'is_callable',
            'is_countable' => 'is_countable',
            'is_double' => 'is_float',
            'is_float' => 'is_float',
            'is_int' => 'is_int',
            'is_integer' => 'is_int',
            'is_iterable' => 'is_iterable',
            'is_long' => 'is_int',
            'is_null' => 'is_null',
            'is_numeric' => 'is_numeric',
            'is_object' => 'is_object',
            'is_real' => 'is_float',
            'is_resource' => 'is_resource',
            'is_scalar' => 'is_scalar',
            'is_string' => 'is_string',
        ];

        if (is_string($callback) && array_key_exists($natives, $callback)) {
            $callback = $natives[$callback];

            $callback = function($value, $key) use ($callback) {
                return $callback($value);
            };
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }
}
