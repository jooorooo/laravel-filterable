<?php
declare(strict_types=1);

namespace Simexis\Filterable\Parser;

use Illuminate\Support\Str;

class InputQueryParser
{
    protected $builtInTokens = [];
    protected $tokens = [];
    protected $noiseWords = [];
    protected $error;
    protected $field;
    protected $valid = false;

    public function __construct(array $field = null)
    {
        $this->builtInTokens["and"] = new Token(TokenType::AndOperator, "+");
        $this->builtInTokens["or"] = new Token(TokenType::OrOperator, "OR");
        $this->builtInTokens["near"] = new Token(TokenType::NearOperator, "NEAR");
        $this->builtInTokens["not"] = new Token(TokenType::NotOperator, "-");
        $this->builtInTokens["("] = new Token(TokenType::LeftParenthis, "(");
        $this->builtInTokens[")"] = new Token(TokenType::RightParenthis, ")");

        $this->field = $field;
    }

    /**
     * Gets the tokens in a string ready for use in a SQL query with the CONTAINS clause
     */
    public function getSqlQuery(string $string): string
    {
        $this->valid = $this->parse($string);
        if(!$this->valid) {
            $this->tokens = [
                new Token(TokenType::UserItem, $string)
            ];
        }

        $stringBuilder = [];
        foreach($this->tokens as $token) {
            $tokenValue = $token->getValue();

            $tokenValue = str_replace(["'"], ["''"], $tokenValue);
            $tokenValue = str_replace(['"'], ['""'], $tokenValue);

            // Wrap the token value in quotes, if it's not in quotes already (ie. might be a phrase search)
            if ($token->getTokenType() === TokenType::UserItem && !Str::startsWith($tokenValue, ["\""]) && !Str::endsWith($tokenValue, ["\""])) {
                $tokenValue = sprintf('"%s"', $tokenValue);
            }

            $stringBuilder[] = $tokenValue;
            $stringBuilder[] = ' ';
        }

        return $this->finalClean(trim(implode($stringBuilder)));
    }

    /**
     * @return null|string
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function getIsValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param string $string
     * @return bool
     */
    protected function parse(string $string): bool
    {
        // Clean the string and make it all lowercase - we can save on this operation later making code cleaner
        $string = $this->firstClean($string);

        if ((substr_count($string, '"') % 2 === 0) === false) {
            return false;
        }

        $string = ltrim($string, '-');

        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->splitIntoTokens($string);
        if(empty($tokens)) {
            return false;
        }

        $this->tokens = [];
        /** @var null|Token $lastParsedToken */
        $lastParsedToken = null;

        foreach($tokens as $tokenIndex => $word) {
            $token = $this->getToken($word);

            if ($lastParsedToken !== null && ($lastParsedToken->getTokenType() === TokenType::NoiseWord) && ($token->getTokenType() & TokenType::BinaryOperator) === $token->getTokenType())
            {
                // Skip this token since it joins a noise word
            }
            else if ($lastParsedToken === null && ($token->getTokenType() & TokenType::Operator) === $token->getTokenType())
            {
                // Skip this as query cannot start with an operator
            }
            else if ($token->getTokenType() === TokenType::NoiseWord)
            {
                $this->unrollExpression(TokenType::Operator);
                $lastParsedToken = $token;
            }
            else
            {
                // Get the last (previous) token
                $lastToken = $this->getLastToken();

                if($lastParsedToken && $token->getTokenType() === TokenType::LeftParenthis && $lastParsedToken->getTokenType() !== TokenType::BinaryOperator) {
                    $this->tokens[] = $this->builtInTokens['and'];
                }

                if ($token->getTokenType() === TokenType::UserItem)
                {
                    if($tokenIndex === 0 && $token->getTokenType() === TokenType::UserItem) {
                        $this->tokens[] = $this->builtInTokens['and'];
                    }

                    // Check if there is a previous token and if it is an expression, then add an 'AND' operator
                    if ($lastToken !== null && ($lastToken->getTokenType() & TokenType::Expression) === $lastToken->getTokenType()) {
                        $this->tokens[] = $this->builtInTokens['and'];
                    } elseif ((($token->getTokenType() & TokenType::NotOperator) === $token->getTokenType()) && $lastToken !== null && ($lastToken->getTokenType() & TokenType::Expression) === $lastToken->getTokenType())
                    {
                        // Same goes for not.  If this token is a 'NOT' operator and the last token is an
                        // expression, then add an 'and' token to keep the syntax correct.
                        $this->tokens[] = $this->builtInTokens['and'];
                    }

                    $this->tokens[] = $lastParsedToken = $token;
                } else {
                    $this->tokens[] = $token;
                }
            }
        }


        return $this->isValid();
    }

    /**
     * Last run over the combined tokens to clean stuff up
     *
     * @param string $string
     *
     * @return string
     */
    protected function finalClean(string $string): string
    {
        $output = str_replace(['- ', '+ '], [' -', ' +'], $string);
        $output = preg_replace('/\s\s+/', ' ', $output); // Remove double spaces
        $output = str_replace([' )', '( '], [')', '('], $output);

        return $output;
    }

