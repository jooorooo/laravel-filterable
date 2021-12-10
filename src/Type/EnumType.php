<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class EnumType implements FilterableType
{
    const type = 'Enum';

    static function defaultRules()
    {
        return [
            Filterable::EQ,
            Filterable::IN
        ];
    }
}
