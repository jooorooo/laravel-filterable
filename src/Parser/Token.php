<?php
declare(strict_types=1);

namespace Simexis\Filterable\Parser;

class Token
{
    protected $tokenType;
    protected $value;

    public function __construct($tokenType = null, $value = null)
    {
        $this->tokenType = $tokenType;
        $this->value = $value;
    }

    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
    }

    public function getTokenType()
    {
        return $this->tokenType;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
