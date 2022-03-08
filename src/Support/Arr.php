<?php

namespace DomainExtractor\src\Support;

class Arr
{
    /**
     * @param array $array
     * @param callable|null $callback
     * @param $default
     * @return false|Mixed
     */
    public static function first(array $array, callable $callback = null, $default = null)
    {
        if (null === $callback) {
            return 0 === count($array) ? Mixed::value($default) : reset($array);
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return Mixed::value($default);
    }

    /**
     * @param array $array
     * @param callable|null $callback
     * @param $default
     * @return false|Mixed
     */
    public static function last(array $array, callable $callback = null, $default = null)
    {
        if (null === $callback) {
            return 0 === count($array) ? Mixed::value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }
}
