<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class EqualStringType implements FilterableType
{
    const type = 'String';

    static function defaultRules()
    {
        return [
            Filterable::EQ,
            Filterable::IN,
        ];
    }
}
