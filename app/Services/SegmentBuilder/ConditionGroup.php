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
        public ?string $nextOperator = null, // AND or OR - operator to use before the next group
    ) {}

    public function addCondition(Condition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function toArray(): array
    {
        $array = [
            'logical_operator' => $this->logicalOperator,
            'conditions' => array_map(fn (Condition $c) => $c->toArray(), $this->conditions),
        ];

        if ($this->nextOperator !== null) {
            $array['next_operator'] = $this->nextOperator;
        }

        return $array;
    }

    public static function fromArray(array $data): self
    {
        $group = new self(
            $data['logical_operator'] ?? 'AND',
            [],
            $data['next_operator'] ?? null
        );

        foreach ($data['conditions'] ?? [] as $conditionData) {
            $group->addCondition(Condition::fromArray($conditionData));
        }

        return $group;
    }
}
