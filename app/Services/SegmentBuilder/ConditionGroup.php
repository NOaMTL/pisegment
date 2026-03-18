<?php

namespace App\Services\SegmentBuilder;

class ConditionGroup
{
    /**
     * @param  array<Condition>  $conditions
     */
    public function __construct(
        public string $logicalOperator = 'AND', // AND or OR
        public array $conditions = [],
    ) {}

    public function addCondition(Condition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'logical_operator' => $this->logicalOperator,
            'conditions' => array_map(fn (Condition $c) => $c->toArray(), $this->conditions),
        ];
    }

    public static function fromArray(array $data): self
    {
        $group = new self($data['logical_operator'] ?? 'AND');

        foreach ($data['conditions'] ?? [] as $conditionData) {
            $group->addCondition(Condition::fromArray($conditionData));
        }

        return $group;
    }
}
