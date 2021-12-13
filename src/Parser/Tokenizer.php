<?php
declare(strict_types=1);

namespace Simexis\Filterable\Parser;

use Illuminate\Support\Str;

class Tokenizer
{
    const SPLITTERS = "@(\r\n|\!\=|\>\=|\<\=|\<\>|\:\=|\\|&&|\>|\<|\||\=|\^|\(|\)|\t|\r|\n|\"|`|,|\@| |\+|\-|\*|/|;)@";

    protected $quoteReplacesNumeric = 0;
    protected $quoteReplaces = [];

    public function splitIntoTokens(string $string): array
    {
        $string = preg_replace_callback('(\s?([\'\"])([^\1]*)\1)iUms', function($match) {
            $keyReplace = sprintf('quoteReplacer%d', $this->quoteReplacesNumeric);
            $this->quoteReplaces[$keyReplace] = $match[2];
            $this->quoteReplacesNumeric++;
            return ' ' . $keyReplace;
        }, $string);

        $tokens = preg_split(self::SPLITTERS, $string,  -1,PREG_SPLIT_DELIM_CAPTURE);

        $tokens = array_map(function($token) {
            return $this->quoteReplaces[$token] ?? $token;
        }, $tokens);

        return $this->parseMatchesIntoWordList(
            $this->filterTokens($tokens)
        );
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function filterTokens(array $tokens): array
    {
        return array_values(array_filter($tokens, function($token) {
            return trim($token) !== '';
        }));
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function parseMatchesIntoWordList(array $tokens): array
    {
        $currentWord = '';
        $newList = $newList2 = [];
        foreach($tokens as $token) {
            if($currentWord === '') {
                $currentWord = $token;

                if(Str::startsWith($currentWord, ['-"', '"']) && !Str::endsWith($currentWord, ['"'])) {
                    continue;
                }
            } else {
                $currentWord .= ' ' . $token;
                if(!Str::endsWith($currentWord, ['"'])) {
                    continue;
                }
            }

            $newList[] = $currentWord;
            $currentWord = '';
        }

        foreach($newList as $token) {
            if(Str::startsWith($token, ['-'])) {
                $token = Str::substr($token, 1);
                $newList2[] = 'not';
            }

            $newList2[] = $token;
        }

        return $this->filterTokens($newList2);
    }
}
