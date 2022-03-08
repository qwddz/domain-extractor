<?php

namespace DomainExtractor\src;

use DomainExtractor\src\Database\Exceptions\IOException;
use DomainExtractor\src\Database\Exceptions\StoreException;
use DomainExtractor\src\Database\Store;
use DomainExtractor\src\Exceptions\RuntimeException;
use DomainExtractor\src\Support\{Arr, IP, Str};
use OutOfBoundsException;

class Extract
{
    /**
     * @const int If this option provided, extract will consider ICANN suffixes.
     */
    const MODE_ALLOW_ICANN = 2;

    /**
     * @const int If this option provided, extract will consider private suffixes.
     */
    const MODE_ALLOW_PRIVATE = 4;
    /**
     * @const int If this option provided, extract will consider custom domains.
     */
    const MODE_ALLOW_NOT_EXISTING_SUFFIXES = 8;
    /**
     * @const string RFC 3986 compliant scheme regex pattern.
     *
     * @see   https://tools.ietf.org/html/rfc3986#section-3.1
     */
    const SCHEMA_PATTERN = '#^([a-zA-Z][a-zA-Z0-9+\-.]*:)?//#';
    /**
     * @const string The specification for this regex is based upon the extracts from RFC 1034 and RFC 2181 below.
     *
     * @see   https://tools.ietf.org/html/rfc1034
     * @see   https://tools.ietf.org/html/rfc2181
     */
    const HOSTNAME_PATTERN = '#^((?!-)[a-z0-9_-]{0,62}[a-z0-9_]\.)+[a-z]{2,63}|[xn\-\-a-z0-9]]{6,63}$#';

    /**
     * @var int Value of extraction options.
     */
    private $extractionMode;
    /**
     * @var string Name of class that will store results of parsing.
     */
    private $resultClassName;
    /**
     * @var IDN Object of IDN class.
     */
    private $idn;
    /**
     * @var Store Object of Store class.
     */
    private $suffixStore;

    /**
     * Factory constructor.
     *
     * @param string|null $databaseFile Optional, name of file with Public Suffix List database
     * @param string|null $resultClassName Optional, name of class that will store results of parsing
     * @param int|null $extractionMode Optional, option that will control extraction process
     *
     * @throws RuntimeException
     * @throws IOException
     */
    public function __construct(string $databaseFile = null, string $resultClassName = null, int $extractionMode = null)
    {
        $this->idn = new IDN();
        $this->suffixStore = new Store($databaseFile);
        $this->resultClassName = Result::class;

        // Checks for resultClassName argument.

        if (null !== $resultClassName) {
            if (!class_exists($resultClassName)) {
                throw new RuntimeException(sprintf('Class "%s" is not defined', $resultClassName));
            }

            if (!in_array(ResultInterface::class, class_implements($resultClassName), true)) {
                throw new RuntimeException(sprintf('Class "%s" not implements ResultInterface', $resultClassName));
            }

            $this->resultClassName = $resultClassName;
        }

        $this->setExtractionMode($extractionMode);
    }

    /**
     * Sets extraction mode, option that will control extraction process.
     *
     * @param int|null $extractionMode One of MODE_* constants
     *
     * @throws RuntimeException
     */
    public function setExtractionMode(int $extractionMode = null)
    {
        if (null === $extractionMode) {
            $this->extractionMode = static::MODE_ALLOW_ICANN
                | static::MODE_ALLOW_PRIVATE
                | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES;

            return;
        }

        if (!is_int($extractionMode)) {
            throw new RuntimeException('Invalid argument type, extractionMode must be integer');
        }

        if (!in_array($extractionMode, [
            static::MODE_ALLOW_ICANN,
            static::MODE_ALLOW_PRIVATE,
            static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_PRIVATE,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_PRIVATE | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_PRIVATE | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES
        ], true)
        ) {
            throw new RuntimeException(
                'Invalid argument type, extractionMode must be one of defined constants of their combination'
            );
        }

        $this->extractionMode = $extractionMode;
    }

    /**
     * @return int
     */
    public function getExtractionMode(): int
    {
        return $this->extractionMode;
    }

    /**
     * Extract the subdomain, host and gTLD/ccTLD components from a URL.
     *
     * @param ?string $url URL that will be extracted
     *
     * @return ResultInterface
     * @throws StoreException
     */
    public function parse(?string $url): ResultInterface
    {
        $hostname = $this->extractHostname($url);

        // If received hostname is valid IP address, result will be formed from it.

        if (IP::isValid($hostname)) {
            return new $this->resultClassName(null, $hostname, null);
        }

        list($subDomain, $host, $suffix) = $this->extractParts($hostname);

        return new $this->resultClassName($subDomain, $host, $suffix);
    }

    /**
     * Method that extracts the hostname or IP address from a URL.
     *
     * @param string|null $url URL for extraction
     *
     * @return null|string Hostname or IP address
     */
    private function extractHostname(?string $url): ?string
    {
        $url = trim(Str::lower($url));
        $url = preg_replace(static::SCHEMA_PATTERN, '', $url);
        $url = $this->fixUriParts($url);

        $hostname = Arr::first(explode('/', $url, 2));
        $hostname = Arr::last(explode('@', $hostname));

        $lastBracketPosition = Str::strrpos($hostname, ']');

        if ($lastBracketPosition !== false && Str::startsWith($hostname, '[')) {
            return Str::substr($hostname, 1, $lastBracketPosition - 1);
        }

        $hostname = Arr::first(explode(':', $hostname));

        return '' === $hostname ? null : $hostname;
    }

