<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class IntegerType implements FilterableType
{
    const type = 'Integer';

    static function defaultRules()
    {
        return [
            Filterable::EQ,
            Filterable::IN,
            Filterable::MIN,
            Filterable::MAX,
            Filterable::LT,
            Filterable::GT
        ];
    }
}
