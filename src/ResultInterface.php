<?php

namespace DomainExtractor\src;

interface ResultInterface
{

    /**
     * Class that implements ResultInterface must have following constructor.
     *
     * @param string|null $subdomain
     * @param string|null $hostname
     * @param string|null $suffix
     */
    public function __construct(?string $subdomain, ?string $hostname, ?string $suffix);

    /**
     * Returns subdomain if it exists.
     *
     * @return null|string
     */
    public function getSubdomain(): ?string;

    /**
     * Return subdomains if they exist, example subdomain is "www.news", method will return array ['www', 'news'].
     *
     * @return array
     */
    public function getSubdomains(): array;

    /**
     * Returns hostname if it exists.
     *
     * @return null|string
     */
    public function getHostname(): ?string;

    /**
     * Returns suffix if it exists.
     *
     * @return null|string
     */
    public function getSuffix(): ?string;

    /**
     * Method that returns full host record.
     *
     * @return null|string
     */
    public function getFullHost(): ?string;

    /**
     * Returns registrable domain or null.
     *
     * @return null|string
     */
    public function getRegistrableDomain(): ?string;

    /**
     * Returns true if domain is valid.
     *
     * @return bool
     */
    public function isValidDomain(): bool;

    /**
     * Returns true is result is IP.
     *
     * @return bool
     */
    public function isIp(): bool;
}
