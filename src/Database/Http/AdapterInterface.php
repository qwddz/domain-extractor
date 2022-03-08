<?php

namespace DomainExtractor\src\Database\Http;

interface AdapterInterface
{
    public function get(string $url): array;
}
