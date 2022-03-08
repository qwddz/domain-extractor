<?php

namespace DomainExtractor\src\Database;

use DomainExtractor\src\Database\Exceptions\{IOException, StoreException};

/**
 * Class for operations with database from Public Suffix List.
 */
class Store
{
    /**
     * @const string Path to database which is supplied with library.
     */
    const DATABASE_FILE = '/Resources/database.php';
    /**
     * @const int Type that is assigned when a suffix is ICANN TLD zone.
     */
    const TYPE_ICANN = 1;
    /**
     * @const int Type that is assigned when a suffix is private domain.
     */
    const TYPE_PRIVATE = 2;

    /**
     * @var array|int[] Array of suffixes where key is suffix and value is type of suffix.
     */
    private $suffixes;

    /**
     * Store constructor.
     *
     * @param string|null $databaseFile Optional, full path to database file.
     *
     * @throws IOException
     */
    public function __construct(string $databaseFile = null)
    {
        $databaseFile = null === $databaseFile
            ? __DIR__ . Store::DATABASE_FILE
            : $databaseFile;

        if (!file_exists($databaseFile)) {
            throw new IOException(sprintf('Database file (%s) does not exists', $databaseFile));
        }

        $this->suffixes = require $databaseFile;

        if (!is_array($this->suffixes)) {
            throw new IOException(sprintf(
                'Database file (%s) is seriously malformed, try reinstall package or run update again',
                $databaseFile
            ));
        }
    }

    /**
     * Checks existence of suffix entry in database. Returns true if suffix entry exists.
     *
     * @param string $suffix Suffix which existence will be checked in database.
     *
     * @return bool
     */
    public function isExists(string $suffix): bool
    {
        return array_key_exists($suffix, $this->suffixes);
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return int
     *
     * @throws StoreException
     */
    public function getType(string $suffix): int
    {
        if (!array_key_exists($suffix, $this->suffixes)) {
            throw new StoreException(sprintf(
                'Provided suffix (%s) does not exists in database, check existence of entry with isExists() method ' .
                'before',
                $suffix
            ));
        }

        return $this->suffixes[$suffix];
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return bool
     *
     * @throws StoreException
     */
    public function isICCAN(string $suffix): bool
    {
        return $this->isICANN($suffix);
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return bool
     * @throws StoreException
     */
    public function isICANN(string $suffix): bool
    {
        return $this->getType($suffix) === Store::TYPE_ICANN;
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is private.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return bool
     * @throws StoreException
     */
    public function isPrivate(string $suffix): bool
    {
        return $this->getType($suffix) === Store::TYPE_PRIVATE;
    }
}
