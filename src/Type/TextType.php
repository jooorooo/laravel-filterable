<?php

declare(strict_types=1);

namespace Simexis\Filterable\Type;

use Simexis\Filterable\Filterable;
use Simexis\Filterable\FilterableType;

class TextType implements FilterableType
{
    const type = 'Text';

    static function defaultRules()
    {
        return [
            Filterable::FT,
            Filterable::EQ,
            Filterable::LIKE,
            Filterable::ILIKE,
            Filterable::MATCH
        ];
    }
}
