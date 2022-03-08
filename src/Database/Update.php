<?php

namespace DomainExtractor\src\Database;

use DomainExtractor\src\Database\Exceptions\{IOException, UpdateException};
use DomainExtractor\src\Database\Http\{AdapterInterface, CurlAdapter};

/**
 * Class that performs database update with actual data from Public Suffix List.
 */
class Update
{
    /**
     * @const string URL to Public Suffix List file.
     */
    const PUBLIC_SUFFIX_LIST_URL = 'https://raw.githubusercontent.com/publicsuffix/list/master/public_suffix_list.dat';

    /**
     * @var AdapterInterface Object of HTTP adapter.
     */
    private $httpAdapter;
    /**
     * @var string Output filename.
     */
    private $outputFileName;

    /**
     * Parser constructor.
     *
     * @param string|null $outputFileName Filename of target file
     * @param string|null $httpAdapter    Optional class name of custom HTTP adapter
     *
     * @throws UpdateException
     */
    public function __construct(string $outputFileName = null, string $httpAdapter = null)
    {
        /*
         * Defining output filename.
         * */

        $this->outputFileName = null === $outputFileName
            ? __DIR__ . Store::DATABASE_FILE
            : $outputFileName;

        /*
         * Defining HTTP adapter.
         * */

        if (null === $httpAdapter) {
            $this->httpAdapter = new CurlAdapter();

            return;
        }

        if (!class_exists($httpAdapter)) {
            throw new Exceptions\UpdateException(sprintf('Class "%s" is not defined', $httpAdapter));
        }

        $this->httpAdapter = new $httpAdapter();

        if (!($this->httpAdapter instanceof AdapterInterface)) {
            throw new Exceptions\UpdateException(sprintf('Class "%s" is implements adapter interface', $httpAdapter));
        }
    }

    /**
     * Fetches actual Public Suffix List and writes obtained suffixes to target file.
     *
     * @return void
     *
     * @throws IOException
     * @throws Exceptions\ParserException|Exceptions\HttpException
     */
    public function run()
    {
        /*
         * Fetching Public Suffix List and parse suffixes.
         * */

        $lines = $this->httpAdapter->get(Update::PUBLIC_SUFFIX_LIST_URL);

        $parser = new Parser($lines);
        $suffixes = $parser->parse();

        /*
         * Write file with exclusive file write lock.
         * */

        $handle = @fopen($this->outputFileName, 'w+');

        if ($handle === false) {
            throw new Exceptions\IOException(error_get_last()['message']);
        }

        if (!flock($handle, LOCK_EX)) {
            throw new Exceptions\IOException(sprintf('Cannot obtain lock to output file (%s)', $this->outputFileName));
        }

        $suffixFile = '<?php' . PHP_EOL . 'return ' . var_export($suffixes, true) . ';';
        $writtenBytes = fwrite($handle, $suffixFile);

        if ($writtenBytes === false || $writtenBytes !== strlen($suffixFile)) {
            throw new Exceptions\IOException(sprintf('Write to output file (%s) failed', $this->outputFileName));
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