    /**
     * First pass over the initial string to clean some elements
     *
     * @param string $string
     *
     * @return string
     */
    protected function firstClean(string $string): string
    {
        //$output = str_ireplace('title:', ' ', $string);
        $output = str_replace(['{', '[', '}', ']', '“', '”'], ['(', '(', ')', ')', '"', '"'], $string);
        $output = preg_replace('# +#', ' ', $output);
        $output = preg_replace('#^\s+#m', '', $output);
        $output = preg_replace('#\s+$#m', '', $output);
        $output = preg_replace('#\n+#', "\n", $output);
        $output = preg_replace('#^\ +#', '', $output);
        $output = preg_replace('#^&nbsp;$#im', '', $output);
        $output = preg_replace('/((\b-\s)|(\s-\s))/', ' ', $output);
        $output = preg_replace('/\s\s+/', ' ', $output);

        return Str::lower(trim($output));
    }

    /**
     * @param string $string
     *
     * @return Token
     */
    protected function getToken(string $word): Token
    {
        return $this->builtInTokens[$word] ?? $this->with(new Token, function(Token $token) use($word) {
            $token->setValue($word);
            $token->setTokenType(
                $this->guessTokenType($word)
            );
            return $token;
        });
    }

    /**
     * @param string $string
     *
     * @return int
     */
    protected function guessTokenType(string $word): int
    {
        if($this->noiseWords && in_array($word, $this->noiseWords)) {
            return TokenType::NoiseWord;
        }

        if($this->field && Str::endsWith($word, ':')) {
            $word = Str::substr($word, 0, -1);
            if(in_array($word, array_map('strtolower', $this->field))) {
                return TokenType::Field;
            }
        }

        return TokenType::UserItem;
    }

    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    protected function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }

    /**
     * Rolls back to the last token of the specified type.
     * All tokens after it are removed from the list.
     */
    private function unrollExpression($type): void
    {
        for($i = count($this->tokens); $i > 0; $i++) {
            $token = $this->tokens[$i - 1];
            if (($token->getTokenType() & $type) != 0)
            {
                unset($this->tokens[i - 1]);
            }
            else
            {
                break;
            }
        }

        $this->tokens = array_values($this->tokens);
    }

    /**
     * Gets the last token in the list.  If there is no last token, null is returned
     */
    private function getLastToken(): ?Token
    {
        if(($total = count($this->tokens)) > 0) {
            return $this->tokens[$total - 1];
        }

        return null;
    }

    /**
     * Validates the tokens and checks if they correctly form a query
     */
    private function isValid(): bool
    {
        if(count($this->tokens) < 1) {
            return false;
        }

        /*
        $this->builtInTokens["and"] = new Token(TokenType::AndOperator, "+");
        $this->builtInTokens["or"] = new Token(TokenType::OrOperator, "OR");
        $this->builtInTokens["near"] = new Token(TokenType::NearOperator, "NEAR");
        $this->builtInTokens["not"] = new Token(TokenType::NotOperator, "-");
        $this->builtInTokens["("] = new Token(TokenType::LeftParenthis, "(");
        $this->builtInTokens[")"] = new Token(TokenType::RightParenthis, ")");
        */

        $valid = true;
        $lastItemOK = false;
        $nextItem = TokenType::UserItem | TokenType::LeftParenthis | TokenType::NotOperator | TokenType::Field;
        $balance = 0;
        $prevToken = null;

        for ($tokIndex = 0; $tokIndex < count($this->tokens); $tokIndex++) {
            /** @var Token $token */
            $token = $this->tokens[$tokIndex];

            if (($token->getTokenType() & $nextItem) != 0) {
                switch($token->getTokenType()) {
                    case TokenType::UserItem;
                        $nextItem = TokenType::BinaryOperator | TokenType::RightParenthis | TokenType::LeftParenthis | TokenType::NotOperator;
                        $lastItemOK = true;
                        break;
                    case TokenType::AndOperator;
                        $nextItem = TokenType::UserItem | TokenType::NotOperator | TokenType::LeftParenthis;
                        $lastItemOK = false;
                        break;
                    case TokenType::NearOperator;
                        $nextItem = TokenType::UserItem;
                        $lastItemOK = false;
                        break;
                    case TokenType::OrOperator;
                        $nextItem = TokenType::UserItem | TokenType::LeftParenthis;
                        $lastItemOK = false;
                        break;
                    case TokenType::NotOperator;
                        $nextItem = TokenType::UserItem | TokenType::LeftParenthis;
                        $lastItemOK = false;
                        break;
                    case TokenType::LeftParenthis;
                        $balance++;
                        $nextItem = TokenType::UserItem;
                        $lastItemOK = false;
                        break;
                    case TokenType::RightParenthis;
                        $balance--;
                        $nextItem = TokenType::OrOperator | TokenType::AndOperator;
                        $lastItemOK = ($balance <= 0);
                        break;
                    case TokenType::Field;
                        $nextItem = TokenType::BinaryOperator | TokenType::RightParenthis | TokenType::UserItem;
                        $lastItemOK = true;
                        break;
                }

                $prevToken = $token;

                if ($balance < 0)
                {
                    $valid = false;
                    $this->error = "Mismatched parenthesis";
                    break;
                }
            } else {
                dd([$token, $nextItem, $token->getTokenType() & $nextItem, $token->getTokenType(), $nextItem, $prevToken]);
                $valid = false;
                $this->error = "Unexpected word or character found: " . $token->getValue();
                break;
            }
        }

        if ($balance != 0)
        {
            $valid = false;
            $this->error = "Mismatched parenthesis";
        }
        else if ($valid && !$lastItemOK)
        {
            $valid = false;
            $this->error = "Unexpected end of search string after: " . $this->tokens[count($this->tokens) - 1]->getValue();
        }

        return $valid;
    }
}