    /**
     * @param string|null $hostname
     * @return array
     * @throws StoreException
     */
    public function extractParts(?string $hostname): array
    {
        $suffix = $this->extractSuffix($hostname);

        if ($suffix === $hostname) {
            return [null, $hostname, null];
        }

        if (null !== $suffix) {
            $hostname = Str::substr($hostname, 0, -Str::length($suffix) - 1);
        }

        $lastDot = Str::strrpos($hostname, '.');

        if (false === $lastDot) {
            return [null, $hostname, $suffix];
        }

        $subDomain = Str::substr($hostname, 0, $lastDot);
        $host = Str::substr($hostname, $lastDot + 1);

        return [
            $subDomain,
            $host,
            $suffix
        ];
    }

    /**
     * Extracts suffix from hostname using Public Suffix List database.
     *
     * @param string|null $hostname Hostname for extraction
     *
     * @return null|string
     * @throws StoreException
     */
    private function extractSuffix(?string $hostname): ?string
    {
        // If hostname has leading dot, it's invalid.
        // If hostname is a single label domain makes, it's invalid.

        if (Str::startsWith($hostname, '.') || Str::strpos($hostname, '.') === false) {
            return null;
        }

        // If domain is in punycode, it will be converted to IDN.

        $isPunycoded = Str::strpos($hostname, 'xn--') !== false;

        if ($isPunycoded) {
            $hostname = $this->idn->toUTF8($hostname);
        }

        // URI producers should use names that conform to the DNS syntax, even when use of DNS is not immediately
        // apparent, and should limit these names to no more than 255 characters in length.
        //
        // @see https://tools.ietf.org/html/rfc3986
        // @see http://blogs.msdn.com/b/oldnewthing/archive/2012/04/12/10292868.aspx

        if (Str::length($hostname) > 253) {
            return null;
        }

        // The DNS itself places only one restriction on the particular labels that can be used to identify resource
        // records. That one restriction relates to the length of the label and the full name. The length of any one
        // label is limited to between 1 and 63 octets. A full domain name is limited to 255 octets (including the
        // separators).
        //
        // @see http://tools.ietf.org/html/rfc2181

        try {
            $asciiHostname = $this->idn->toASCII($hostname);
        } catch (OutOfBoundsException $e) {
            return null;
        }

        if (0 === preg_match(self::HOSTNAME_PATTERN, $asciiHostname)) {
            return null;
        }

        $suffix = $this->parseSuffix($hostname);

        if (null === $suffix) {
            if (!($this->extractionMode & static::MODE_ALLOW_NOT_EXISTING_SUFFIXES)) {
                return null;
            }

            $suffix = Str::substr($hostname, Str::strrpos($hostname, '.') + 1);
        }

        // If domain is punycoded, suffix will be converted to punycode.

        return $isPunycoded ? $this->idn->toASCII($suffix) : $suffix;
    }

    /**
     * Extracts suffix from hostname using Public Suffix List database.
     *
     * @param string $hostname Hostname for extraction
     *
     * @return null|string
     * @throws StoreException
     */
    private function parseSuffix(string $hostname): ?string
    {
        $hostnameParts = explode('.', $hostname);
        $realSuffix = null;

        for ($i = 0, $count = count($hostnameParts); $i < $count; $i++) {
            $possibleSuffix = implode('.', array_slice($hostnameParts, $i));
            $exceptionSuffix = '!' . $possibleSuffix;

            if ($this->suffixExists($exceptionSuffix)) {
                $realSuffix = implode('.', array_slice($hostnameParts, $i + 1));

                break;
            }

            if ($this->suffixExists($possibleSuffix)) {
                $realSuffix = $possibleSuffix;

                break;
            }

            $wildcardTld = '*.' . implode('.', array_slice($hostnameParts, $i + 1));

            if ($this->suffixExists($wildcardTld)) {
                $realSuffix = $possibleSuffix;

                break;
            }
        }

        return $realSuffix;
    }

    /**
     * Method that checks existence of entry in Public Suffix List database, including provided options.
     *
     * @param string $entry Entry for check in Public Suffix List database
     *
     * @return bool
     * @throws StoreException
     */
    protected function suffixExists(string $entry): bool
    {
        if (!$this->suffixStore->isExists($entry)) {
            return false;
        }

        $type = $this->suffixStore->getType($entry);

        if ($this->extractionMode & static::MODE_ALLOW_ICANN && $type === Store::TYPE_ICANN) {
            return true;
        }

        return $this->extractionMode & static::MODE_ALLOW_PRIVATE && $type === Store::TYPE_PRIVATE;
    }

    /**
     * Fixes URL:
     * - from "github.com?layershifter" to "github.com/?layershifter".
     * - from "github.com#layershifter" to "github.com/#layershifter".
     *
     *
     * @param string $url
     *
     * @return string
     */
    private function fixUriParts(string $url): string
    {
        $position = Str::strpos($url, '?') ?: Str::strpos($url, '#');

        if ($position === false) {
            return $url;
        }

        return Str::substr($url, 0, $position) . '/' . Str::substr($url, $position);
    }
}
