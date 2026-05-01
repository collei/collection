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

if (! function_exists("array_first")) {
    function array_first(array $array) {
        return $array ? $array[array_key_first($array)] : null;
    }
}

if (! function_exists("array_key_last")) {
    function array_key_last($array) {
        if (!is_array($array) || empty($array)) {
            return null;
        }
        
        return array_keys($array)[count($array)-1];
    }
}

if (! function_exists("array_last")) {
    function array_last(array $array) {
        return $array ? $array[array_key_last($array)] : null;
    }
}

