<?php

namespace DomainExtractor\src\Support;

class IP
{
    /**
     * @param string|null $hostname
     * @return bool
     */
    public static function isValid(?string $hostname): bool
    {
        if ($hostname === null) {
            return false;
        }

        $hostname = trim($hostname);

        if (Str::startsWith($hostname, '[') && Str::endsWith($hostname, ']')) {
            $hostname = substr($hostname, 1, -1);
        }

        return (bool)filter_var($hostname, FILTER_VALIDATE_IP);
    }
}
