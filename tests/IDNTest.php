<?php

namespace DomainExtractor\Tests;

use DomainExtractor\src\IDN;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IDN class.
 */
class IDNTest extends TestCase
{

    /**
     * @var IDN Object for tests
     */
    private $idn;

    /**
     * Method that setups test's environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->idn = new IDN();
    }

    /**
     * Tests toASCII() method.
     *
     * @return void
     */
    public function testToASCII()
    {
        $this->assertEquals('xn--tst-qla.de', $this->idn->toASCII('täst.de'));
    }

    /**
     * Tests toUTF8() method.
     *
     * @return void
     */
    public function testToUTF8()
    {
        $this->assertEquals('täst.de', $this->idn->toUTF8('xn--tst-qla.de'));
    }
}
