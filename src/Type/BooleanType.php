<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class BooleanType implements FilterableType
{
    const type = 'Boolean';

    static function defaultRules()
    {
        return [
            Filterable::EQ
        ];
    }
}
