<?php

namespace DomainExtractor\src\Database\Http;

use DomainExtractor\src\Database\Exceptions\HttpException;

final class CurlAdapter implements AdapterInterface
{
    const TIMEOUT = 60;

    /**
     * @param string $url
     * @return array
     * @throws HttpException
     */
    public function get(string $url): array
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, CurlAdapter::TIMEOUT);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, CurlAdapter::TIMEOUT);

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $responseContent = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $errorMessage = curl_error($curl);
        $errorNumber = curl_errno($curl);

        curl_close($curl);

        if ($errorNumber !== 0) {
            throw new HttpException(sprintf('Get cURL error while fetching PSL file: %s', $errorMessage));
        }

        if ($responseCode !== 200) {
            throw new HttpException(
                sprintf('Get invalid HTTP response code "%d" while fetching PSL file', $responseCode)
            );
        }

        return preg_split('/[\n\r]+/', $responseContent);
    }
}
