<?php

namespace DomainExtractor\Tests;

use DomainExtractor\src\{Database\Exceptions\StoreException, Extract, Result};
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    private $entity;

    public function setUp(): void
    {
        $this->entity = new Result(null, '192.168.0.1', null);
    }

    /**
     * Test for __constructor.
     *
     * @return void
     */
    public function testConstruct()
    {
        static::assertNull($this->entity->subdomain);
        static::assertEquals('192.168.0.1', $this->entity->hostname);
        static::assertNull($this->entity->suffix);

        $entity = new Result(null, 'domain', 'com');

        static::assertNull($this->entity->subdomain);
        static::assertEquals('domain', $entity->hostname);
        static::assertEquals('com', $entity->suffix);

        unset($entity);

        $entity = new Result('www', 'domain', 'com');

        static::assertEquals('www', $entity->subdomain);
        static::assertEquals('domain', $entity->hostname);
        static::assertEquals('com', $entity->suffix);

        static::assertArrayHasKey('subdomain', $entity);
        static::assertArrayHasKey('hostname', $entity);
        static::assertArrayHasKey('suffix', $entity);
    }

    /**
     * Test domain entry.
     *
     * @return void
     * @throws StoreException
     */
    public function testDomain()
    {
        $extract = new Extract();
        $result = $extract->parse('github.com');

        static::assertEquals('github.com', $result->getFullHost());
        static::assertEquals(null, $result->getSubdomain());
        static::assertEquals(array(), $result->getSubdomains());
        static::assertEquals('github.com', $result->getRegistrableDomain());
        static::assertTrue($result->isValidDomain());
        static::assertFalse($result->isIp());
    }

    /**
     * @return void
     * @throws StoreException
     */
    public function testSubDomain()
    {
        $extract = new Extract();
        $result = $extract->parse('shop.github.com');

        static::assertEquals('shop.github.com', $result->getFullHost());
        static::assertEquals('shop', $result->getSubdomain());
        static::assertCount(1, $result->getSubdomains());
        static::assertContainsOnly('string', $result->getSubdomains());
        static::assertEquals(['shop'], $result->getSubdomains());
        static::assertEquals('github.com', $result->getRegistrableDomain());
        static::assertTrue($result->isValidDomain());
        static::assertFalse($result->isIp());
    }

    /**
     * @return void
     * @throws StoreException
     */
    public function testSubdomains()
    {
        $extract = new Extract();
        $result = $extract->parse('new.shop.github.com');

        static::assertEquals('new.shop.github.com', $result->getFullHost());
        static::assertEquals('new.shop', $result->getSubdomain());
        static::assertCount(2, $result->getSubdomains());
        static::assertContainsOnly('string', $result->getSubdomains());
        static::assertEquals(['new', 'shop'], $result->getSubdomains());
        static::assertEquals('github.com', $result->getRegistrableDomain());
        static::assertTrue($result->isValidDomain());
        static::assertFalse($result->isIp());
    }

    /**
     * @return void
     * @throws StoreException
     */
    public function testIp()
    {
        $extract = new Extract();
        $result = $extract->parse('192.168.0.1');

        static::assertEquals('192.168.0.1', $result->getFullHost());
        static::assertEquals(null, $result->getSubdomain());
        static::assertEquals(array(), $result->getSubdomains());
        static::assertNull($result->getRegistrableDomain());
        static::assertFalse($result->isValidDomain());
        static::assertTrue($result->isIp());
    }

    /**
     * Test for toJson().
     *
     * @return void
     */
    public function testToJson()
    {
        static::assertJsonStringEqualsJsonString(
            json_encode((object)[
                'subdomain' => null,
                'hostname' => '192.168.0.1',
                'suffix' => null,
            ]),
            $this->entity->toJson()
        );
    }

    /**
     * Test for magic method __toString().
     *
     * @return void
     */
    public function testToString()
    {
        static::assertEquals('192.168.0.1', (string)$this->entity);
    }

    /**
     * Test for magic method __isset().
     *
     * @return void
     */
    public function testIsset()
    {
        static::assertNull($this->entity->subdomain);
        static::assertNotNull($this->entity->hostname);
        static::assertNull($this->entity->suffix);
        static::assertEquals(false, isset($this->entity->test));
    }

    /**
     * Test for magic method __set().
     *
     * @return void
     */
    public function testSet()
    {
        $this->expectException('LogicException');
        $this->entity->offsetSet('hostname', 'another-domain');
    }

    /**
     * Test for magic method __offsetSet().
     *
     * @return void
     */
    public function testOffsetSet()
    {
        $this->expectException('LogicException');
        $this->entity['hostname'] = 'another-domain';
    }

    /**
     * Test for magic method __offsetGet().
     *
     * @return void
     */
    public function testOffsetGet()
    {
        static::assertEquals('192.168.0.1', $this->entity['hostname']);
    }

    /**
     * Test for magic method __offsetUnset().
     *
     * @return void
     */
    public function testOffsetUnset()
    {
        $this->expectException('LogicException');
        unset($this->entity['hostname']);
    }
}
