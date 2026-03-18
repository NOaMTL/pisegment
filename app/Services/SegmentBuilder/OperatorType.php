<?php

namespace App\Services\SegmentBuilder;

enum OperatorType: string
{
    case Equals = '=';
    case NotEquals = '!=';
    case GreaterThan = '>';
    case GreaterThanOrEqual = '>=';
    case LessThan = '<';
    case LessThanOrEqual = '<=';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case In = 'in';
    case NotIn = 'not_in';
    case Between = 'between';
    case IsNull = 'is_null';
    case IsNotNull = 'is_not_null';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'est égal à',
            self::NotEquals => 'n\'est pas égal à',
            self::GreaterThan => 'est supérieur à',
            self::GreaterThanOrEqual => 'est supérieur ou égal à',
            self::LessThan => 'est inférieur à',
            self::LessThanOrEqual => 'est inférieur ou égal à',
            self::Contains => 'contient',
            self::NotContains => 'ne contient pas',
            self::In => 'est dans',
            self::NotIn => 'n\'est pas dans',
            self::Between => 'est entre',
            self::IsNull => 'est vide',
            self::IsNotNull => 'n\'est pas vide',
        };
    }
}
