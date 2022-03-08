<?php

namespace DomainExtractor\Tests;

use DomainExtractor\src\Exceptions\RuntimeException;
use DomainExtractor\src\Extract;
use DomainExtractor\src\Database\Exceptions\StoreException;
use PHPUnit\Framework\TestCase;

class StaticTest extends TestCase
{
    /**
     * @return void
     * @throws RuntimeException
     * @throws StoreException
     */
    public function testExtract()
    {
        $result = tld_extract('http://www.domain.com');

        static::assertEquals('www.domain.com', $result->getFullHost());
        static::assertEquals('domain.com', $result->getRegistrableDomain());

        $result = tld_extract('a.b.blogspot.com', Extract::MODE_ALLOW_ICANN);

        static::assertEquals('a.b.blogspot.com', $result->getFullHost());
        static::assertEquals('blogspot.com', $result->getRegistrableDomain());
        static::assertEquals('a.b', $result->getSubdomain());
        static::assertEquals('com', $result->getSuffix());
    }
}
