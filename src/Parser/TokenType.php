<?php
declare(strict_types=1);

namespace Simexis\Filterable\Parser;

class TokenType
{
    const UserItem = 1;
    const AndOperator = 2;
    const OrOperator = 4;
    const NotOperator = 8;
    const LeftParenthis = 16;
    const RightParenthis = 32;
    const NearOperator = 64;
    const NoiseWord = 128;
    const Field = 256;

    const Operator = self::AndOperator | self::OrOperator | self::NotOperator | self::NearOperator;
    const BinaryOperator = self::AndOperator | self::OrOperator | self::NearOperator;
    const Expression = self::RightParenthis | self::UserItem | self::Field;
}
