<?php

namespace App\Services\SegmentBuilder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class SegmentQueryBuilder
{
    /**
     * @param  array<ConditionGroup>  $conditionGroups
     */
    public function __construct(
        public array $conditionGroups = [],
    ) {}

    public function addConditionGroup(ConditionGroup $group): self
    {
        $this->conditionGroups[] = $group;

        return $this;
    }

    public function build(): Builder
    {
        $query = Customer::query();

        foreach ($this->conditionGroups as $index => $group) {
            // First group always uses 'where', subsequent groups use the previous group's nextOperator
            if ($index === 0) {
                $query->where(function (Builder $q) use ($group) {
                    $this->applyConditionGroup($q, $group);
                });
            } else {
                $previousGroup = $this->conditionGroups[$index - 1];
                $method = ($previousGroup->nextOperator ?? 'AND') === 'OR' ? 'orWhere' : 'where';
                $query->$method(function (Builder $q) use ($group) {
                    $this->applyConditionGroup($q, $group);
                });
            }
        }

        return $query;
    }

    protected function applyConditionGroup(Builder $query, ConditionGroup $group): void
    {
        $method = $group->logicalOperator === 'OR' ? 'orWhere' : 'where';

        foreach ($group->conditions as $condition) {
            $this->applyCondition($query, $condition, $method);
        }
    }

    protected function applyCondition(Builder $query, Condition $condition, string $method = 'where'): void
    {
        match ($condition->operator) {
            OperatorType::Equals => $query->$method($condition->field, '=', $condition->value),
            OperatorType::NotEquals => $query->$method($condition->field, '!=', $condition->value),
            OperatorType::GreaterThan => $query->$method($condition->field, '>', $condition->value),
            OperatorType::GreaterThanOrEqual => $query->$method($condition->field, '>=', $condition->value),
            OperatorType::LessThan => $query->$method($condition->field, '<', $condition->value),
            OperatorType::LessThanOrEqual => $query->$method($condition->field, '<=', $condition->value),
            OperatorType::Contains => $query->$method($condition->field, 'LIKE', "%{$condition->value}%"),
            OperatorType::NotContains => $query->$method($condition->field, 'NOT LIKE', "%{$condition->value}%"),
            OperatorType::In => $query->$method(fn ($q) => $q->whereIn($condition->field, $condition->value)),
            OperatorType::NotIn => $query->$method(fn ($q) => $q->whereNotIn($condition->field, $condition->value)),
            OperatorType::Between => $query->$method(fn ($q) => $q->whereBetween($condition->field, [$condition->value, $condition->valueMax])),
            OperatorType::IsNull => $query->$method(fn ($q) => $q->whereNull($condition->field)),
            OperatorType::IsNotNull => $query->$method(fn ($q) => $q->whereNotNull($condition->field)),
        };
    }

    public function toArray(): array
    {
        return [
            'condition_groups' => array_map(fn (ConditionGroup $g) => $g->toArray(), $this->conditionGroups),
        ];
    }

    public static function fromArray(array $data): self
    {
        $builder = new self;

        foreach ($data['condition_groups'] ?? [] as $groupData) {
            $builder->addConditionGroup(ConditionGroup::fromArray($groupData));
        }

        return $builder;
    }
}
