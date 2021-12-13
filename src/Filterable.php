<?php

declare(strict_types=1);

namespace Simexis\Filterable;

class Filterable
{
    const EQ = 'EQ'; // equal to
    const LIKE = 'LIKE'; // SQL like
    const MATCH = 'MATCH'; // glob matching
    const MIN = 'MIN'; // greater than or equal to
    const MAX = 'MAX'; // less than or equal to
    const LT = 'LT'; // less than
    const GT = 'GT'; // greater than
    const RE = 'RE'; // regular expression match
    const FT = 'FT'; // full text search
    const IN = 'IN'; // list contains
    const NULL = 'NULL'; // is null

    const String = Type\StringType::class;
    const Text = Type\TextType::class;
    const Integer = Type\IntegerType::class;
    const Numeric = Type\NumericType::class;
    const Enum = Type\EnumType::class;
    const Date = Type\DateType::class;
    const Boolean = Type\BooleanType::class;
    const EqualInteger = Type\EqualIntegerType::class;
    const EqualString = Type\EqualStringType::class;
    const FullText = Type\TextFullTextType::class;

    public static function isFilterableType($class): bool
    {
        return is_string($class) && class_exists($class) && in_array(FilterableType::class, class_implements($class));
    }
}
