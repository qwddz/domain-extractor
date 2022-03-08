<?php

namespace {

    use DomainExtractor\src\{Extract, ResultInterface, Database\Exceptions\StoreException};

    /**
     * @param string $url
     * @param int|null $mode
     * @return ResultInterface
     * @throws StoreException
     * @throws \DomainExtractor\src\Exceptions\RuntimeException
     */
    function tld_extract(string $url, int $mode = null): ResultInterface
    {
        static $extract = null;

        if (null === $extract) {
            $extract = new Extract();
        }

        if (null !== $mode) {
            $extract->setExtractionMode($mode);
        }

        return $extract->parse($url);
    }
}
