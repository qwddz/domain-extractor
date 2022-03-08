<?php

namespace DomainExtractor\src\Support;

class Mixed
{

    /**
     * @param $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
