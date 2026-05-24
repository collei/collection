<?php

if (! function_exists('deep_get')) {
    /**
     * Performs deep getting of value using dot notation.
     * 
     * @param mixed $target
     * @param int|string|array|null $key
     * @param mixed $default = null
     * @return mixed
     */
    function deep_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                return ($target instanceof Closure) ? $target() : $target;
            }

            if ((is_array($target) || $target instanceof ArrayAccess) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return ($default instanceof Closure) ? $default() : $default;
            }
        }

        return $target;
    }
}

if (! function_exists('closure_count_required_args')) {
    /**
     * Retrieve how many arguments the given closure requires.
     * 
     * @return int
     */
    function closure_count_required_args(Closure $callback)
    {
        $refl = new ReflectionFunction($callback);

        return $refl->getNumberOfRequiredParameters();
    }
}

if (! function_exists('closure_count_args')) {
    /**
     * Retrieve how many arguments the given closure accepts.
     * 
     * @return int
     */
    function closure_count_args(Closure $callback)
    {
        $refl = new ReflectionFunction($callback);

        return $refl->getNumberOfParameters();
    }
}

if (! function_exists('method_count_required_args')) {
    /**
     * Retrieve how many arguments the given class method requires.
     * Returns null if the method does not exist.
     * 
     * @return int|null
     */
    function method_count_required_args(object $object, string $methodName)
    {
        $refl = new ReflectionClass($object);

        if (! $refl->hasMethod($methodName)) {
            return null;
        }

        return $refl->getMethod($methodName)->getNumberOfRequiredParameters();
    }
}

if (! function_exists('method_count_args')) {
    /**
     * Retrieve how many arguments the given class method accepts.
     * Returns null if the method does not exist.
     * 
     * @return int|null
     */
    function method_count_args(object $object, string $methodName)
    {
        $refl = new ReflectionClass($object);

        if (! $refl->hasMethod($methodName)) {
            return null;
        }

        return $refl->getMethod($methodName)->getNumberOfParameters();
    }
}

if (! function_exists('pretty_dump')) {
    function pretty_dump($value, bool $open = false)
    {
        // unique dump ID
        static $dump_id = 0;

        // built-in necessary CSS code and some vanilla Javascript
        static $cssJscript = "
            <style>
            button.dumper-btn-toggle { height: 19px !important; border-top: 3px !important; }
            button.dumper-btn-toggle[data-state=\"open\"]::before { content: '▲' }
            button.dumper-btn-toggle[data-state=\"open\"] + span + div.dumper-panel>div { display: block; }
            button.dumper-btn-toggle[data-state=\"closed\"]::before { content: '▼' }
            button.dumper-btn-toggle[data-state=\"closed\"] + span + div.dumper-panel>div { display: none; }
            span { color: #3ff !important; font-family: monospace; }
            div.dumper-main { background-color: #020 !important; line-height: 1.75em !important; }
            div.dumper-panel { background-color: #020 !important; padding: 0px 2px 0px 32px !important; }
            div.dumper-panel>div { color: #ff3 !important; white-space: pre; font-family: monospace; line-height: 1.75em !important; }
            </style>
            <script>
            function tggl(btnRef) { btnRef.setAttribute('data-state', ((btnRef.getAttribute('data-state') == 'open') ? 'closed' : 'open')); }
            </script>
        ";

        $divState = $open ? 'open' : 'closed';

        $lines = explode("\n", print_r($value, true));

        list($levels, $level_prior, $element_count) = array([], 0, 1);

        foreach ($lines as $k => $line) {
            if ($line !== ltrim($line)) {
                $line = str_repeat(' ', 4) . $line;
            }

            $line_cleaned = trim($line);

            if (empty($line_cleaned)) {
                unset($lines[$k]);
                continue;
            }

            if ($line_cleaned === '(' || $line_cleaned === ')') {
                unset($lines[$k]);
                continue;
            }

            $level = (strlen($line) - strlen(ltrim($line))) / 8;

            $line_ready = ($line_cleaned !== $line) ? ltrim($line) : $line;

            if ($level < $level_prior) {
                for ($co = $level_prior; $co > $level; --$co)
                    $levels[] = '</div></div>';
                
                ++$element_count;
            } elseif ($level > $level_prior) {
                $last = count($levels) - 1;
                $state = ($level > 1) ? $divState : 'open';
                $btnName = sprintf('%sp%s', $dump_id, $element_count);
                $button = "<button id=\"btnDump{$btnName}\" class=\"dumper-btn-toggle\" data-state=\"{$state}\" onclick=\"tggl(this)\"></button>";
                $span = "<span>{$levels[$last]}</span>";
                $div = "<div class=\"dumper-panel dumper-level-{$level}\"><div id=\"panelDump{$btnName}\">";
                $levels[$last] = $button.$span.$div;
                ++$element_count;
            }

            $levels[] = trim($line_ready);

            $level_prior = $level;
        }

        // closes any previous opened level DIVs
        for ($co = $level_prior; $co > 0; --$co) {
            $levels[] = '</div></div>';
        }

        // Only includes CSS and Javascript code on output
        // when calling the dump() function at first time.
        $dump_result = (0 === $dump_id) ? $cssJscript : '';

        // Remove unnecessary line breaks from undesired places,
        // so we can let css3 do the hard work accordingly.
        $dump_result .= "<div class=\"dumper-main\" data-id=\"{$dump_id}\"><span></span>"
            . str_replace(
                [">\x01\x03\x02\x04[", "\x01\x03\x02\x04</", ">\x01\x03\x02\x04<", "\x01\x03\x02\x04"],
                ['>[', '</', '><', "\n"],
                implode("\x01\x03\x02\x04", $levels)
            ) . '</div>';

        // allows counting function calls
        ++$dump_id;

        return $dump_result;
    }
}
