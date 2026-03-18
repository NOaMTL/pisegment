<?php

namespace App\Services\SegmentBuilder;

enum FieldType: string
{
    case Number = 'number';
    case Text = 'text';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case MultiSelect = 'multi_select';

    public function getAvailableOperators(): array
    {
        return match ($this) {
            self::Number => [
                OperatorType::Equals,
                OperatorType::NotEquals,
                OperatorType::GreaterThan,
                OperatorType::GreaterThanOrEqual,
                OperatorType::LessThan,
                OperatorType::LessThanOrEqual,
                OperatorType::Between,
            ],
            self::Text => [
                OperatorType::Equals,
                OperatorType::NotEquals,
                OperatorType::Contains,
                OperatorType::NotContains,
            ],
            self::Date => [
                OperatorType::Equals,
                OperatorType::GreaterThan,
                OperatorType::LessThan,
                OperatorType::Between,
            ],
            self::Boolean => [
                OperatorType::Equals,
            ],
            self::Select => [
                OperatorType::Equals,
                OperatorType::NotEquals,
            ],
            self::MultiSelect => [
                OperatorType::In,
                OperatorType::NotIn,
            ],
        };
    }
}
