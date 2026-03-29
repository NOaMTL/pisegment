<?php

namespace App\Services\SegmentBuilder;

class Condition
{
    public function __construct(
        public string $field,
        public OperatorType $operator,
        public mixed $value,
        public bool $isEditable = false,
        public ?array $editableOptions = null,
        public mixed $valueMax = null,
    ) {}

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator->value,
            'value' => $this->value,
            'value_max' => $this->valueMax,
            'is_editable' => $this->isEditable,
            'editable_options' => $this->editableOptions,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: $data['field'],
            operator: OperatorType::from($data['operator']),
            value: $data['value'],
            isEditable: $data['is_editable'] ?? false,
            editableOptions: $data['editable_options'] ?? null,
            valueMax: $data['value_max'] ?? null,
        );
    }
}
