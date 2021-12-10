<?php

declare(strict_types=1);

namespace Simexis\Filterable;

use Exception;

class FilterableException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
