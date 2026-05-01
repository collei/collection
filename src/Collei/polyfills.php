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