<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class DateType implements FilterableType
{
    const type = 'Date';

    static function defaultRules()
    {
        return [
            Filterable::EQ,
            Filterable::MIN,
            Filterable::MAX,
            Filterable::LT,
            Filterable::GT
        ];
    }
}
