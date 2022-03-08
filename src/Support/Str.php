<?php

namespace DomainExtractor\src\Support;

class Str
{
    /**
     * @const string Encoding for strings.
     */
    const ENCODING = 'UTF-8';

    /**
     * @param string $haystack
     * @param $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === self::substr($haystack, -self::length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     * @return int
     */
    public static function length(string $value): int
    {
        return mb_strlen($value, self::ENCODING);
    }

    /**
     * @param string|null $value
     * @return string
     */
    public static function lower(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return mb_strtolower($value, self::ENCODING);
    }

    /**
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length, self::ENCODING);
    }

    /**
     * @param string|null $haystack
     * @param $needles
     * @return bool
     */
    public static function startsWith(?string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($haystack === null) {
                return false;
            }

            if ($needle === null) {
                return false;
            }

            if ($needle !== '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|null $haystack
     * @param string|null $needle
     * @param int $offset
     * @return false|int
     */
    public static function strpos(?string $haystack, ?string $needle, int $offset = 0)
    {
        if ($haystack === null) {
            return false;
        }

        if ($needle === null) {
            return false;
        }

        return mb_strpos($haystack, $needle, $offset, self::ENCODING);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return false|int
     */
    public static function strrpos(string $haystack, string $needle, int $offset = 0)
    {
        return mb_strrpos($haystack, $needle, $offset, self::ENCODING);
    }
}
