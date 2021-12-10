<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use \Simexis\Filterable\FilterableType;

class StringType implements FilterableType
{
    const type = 'String';

    static function defaultRules()
    {
        return [
            Filterable::EQ,
            Filterable::LIKE,
            Filterable::MATCH
        ];
    }
}
