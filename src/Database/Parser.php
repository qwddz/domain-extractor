<?php

namespace DomainExtractor\src\Database;

use DomainExtractor\src\Database\Exceptions\ParserException;
use DomainExtractor\src\Support\Str;

final class Parser
{
    /**
     * @const string String which shows that the line is a comment.
     */
    const COMMENT_STRING_START = '//';
    /**
     * @const string Commentary string which shows that beginning of private domains list.
     */
    const PRIVATE_DOMAINS_STRING = '// ===BEGIN PRIVATE DOMAINS===';

    /**
     * @var bool Flags which indicates what type of domain is currently parsed.
     */
    private $isICANNSuffix = true;
    /**
     * @var array|string[] Input array of lines from Public Suffix List.
     */
    private $lines;

    /**
     * @param array $lines
     */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    /**
     * Method that parses submitted strings and returns array from valid suffixes.
     *
     * Parser features the following rules apply:
     * - the list is a set of rules, with one rule per line;
     * - each line is only read up to the first whitespace; entire lines can also be commented using //;
     * - each line which is not entirely whitespace or begins with a comment contains a rule;
     *
     * @see https://publicsuffix.org/list/
     *
     * @return array
     *
     * @throws ParserException
     */
    public function parse(): array
    {
        $suffixes = [];

        foreach ($this->lines as $line) {
            if (Str::startsWith($line, Parser::PRIVATE_DOMAINS_STRING)) {
                $this->isICANNSuffix = false;

                continue;
            }

            if (Str::startsWith($line, Parser::COMMENT_STRING_START)) {
                continue;
            }

            $line = explode(' ', trim($line))[0];

            if (Str::length($line) === 0) {
                continue;
            }

            $suffixes[$line] = $this->isICANNSuffix ? Store::TYPE_ICANN : Store::TYPE_PRIVATE;
        }

        if (count($suffixes) === 0) {
            throw new ParserException('Input array of lines does not have any valid suffix, check input');
        }

        return $suffixes;
    }
}
